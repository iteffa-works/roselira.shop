<div class="admin-auth-card">
    <header class="admin-auth-card__head">
        <a href="/" class="admin-auth-card__logo" aria-label="Roselira">
            <img src="<?= asset('assets/img/brand/logo-dark.svg') ?>" alt="Roselira" width="148" height="36">
        </a>
        <h1 class="admin-auth-card__title">Вхід</h1>
        <p class="admin-auth-card__lead">Панель керування магазином</p>
    </header>

    <?php if (!empty($error)): ?>
    <div class="admin-auth-card__alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="admin-auth-form">
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

        <label class="admin-auth-form__field">
            <span class="admin-auth-form__label">Логін</span>
            <input type="text" name="username" required autocomplete="username" placeholder="Ваш логін">
        </label>

        <label class="admin-auth-form__field">
            <span class="admin-auth-form__label">Пароль</span>
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </label>

        <?php if (recaptcha_enabled()): ?>
        <?php
        $recaptchaClass = 'recaptcha-widget--admin';
        $recaptchaTheme = 'light';
        require dirname(__DIR__, 3) . '/views/partials/recaptcha.php';
        ?>
        <?php endif; ?>

        <button type="submit" class="admin-btn admin-btn--block admin-auth-form__submit">Увійти</button>
    </form>

    <footer class="admin-auth-card__foot">
        <a href="/" class="admin-auth-card__back">← Повернутися на сайт</a>
    </footer>
</div>
