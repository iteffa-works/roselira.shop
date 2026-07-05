<div class="container">
    <article class="legal-page">
        <h1 class="legal-page__title"><?= e($pageHeading ?? '') ?></h1>
        <?php foreach ($legalSections ?? [] as $section): ?>
        <section class="legal-page__section">
            <?php if (!empty($section['heading'])): ?>
            <h2><?= e($section['heading']) ?></h2>
            <?php endif; ?>
            <?php foreach ($section['paragraphs'] ?? [] as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
    </article>
</div>
