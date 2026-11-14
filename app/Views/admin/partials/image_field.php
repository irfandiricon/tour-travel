<?php
/**
 * @var array|null $item
 * @var bool       $imageRequired
 * @var bool       $removable     Show a "Remove current image" checkbox (name image_remove)
 * @var string     $hint          Optional help text under the input
 */
$imageRequired = $imageRequired ?? false;
$removable     = $removable ?? false;
$hint          = $hint ?? '';
$path          = $item['image_path'] ?? '';
?>
<div class="form-row col-12">
    <label for="image" class="form-label">Image (JPEG, PNG or WebP, max 5 MB)<?php if ($imageRequired && $path === ''): ?> <span class="req" aria-hidden="true">*</span><span class="sr-only">required</span><?php endif; ?></label>
    <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp"
        <?= ($imageRequired && $path === '') ? 'required aria-required="true"' : '' ?>
        aria-describedby="<?= $hint !== '' ? 'image-hint ' : '' ?>error-image">
    <?php if ($hint !== ''): ?>
        <div id="image-hint" class="form-text"><?= esc($hint) ?></div>
    <?php endif; ?>
    <div class="field-error invalid-feedback" id="error-image" data-error-for="image" role="alert" hidden></div>
    <?php if ($path !== ''): ?>
        <p class="mt-2 mb-0 small">Current image:<br>
            <img src="<?= esc(base_url($path)) ?>" alt="Current image" class="thumb img-thumbnail mt-1">
        </p>
        <?php if ($removable): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="image_remove" name="image_remove">
                <label class="form-check-label" for="image_remove">Remove current image</label>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>