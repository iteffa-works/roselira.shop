<?php
$siteConfig = app_config();
$gtmId = (string) ($siteConfig['gtm_container_id'] ?? '');
$ga4Id = (string) ($siteConfig['ga4_measurement_id'] ?? '');

if ($gtmId === '' && $ga4Id === '') {
    return;
}
?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
window.gtag = gtag;
gtag('consent', 'default', {
    analytics_storage: 'granted',
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied'
});
</script>
<?php if ($gtmId !== ''): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtmId) ?>');</script>
<?php else: ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id) ?>"></script>
<script>
gtag('js', new Date());
gtag('config', <?= json_encode($ga4Id) ?>, {
    send_page_view: true,
    page_path: window.location.pathname,
    content_group1: 'admin'
});
</script>
<?php endif; ?>
<script>
window.__FLOWAXY_ADMIN_ANALYTICS__ = <?= json_encode([
    'gtmId' => $gtmId,
    'ga4Id' => $ga4Id,
    'section' => 'admin',
], JSON_UNESCAPED_UNICODE) ?>;
</script>
