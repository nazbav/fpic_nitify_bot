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
 * This file is used to run a list of commands with crontab.
 */

// Your command(s) to run, pass them just like in a message (arguments supported)
use bot\DB\NotificationDB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

// Load composer
require_once __DIR__ . '/vendor/autoload.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';


Request::setClient(new \GuzzleHttp\Client([
    'base_uri' => 'https://api.telegram.org',
    'verify' => false,
]));


try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);


    $telegram->enableMySql($config['mysql']);

    NotificationDB::initializeNotification();

    /**
     * Check `hook.php` for configuration code to be added here.
     */


    $text = 'Ну, учебный год подошел к концу, увидимся в следующем! Если конечно не забудете включить меня снова...' . PHP_EOL;
    $text .= '* Уведомления выключены *' . PHP_EOL;

    /** @var ServerResponse[] $results */
    $results = Request::sendToActiveChats(
        'sendMessage',     //callback function to execute (see Request.php methods)
        ['text' => $text], //Param to evaluate the request
        [
            'groups' => true,
            'supergroups' => true,
            'channels' => false,
            'users' => true,
        ]
    );


    $total = 0;
    $failed = 0;

    $text = 'Message sent to:' . PHP_EOL;

    foreach ($results as $result) {
        $name = '';
        $type = '';
        if ($result->isOk()) {
            $status = '✔️';

            /** @var Message $message */
            $message = $result->getResult();
            $chat = $message->getChat();
            if ($chat->isPrivateChat()) {
                $name = $chat->getFirstName();
                $type = 'user';
            } else {
                $name = $chat->getTitle();
                $type = 'chat';
            }
        } else {
            $status = '✖️';
            ++$failed;
        }
        ++$total;

        $text .= $total . ') ' . $status . ' ' . $type . ' ' . $name . PHP_EOL;
    }
    echo $text .= 'Delivered: ' . ($total - $failed) . '/' . $total . PHP_EOL;


    NotificationDB::deleteAll();

    $docs = __DIR__ . '/cache/cache.json';
    if (is_file($docs)) {
        unlink($docs);
    }
    $docs = __DIR__ . '/cache/groups.json';
    if (is_file($docs)) {
        unlink($docs);
    }
    $docs = __DIR__ . '/cache/block.json';
    if (is_file($docs)) {
        unlink($docs);
    }

    array_map('unlink', glob(__DIR__ . '/cache/chats/*'));

    // return $this->replyToChat($text);


} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);

    // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
    echo $e;
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
    echo $e;
}
