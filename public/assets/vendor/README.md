# Vendor assets (bundled locally)

All third-party front-end assets are served from this directory. No runtime CDN requests are made (Requirements 10.1, 10.2, 12.26). Files were downloaded from jsDelivr with PowerShell `Invoke-WebRequest`.

| Library | Version | Local path | Source URL |
|---|---|---|---|
| Bootstrap CSS | 5.3.8 | `bootstrap/css/bootstrap.min.css` | https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css |
| Bootstrap JS bundle (with Popper) | 5.3.8 | `bootstrap/js/bootstrap.bundle.min.js` | https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js |
| Bootstrap Icons CSS | 1.11.4 | `bootstrap-icons/font/bootstrap-icons.min.css` | https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/bootstrap-icons.min.css |
| Bootstrap Icons fonts | 1.11.4 | `bootstrap-icons/font/fonts/bootstrap-icons.woff2`, `.woff` | https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/fonts/ |
| Chart.js | 4.6.1 | `chartjs/chart.umd.min.js` | https://cdn.jsdelivr.net/npm/chart.js@4.6.1/dist/chart.umd.min.js |

## Notes

- The Bootstrap Icons CSS references fonts with the relative path `fonts/bootstrap-icons.woff2` and `.woff`, which resolve to `bootstrap-icons/font/fonts/`. No rewrite was needed.
- The trailing `sourceMappingURL` comments were removed from the Bootstrap and Chart.js files so browsers do not request missing `.map` files.
- The only remaining `http(s)://` strings are license or documentation comments and the SVG XML namespace. None trigger a network request.
- To upgrade, re-download from the same URLs with a new version and update this table.

## Fonts (UI redesign)
Variable woff2 fonts (latin subset, weight axis) bundled locally and loaded via `@font-face` in `css/tokens.css`. Downloaded once during development from jsDelivr (npm package `@fontsource-variable/*`, files `files/<name>-latin-wght-normal.woff2`); nothing is requested from a font service at runtime. Both fonts are licensed under the SIL Open Font License 1.1; the license text is stored next to each font as `OFL.txt`.
| Font | Version | License | Local path | Source URL |
|---|---|---|---|---|
| Plus Jakarta Sans (landing) | 5.3.0 (`@fontsource-variable/plus-jakarta-sans`) | SIL OFL 1.1 | `fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2`, `fonts/plus-jakarta-sans/OFL.txt` | https://cdn.jsdelivr.net/npm/@fontsource-variable/plus-jakarta-sans@5.3.0/files/plus-jakarta-sans-latin-wght-normal.woff2 |
| Figtree (admin) | 5.3.0 (`@fontsource-variable/figtree`) | SIL OFL 1.1 | `fonts/figtree/Figtree-latin-wght.woff2`, `fonts/figtree/OFL.txt` | https://cdn.jsdelivr.net/npm/@fontsource-variable/figtree@5.3.0/files/figtree-latin-wght-normal.woff2 |
