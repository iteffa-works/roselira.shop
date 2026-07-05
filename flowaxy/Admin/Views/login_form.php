<h1>Вхід</h1>
<?php if (!empty($error)): ?><p class="admin-error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
    <label>Логін <input type="text" name="username" required autocomplete="username"></label>
    <label>Пароль <input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit">Увійти</button>
</form>
