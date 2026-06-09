<?php

$debug = true;
$runtime = null;

/**
 * Simple logger for CLI / GitHub Actions
 */
function logMessage(string $level, string $message): void {

    global $debug;

    if (!$debug) {
        return;
    }

    $lineBreak = PHP_SAPI === 'cli' ? PHP_EOL : '<br>' . PHP_EOL;

    $time = date('Y-m-d H:i:s');

    echo "[{$time}] [{$level}] {$message}{$lineBreak}";
}


/**
 * Safely get author name from RSS item
 */
function safeGetAuthor($item): string
{
    try {
        $author = $item->get_author();

        if ($author && method_exists($author, 'get_name')) {
            return trim($author->get_name());
        }
    } catch (Exception $e) {
        // ignore
    }

    return '';
}


/**
 * Safely get media image URL from RSS item
 */
function safeGetMedia($item): string
{
    try {
        $mediaTags = $item->get_item_tags(
            'http://search.yahoo.com/mrss/',
            'content'
        );

        if (
            !empty($mediaTags[0]['attribs']['']['url'])
        ) {
            return htmlspecialchars(
                $mediaTags[0]['attribs']['']['url'],
                ENT_QUOTES,
                'UTF-8'
            );
        }
    } catch (Exception $e) {
        // ignore
    }

    return '';
}


/**
 * Normalize RSS published date
 */
function normalizeDate($item): ?array
{
    $timestamp = $item->get_date('U');

    if (!$timestamp) {
        return null;
    }

    return [
        'timestamp' => (int) $timestamp,
        'pubDate' => date(
            'D, d M Y H:i:s O',
            $timestamp
        )
    ];
}

/**
 * Clean and sanitize text
 */
function cleanText(
    ?string $text,
    array $replaceRules = [],
    string $allowedTags = ''
): string {
    if (!$text) {
        return '';
    }

    foreach ($replaceRules as $search => $replace) {
        $text = str_replace(
            $search,
            $replace,
            $text
        );
    }

    $text = strip_tags(
        $text,
        $allowedTags
    );

    return trim($text);
}


/**
 * Safe UTF-8 text truncate
 */
function truncateText(
    string $text,
    int $limit = 100
): string {
    return mb_substr(
        trim($text),
        0,
        $limit,
        'UTF-8'
    );
}


/**
 * Verify RSS item validity
 */
function verifyItem(
    array $item,
    &$reason = ''
): bool {

    if (empty($item['title'])) {

        $reason = 'empty title';
        return false;
    }

    if (empty($item['link'])) {

        $reason = 'empty link';
        return false;
    }

    if (empty($item['pubDate'])) {

        $reason = 'empty pubDate';
        return false;
    }

    return true;
}


/**
 * Build final RSS feed link
 */
function buildFeedLink(array $channel): string
{
    return rtrim(
        $channel['feed_link'],
        '/'
    );
}