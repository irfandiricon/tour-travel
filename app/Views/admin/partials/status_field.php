<?php
/** @var array|null $item */
$current = $item['status'] ?? 'published';
?>
<div class="form-row col-12 col-md-6">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select" aria-describedby="error-status">
        <option value="published" <?= $current === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $current === 'draft' ? 'selected' : '' ?>>Draft</option>
    </select>
    <div class="field-error invalid-feedback" id="error-status" data-error-for="status" role="alert" hidden></div>
</div>