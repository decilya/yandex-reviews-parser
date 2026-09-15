<?php

declare(strict_types=1);

/**
 * Конфигурация парсинга Яндекс.Карт.
 *
 * Все настраиваемые параметры вынесены в этот файл и читаются через env().
 * Это позволяет менять поведение парсера без правки кода.
 */
return [
    /**
     * Таймаут HTTP-запросов к Яндексу (в секундах).
     * Если Яндекс не отвечает за это время — запрос прерывается.
     */
    'http_timeout' => (int) env('PARSING_HTTP_TIMEOUT', 30),

    /**
     * Минимальная пауза между последовательными запросами (в миллисекундах).
     * Используется для троттлинга — защиты от rate-limit.
     */
    'throttle_min_ms' => (int) env('PARSING_THROTTLE_MIN_MS', 500),

    /**
     * Максимальная пауза между последовательными запросами (в миллисекундах).
     * Реальная пауза выбирается случайно между min и max.
     */
    'throttle_max_ms' => (int) env('PARSING_THROTTLE_MAX_MS', 1500),

    /**
     * Максимальное количество попыток выполнения задания парсинга.
     * После исчерпания попыток задание помечается как failed.
     */
    'max_retries' => (int) env('PARSING_MAX_RETRIES', 3),

    /**
     * Массив задержек (в секундах) между повторными попытками.
     * Индекс массива соответствует номеру попытки.
     *
     * @var array<int, int>
     */
    'backoff' => [60, 300, 900],

    /**
     * Максимальное количество сбоев разметки до бросания LayoutChangedException.
     * Если парсер не может найти ожидаемые данные N раз подряд — он сдаётся.
     */
    'max_layout_failures' => (int) env('PARSING_MAX_LAYOUT_FAILURES', 3),

    /**
     * Количество отзывов, запрашиваемых за одну страницу внутреннего API.
     */
    'reviews_per_page' => (int) env('PARSING_REVIEWS_PER_PAGE', 20),

    /**
     * Максимальное количество страниц при итеративной подгрузке отзывов.
     * Ограничивает общее количество запросов к Яндексу.
     */
    'max_pages' => (int) env('PARSING_MAX_PAGES', 30),

    /**
     * Порог пропущенных отзывов (доля от 0 до 1).
     * Если доля некорректных отзывов на странице превышает этот порог,
     * бросается LayoutChangedException — считается, что разметка сломалась.
     */
    'skipped_reviews_threshold' => (float) env('PARSING_SKIPPED_THRESHOLD', 0.5),

    /**
     * Массив User-Agent'ов для ротации.
     * При каждом запросе выбирается случайный UA для имитации разных браузеров.
     *
     * @var array<int, string>
     */
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],
];
