<div class="admin-page-header">
    <div>
        <h1>Сторінки</h1>
        <p class="admin-muted">Конфіденційність, умови та доставка · uk / ru</p>
    </div>
    <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--outline">Переглянути на сайті ↗</a>
</div>

<div class="admin-filters">
    <?php foreach ($pages as $slug => $label): ?>
    <a href="<?= admin_url('pages', ['page' => $slug, 'locale' => $activeLocale]) ?>" class="<?= $activePage === $slug ? 'is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-filters admin-filters--sub">
    <?php foreach ($locales as $loc): ?>
    <a href="<?= admin_url('pages', ['page' => $activePage, 'locale' => $loc]) ?>" class="<?= $activeLocale === $loc ? 'is-active' : '' ?>"><?= e(strtoupper($loc)) ?></a>
    <?php endforeach; ?>
</div>

<form method="post" action="<?= admin_url('pages') ?>" class="admin-form admin-form--stacked admin-pages">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="page" value="<?= e($activePage) ?>">
    <input type="hidden" name="locale" value="<?= e($activeLocale) ?>">
    <input type="hidden" name="action" value="save">

    <div class="admin-pages__sections" data-legal-sections>
        <?php foreach ($sections as $index => $section): ?>
        <fieldset class="admin-card admin-pages__section">
            <legend class="admin-pages__section-num">Секція <?= (int) $index + 1 ?></legend>
            <label class="admin-field">
                <span class="admin-field__label">Заголовок</span>
                <input type="text" name="sections[<?= (int) $index ?>][heading]" value="<?= e((string) ($section['heading'] ?? '')) ?>" placeholder="Необов'язково">
            </label>
            <label class="admin-field">
                <span class="admin-field__label">Абзаци (кожен з нового рядка)</span>
                <textarea name="sections[<?= (int) $index ?>][paragraphs]" rows="4" class="admin-textarea"><?= e(implode("\n", $section['paragraphs'] ?? [])) ?></textarea>
            </label>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-pages__remove" data-remove-section hidden>Видалити секцію</button>
        </fieldset>
        <?php endforeach; ?>
    </div>

    <template id="admin-legal-section-template">
        <fieldset class="admin-card admin-pages__section">
            <legend class="admin-pages__section-num">Секція</legend>
            <label class="admin-field">
                <span class="admin-field__label">Заголовок</span>
                <input type="text" name="sections[__INDEX__][heading]" placeholder="Необов'язково">
            </label>
            <label class="admin-field">
                <span class="admin-field__label">Абзаци (кожен з нового рядка)</span>
                <textarea name="sections[__INDEX__][paragraphs]" rows="4" class="admin-textarea"></textarea>
            </label>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-pages__remove" data-remove-section>Видалити секцію</button>
        </fieldset>
    </template>

    <div class="admin-form__actions">
        <button type="button" class="admin-btn admin-btn--outline" data-add-section>+ Додати секцію</button>
        <button type="submit" class="admin-btn">Зберегти</button>
    </div>
</form>

<form method="post" action="<?= admin_url('pages') ?>" class="admin-pages__reset" onsubmit="return confirm('Скинути текст цієї сторінки до стандартного?')">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="page" value="<?= e($activePage) ?>">
    <input type="hidden" name="locale" value="<?= e($activeLocale) ?>">
    <input type="hidden" name="action" value="reset">
    <button type="submit" class="admin-btn admin-btn--ghost">Скинути до стандартного тексту</button>
</form>

<script>
(function () {
    var container = document.querySelector('[data-legal-sections]');
    var template = document.getElementById('admin-legal-section-template');
    var addButton = document.querySelector('[data-add-section]');
    if (!container || !template || !addButton) {
        return;
    }

    function renumberSections() {
        var sections = container.querySelectorAll('.admin-pages__section');
        sections.forEach(function (section, index) {
            var legend = section.querySelector('.admin-pages__section-num');
            if (legend) {
                legend.textContent = 'Секція ' + (index + 1);
            }
            section.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/sections\[\d+]/, 'sections[' + index + ']');
            });
            var removeBtn = section.querySelector('[data-remove-section]');
            if (removeBtn) {
                removeBtn.hidden = sections.length <= 1;
            }
        });
    }

    addButton.addEventListener('click', function () {
        var index = container.querySelectorAll('.admin-pages__section').length;
        var html = template.innerHTML.replace(/__INDEX__/g, String(index));
        container.insertAdjacentHTML('beforeend', html);
        renumberSections();
    });

    container.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-section]');
        if (!button) {
            return;
        }
        var section = button.closest('.admin-pages__section');
        if (!section || container.querySelectorAll('.admin-pages__section').length <= 1) {
            return;
        }
        section.remove();
        renumberSections();
    });

    renumberSections();
})();
</script>
