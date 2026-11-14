<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
/* ================================================================
   Sign-in page — split-screen layout
   - kiri  : ilustrasi / branding (tersembunyi di mobile, tampil >= lg)
   - kanan : form login (full-width di mobile, setengah lebar >= lg)
================================================================ */

/* Override layout's .admin-auth so we can take full viewport */
.admin-auth {
    display: grid;
    grid-template-columns: 1fr;
    min-height: 100dvh;
    padding: 0 !important;
    max-width: none;
    width: 100%;
}

@media (min-width: 992px) {
    .admin-auth {
        grid-template-columns: 1fr 1fr;
    }
}

/* ---- Branding panel (kiri) ---- */
.signin-visual {
    display: none;
    position: relative;
    overflow: hidden;
    background: linear-gradient(160deg, #0d2240 0%, #1a4a8a 55%, #2d7dd2 100%);
}
@media (min-width: 992px) {
    .signin-visual { display: flex; flex-direction: column; }
}

.signin-visual__bg {
    position: absolute;
    inset: 0;
    background-image: url('<?= base_url("assets/img/signin-bg.svg") ?>');
    background-size: cover;
    background-position: center bottom;
    opacity: .9;
}
.signin-visual__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom,
        rgba(13,34,64,.15) 0%,
        rgba(13,34,64,.0) 40%,
        rgba(13,34,64,.5) 75%,
        rgba(13,34,64,.85) 100%);
}

.signin-visual__body {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    padding: 2.5rem;
}

.signin-visual__logo {
    display: inline-flex;
    align-items: center;
    gap: .75rem;
    color: #fff;
    text-decoration: none;
}
.signin-visual__logo-mark {
    width: 44px; height: 44px;
    background: rgba(255,255,255,.18);
    border: 1.5px solid rgba(255,255,255,.35);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    backdrop-filter: blur(4px);
}
.signin-visual__logo-text {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: .02em;
    line-height: 1.2;
}
.signin-visual__logo-text small {
    display: block;
    font-size: .72rem;
    font-weight: 400;
    opacity: .75;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.signin-visual__quote {
    color: #fff;
}
.signin-visual__quote blockquote {
    margin: 0 0 1.25rem;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.35;
}
.signin-visual__quote blockquote span {
    color: #f5c842;
}
.signin-visual__quote p {
    margin: 0;
    font-size: .9rem;
    opacity: .75;
}

/* badges bawah kiri */
.signin-badges {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}
.signin-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .8rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 999px;
    color: #fff;
    font-size: .8rem;
    font-weight: 500;
    backdrop-filter: blur(6px);
}
.signin-badge i { font-size: .95rem; }

/* ---- Form panel (kanan) ---- */
.signin-form-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 100dvh;
    padding: 2rem 1.25rem;
    background: #f7f8fc;
}
@media (min-width: 576px) {
    .signin-form-panel { padding: 2.5rem 2rem; }
}

.signin-form-wrap, .signin-form-wrap.auth-card {
    width: 100%;
    max-width: 420px;
    background: transparent;
    border-radius: 0;
}

/* Mobile logo (hanya tampil < lg) */
.signin-mobile-logo {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: 2rem;
    color: var(--ad-primary);
}
@media (min-width: 992px) { .signin-mobile-logo { display: none; } }
.signin-mobile-logo-mark {
    width: 40px; height: 40px;
    background: var(--ad-primary-soft);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.signin-mobile-logo-text {
    font-weight: 700;
    font-size: 1.05rem;
    line-height: 1.2;
}
.signin-mobile-logo-text small {
    display: block;
    font-size: .7rem;
    color: var(--c-muted);
    font-weight: 400;
}

/* Card form */
.signin-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,.07), 0 24px 60px -8px rgba(16,24,40,.12);
    padding: 2.25rem 2rem;
}
@media (min-width: 576px) {
    .signin-card { padding: 2.5rem 2.5rem; }
}

.signin-card__heading {
    margin: 0 0 .4rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--c-ink);
}
.signin-card__sub {
    margin: 0 0 1.75rem;
    font-size: .9rem;
    color: var(--c-muted);
}

/* Input enhancements */
.signin-card .input-group-text {
    background: #f1f4f9;
    border-color: #dde2ea;
    color: var(--c-muted);
    border-radius: 10px 0 0 10px !important;
}
.signin-card .form-control {
    border-left: 0;
    border-color: #dde2ea;
    border-radius: 0 10px 10px 0 !important;
    min-height: 46px;
    background: #f9fafc;
    transition: border-color .18s, box-shadow .18s;
}
.signin-card .form-control:focus {
    background: #fff;
    border-color: var(--ad-primary);
    box-shadow: 0 0 0 3px rgba(31,95,208,.14);
}
.signin-card .form-control:focus ~ .input-group-text,
.signin-card .input-group:focus-within .input-group-text {
    border-color: var(--ad-primary);
    color: var(--ad-primary);
}
.signin-card label {
    font-size: .85rem;
    font-weight: 600;
    color: var(--c-ink);
    margin-bottom: .4rem;
}

/* Show/hide password button */
.btn-reveal {
    border: 1px solid #dde2ea;
    border-left: 0;
    background: #f1f4f9;
    color: var(--c-muted);
    min-height: 46px;
    border-radius: 0 10px 10px 0 !important;
    padding: 0 .9rem;
}
.btn-reveal:hover { background: #e9edf5; color: var(--c-ink); }

/* Submit button */
.signin-btn {
    min-height: 48px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: .01em;
    background: var(--ad-primary);
    border-color: var(--ad-primary);
    transition: background .2s, transform .1s, box-shadow .2s;
    box-shadow: 0 4px 14px rgba(31,95,208,.35);
}
.signin-btn:hover, .signin-btn:focus-visible {
    background: #174eae;
    border-color: #174eae;
    box-shadow: 0 6px 20px rgba(31,95,208,.45);
    transform: translateY(-1px);
}
.signin-btn:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(31,95,208,.3); }

/* Error alert */
.signin-error {
    border-radius: 10px;
    font-size: .88rem;
    border-left: 4px solid var(--ad-danger);
    background: #fff1f2;
    color: #9b1c2a;
    border-color: var(--ad-danger);
}

/* Footer text */
.signin-footer {
    margin-top: 1.75rem;
    text-align: center;
    font-size: .78rem;
    color: var(--c-muted);
}
.signin-footer a {
    color: var(--ad-primary);
    text-decoration: none;
}
.signin-footer a:hover { text-decoration: underline; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- ===================== SIGN-IN: split-screen ===================== -->
<aside class="signin-visual" aria-hidden="true">
    <div class="signin-visual__bg"></div>
    <div class="signin-visual__overlay"></div>
    <div class="signin-visual__body">

        <!-- Logo -->
        <a class="signin-visual__logo" href="<?= site_url() ?>">
            <span class="signin-visual__logo-mark">
                <i class="bi bi-compass"></i>
            </span>
            <span class="signin-visual__logo-text">
                Travel Rental
                <small>Administration Panel</small>
            </span>
        </a>

        <!-- Tagline -->
        <div class="signin-visual__quote">
            <blockquote>
                Every journey<br>starts with a&nbsp;<span>great&nbsp;ride.</span>
            </blockquote>
            <p>Manage your fleet, news, and bookings all in one place.</p>
            <div class="signin-badges">
                <span class="signin-badge"><i class="bi bi-truck-front"></i> Fleet Management</span>
                <span class="signin-badge"><i class="bi bi-graph-up-arrow"></i> Analytics</span>
                <span class="signin-badge"><i class="bi bi-translate"></i> 3 Languages</span>
            </div>
        </div>

    </div>
</aside>

<!-- ---- Form panel ---- -->
<div class="signin-form-panel">
    <div class="signin-form-wrap auth-card">

        <!-- Mobile logo -->
        <div class="signin-mobile-logo">
            <span class="signin-mobile-logo-mark">
                <i class="bi bi-compass"></i>
            </span>
            <span class="signin-mobile-logo-text">
                Travel Rental
                <small>Administration</small>
            </span>
        </div>

        <div class="signin-card">
            <h1 class="signin-card__heading">Welcome back</h1>
            <p class="signin-card__sub">Sign in to your admin account to continue.</p>

            <?php if (! empty($error)): ?>
                <div class="alert signin-error d-flex align-items-start gap-2 mb-4" role="alert">
                    <i class="bi bi-shield-exclamation flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div><?= esc($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('signin') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text" id="usernameAddon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               id="username"
                               name="username"
                               aria-describedby="usernameAddon"
                               autocomplete="username"
                               spellcheck="false"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text" id="passwordAddon">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                        </span>
                        <input type="password"
                               class="form-control border-end-0"
                               id="password"
                               name="password"
                               aria-describedby="passwordAddon"
                               autocomplete="current-password"
                               required>
                        <button type="button"
                                class="btn btn-reveal"
                                id="revealBtn"
                                aria-label="Show password"
                                aria-pressed="false">
                            <i class="bi bi-eye" id="revealIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary signin-btn w-100">
                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                    Sign In
                </button>
            </form>
        </div>

        <div class="signin-footer">
            <a href="<?= site_url() ?>" target="_blank" rel="noopener">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to website
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var btn  = document.getElementById('revealBtn');
    var inp  = document.getElementById('password');
    var icon = document.getElementById('revealIcon');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
</script>
<?= $this->endSection() ?>