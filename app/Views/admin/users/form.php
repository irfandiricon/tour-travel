<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$item     = $item ?? [];
$editing  = ! empty($item['id']);
$isSelf   = ! empty($item['is_self']);
$status   = (string) ($item['status'] ?? 'active');
$pwHint   = $editing ? 'Leave blank to keep the current password. ' : '';
$pwHint  .= 'At least 8 characters.';
?>
<?= view('admin/partials/form_open', ['entity' => 'users', 'page' => 'users', 'title' => $title, 'item' => $item, 'legend' => 'Required.']) ?>
    <div class="form-row col-12 col-md-6">
        <label for="username" class="form-label">Username <span class="req" aria-hidden="true">*</span></label>
        <input type="text" class="form-control" id="username" name="username" required maxlength="50"
               autocomplete="off" autocapitalize="none" spellcheck="false"
               value="<?= esc((string) ($item['username'] ?? ''), 'attr') ?>" aria-describedby="hint-username error-username">
        <div class="form-text" id="hint-username">3 to 50 characters: letters, numbers, dot, dash or underscore.</div>
        <div class="field-error invalid-feedback" id="error-username" data-error-for="username" role="alert" hidden></div>
    </div>
    <div class="form-row col-12 col-md-6">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select" aria-describedby="<?= $isSelf ? 'hint-status ' : '' ?>error-status"<?= $isSelf ? ' disabled' : '' ?>>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (can sign in)</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (cannot sign in)</option>
        </select>
        <?php if ($isSelf): ?>
            <div class="form-text" id="hint-status">You cannot deactivate the account you are signed in with.</div>
        <?php endif; ?>
        <div class="field-error invalid-feedback" id="error-status" data-error-for="status" role="alert" hidden></div>
    </div>
    <div class="form-row col-12 col-md-6">
        <label for="password" class="form-label">Password<?php if (! $editing): ?> <span class="req" aria-hidden="true">*</span><?php endif; ?></label>
        <input type="password" class="form-control" id="password" name="password" minlength="8" maxlength="72"
               autocomplete="new-password"<?= $editing ? '' : ' required' ?> aria-describedby="hint-password error-password">
        <div class="form-text" id="hint-password"><?= esc($pwHint) ?></div>
        <div class="field-error invalid-feedback" id="error-password" data-error-for="password" role="alert" hidden></div>
    </div>
    <div class="form-row col-12 col-md-6">
        <label for="password_confirm" class="form-label">Confirm password<?php if (! $editing): ?> <span class="req" aria-hidden="true">*</span><?php endif; ?></label>
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" maxlength="72"
               autocomplete="new-password"<?= $editing ? '' : ' required' ?> aria-describedby="error-password_confirm">
        <div class="field-error invalid-feedback" id="error-password_confirm" data-error-for="password_confirm" role="alert" hidden></div>
    </div>
<?= view('admin/partials/form_close', ['page' => 'users']) ?>
<?= $this->endSection() ?>