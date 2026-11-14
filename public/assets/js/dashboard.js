/**
 * Admin dashboard charts (Requirements 8.5-8.11, 8.18; legacy 12.17, 12.18, 12.25, 12.26).
 *
 * Contract with admin/dashboard.php:
 *   <script type="application/json" id="dashboard-data">{...summary...}</script>
 *   <canvas id="chart-daily"> + <select id="daily-range"> (7/14/30)
 *   <canvas id="chart-weekly">, <canvas id="chart-language">, <canvas id="chart-device">
 *   <span id="total-language|device">, <ul id="legend-language|device">
 *   Optional empty-state elements: #chart-*-empty (created next to the canvas when missing).
 * The JSON may be the bare `data` object or the full {status,message,data} envelope.
 * Chart.js is loaded locally from /assets/vendor/chartjs/chart.umd.min.js.
 * Every enhancement is skipped silently when its hook element is missing.
 */
(function () {
    'use strict';

    var EMPTY_TEXT = 'No data available yet.';

    // Single source of colours for all charts.
    var THEME = {
        series: ['#1f5fd0', '#2e9e6b', '#e6a117', '#7a4fd1', '#c0392b', '#1aa6a6'],
        line: '#2e9e6b',
        lineFillTop: 'rgba(46, 158, 107, 0.28)',
        lineFillBottom: 'rgba(46, 158, 107, 0)',
        barPrimary: '#1f5fd0',
        barSecondary: '#9db8ea',
        grid: 'rgba(31, 36, 48, 0.08)',
        tick: '#5b6472',
        tooltipBg: '#1f2430',
        tooltipText: '#ffffff',
        guide: 'rgba(255, 255, 255, 0.85)',
        guideLine: 'rgba(31, 36, 48, 0.35)',
        donutBorder: '#ffffff'
    };

    var charts = {};

    function isObject(v) {
        return v !== null && typeof v === 'object' && !Array.isArray(v);
    }

    function num(v) {
        var n = Number(v);
        return isFinite(n) && n > 0 ? n : 0;
    }

    function sum(arr) {
        return arr.reduce(function (a, b) { return a + b; }, 0);
    }

    function reducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function readData() {
        var el = document.getElementById('dashboard-data');
        if (!el) {
            return {};
        }
        try {
            var parsed = JSON.parse(el.textContent || '{}');
            if (isObject(parsed) && isObject(parsed.data)) {
                return parsed.data;
            }
            return isObject(parsed) ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function showEmpty(canvas, name) {
        if (!canvas) {
            return;
        }
        canvas.classList.add('d-none');
        canvas.setAttribute('aria-hidden', 'true');
        var empty = document.getElementById(name + '-empty');
        if (!empty) {
            empty = document.createElement('p');
            empty.id = name + '-empty';
            empty.className = 'text-muted text-center my-4';
            empty.textContent = EMPTY_TEXT;
            canvas.parentNode.insertBefore(empty, canvas.nextSibling);
        }
        empty.classList.remove('d-none');
    }

    function hideEmpty(canvas, name) {
        canvas.classList.remove('d-none');
        canvas.removeAttribute('aria-hidden');
        var empty = document.getElementById(name + '-empty');
        if (empty) {
            empty.classList.add('d-none');
        }
    }

    function destroy(name) {
        if (charts[name]) {
            charts[name].destroy();
            delete charts[name];
        }
    }

    function baseOptions() {
        var opts = {
            responsive: true,
            maintainAspectRatio: false
        };
        if (reducedMotion()) {
            opts.animation = false;
            opts.transitions = { active: { animation: { duration: 0 } } };
        }
        return opts;
    }

    function darkTooltip() {
        return {
            backgroundColor: THEME.tooltipBg,
            titleColor: THEME.tooltipText,
            bodyColor: THEME.tooltipText,
            padding: 10,
            cornerRadius: 8,
            displayColors: true
        };
    }

    function axes() {
        return {
            x: { grid: { display: false }, ticks: { color: THEME.tick, maxRotation: 0, autoSkip: true } },
            y: {
                beginAtZero: true,
                grid: { color: THEME.grid },
                border: { display: false },
                ticks: { precision: 0, color: THEME.tick }
            }
        };
    }

    // Dashed vertical guide that follows the hovered point.
    var guidePlugin = {
        id: 'dashboardGuide',
        afterDatasetsDraw: function (chart) {
            var active = chart.tooltip && chart.tooltip.getActiveElements ? chart.tooltip.getActiveElements() : [];
            if (!active || !active.length) {
                return;
            }
            var x = active[0].element.x;
            var area = chart.chartArea;
            var ctx = chart.ctx;
            ctx.save();
            ctx.beginPath();
            ctx.setLineDash([4, 4]);
            ctx.lineWidth = 1;
            ctx.strokeStyle = THEME.guideLine;
            ctx.moveTo(x, area.top);
            ctx.lineTo(x, area.bottom);
            ctx.stroke();
            ctx.restore();
        }
    };

    function readDaily(data) {
        var rows = Array.isArray(data.daily) ? data.daily : [];
        var out = [];
        rows.forEach(function (r) {
            if (!isObject(r)) {
                return;
            }
            out.push({
                date: String(r.date || ''),
                views: num(r.page_views),
                unique: num(r.unique)
            });
        });
        return out;
    }

    /** Last n rows of the daily series (n is clamped to the available rows). */
    function sliceDaily(rows, n) {
        var count = Number(n);
        if (!isFinite(count) || count <= 0) {
            return rows.slice();
        }
        return rows.slice(Math.max(0, rows.length - count));
    }

    function renderDaily(canvas, rows) {
        destroy('daily');
        var views = rows.map(function (r) { return r.views; });
        var unique = rows.map(function (r) { return r.unique; });

        if (sum(views) + sum(unique) === 0) {
            showEmpty(canvas, 'chart-daily');
            return;
        }
        hideEmpty(canvas, 'chart-daily');

        var opts = baseOptions();
        opts.interaction = { mode: 'index', intersect: false };
        opts.scales = axes();
        opts.plugins = {
            legend: { position: 'bottom', labels: { usePointStyle: true, color: THEME.tick } },
            tooltip: darkTooltip()
        };

        charts.daily = new window.Chart(canvas, {
            type: 'line',
            plugins: [guidePlugin],
            data: {
                labels: rows.map(function (r) { return r.date; }),
                datasets: [
                    {
                        label: 'Page views',
                        data: views,
                        borderColor: THEME.line,
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: function (context) {
                            var chart = context.chart;
                            var area = chart.chartArea;
                            if (!area) {
                                return THEME.lineFillTop;
                            }
                            var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                            g.addColorStop(0, THEME.lineFillTop);
                            g.addColorStop(1, THEME.lineFillBottom);
                            return g;
                        },
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: THEME.line,
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Unique visitors',
                        data: unique,
                        borderColor: THEME.barPrimary,
                        backgroundColor: THEME.barPrimary,
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: THEME.barPrimary,
                        pointBorderWidth: 2
                    }
                ]
            },
            options: opts
        });
    }

    function dailyChart(data) {
        var canvas = document.getElementById('chart-daily');
        if (!canvas) {
            return;
        }
        var all = readDaily(data);
        var select = document.getElementById('daily-range');

        function draw() {
            var n = select ? parseInt(select.value, 10) : all.length;
            renderDaily(canvas, sliceDaily(all, n));
        }

        if (select) {
            select.addEventListener('change', draw);
        }
        draw();
    }

    /** Grouped bars: page views vs unique visitors for the last 7 days. */
    function weeklyChart(data) {
        var canvas = document.getElementById('chart-weekly');
        if (!canvas) {
            return;
        }
        var rows = sliceDaily(readDaily(data), 7);
        var views = rows.map(function (r) { return r.views; });
        var unique = rows.map(function (r) { return r.unique; });

        if (sum(views) + sum(unique) === 0) {
            showEmpty(canvas, 'chart-weekly');
            return;
        }
        hideEmpty(canvas, 'chart-weekly');

        var opts = baseOptions();
        opts.scales = axes();
        opts.plugins = {
            legend: { position: 'bottom', labels: { usePointStyle: true, color: THEME.tick } },
            tooltip: darkTooltip()
        };

        charts.weekly = new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: rows.map(function (r) { return r.date; }),
                datasets: [
                    {
                        label: 'Page views',
                        data: views,
                        backgroundColor: THEME.barPrimary,
                        borderRadius: 6,
                        maxBarThickness: 18
                    },
                    {
                        label: 'Unique visitors',
                        data: unique,
                        backgroundColor: THEME.barSecondary,
                        borderRadius: 6,
                        maxBarThickness: 18
                    }
                ]
            },
            options: opts
        });
    }

    function renderLegend(listId, labels, counts, percents) {
        var list = document.getElementById(listId);
        if (!list) {
            return;
        }
        while (list.firstChild) {
            list.removeChild(list.firstChild);
        }
        labels.forEach(function (label, i) {
            var li = document.createElement('li');
            li.className = 'donut-legend__item d-flex align-items-center gap-2';

            var dot = document.createElement('span');
            dot.className = 'donut-legend__dot';
            dot.setAttribute('aria-hidden', 'true');
            dot.style.display = 'inline-block';
            dot.style.width = '0.75rem';
            dot.style.height = '0.75rem';
            dot.style.borderRadius = '50%';
            dot.style.backgroundColor = THEME.series[i % THEME.series.length];

            var name = document.createElement('span');
            name.className = 'donut-legend__label flex-grow-1';
            name.textContent = label;

            var pct = document.createElement('span');
            pct.className = 'donut-legend__percent text-muted';
            pct.textContent = percents[i].toFixed(1) + '%';

            var count = document.createElement('span');
            count.className = 'donut-legend__count fw-semibold';
            count.textContent = String(counts[i]);

            li.appendChild(dot);
            li.appendChild(name);
            li.appendChild(pct);
            li.appendChild(count);
            list.appendChild(li);
        });
    }

    /**
     * Doughnut over a {key:{count,percent}} map. Tooltips show count and percent;
     * the total is written in the centre and an HTML legend is rendered.
     */
    function distributionChart(data, key, canvasId, order, suffix) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }
        var src = isObject(data[key]) ? data[key] : {};
        var keys = order.slice();
        Object.keys(src).forEach(function (k) {
            if (keys.indexOf(k) === -1) {
                keys.push(k);
            }
        });

        var labels = [];
        var counts = [];
        var percents = [];
        keys.forEach(function (k) {
            var item = isObject(src[k]) ? src[k] : {};
            labels.push(k);
            counts.push(num(item.count));
            percents.push(num(item.percent));
        });

        var total = sum(counts);
        var totalEl = document.getElementById('total-' + suffix);
        if (totalEl) {
            totalEl.textContent = String(total);
        }

        if (total === 0) {
            showEmpty(canvas, canvasId);
            return;
        }
        hideEmpty(canvas, canvasId);

        // Fall back to computing percentages when the server omitted them.
        if (sum(percents) === 0) {
            percents = counts.map(function (c) { return Math.round((c / total) * 1000) / 10; });
        }

        renderLegend('legend-' + suffix, labels, counts, percents);

        var opts = baseOptions();
        opts.cutout = '68%';
        opts.plugins = {
            legend: { display: false },
            tooltip: Object.assign(darkTooltip(), {
                callbacks: {
                    label: function (ctx) {
                        var i = ctx.dataIndex;
                        return ctx.label + ': ' + counts[i] + ' (' + percents[i].toFixed(1) + '%)';
                    }
                }
            })
        };

        charts[canvasId] = new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: labels.map(function (_, i) { return THEME.series[i % THEME.series.length]; }),
                    borderColor: THEME.donutBorder,
                    borderWidth: 2
                }]
            },
            options: opts
        });
    }

    function init() {
        var ids = ['chart-daily', 'chart-weekly', 'chart-language', 'chart-device'];
        if (typeof window.Chart !== 'function') {
            // Chart.js failed to load: degrade to empty-state messages.
            ids.forEach(function (id) {
                showEmpty(document.getElementById(id), id);
            });
            return;
        }
        var data = readData();
        dailyChart(data);
        weeklyChart(data);
        distributionChart(data, 'languages', 'chart-language', ['en', 'id', 'ms'], 'language');
        distributionChart(data, 'devices', 'chart-device', ['desktop', 'mobile', 'tablet'], 'device');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
