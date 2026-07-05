<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class LocaleDefaults
{
    public const STRINGS_VERSION = 2;

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return [
            'en' => self::en(),
            'uk' => self::uk(),
            'ru' => self::ru(),
        ];
    }

    /** @return list<string> */
    public static function patchKeys(): array
    {
        return [
            'home_title',
            'home_subtitle',
            'footer',
            'meta_home_title',
            'meta_home_desc',
        ];
    }

    /** @return array<string, string> */
    public static function en(): array
    {
        return [
            'home_title' => 'Roselira Product Catalog',
            'home_subtitle' => 'Original KIKO Milano cosmetics and accessories — browse and order online.',
            'reviews' => 'reviews',
            'rating_of' => ':rating of :max',
            'footer' => '© Roselira — roselira.com',
            'not_found_text' => 'The requested product was not found.',
            'not_found_back' => 'Back to catalog',
            'theme_toggle' => 'Toggle theme',
            'meta_home_title' => 'Roselira Product Catalog',
            'meta_home_desc' => 'KIKO Milano cosmetics and accessories. Browse the catalog and place orders online.',
            'meta_not_found_title' => 'Product not found — Roselira',
            'meta_not_found_desc' => 'The requested product is not in the catalog.',
            'variant_label' => 'Shade',
            'variant_all' => 'All shades (:count)',
            'variant_collapse' => 'Collapse',
            'description_title' => 'Description',
            'section_description' => 'Description',
            'section_results' => 'Results',
            'section_tips' => 'Tips',
            'section_pack' => 'Package',
            'section_ingredients' => 'Ingredients',
            'section_disposal' => 'Disposal',
            'group_kiko' => 'KIKO Milano',
            'group_bags' => 'Bags',
            'group_other' => 'Other',
            'order_title' => 'Place an order',
            'order_name' => 'Your name',
            'order_name_placeholder' => 'Anna',
            'order_phone' => 'Phone',
            'order_phone_placeholder' => '+380 XX XXX XX XX',
            'order_comment' => 'Comment',
            'order_comment_placeholder' => 'Delivery or shade preference',
            'order_submit' => 'Submit order',
            'order_note' => 'We will contact you to confirm.',
            'order_success' => 'Thank you! Your order has been received.',
            'order_error_product' => 'Product not found.',
            'order_error_variant' => 'Please select a shade.',
            'order_error_name' => 'Enter your name (at least 2 characters).',
            'order_error_phone' => 'Enter a valid phone number.',
            'order_error_server' => 'Something went wrong. Please try again.',
            'order_error_rate_limit' => 'Too many orders. Please try again later.',
        ];
    }

    /** @return array<string, string> */
    public static function uk(): array
    {
        return [
            'home_title' => 'Каталог товарів Roselira',
            'home_subtitle' => 'Оригінальна косметика KIKO Milano та аксесуари — перегляд і швидке замовлення онлайн.',
            'reviews' => 'відгуків',
            'rating_of' => ':rating з :max',
            'footer' => '© Roselira — roselira.com',
            'not_found_text' => 'Запрошений товар не знайдено.',
            'not_found_back' => 'До каталогу',
            'theme_toggle' => 'Перемкнути тему',
            'meta_home_title' => 'Каталог товарів Roselira',
            'meta_home_desc' => 'Косметика KIKO Milano та аксесуари. Перегляд асортименту та оформлення замовлення онлайн.',
            'meta_not_found_title' => 'Товар не знайдено — Roselira',
            'meta_not_found_desc' => 'Запрошений товар відсутній у каталозі.',
            'variant_label' => 'Відтінок',
            'variant_all' => 'Усі відтінки (:count)',
            'variant_collapse' => 'Згорнути',
            'description_title' => 'Опис',
            'section_description' => 'Опис',
            'section_results' => 'Результат',
            'section_tips' => 'Поради',
            'section_pack' => 'Упаковка',
            'section_ingredients' => 'Склад',
            'section_disposal' => 'Утилізація',
            'group_kiko' => 'KIKO Milano',
            'group_bags' => 'Сумки',
            'group_other' => 'Інше',
            'order_title' => 'Оформити замовлення',
            'order_name' => 'Ваше ім\'я',
            'order_name_placeholder' => 'Анна',
            'order_phone' => 'Телефон',
            'order_phone_placeholder' => '+380 XX XXX XX XX',
            'order_comment' => 'Коментар',
            'order_comment_placeholder' => 'Побажання щодо доставки або відтінку',
            'order_submit' => 'Надіслати замовлення',
            'order_note' => 'Ми зв\'яжемося з вами для підтвердження.',
            'order_success' => 'Дякуємо! Замовлення отримано.',
            'order_error_product' => 'Товар не знайдено.',
            'order_error_variant' => 'Оберіть відтінок.',
            'order_error_name' => 'Вкажіть ім\'я (мінімум 2 символи).',
            'order_error_phone' => 'Вкажіть коректний номер телефону.',
            'order_error_server' => 'Помилка. Спробуйте ще раз.',
            'order_error_rate_limit' => 'Забагато спроб. Спробуйте пізніше.',
        ];
    }

    /** @return array<string, string> */
    public static function ru(): array
    {
        return [
            'home_title' => 'Каталог товаров Roselira',
            'home_subtitle' => 'Оригинальная косметика KIKO Milano и аксессуары — просмотр и быстрый заказ онлайн.',
            'reviews' => 'отзывов',
            'rating_of' => ':rating из :max',
            'footer' => '© Roselira — roselira.com',
            'not_found_text' => 'Запрошенный товар не найден.',
            'not_found_back' => 'В каталог',
            'theme_toggle' => 'Переключить тему',
            'meta_home_title' => 'Каталог товаров Roselira',
            'meta_home_desc' => 'Косметика KIKO Milano и аксессуары. Просмотр ассортимента и оформление заказа онлайн.',
            'meta_not_found_title' => 'Товар не найден — Roselira',
            'meta_not_found_desc' => 'Запрошенный товар отсутствует в каталоге.',
            'variant_label' => 'Оттенок',
            'variant_all' => 'Все оттенки (:count)',
            'variant_collapse' => 'Свернуть',
            'description_title' => 'Описание',
            'section_description' => 'Описание',
            'section_results' => 'Результат',
            'section_tips' => 'Советы',
            'section_pack' => 'Упаковка',
            'section_ingredients' => 'Состав',
            'section_disposal' => 'Утилизация',
            'group_kiko' => 'KIKO Milano',
            'group_bags' => 'Сумки',
            'group_other' => 'Другое',
            'order_title' => 'Оформить заказ',
            'order_name' => 'Ваше имя',
            'order_name_placeholder' => 'Анна',
            'order_phone' => 'Телефон',
            'order_phone_placeholder' => '+380 XX XXX XX XX',
            'order_comment' => 'Комментарий',
            'order_comment_placeholder' => 'Пожелания по доставке или оттенку',
            'order_submit' => 'Отправить заказ',
            'order_note' => 'Мы свяжемся с вами для подтверждения.',
            'order_success' => 'Спасибо! Заказ получен.',
            'order_error_product' => 'Товар не найден.',
            'order_error_variant' => 'Выберите оттенок.',
            'order_error_name' => 'Укажите имя (минимум 2 символа).',
            'order_error_phone' => 'Укажите корректный номер телефона.',
            'order_error_server' => 'Ошибка. Попробуйте ещё раз.',
            'order_error_rate_limit' => 'Слишком много попыток. Попробуйте позже.',
        ];
    }
}
