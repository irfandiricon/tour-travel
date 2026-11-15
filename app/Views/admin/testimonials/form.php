<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$item   = $item ?? [];
$rating = (int) ($item['rating'] ?? 5);
$rating = $rating >= 1 && $rating <= 5 ? $rating : 5;
// image_field reads image_path; testimonials store the picture in photo_path.
$photoItem = ['image_path' => (string) ($item['photo_path'] ?? '')];
?>
<?= view('admin/partials/form_open', ['entity' => 'testimonials', 'page' => 'testimonials', 'title' => $title, 'item' => $item, 'legend' => 'Required. English (EN) is the default language and must be filled in.']) ?>
    <div class="form-row col-12 col-md-6">
        <label for="customer_name" class="form-label">Customer name <span class="req" aria-hidden="true">*</span></label>
        <input type="text" class="form-control" id="customer_name" name="customer_name" required maxlength="100"
               value="<?= esc((string) ($item['customer_name'] ?? ''), 'attr') ?>" aria-describedby="error-customer_name">
        <div class="field-error invalid-feedback" id="error-customer_name" data-error-for="customer_name" role="alert" hidden></div>
    </div>
    <fieldset class="form-row col-12 col-md-6" aria-describedby="error-rating">
        <legend class="form-label fs-6 mb-1">Rating <span class="req" aria-hidden="true">*</span></legend>
        <div class="star-input">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <input class="visually-hidden" type="radio" id="rating-<?= $i ?>" name="rating" value="<?= $i ?>"<?= $i === $rating ? ' checked' : '' ?> required>
                <label for="rating-<?= $i ?>" title="<?= $i ?> <?= $i === 1 ? 'star' : 'stars' ?>">
                    <i class="bi bi-star-fill" aria-hidden="true"></i><span class="visually-hidden"><?= $i ?> <?= $i === 1 ? 'star' : 'stars' ?></span>
                </label>
            <?php endfor; ?>
        </div>
        <div class="field-error invalid-feedback" id="error-rating" data-error-for="rating" role="alert" hidden></div>
    </fieldset>
    <?= view('admin/partials/lang_field', ['base' => 'review', 'label' => 'Review', 'type' => 'textarea', 'required' => true, 'maxlength' => 500, 'item' => $item], ['saveData' => false]) ?>
    <p class="form-text col-12 mt-n2">Up to 500 characters per language. Indonesian and Malay are optional; when empty, the English text is shown.</p>
    <?= view('admin/partials/image_field', [
        'item'      => $photoItem,
        'label'     => 'Profile photo (JPEG, PNG or WebP, max 5 MB)',
        'removable' => true,
        'hint'      => 'Optional. A square photo works best. Without a photo the customer\'s initials are shown.',
    ], ['saveData' => false]) ?>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'testimonials']) ?>
<?= $this->endSection() ?>