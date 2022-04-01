#!/usr/bin/env php
<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file is used to run the bot with the getUpdates method.
 */

// Load composer
use bot\DB\NotificationDB;
use Longman\TelegramBot\Request;

require_once __DIR__ . '/vendor/autoload.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';

Request::setClient(new \GuzzleHttp\Client([
    'base_uri' => 'https://api.telegram.org',
    'verify' => false,
]));


// Create Telegram API object
$telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

$telegram->enableMySql($config['mysql']);
NotificationDB::initializeNotification();

$telegram->enableAdmins($config['admins']);

// Add commands paths containing your custom commands
$telegram->addCommandsPaths($config['commands']['paths']);


$telegram->enableLimiter($config['limiter']);

/**
 * Check `hook.php` for configuration code to be added here.
 */
while (true) {
    if ((time() % 60) == 0) {
        $telegram->enableMySql($config['mysql']);
    }
    try {
        // Handle telegram getUpdates request


        $server_response = $telegram->handleGetUpdates(null, 30);


        if ($server_response->isOk()) {
            $update_count = count($server_response->getResult());
            echo date('Y-m-d H:i:s') . ' - Processed ' . $update_count . ' updates' . PHP_EOL;

        } else {
            echo date('Y-m-d H:i:s') . ' - Failed to fetch updates' . PHP_EOL;
            echo $server_response->printError();
        }

    } catch (Longman\TelegramBot\Exception\TelegramException $e) {
        // Log telegram errors
        Longman\TelegramBot\TelegramLog::error($e);

        // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
        echo $e;
    } catch (\Throwable $e) {
        // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
        echo $e;
    }

}
