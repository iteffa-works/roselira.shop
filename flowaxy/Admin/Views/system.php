<?php
$gitCanUpdate = !empty($gitStatus['can_update']);
$gitIsRepo = !empty($gitStatus['is_repo']);
$gitUpdates = !empty($gitStatus['updates_available']);
$summary = $checks['summary'] ?? ['ok' => 0, 'warn' => 0, 'error' => 0];
$checkItems = $checks['items'] ?? [];
$feedToken = $feedSecret !== '' ? rawurlencode($feedSecret) : '';
$base = rtrim(app_url(), '/');
$projectPath = rtrim($projectRoot, '/\\');
$cronCommand = '0 4 * * * /usr/bin/php ' . $projectPath . '/cron.php';

$checkGroups = [
    'Реклама та feeds' => ['google_feed', 'meta_feed', 'google_feed_url', 'meta_feed_url', 'analytics'],
    'SEO' => ['sitemap', 'sitemap_url', 'robots'],
    'Сервер і конфіг' => ['app_url', 'https', 'storage', 'feed_secret', 'catalog', 'telegram'],
];
$groupedChecks = [];
$usedIds = [];
foreach ($checkGroups as $title => $ids) {
    $items = array_values(array_filter($checkItems, static fn(array $item): bool => in_array($item['id'] ?? '', $ids, true)));
    if ($items !== []) {
        $groupedChecks[$title] = $items;
        foreach ($items as $item) {
            $usedIds[] = $item['id'] ?? '';
        }
    }
}
$otherChecks = array_values(array_filter($checkItems, static fn(array $item): bool => !in_array($item['id'] ?? '', $usedIds, true)));
if ($otherChecks !== []) {
    $groupedChecks['Інше'] = $otherChecks;
}

$checkedAt = !empty($checks['checked_at']) ? (string) $checks['checked_at'] : null;
$statusLabels = ['ok' => 'OK', 'warn' => 'Увага', 'error' => 'Помилка'];

$gitHubWebUrl = static function (string $repoUrl): string {
    $url = preg_replace('#\.git$#', '', trim($repoUrl)) ?? $repoUrl;
    if (str_starts_with($url, 'git@github.com:')) {
        $url = 'https://github.com/' . substr($url, 15);
    }

    return rtrim($url, '/');
};

$gitRepoWeb = $gitCanUpdate ? $gitHubWebUrl((string) ($gitStatus['repo_url'] ?? 'https://github.com/iteffa-works/roselira.shop')) : '';
$gitLocalCommit = (string) ($gitStatus['local_commit'] ?? '');
$gitRemoteCommit = (string) ($gitStatus['remote_commit'] ?? '');
$gitCommitUrl = $gitLocalCommit !== '' && $gitRepoWeb !== '' ? $gitRepoWeb . '/commit/' . $gitLocalCommit : '';
$gitCompareUrl = $gitUpdates && $gitLocalCommit !== '' && $gitRemoteCommit !== '' && $gitRepoWeb !== ''
    ? $gitRepoWeb . '/compare/' . substr($gitLocalCommit, 0, 7) . '...' . substr($gitRemoteCommit, 0, 7)
    : '';
$gitCommitDate = format_datetime_or_null((string) ($gitStatus['local_date'] ?? ''));
$gitRemoteDate = format_datetime_or_null((string) ($gitStatus['remote_date'] ?? ''));
$gitLastPullAt = format_datetime_or_null((string) ($gitStatus['last_update_at'] ?? ''));
$gitLastPullStatus = (string) ($gitStatus['last_update_status'] ?? '');
$gitSubject = trim((string) ($gitStatus['local_subject'] ?? ''));

$feedLinks = [
    [
        'label' => 'Google Merchant Center',
        'desc' => 'Товарний XML-feed для Google Ads / Merchant Center',
        'path' => '/feeds/google.xml',
        'tone' => 'google',
    ],
    [
        'label' => 'Meta Catalog',
        'desc' => 'Товарний XML-feed для Facebook та Instagram',
        'path' => '/feeds/meta.xml',
        'tone' => 'meta',
    ],
    [
        'label' => 'Sitemap.xml',
        'desc' => 'XML-карта сайту для пошукових систем',
        'path' => '/sitemap.xml',
        'tone' => 'seo',
    ],
    [
        'label' => 'robots.txt',
        'desc' => 'Правила індексації для пошукових роботів',
        'path' => '/robots.txt',
        'tone' => 'seo',
    ],
];
?>

<div class="admin-system">
    <header class="admin-system__header">
        <div class="admin-system__header-main">
            <h1>Система</h1>
            <p class="admin-muted">Деплой, cron, feeds і перевірка готовності до реклами</p>
        </div>
        <?php
        $healthClass = ($summary['error'] ?? 0) > 0 ? 'error' : (($summary['warn'] ?? 0) > 0 ? 'warn' : 'ok');
        $healthLabel = ($summary['error'] ?? 0) > 0 ? 'Потрібна увага' : (($summary['warn'] ?? 0) > 0 ? 'Є попередження' : 'Готово до запуску');
        ?>
        <div class="admin-system__health admin-system__health--<?= e($healthClass) ?>">
            <span class="admin-system__health-dot" aria-hidden="true"></span>
            <?= e($healthLabel) ?>
        </div>
    </header>

    <div class="admin-system__kpi">
        <div class="admin-system__kpi-item">
            <span class="admin-system__kpi-value admin-system__kpi-value--ok"><?= (int) ($summary['ok'] ?? 0) ?></span>
            <span class="admin-system__kpi-label">Перевірок OK</span>
        </div>
        <div class="admin-system__kpi-item">
            <span class="admin-system__kpi-value admin-system__kpi-value--warn"><?= (int) ($summary['warn'] ?? 0) ?></span>
            <span class="admin-system__kpi-label">Попереджень</span>
        </div>
        <div class="admin-system__kpi-item">
            <span class="admin-system__kpi-value admin-system__kpi-value--error"><?= (int) ($summary['error'] ?? 0) ?></span>
            <span class="admin-system__kpi-label">Помилок</span>
        </div>
        <div class="admin-system__kpi-item">
            <span class="admin-system__kpi-value"><?= $gitCanUpdate ? ($gitUpdates ? '+' . (int) ($gitStatus['behind'] ?? 0) : 'OK') : '—' ?></span>
            <span class="admin-system__kpi-label"><?= $gitCanUpdate ? 'Git / GitHub' : 'Git недоступний' ?></span>
        </div>
    </div>

    <div class="admin-system__grid admin-system__grid--2">
        <section class="admin-card admin-system__panel">
            <div class="admin-system__panel-head">
                <div class="admin-system__panel-icon admin-system__panel-icon--git" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.29-.01-1.05-.02-2.06-3.34.73-4.04-1.61-4.04-1.61-.54-1.38-1.35-1.75-1.35-1.75-1.1-.75.08-.74.08-.74 1.22.09 1.86 1.25 1.86 1.25 1.08 1.85 2.83 1.32 3.52 1.01.11-.78.42-1.32.76-1.62-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.52.12-3.17 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 3-.4c1.02.01 2.05.14 3 .4 2.29-1.55 3.3-1.23 3.3-1.23.66 1.65.24 2.87.12 3.17.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.62-5.49 5.92.43.37.81 1.1.81 2.22 0 1.61-.01 2.9-.01 3.29 0 .32.21.7.83.58A12.01 12.01 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                </div>
                <div class="admin-system__panel-title">
                    <h2>GitHub</h2>
                    <a href="https://github.com/iteffa-works/roselira.shop" target="_blank" rel="noopener" class="admin-system__panel-link">iteffa-works/roselira.shop</a>
                </div>
                <?php if ($gitCanUpdate): ?>
                <span class="admin-system__pill admin-system__pill--<?= $gitUpdates ? 'warn' : 'ok' ?>">
                    <?= $gitUpdates ? 'Є оновлення' : 'Актуально' ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if ($gitCanUpdate): ?>
            <dl class="admin-system__git-info">
                <div>
                    <dt>Поточна версія</dt>
                    <dd>
                        <?php if ($gitCommitUrl !== ''): ?>
                        <a href="<?= e($gitCommitUrl) ?>" target="_blank" rel="noopener" class="admin-system__sha-link">
                            <code class="admin-system__sha"><?= e(substr($gitLocalCommit, 0, 7)) ?></code>
                        </a>
                        <?php else: ?>
                        <code class="admin-system__sha"><?= e(substr($gitLocalCommit, 0, 7)) ?></code>
                        <?php endif; ?>
                        <span class="admin-system__branch"><?= e((string) ($gitStatus['branch'] ?? '')) ?></span>
                    </dd>
                </div>
                <div>
                    <dt>Дата commit</dt>
                    <dd><?= $gitCommitDate !== null ? e($gitCommitDate) : '—' ?></dd>
                </div>
                <div>
                    <dt>Останнє оновлення</dt>
                    <dd>
                        <?php if ($gitLastPullAt !== null): ?>
                        <?= e($gitLastPullAt) ?>
                        <?php if ($gitLastPullStatus !== ''): ?>
                        <span class="admin-system__git-status"><?= e($gitLastPullStatus) ?></span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="admin-system__git-muted">Ще не оновлювали через адмінку</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if ($gitUpdates): ?>
                <div>
                    <dt>На GitHub</dt>
                    <dd>
                        +<?= (int) ($gitStatus['behind'] ?? 0) ?> commit<?= ((int) ($gitStatus['behind'] ?? 0)) === 1 ? '' : 'ів' ?>
                        <?php if ($gitRemoteCommit !== ''): ?>
                        · <code><?= e(substr($gitRemoteCommit, 0, 7)) ?></code>
                        <?php endif; ?>
                        <?php if ($gitRemoteDate !== null): ?>
                        <span class="admin-system__git-muted">(<?= e($gitRemoteDate) ?>)</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>

            <?php if ($gitSubject !== ''): ?>
            <div class="admin-system__commit-wrap">
                <span class="admin-system__field-label">Повідомлення commit</span>
                <blockquote class="admin-system__commit"><?= e($gitSubject) ?></blockquote>
            </div>
            <?php endif; ?>

            <?php if ($gitUpdates && !empty($gitStatus['remote_subject'])): ?>
            <p class="admin-system__git-next">
                <span>Наступний на GitHub:</span>
                <?= e((string) $gitStatus['remote_subject']) ?>
                <?php if ($gitCompareUrl !== ''): ?>
                · <a href="<?= e($gitCompareUrl) ?>" target="_blank" rel="noopener">Переглянути diff</a>
                <?php endif; ?>
            </p>
            <?php endif; ?>

            <form method="post" action="<?= admin_url('system/git-pull') ?>" class="admin-system__actions">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="admin-btn admin-btn--telegram">
                    <?= $gitUpdates ? 'Завантажити оновлення' : 'Перевірити / оновити з GitHub' ?>
                </button>
            </form>
            <p class="admin-system__footnote">Fast-forward pull · <code>.env</code> і <code>storage/</code> не змінюються</p>

            <?php else: ?>
            <p class="admin-system__lead"><?= e((string) ($gitStatus['message'] ?? '')) ?></p>

            <?php if ($gitIsRepo): ?>
            <div class="admin-callout admin-callout--warn">
                <strong>Git не знайдено.</strong> Додайте в <code>.env</code>: <code>GIT_BINARY=/usr/bin/git</code>
            </div>
            <?php else: ?>
            <div class="admin-callout admin-callout--info">
                У кінці <code>git clone</code> обовʼязкова крапка <code>.</code> — інакше зʼявиться підпапка <code>roselira.shop</code>.
            </div>
            <details class="admin-system__fold">
                <summary>Перше встановлення</summary>
                <pre class="admin-code">cd <?= e($projectPath) ?>

git clone <?= e($gitRepoUrl ?? 'https://github.com/iteffa-works/roselira.shop.git') ?> .

cp .env.example .env</pre>
            </details>
            <details class="admin-system__fold">
                <summary>Якщо вже є підпапка roselira.shop</summary>
                <pre class="admin-code">cd <?= e($projectPath) ?>
mv roselira.shop/* .
mv roselira.shop/.git .
rmdir roselira.shop</pre>
            </details>
            <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="admin-card admin-system__panel">
            <div class="admin-system__panel-head">
                <div class="admin-system__panel-icon admin-system__panel-icon--cron" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div class="admin-system__panel-title">
                    <h2>Cron</h2>
                    <p>Щоденно о 04:00 — git pull і перевірки</p>
                </div>
            </div>

            <label class="admin-system__field-label">Crontab (клік — копіювати)</label>
            <pre class="admin-code admin-code--copy admin-system__cron" data-copy="<?= e($cronCommand) ?>"><?= e($cronCommand) ?></pre>

            <?php
            $cronRunLabel = static function (?string $at, ?string $status): string {
                if ($at === null || $at === '') {
                    return 'Ще не запускався';
                }
                $when = format_datetime_or_null($at) ?? $at;
                $result = $status ?? '—';
                if (preg_match('/^(ok|error|skipped):\s*(.+)$/i', $result, $m)) {
                    $result = strtoupper($m[1]) . ' — ' . $m[2];
                }

                return $when . ' · ' . $result;
            };
            $hostingViaLabels = [
                'cli' => 'CLI (crontab)',
                'http' => 'HTTP (?token=)',
            ];
            ?>

            <dl class="admin-system__meta admin-system__meta--cron">
                <div><dt>Розклад хостингу</dt><dd><?= e((string) ($cronStatus['schedule_label'] ?? 'Щодня о 04:00')) ?></dd></div>
                <div><dt>Наступний запуск</dt><dd><?= e(format_datetime_or_null((string) ($cronStatus['next_scheduled_at'] ?? '')) ?? '—') ?></dd></div>
                <div><dt>Останній — адмінка</dt><dd><?= e($cronRunLabel($cronStatus['admin']['at'] ?? null, $cronStatus['admin']['status'] ?? null)) ?></dd></div>
                <div><dt>Останній — хостинг</dt><dd>
                    <?php
                    $hosting = $cronStatus['hosting'] ?? [];
                    echo e($cronRunLabel($hosting['at'] ?? null, $hosting['status'] ?? null));
                    if (!empty($hosting['via'])) {
                        echo ' · ' . e($hostingViaLabels[$hosting['via']] ?? $hosting['via']);
                    }
                    ?>
                </dd></div>
                <?php if (!empty($cronStatus['cli']['at']) && !empty($cronStatus['http']['at'])): ?>
                <div><dt>CLI / HTTP окремо</dt><dd><?= e($cronRunLabel($cronStatus['cli']['at'] ?? null, $cronStatus['cli']['status'] ?? null)) ?> · <?= e($cronRunLabel($cronStatus['http']['at'] ?? null, $cronStatus['http']['status'] ?? null)) ?></dd></div>
                <?php endif; ?>
            </dl>

            <?php if (!empty($cronStatus['hosting_stale'])): ?>
            <div class="admin-callout admin-callout--warn admin-system__callout">
                Cron на хостингу давно не запускався (&gt;25 год). Перевірте crontab або запустіть команду вручну.
            </div>
            <?php endif; ?>

            <div class="admin-system__actions admin-system__actions--row">
                <form method="post" action="<?= admin_url('system/cron') ?>">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="admin-btn admin-btn--telegram">Запустити cron</button>
                </form>
                <form method="post" action="<?= admin_url('system/checks') ?>">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="admin-btn admin-btn--outline">Перевірити все</button>
                </form>
            </div>
        </section>
    </div>

    <section class="admin-card admin-system__panel">
        <div class="admin-system__panel-head admin-system__panel-head--block">
            <div class="admin-system__panel-title">
                <h2>Посилання для реклами</h2>
                <p>Feeds, sitemap і robots для Merchant Center та SEO</p>
            </div>
        </div>

        <ul class="admin-system__endpoints">
            <?php foreach ($feedLinks as $link):
                $href = $base !== '' ? $base . $link['path'] : '';
                if ($href !== '' && str_contains($link['path'], 'feeds/') && $feedToken !== '') {
                    $href .= '?token=' . $feedToken;
                }
            ?>
            <li>
                <a
                    href="<?= $href !== '' ? e($href) : '#' ?>"
                    class="admin-system__endpoint<?= $href === '' ? ' is-disabled' : '' ?>"
                    <?= $href !== '' ? 'target="_blank" rel="noopener"' : '' ?>
                >
                    <span class="admin-system__endpoint-icon admin-system__endpoint-icon--<?= e($link['tone']) ?>" aria-hidden="true"></span>
                    <span class="admin-system__endpoint-body">
                        <strong><?= e($link['label']) ?></strong>
                        <span><?= e($link['desc']) ?></span>
                        <code><?= e($href !== '' ? $href : $link['path']) ?></code>
                    </span>
                    <span class="admin-system__endpoint-go" aria-hidden="true">↗</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($feedSecret === '' || $base === ''): ?>
        <div class="admin-callout admin-callout--<?= $feedSecret === '' ? 'warn' : 'info' ?> admin-system__callout">
            <?php if ($feedSecret === ''): ?>
            <strong>FEED_SECRET</strong> не задано — XML feeds доступні без токена.
            <?php endif; ?>
            <?php if ($base === ''): ?>
            <?php if ($feedSecret === ''): ?><br><?php endif; ?>
            Задайте <code>APP_URL</code> у <code>.env</code> для абсолютних посилань.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="admin-card admin-system__panel">
        <div class="admin-system__panel-head admin-system__panel-head--block">
            <div class="admin-system__panel-title">
                <h2>Діагностика</h2>
                <p><?php if ($checkedAt): ?>Перевірено <?= e($checkedAt) ?><?php else: ?>Натисніть «Перевірити все» для оновлення<?php endif; ?></p>
            </div>
        </div>

        <?php foreach ($groupedChecks as $groupTitle => $items): ?>
        <div class="admin-system__diag-group">
            <h3><?= e($groupTitle) ?></h3>
            <ul class="admin-system__diag-list">
                <?php foreach ($items as $item):
                    $st = (string) ($item['status'] ?? 'warn');
                ?>
                <li class="admin-system__diag-item admin-system__diag-item--<?= e($st) ?>">
                    <span class="admin-system__pill admin-system__pill--<?= e($st) ?>"><?= e($statusLabels[$st] ?? $st) ?></span>
                    <div class="admin-system__diag-body">
                        <strong><?= e((string) ($item['label'] ?? '')) ?></strong>
                        <span><?= e((string) ($item['message'] ?? '')) ?></span>
                    </div>
                    <?php if (!empty($item['url'])): ?>
                    <a href="<?= e((string) $item['url']) ?>" class="admin-system__diag-link" target="_blank" rel="noopener" title="Відкрити">↗</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($cronStatus['last_output']) || !empty($gitStatus['last_update_output'])): ?>
    <section class="admin-card admin-system__panel">
        <div class="admin-system__panel-title">
            <h2>Логи</h2>
        </div>
        <?php if (!empty($cronStatus['last_output'])): ?>
        <details class="admin-system__fold" open>
            <summary>Cron</summary>
            <pre class="admin-code admin-code--log"><?= e((string) $cronStatus['last_output']) ?></pre>
        </details>
        <?php endif; ?>
        <?php if (!empty($gitStatus['last_update_output'])): ?>
        <details class="admin-system__fold">
            <summary>Git pull</summary>
            <pre class="admin-code admin-code--log"><?= e((string) $gitStatus['last_update_output']) ?></pre>
        </details>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
