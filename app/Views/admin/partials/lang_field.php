<?php
/**
 * Translatable field with one input per language (en required / id / ms), shown as tabs.
 *
 * @var string      $base      Field base name, e.g. "title" -> title_en, title_id, title_ms
 * @var string      $label     Visible label
 * @var string      $type      'text' (default) or 'textarea'
 * @var bool        $required  Whether the _en value is required (default true)
 * @var int|null    $maxlength Optional max length
 * @var array|null  $item      Record to prefill from
 * @var bool        $allRequired Mark all three languages required (site-config sections)
 */
$type        = $type ?? 'text';
$required    = $required ?? true;
$allRequired = $allRequired ?? false;
$maxlength   = $maxlength ?? null;
$item        = $item ?? [];
$langs       = ['en' => 'English', 'id' => 'Indonesia', 'ms' => 'Melayu'];
?>
<fieldset class="lang-field col-12" data-lang-field>
    <legend class="float-none w-auto fs-6 fw-semibold px-1"><?= esc($label) ?><?php if ($required || $allRequired): ?> <span class="req" aria-hidden="true">*</span><?php endif; ?></legend>
    <div role="tablist" aria-label="<?= esc($label) ?> languages" class="lang-tabs nav nav-tabs mb-2">
        <?php $first = true; foreach ($langs as $code => $name): ?>
            <button type="button" class="nav-link" role="tab" id="tab-<?= esc($base) ?>-<?= $code ?>"
                    aria-controls="panel-<?= esc($base) ?>-<?= $code ?>"
                    aria-selected="<?= $first ? 'true' : 'false' ?>"
                    tabindex="<?= $first ? '0' : '-1' ?>" data-lang-tab="<?= $code ?>">
                <?= esc($name) ?> (<?= strtoupper($code) ?>)<?php if ($code === 'en' && $required || $allRequired): ?> *<?php endif; ?>
            </button>
        <?php $first = false; endforeach; ?>
    </div>
    <?php $first = true; foreach ($langs as $code => $name):
        $field   = $base . '_' . $code;
        $isReq   = $allRequired || ($required && $code === 'en');
        $value   = $item[$field] ?? '';
        $errorId = 'error-' . $field;
    ?>
        <div role="tabpanel" id="panel-<?= esc($base) ?>-<?= $code ?>"
             aria-labelledby="tab-<?= esc($base) ?>-<?= $code ?>"
             data-lang-panel="<?= $code ?>" <?= $first ? '' : 'hidden' ?>>
            <label for="<?= esc($field) ?>" class="form-label">
                <?= esc($label) ?> (<?= strtoupper($code) ?>)<?php if ($isReq): ?> <span class="req" aria-hidden="true">*</span><span class="sr-only">required</span><?php endif; ?>
            </label>
            <?php if ($type === 'textarea'): ?>
                <textarea class="form-control" id="<?= esc($field) ?>" name="<?= esc($field) ?>" rows="5"
                    <?= $maxlength ? 'maxlength="' . (int) $maxlength . '"' : '' ?>
                    <?= $isReq ? 'required aria-required="true"' : '' ?>
                    aria-describedby="<?= $errorId ?>"><?= esc((string) $value) ?></textarea>
            <?php else: ?>
                <input type="text" class="form-control" id="<?= esc($field) ?>" name="<?= esc($field) ?>"
                    value="<?= esc((string) $value) ?>"
                    <?= $maxlength ? 'maxlength="' . (int) $maxlength . '"' : '' ?>
                    <?= $isReq ? 'required aria-required="true"' : '' ?>
                    aria-describedby="<?= $errorId ?>">
            <?php endif; ?>
            <div class="field-error invalid-feedback" id="<?= $errorId ?>" data-error-for="<?= esc($field) ?>" role="alert" hidden></div>
        </div>
    <?php $first = false; endforeach; ?>
</fieldset>
