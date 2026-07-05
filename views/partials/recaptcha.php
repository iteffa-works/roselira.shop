<?php
$siteKey = recaptcha_site_key();
if ($siteKey === '') {
    return;
}
$class = trim((string) ($recaptchaClass ?? ''));
$theme = trim((string) ($recaptchaTheme ?? 'auto'));
?>
<div class="recaptcha-widget<?= $class !== '' ? ' ' . e($class) : '' ?>">
    <div class="recaptcha-widget__frame">
        <div
            class="recaptcha-widget__slot"
            data-recaptcha-sitekey="<?= e($siteKey) ?>"
            data-recaptcha-theme="<?= e($theme) ?>"
        ></div>
    </div>
</div>
