<?php

date_default_timezone_set('Asia/Jakarta');

require_once(__DIR__ . '/rss.php');


$config = json_decode(
    file_get_contents(__DIR__ . '/config.json'), true
);

if ($config === null) {

    throw new Exception(
        'Failed to load config.json'
    );
}


if (!isset($config['channels'])) {

    throw new Exception(
        'channels not found'
    );
}


/*
|--------------------------------------------------------------------------
| Determine selected channel
|--------------------------------------------------------------------------
|
| CLI:
| php generate.php
| php generate.php islam
|
| Browser:
| generate.php
| generate.php?channel=islam
|
*/

$selectedChannel = PHP_SAPI === 'cli'
    ? ($argv[1] ?? null)
    : ($_GET['channel'] ?? null);

$mode = PHP_SAPI === 'cli'
    ? ($argv[2] ?? 'generate')
    : ($_GET['mode'] ?? 'generate');

global $debug;
$debug = ($mode !== 'preview');

global $runtime; 
$runtime = null;

if (PHP_SAPI !== 'cli') {
    $protocol = ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ) ? 'https://' : 'http://'; 
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $runtime = rtrim($protocol . $_SERVER['HTTP_HOST'] . $basePath, '/');
}

/*
|--------------------------------------------------------------------------
| Ensure data directory exists
|--------------------------------------------------------------------------
*/

if (!is_dir('data')) {

    mkdir(
        'data',
        0755,
        true
    );
}


/*
|--------------------------------------------------------------------------
| Generate RSS
|--------------------------------------------------------------------------
*/

foreach (
    $config['channels']
    as $channelName => $channel
) {

    // Skip disabled channel
    if (empty($channel['enabled'])) {

        logMessage(
            'INFO',
            "Skipping disabled channel: {$channelName}"
        );

        continue;
    }


    // Generate selected channel only
    if (
        $selectedChannel !== null
        &&
        $channelName !== $selectedChannel
    ) {
        continue;
    }


    logMessage(
        'INFO',
        "Processing channel: {$channelName}"
    );


    try {

        $rss = generateRss($channel);

        if ($mode === 'preview') {
            header('Content-Type: application/xml');
            echo $rss;
            exit;
        }

        $outputFile =  __DIR__ . 'data/' . $channel['output_file'];


        file_put_contents($outputFile, $rss);


        logMessage('INFO',"Saved: {$outputFile}");

    }
    catch (Throwable $e) {

        logMessage(
            'ERROR',
            "{$channelName}: " .
            $e->getMessage()
        );

    }

}


logMessage(
    'INFO',
    'Done'
);
