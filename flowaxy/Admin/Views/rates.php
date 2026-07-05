<h1>Курси валют</h1>
<p>Джерело: <?= e($rates['source']) ?> · Дата: <?= e($rates['date'] ?: '—') ?></p>

<table class="admin-table">
    <thead><tr><th>Валюта</th><th>Курс до UAH</th></tr></thead>
    <tbody>
        <tr><td>EUR</td><td><?= e(number_format($rates['EUR'], 4)) ?></td></tr>
        <tr><td>USD</td><td><?= e(number_format($rates['USD'], 4)) ?></td></tr>
    </tbody>
</table>

<form method="post">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="admin-btn">Оновити з НБУ та перерахувати ціни</button>
</form>

<p class="admin-muted">Приклад: Lip Volume 8,99 EUR → <?= e((string) ($exampleUah ?? '')) ?> ₴</p>
