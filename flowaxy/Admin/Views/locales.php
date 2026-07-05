<h1>Тексти UI</h1>
<div class="admin-filters">
    <?php foreach ($locales as $loc): ?>
    <a href="<?= admin_url('locales', ['locale' => $loc]) ?>" class="<?= $activeLocale === $loc ? 'is-active' : '' ?>"><?= e(strtoupper($loc)) ?></a>
    <?php endforeach; ?>
</div>

<form method="post" class="admin-form admin-form--wide">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="locale" value="<?= e($activeLocale) ?>">
    <table class="admin-table">
        <thead><tr><th>Ключ</th><th><?= e(strtoupper($activeLocale)) ?></th><th>EN (fallback)</th></tr></thead>
        <tbody>
        <?php foreach ($strings as $key => $value): ?>
        <tr>
            <td><code><?= e($key) ?></code></td>
            <td><input type="text" name="key_<?= e($key) ?>" value="<?= e($value) ?>" class="admin-input-full"></td>
            <td class="admin-muted"><small><?= e((string) ($enStrings[$key] ?? '')) ?></small></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="admin-btn">Зберегти</button>
</form>
