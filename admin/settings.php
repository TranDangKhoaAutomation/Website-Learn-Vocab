<?php
require_once __DIR__ . '/_admin.php';

$definitions = setting_definitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $oldValues = [];
    $newValues = [];

    foreach ($definitions as $key => $definition) {
        $type = $definition['type'];
        $oldValues[$key] = get_setting($key, $definition['default'] ?? null);

        if ($type === 'boolean') {
            $value = isset($_POST[$key]);
        } else {
            $value = $_POST[$key] ?? ($definition['default'] ?? '');
        }

        $value = normalize_setting_value($value, $type, $definition['default'] ?? null, $definition);
        set_setting($pdo, $key, $value, $type);
        $newValues[$key] = $value;
    }

    audit_log($pdo, 'settings_update', 'settings', null, $oldValues, $newValues);
    set_flash('success', 'Đã lưu cài đặt website.');
    redirect('admin/settings.php');
}

admin_header('Settings', 'settings');
?>

<section class="panel settings-admin-panel">
    <div class="panel-header">
        <div>
            <h2>Cài đặt website</h2>
            <p>Những thay đổi ở đây có tác dụng ngay trên dashboard, trang học và trang public.</p>
        </div>
        <span class="badge text-bg-light">Live settings</span>
    </div>

    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <div class="settings-admin-grid">
            <?php foreach ($definitions as $key => $definition): ?>
                <?php
                $type = $definition['type'];
                $value = get_setting($key, $definition['default'] ?? null);
                ?>
                <div class="settings-admin-item <?= $type === 'boolean' ? 'is-switch' : '' ?>">
                    <div>
                        <label class="form-label" for="setting_<?= e($key) ?>"><?= e($definition['label']) ?></label>
                        <?php if (!empty($definition['help'])): ?>
                            <div class="form-text"><?= e($definition['help']) ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($type === 'boolean'): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" id="setting_<?= e($key) ?>" type="checkbox" name="<?= e($key) ?>" <?= $value ? 'checked' : '' ?>>
                        </div>
                    <?php elseif ($type === 'number'): ?>
                        <input
                            class="form-control"
                            id="setting_<?= e($key) ?>"
                            name="<?= e($key) ?>"
                            type="number"
                            value="<?= e($value) ?>"
                            min="<?= e($definition['min'] ?? 0) ?>"
                            max="<?= e($definition['max'] ?? 9999) ?>"
                        >
                    <?php else: ?>
                        <input class="form-control" id="setting_<?= e($key) ?>" name="<?= e($key) ?>" type="text" value="<?= e($value) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Lưu cài đặt</button>
            <a class="btn btn-outline-secondary" href="<?= app_url('dashboard.php') ?>"><i class="bi bi-arrow-left"></i> Về dashboard</a>
        </div>
    </form>
</section>

<?php admin_footer(); ?>
