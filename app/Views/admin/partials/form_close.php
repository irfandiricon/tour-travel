<?php /** @var string $page */ ?>
    <div class="form-actions col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save</button>
        <a href="<?= base_url('admin/' . $page) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>