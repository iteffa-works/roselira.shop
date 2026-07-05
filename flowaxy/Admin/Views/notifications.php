<div class="admin-page-header">
    <div>
        <h1>Сповіщення</h1>
        <p class="admin-muted">Telegram-повідомлення про нові замовлення</p>
    </div>
</div>

<form method="post" class="admin-form admin-form--wide">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <fieldset>
        <legend>Telegram</legend>

        <label class="admin-checkbox">
            <input type="checkbox" name="enabled" value="1" <?= !empty($config['enabled']) ? 'checked' : '' ?>>
            Увімкнено
        </label>

        <label>Bot Token
            <input type="text" name="bot_token" value="<?= e($config['bot_token'] ?? '') ?>" autocomplete="off" placeholder="123456789:ABC...">
        </label>

        <label>Chat ID
            <input type="text" name="chat_id" value="<?= e($config['chat_id'] ?? '') ?>" placeholder="-1001234567890">
        </label>

        <label class="admin-checkbox">
            <input type="checkbox" name="is_forum" value="1" <?= !empty($config['is_forum']) ? 'checked' : '' ?>>
            Група з темами (forum)
        </label>

        <label>ID теми (thread)
            <input type="text" name="thread_id" value="<?= e($config['thread_id'] ?? '') ?>" placeholder="Наприклад: 42">
        </label>
    </fieldset>

    <button type="submit" class="admin-btn">Зберегти</button>
</form>

<form method="post" action="<?= admin_url('notifications/test') ?>" class="admin-actions">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="admin-btn admin-btn--ghost">Надіслати тестове повідомлення</button>
</form>

<p class="admin-muted">Бот має бути доданий у чат/групу. Для forum-груп вкажіть ID теми, куди слати повідомлення.</p>
