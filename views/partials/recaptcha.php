<?php
$siteKey = recaptcha_site_key();
if ($siteKey === '') {
    return;
}
$class = $recaptchaClass ?? '';
?>
<div class="recaptcha-widget<?= $class !== '' ? ' ' . e($class) : '' ?>">
    <div class="g-recaptcha" data-sitekey="<?= e($siteKey) ?>"></div>
</div>
