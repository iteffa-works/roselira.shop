<?php
$gitAvailable = !empty($gitStatus['available']);
$gitUpdates = !empty($gitStatus['updates_available']);
$summary = $checks['summary'] ?? ['ok' => 0, 'warn' => 0, 'error' => 0];
$checkItems = $checks['items'] ?? [];
$feedToken = $feedSecret !== '' ? rawurlencode($feedSecret) : '';
$base = rtrim($appUrl, '/');
?>

<div class="admin-page-header">
    <div>
        <h1>Система та cron</h1>
        <p class="admin-muted">Git-оновлення, cron, перевірка feeds, sitemap, SEO та конфігурації</p>
    </div>
    <?php if (($summary['error'] ?? 0) > 0): ?>
    <span class="admin-badge admin-badge--cancelled"><?= (int) $summary['error'] ?> помилок</span>
    <?php elseif (($summary['warn'] ?? 0) > 0): ?>
    <span class="admin-badge admin-badge--new"><?= (int) $summary['warn'] ?> попереджень</span>
    <?php else: ?>
    <span class="admin-badge admin-badge--done">OK</span>
    <?php endif; ?>
</div>

<section class="admin-card admin-card--telegram">
    <div class="admin-card__head">
        <div>
            <h2 class="admin-card__title">Cron — щоденні задачі</h2>
            <p class="admin-muted admin-card__desc">Git pull + перевірка Google/Meta feed, sitemap, robots, Telegram, analytics</p>
        </div>
    </div>

    <dl class="admin-dl">
        <dt>Crontab (CLI)</dt>
        <dd><pre class="admin-code admin-code--inline">0 4 * * * /usr/bin/php <?= e(rtrim($projectRoot, '/\\')) ?>/cron.php</pre></dd>
        <?php if (!empty($cronStatus['last_run_at'])): ?>
        <dt>Останній cron</dt>
        <dd><?= e((string) $cronStatus['last_run_at']) ?></dd>
        <dt>Статус</dt>
        <dd><?= e((string) ($cronStatus['last_status'] ?? '—')) ?></dd>
        <?php endif; ?>
    </dl>

    <div class="admin-form__actions">
        <form method="post" action="<?= admin_url('system/cron') ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="admin-btn admin-btn--telegram">Запустити cron зараз</button>
        </form>
        <form method="post" action="<?= admin_url('system/checks') ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="admin-btn admin-btn--outline">Перевірити все</button>
        </form>
    </div>

    <?php if (!empty($cronStatus['last_output'])): ?>
    <details class="admin-details">
        <summary>Лог останнього cron</summary>
        <pre class="admin-code admin-code--log"><?= e((string) $cronStatus['last_output']) ?></pre>
    </details>
    <?php endif; ?>
</section>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2 class="admin-card__title">Перевірки</h2>
            <p class="admin-muted admin-card__desc">
                <?php if (!empty($checks['checked_at'])): ?>
                Остання: <?= e((string) $checks['checked_at']) ?> —
                <?php endif; ?>
                OK <?= (int) ($summary['ok'] ?? 0) ?>,
                попереджень <?= (int) ($summary['warn'] ?? 0) ?>,
                помилок <?= (int) ($summary['error'] ?? 0) ?>
            </p>
        </div>
    </div>

    <div class="admin-check-list">
        <?php foreach ($checkItems as $item): ?>
        <div class="admin-check admin-check--<?= e((string) ($item['status'] ?? 'warn')) ?>">
            <span class="admin-check__status" aria-hidden="true"></span>
            <div class="admin-check__body">
                <strong><?= e((string) ($item['label'] ?? '')) ?></strong>
                <span><?= e((string) ($item['message'] ?? '')) ?></span>
            </div>
            <?php if (!empty($item['url'])): ?>
            <a href="<?= e((string) $item['url']) ?>" class="admin-check__link" target="_blank" rel="noopener">↗</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="admin-grid admin-grid--split">
    <section class="admin-card">
        <h2 class="admin-card__title">Product feeds</h2>
        <p class="admin-muted">Підключіть у Google Merchant Center та Meta Commerce Manager</p>
        <dl class="admin-dl">
            <dt>Google feed</dt>
            <dd>
                <?php if ($base !== ''): ?>
                <a href="<?= e($base) ?>/feeds/google.xml<?= $feedToken !== '' ? '?token=' . e($feedToken) : '' ?>" target="_blank" rel="noopener"><?= e($base) ?>/feeds/google.xml</a>
                <?php else: ?>
                <span class="admin-muted">Задайте APP_URL</span>
                <?php endif; ?>
            </dd>
            <dt>Meta feed</dt>
            <dd>
                <?php if ($base !== ''): ?>
                <a href="<?= e($base) ?>/feeds/meta.xml<?= $feedToken !== '' ? '?token=' . e($feedToken) : '' ?>" target="_blank" rel="noopener"><?= e($base) ?>/feeds/meta.xml</a>
                <?php else: ?>
                <span class="admin-muted">Задайте APP_URL</span>
                <?php endif; ?>
            </dd>
            <dt>FEED_SECRET</dt>
            <dd><?= $feedSecret !== '' ? 'Увімкнено' : 'Не задано (feeds публічні)' ?></dd>
        </dl>
    </section>

    <section class="admin-card">
        <h2 class="admin-card__title">SEO</h2>
        <dl class="admin-dl">
            <dt>Sitemap</dt>
            <dd><?php if ($base !== ''): ?><a href="<?= e($base) ?>/sitemap.xml" target="_blank" rel="noopener"><?= e($base) ?>/sitemap.xml</a><?php else: ?>—<?php endif; ?></dd>
            <dt>robots.txt</dt>
            <dd><?php if ($base !== ''): ?><a href="<?= e($base) ?>/robots.txt" target="_blank" rel="noopener"><?= e($base) ?>/robots.txt</a><?php else: ?>—<?php endif; ?></dd>
        </dl>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2 class="admin-card__title">Git — оновлення коду</h2>
            <p class="admin-muted admin-card__desc">
                <a href="https://github.com/iteffa-works/roselira.shop" target="_blank" rel="noopener">github.com/iteffa-works/roselira.shop</a>
            </p>
        </div>
        <?php if ($gitAvailable): ?>
        <span class="admin-badge admin-badge--<?= $gitUpdates ? 'new' : 'done' ?>">
            <?= $gitUpdates ? 'Є оновлення' : 'Актуально' ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if (!$gitAvailable): ?>
    <p><?= e((string) ($gitStatus['message'] ?? '')) ?></p>
    <pre class="admin-code">git clone https://github.com/iteffa-works/roselira.shop.git .</pre>
    <?php else: ?>
    <dl class="admin-dl">
        <dt>Гілка</dt>
        <dd><code><?= e((string) ($gitStatus['branch'] ?? '')) ?></code></dd>
        <dt>Commit</dt>
        <dd><code><?= e(substr((string) ($gitStatus['local_commit'] ?? ''), 0, 7)) ?></code> — <?= e((string) ($gitStatus['local_subject'] ?? '')) ?></dd>
        <?php if ($gitUpdates): ?>
        <dt>На GitHub</dt>
        <dd>+<?= (int) ($gitStatus['behind'] ?? 0) ?> commit</dd>
        <?php endif; ?>
        <?php if (!empty($gitStatus['last_update_at'])): ?>
        <dt>Останній pull</dt>
        <dd><?= e((string) $gitStatus['last_update_at']) ?></dd>
        <?php endif; ?>
    </dl>
    <form method="post" action="<?= admin_url('system/git-pull') ?>" class="admin-form__actions">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="admin-btn" <?= !$gitUpdates ? 'disabled' : '' ?>>Git pull зараз</button>
    </form>
    <?php endif; ?>
</section>
