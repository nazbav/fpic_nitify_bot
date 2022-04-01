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


    /**
     * @param string $temp_file
     * @param int $chat_id
     */
    function deleteOldMessages(string $temp_file, int $chat_id): void
    {
        $handle = @fopen($temp_file, "r");
        if ($handle) {
            $messages_ids = [];
            while (($buffer = fgets($handle, 4096)) !== false) {
                $res = Request::deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => trim($buffer)
                ]);
            }
            fclose($handle);
        }
        // if (is_file($temp_file)) {
        //     unlink($temp_file);
        // }
    }


    $results = [];

    $data = [];


    $docs = __DIR__ . '/cache/cache.json';
    $blocked_notify = __DIR__ . '/cache/block.json';

    if (is_file($docs)) {
        $docs = json_decode(file_get_contents($docs), true);
    }


    $button = function ($group) {
        return ['text' => $group['name'], 'url' => $group['link']];
    };

    $out_text = 'Ооо нашел кое-что для твоей группы! Жми на кнопку с названием документа, посмотрим что там 🤔' . PHP_EOL;

    $out_text .= PHP_EOL . 'Нравится проект? [Поддержи любой суммой](https://www.tinkoff.ru/cf/1MKy3Xce7sH) - деньги пойдут на оплату сервера.' . PHP_EOL;

    //проверить

    $blocked_notifications = [];
    $handle = @fopen($blocked_notify, "r");
    if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
            $blocked_notifications[] = trim($buffer);
        }
        fclose($handle);
    }

    foreach ($docs as $block) {
        if (is_array($block)) {
            foreach ($block['items'] as $document) {
                if (in_array($document['hash'], $blocked_notifications)) {
                    continue;
                }
                $status = 0;
                $chats = \bot\Notifications\Notification::getByGroupList($document['groups']);
                if (is_array($chats)) {
                    foreach ($chats as $chat) {
                        $temp_file = __DIR__ . '/cache/chats/del_old' . $chat['chat_id'];

                        $keyboard = [];
                        $line = 0;
                        $return_docs = '';
                        foreach ($docs as $block) {
                            if (is_array($block)) {
                                foreach ($block['items'] as $doc) {
                                    if (in_array($chat['group'], $doc['groups'])) {
                                        $return_docs = 1;
                                        $keyboard[$line][] = $button($doc);
                                        $line++;
                                    }
                                }
                            }
                        }

                        if ($keyboard) {
                            $data['reply_markup'] = new InlineKeyboard(...$keyboard);
                        }
                        $data['text'] = $out_text;
                        $data['parse_mode'] = 'markdown';
                        $data['chat_id'] = $chat['chat_id'];
						$data['disable_web_page_preview'] = 1;

                        deleteOldMessages($temp_file, $chat['chat_id']);
                        $results[] = $result = Request::sendMessage($data);
                        if ($result->isOk()) {
                            file_put_contents($temp_file, $result->getResult()->message_id . PHP_EOL, FILE_APPEND);
                        }

                        if ($result->isOk()) {
                            $status = 1;
                        }
                    }
                }
                if ($status) {
                    file_put_contents($blocked_notify, $document['hash'] . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }


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
