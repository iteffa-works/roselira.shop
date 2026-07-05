<?php $isEnabled = !empty($config['enabled']); ?>

<div class="admin-page-header">
    <div>
        <h1>Сповіщення</h1>
        <p class="admin-muted">Telegram-повідомлення про нові замовлення з сайту</p>
    </div>
    <span class="admin-badge admin-badge--<?= $isEnabled ? 'done' : 'cancelled' ?>"><?= $isEnabled ? 'Активно' : 'Вимкнено' ?></span>
</div>

<div class="admin-grid admin-grid--split admin-notify">
    <section class="admin-card admin-card--telegram">
        <div class="admin-card__head">
            <div>
                <h2 class="admin-card__title">Налаштування Telegram</h2>
                <p class="admin-muted admin-card__desc">Підключіть бота для миттєвих сповіщень про замовлення</p>
            </div>
            <span class="admin-notify__icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.19 1.3l-2.57 12.08c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
            </span>
        </div>

        <form method="post" class="admin-form admin-form--notify">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

            <div class="admin-toggle">
                <label class="admin-toggle__control">
                    <input type="checkbox" name="enabled" value="1" <?= $isEnabled ? 'checked' : '' ?>>
                    <span class="admin-toggle__track" aria-hidden="true"></span>
                </label>
                <div class="admin-toggle__text">
                    <strong>Увімкнено</strong>
                    <span>Надсилати сповіщення при новому замовленні</span>
                </div>
            </div>

            <div class="admin-form__group">
                <label class="admin-field">
                    <span class="admin-field__label">Bot Token</span>
                    <input type="text" class="admin-field__input admin-field__input--mono" name="bot_token" value="<?= e($config['bot_token'] ?? '') ?>" autocomplete="off" placeholder="123456789:ABC-DEF..." spellcheck="false">
                    <span class="admin-field__hint">Отримайте у <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a> → /newbot</span>
                </label>

                <label class="admin-field">
                    <span class="admin-field__label">Chat ID</span>
                    <input type="text" class="admin-field__input admin-field__input--mono" name="chat_id" value="<?= e($config['chat_id'] ?? '') ?>" placeholder="-1001234567890" spellcheck="false">
                    <span class="admin-field__hint">ID чату або групи (зазвичай <code>-100…</code>)</span>
                </label>
            </div>

            <div class="admin-panel">
                <label class="admin-checkbox admin-checkbox--panel">
                    <input type="checkbox" name="is_forum" value="1" data-forum-toggle <?= !empty($config['is_forum']) ? 'checked' : '' ?>>
                    <span>Група з темами (forum)</span>
                </label>

                <label class="admin-field admin-field--thread" data-thread-field <?= empty($config['is_forum']) ? 'hidden' : '' ?>>
                    <span class="admin-field__label">ID теми (thread)</span>
                    <input type="text" class="admin-field__input admin-field__input--mono" name="thread_id" value="<?= e($config['thread_id'] ?? '') ?>" placeholder="42">
                    <span class="admin-field__hint">Потрібно для supergroup з увімкненими Topics</span>
                </label>
            </div>

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--telegram">Зберегти</button>
                <button type="submit" formaction="<?= admin_url('notifications/test') ?>" class="admin-btn admin-btn--outline">Тестове повідомлення</button>
            </div>
        </form>
    </section>

    <section class="admin-card admin-guide">
        <h2 class="admin-card__title">Інструкція з налаштування</h2>

        <ol class="admin-guide__steps">
            <li>
                <span class="admin-guide__num">1</span>
                <div>
                    <strong>Створіть бота</strong>
                    <p>Відкрийте <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a> → <code>/newbot</code> → скопіюйте Bot Token.</p>
                </div>
            </li>
            <li>
                <span class="admin-guide__num">2</span>
                <div>
                    <strong>Додайте бота в чат</strong>
                    <p>Додайте бота в групу і зробіть <strong>адміністратором</strong> з правом надсилати повідомлення.</p>
                </div>
            </li>
            <li>
                <span class="admin-guide__num">3</span>
                <div>
                    <strong>Дізнайтесь Chat ID</strong>
                    <p>Напишіть у групу → відкрийте:</p>
                    <p><code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code></p>
                    <p>Або скористайтесь <a href="https://t.me/getidsbot" target="_blank" rel="noopener">@getidsbot</a>.</p>
                </div>
            </li>
            <li>
                <span class="admin-guide__num">4</span>
                <div>
                    <strong>Topics (опційно)</strong>
                    <p>Для груп з темами — увімкніть forum і вкажіть <code>message_thread_id</code>.</p>
                </div>
            </li>
            <li>
                <span class="admin-guide__num">5</span>
                <div>
                    <strong>Перевірте</strong>
                    <p>Збережіть → «Тестове повідомлення». Якщо помилка — перевірте token, chat ID і права бота.</p>
                </div>
            </li>
        </ol>

        <div class="admin-guide__note">
            <strong>Коли надсилається сповіщення?</strong>
            <p>Лише при новому замовленні з форми на сайту. Зміна статусу в адмінці — ні.</p>
        </div>
    </section>
</div>
