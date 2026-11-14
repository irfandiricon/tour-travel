<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$config  = $config ?? [];
$ogImage = (string) ($config['og_image'] ?? '');
$aboutImage = (string) ($config['about_image'] ?? '');
$sections = [
    'content'  => 'Content',
    'contact'  => 'Contact',
    'whatsapp' => 'WhatsApp',
    'seo'      => 'SEO',
];
?>
<h1 class="h3 mb-3"><?= esc($title) ?></h1>
<p class="legend text-body-secondary small"><span class="req" aria-hidden="true">*</span> Required. Hero, about, gallery and contact sections need English, Indonesian and Malay content.</p>

<div id="form-summary" class="alert alert-danger" role="alert" tabindex="-1" hidden></div>

<form data-admin-form data-entity="site-config" data-mode="save" data-id="" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <ul class="nav nav-tabs mb-3" id="site-config-tabs" role="tablist" aria-label="Configuration sections">
        <?php $first = true; foreach ($sections as $key => $name): ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link<?= $first ? ' active' : '' ?>" id="config-tab-<?= $key ?>"
                        data-bs-toggle="tab" data-bs-target="#config-pane-<?= $key ?>"
                        role="tab" aria-controls="config-pane-<?= $key ?>"
                        aria-selected="<?= $first ? 'true' : 'false' ?>"><?= esc($name) ?></button>
            </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="config-pane-content" role="tabpanel" aria-labelledby="config-tab-content" tabindex="0">
            <div class="row g-3">
                <h2 class="h5 col-12 mb-0">Hero</h2>
                <?= view('admin/partials/lang_field', ['base' => 'hero_title', 'label' => 'Hero title', 'type' => 'text', 'allRequired' => true, 'maxlength' => 255, 'item' => $config]) ?>
                <?= view('admin/partials/lang_field', ['base' => 'hero_subtitle', 'label' => 'Hero subtitle', 'type' => 'text', 'allRequired' => true, 'maxlength' => 500, 'item' => $config]) ?>

                <h2 class="h5 col-12 mb-0">About</h2>
                <?= view('admin/partials/lang_field', ['base' => 'about_title', 'label' => 'About heading (optional)', 'type' => 'text', 'required' => false, 'maxlength' => 150, 'item' => $config]) ?>
                <?= view('admin/partials/lang_field', ['base' => 'about_body', 'label' => 'About', 'type' => 'textarea', 'allRequired' => true, 'item' => $config]) ?>

                <div class="form-row col-12">
                    <label for="about_image" class="form-label">About picture (JPEG, PNG or WebP, max 5 MB)</label>
                    <input type="file" class="form-control" id="about_image" name="about_image" accept="image/jpeg,image/png,image/webp"
                           aria-describedby="about-image-help error-about_image">
                    <div id="about-image-help" class="form-text">Shown beside the About text on the landing page. Without a picture the text is shown on its own. A square or 4:3 photo works best.</div>
                    <div class="field-error invalid-feedback" id="error-about_image" data-error-for="about_image" role="alert" hidden></div>

                    <?php if ($aboutImage !== ''): ?>
                        <p class="mt-2 mb-1 small">Current picture:<br>
                            <img src="<?= esc(base_url($aboutImage), 'attr') ?>" alt="Current About picture" class="thumb img-thumbnail mt-1" id="about_image_preview">
                        </p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="about_image_remove" name="about_image_remove">
                            <label class="form-check-label" for="about_image_remove">Remove current picture</label>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 class="h5 col-12 mb-0">Gallery</h2>
                <?= view('admin/partials/lang_field', ['base' => 'gallery_title', 'label' => 'Gallery title', 'type' => 'text', 'allRequired' => true, 'maxlength' => 255, 'item' => $config]) ?>
            </div>
        </div>

        <div class="tab-pane fade" id="config-pane-contact" role="tabpanel" aria-labelledby="config-tab-contact" tabindex="0">
            <div class="row g-3">
                <h2 class="h5 col-12 mb-0">Contact</h2>
                <?= view('admin/partials/lang_field', ['base' => 'contact_body', 'label' => 'Contact text', 'type' => 'textarea', 'allRequired' => true, 'item' => $config]) ?>

                <div class="form-row col-md-6">
                    <label for="contact_email" class="form-label">Contact email</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="255" value="<?= esc((string) ($config['contact_email'] ?? '')) ?>" aria-describedby="error-contact_email">
                    <div class="field-error invalid-feedback" id="error-contact_email" data-error-for="contact_email" role="alert" hidden></div>
                </div>
                <div class="form-row col-md-6">
                    <label for="contact_address" class="form-label">Contact address</label>
                    <input type="text" class="form-control" id="contact_address" name="contact_address" maxlength="500" value="<?= esc((string) ($config['contact_address'] ?? '')) ?>" aria-describedby="error-contact_address">
                    <div class="field-error invalid-feedback" id="error-contact_address" data-error-for="contact_address" role="alert" hidden></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="config-pane-whatsapp" role="tabpanel" aria-labelledby="config-tab-whatsapp" tabindex="0">
            <div class="row g-3">
                <h2 class="h5 col-12 mb-0">WhatsApp booking</h2>
                <div class="form-row col-md-6">
                    <label for="whatsapp_number" class="form-label">WhatsApp number</label>
                    <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" inputmode="tel" maxlength="16"
                           placeholder="+628123456789" pattern="^\+\d{8,15}$"
                           value="<?= esc((string) ($config['whatsapp_number'] ?? '')) ?>"
                           aria-describedby="hint-whatsapp_number error-whatsapp_number">
                    <div class="form-text hint" id="hint-whatsapp_number">International (E.164) format: "+" then 8 to 15 digits, for example +628123456789. Leave empty to hide the booking button.</div>
                    <div class="field-error invalid-feedback" id="error-whatsapp_number" data-error-for="whatsapp_number" role="alert" hidden></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="config-pane-seo" role="tabpanel" aria-labelledby="config-tab-seo" tabindex="0">
            <div class="row g-3">
                <h2 class="h5 col-12 mb-0">Search engine optimization</h2>
                <?= view('admin/partials/lang_field', ['base' => 'site_name', 'label' => 'Site name', 'type' => 'text', 'required' => false, 'maxlength' => 120, 'item' => $config]) ?>
                <?= view('admin/partials/lang_field', ['base' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'required' => false, 'maxlength' => 320, 'item' => $config]) ?>
                <?= view('admin/partials/lang_field', ['base' => 'meta_keywords', 'label' => 'Meta keywords', 'type' => 'text', 'required' => false, 'maxlength' => 255, 'item' => $config]) ?>

                <div class="form-row col-12">
                    <label for="og_image" class="form-label">Social sharing image (og:image; JPEG, PNG or WebP, max 5 MB)</label>
                    <input type="file" class="form-control" id="og_image" name="og_image" accept="image/jpeg,image/png,image/webp"
                           aria-describedby="error-og_image">
                    <div class="field-error invalid-feedback" id="error-og_image" data-error-for="og_image" role="alert" hidden></div>
                    <?php if ($ogImage !== ''): ?>
                        <p class="mt-2 mb-0 small">Current image:<br>
                            <img src="<?= esc(base_url($ogImage), 'attr') ?>" alt="Current social sharing image" class="thumb img-thumbnail mt-1" id="og_image_preview">
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions mt-4">
        <button type="submit" class="btn btn-primary">Save configuration</button>
    </div>
</form>
<?= $this->endSection() ?>
