<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/helpers.php');


function generateRss(array $channel): string
{
    logMessage(
        'INFO',
        'Generating channel: ' . $channel['title']
    );

    $items = [];

    foreach ($channel['sources'] as $source) {

        if (!$source['enabled']) {
            continue;
        }

        $items = array_merge(
            $items,
            fetchSourceItems(
                $source,
                $channel
            )
        );
    }

    $items = deduplicateItems($items);

    usort(
        $items,
        fn($a, $b) =>
            $b['timestamp'] <=> $a['timestamp']
    );

    if (!empty($channel['max_items'])) {

        $items = array_slice(
            $items,
            0,
            $channel['max_items']
        );
    }

    logMessage(
        'INFO',
        'Generated ' . count($items) . ' items'
    );

    return buildXml(
        $channel,
        $items
    );
}


function fetchSourceItems(
    array $source,
    array $channel
): array {

    logMessage(
        'INFO',
        'Fetching: ' . $source['url']
    );

    $feed = new SimplePie();

    $feed->set_feed_url(
        $source['url']
    );

    $feed->set_cache_location( __DIR__ . '/cache/rss' );

    $feed->set_cache_duration(
        ($channel['cache_minutes'] ?? 60) * 60
    );

    $feed->set_timeout(20);

    $success = $feed->init();

    if (!$success) {

        logMessage(
            'WARNING',
            'Failed: ' . $source['url']
        );

        return [];
    }

    $result = [];

    $maxItems = $source['max_items'] ?? 50;

    foreach ($feed->get_items() as $item) {

        if (count($result) >= $maxItems) {
            break;
        }

        $normalized = normalizeItem(
            $item,
            $source,
            $channel
        );

        if (!$normalized) {
            continue;
        }

      $reason = '';

        if (!verifyItem($normalized, $reason)) {
        
            logMessage(
                'WARNING',
                sprintf(
                    '[%s] skipped: "%s" (%s)',
                    $source['name'],
                    $normalized['title'] ?? '',
                    $reason
                )
            );
        
            continue;
        }

        $result[] = $normalized;
    }

    return $result;
}


function normalizeItem(
    $item,
    array $source,
    array $channel
): ?array {

    $dateInfo = normalizeDate($item);

    if (!$dateInfo) {
        return null;
    }

    $replaceRules =
        $channel['replace_rules']
        ?? [];

    $allowedTags =
        $channel['allowed_tags']
        ?? '';

    $title = cleanText(
        $item->get_title(),
        $replaceRules
    );

    $title = truncateText(
        $title,
        100
    );

    $description = cleanText(
        $item->get_description(),
        $replaceRules,
        $allowedTags
    );

    $content = cleanText(
        $item->get_content(),
        $replaceRules,
        $allowedTags
    );

    if (isset($source['display'])) {
    
        if (empty($source['display']['title'])) {
            $title = '';
        }
    
        if (empty($source['display']['description'])) {
            $description = '';
        }
    
        if (empty($source['display']['content'])) {
            $content = '';
        }
    }

    return [

        'title' => $title,

        'link' => $item->get_permalink(),

        'guid' => $item->get_permalink(),

        'pubDate' => $dateInfo['pubDate'],

        'timestamp' => $dateInfo['timestamp'],

        'creator' => safeGetAuthor($item),

        'description' => $description,

        'content' => $content,

        'image' => safeGetMedia($item),

        'source' =>
            $source['name']
            ?? parse_url(
                $source['url'],
                PHP_URL_HOST
            )
    ];
}


function deduplicateItems(
    array $items
): array {

    $seen = [];

    $result = [];

    foreach ($items as $item) {

        $key = sha1(
            strtolower(
                trim(
                    $item['title']
                )
            )
        );

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;

        $result[] = $item;
    }

    return $result;
}


function buildXml(array $channel, array $items): string {

    global $runtime;
    $feedLink = $channel['feed_link'];
    if (!empty($runtime)) {
        $feedLink = rtrim($runtime, '/') . '/data/' . $channel['output_file'];
    }
    
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->setIndentString('    ');

    $xml->startDocument('1.0', 'UTF-8');

    $xml->startElement('rss');
    $xml->writeAttribute('version','2.0');
    $xml->writeAttribute('xmlns:atom','http://www.w3.org/2005/Atom');
    $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
    $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
    $xml->writeAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');


    $xml->startElement('channel');
    $xml->writeElement('title', $channel['title']);
    $xml->writeElement('description', $channel['description']);
    $xml->writeElement('link', $feedLink);
    $xml->writeElement('language', $channel['language'] ?? 'id-ID');
    $xml->writeElement('lastBuildDate', date(DATE_RSS)); 
    
    $xml->startElement('atom:link');
    $xml->writeAttribute('href', $feedLink);
    $xml->writeAttribute('rel','self');
    $xml->writeAttribute('type', 'application/rss+xml');
    $xml->endElement();


    foreach ($items as $item) {

        $xml->startElement('item');
        $xml->writeElement('title', $item['title']);
        $xml->writeElement('link', $item['link']);
        $xml->writeElement('guid', $item['guid']);
        $xml->writeElement('pubDate', $item['pubDate']);

        if (!empty($item['creator'])) {

            $xml->writeElement(
                'dc:creator',
                $item['creator']
            );
        }

        $xml->writeElement(
            'category',
            $item['source']
        );

        $xml->writeElement(
            'description',
            $item['description']
        );

        $xml->startElement(
            'content:encoded'
        );

        $xml->writeCData(
            $item['content']
        );

        $xml->endElement();

        if (!empty($item['image'])) {

            $xml->startElement(
                'media:content'
            );

            $xml->writeAttribute(
                'medium',
                'image'
            );

            $xml->writeAttribute(
                'url',
                $item['image']
            );

            $xml->endElement();
        }

        $xml->endElement();
    }

    $xml->endElement();

    $xml->endElement();

    return $xml->outputMemory() . PHP_EOL;
}