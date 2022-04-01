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
 * User "/survey" command
 *
 * Example of the Conversation functionality in form of a simple survey.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use bot\DB\NotificationDB;
use bot\Notifications\Notification;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 *
 */
class GroupCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'group';

    /**
     * @var string
     */
    protected $description = 'Установить отслеживание документов группы';

    /**
     * @var string
     */
    protected $usage = '/group';

    /**
     * @var string
     */
    protected $version = '0.3.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * Conversation Object
     *
     * @var Conversation
     */
    protected $conversation;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $callback_query = $this->getCallbackQuery();
        if ($callback_query) {
            $message = $callback_query->getMessage();
        } else {
            $message = $this->getMessage();
        }

        $chat = $message->getChat();
        $user = $message->getFrom();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if ($callback_query) {
            $callback_data = $callback_query->getData();
        } else {
            $callback_data = false;
        }
        // Preparing response
        $data = [
            'chat_id' => $chat_id,
            // Remove any keyboard by default
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        $temp_file = dirname(__DIR__, 2) . '/cache/chats/' . $chat_id;

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            // Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        // Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        // Load any existing notes from this conversation
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        // Load the current state of the conversation
        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();

        // State machine
        // Every time a step is achieved the state is updated


        switch ($state) {
            case 0:
                $data['text'] = 'Выбери номер своего курса';

                $data['reply_markup'] = new InlineKeyboard([
                    ['text' => '1 Курс', 'callback_data' => '1'],
                    ['text' => '2 Курс', 'callback_data' => '2'],
                ], [
                    ['text' => '3 Курс', 'callback_data' => '3'],
                    ['text' => '4 Курс', 'callback_data' => '4'],
                ], [
                    ['text' => '5 Курс', 'callback_data' => '5']
                ]);


                if ($callback_data) {
                    if (((int)$callback_data) < 1 || ((int)$callback_data) > 5) {
                        $result = Request::sendMessage($data);
                        if ($result->isOk()) {
                            file_put_contents($temp_file, $result->getResult()->message_id . PHP_EOL, FILE_APPEND);
                        }
                        break;
                    }
                    $notes['course'] = $callback_data;
                } else {
                    $notes['state'] = 0;

                    $result = Request::sendMessage($data);
                    if ($result->isOk()) {
                        file_put_contents($temp_file, $result->getResult()->message_id . PHP_EOL, FILE_APPEND);
                    }
                    $this->conversation->update();
                    break;
                }


                $callback_data = '';
                unset($data['reply_markup']);

            // No break!
            case 1:
                if ($callback_data) {
                    $group = json_decode($callback_data, true);
                    if (isset($group['group'])) {
                        $notes['group'] = $group['group'];
                    }
                }


                if (!isset($notes['group']) || isset($notes['group']) && !$notes['group']) {
                    $notes['state'] = 1;


                    $data['text'] = 'Выбери группу' . PHP_EOL;
                    $data['text'] .= '(если не нашел свою группу, придется подождать когда опубликуют первое расписание для твоей группы):' . PHP_EOL;

                    $groups = dirname(dirname(__DIR__)) . '/cache/groups.json';

                    if (is_file($groups)) {
                        $groups = json_decode(file_get_contents($groups), true);
                    }
                    if (!is_array($groups)) {
                        $data['text'] = 'Не вижу групп! Напиши мне как на сайте будет опубликован хоть 1 документ для твоей группы';
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $keyboard = [];

                    $button = function ($group) {
                        return ['text' => $group, 'callback_data' => json_encode(['group' => $group], JSON_UNESCAPED_UNICODE)];
                    };


                    $line = 0;

                    array_multisort($groups);


                    foreach ($groups as $group) {
                        if ($group['course'] == $notes['course'] && $group['group']) {
                            if (isset($keyboard[$line]) && sizeof($keyboard[$line]) > 2) {
                                $line++;
                            }
                            $keyboard[$line][] = $button($group['group']);

                        }
                    }


                    if ($keyboard) {
                        $data['reply_markup'] = new InlineKeyboard(...$keyboard);
                    }
                    $result = Request::sendMessage($data);
                    if ($result->isOk()) {
                        file_put_contents($temp_file, $result->getResult()->message_id . PHP_EOL, FILE_APPEND);
                    }
                    $this->conversation->update();
                    break;
                }

                $callback_data = '';

//            case 2:
//                if (!$callback_data) {
//                    $notes['state'] = 2;
//                    $this->conversation->update();
//
//                    $data['text'] = 'Выберите форму обучения:' . PHP_EOL;
//
//                    //Слишком тупой чтобы понять как работают кнопки
//
//
//                    $forms = dirname(dirname(__DIR__)) . '/cache/cache.json';
//
//                    if (is_file($forms)) {
//                        $forms = json_decode(file_get_contents($forms), true);
//                    }
//                    $keyboard = [];
//
//                    $line = 0;
//                    foreach ($forms as $item => $form) {
//                        if (is_array($form))
//                            $keyboard[$line][] = ['text' => $form['header'], 'callback_data' => json_encode(['form' => $item], JSON_UNESCAPED_UNICODE)];
//                        $line++;
//                    }
//
//
//                    if ($keyboard)
//                        $data['reply_markup'] = new InlineKeyboard(...$keyboard);
//
//                    $result = Request::sendMessage($data);
//                    break;
//                }
//
//
//                if ($callback_data) {
//                    $form = json_decode($callback_data, true);
//                    if (isset($form['form']))
//                        $notes['form'] = $form['form'];
//                }
//
//                $callback_data = '';
            //       break;
            // No break!
            case 2:
                $notes['state'] = 0;
                $this->conversation->update();

                Notification::setGroup($chat_id, $notes['group']);

                $out_text = '*Ве настроено!* Как будут опубликованы новые документы, пришлю их сюда!' . PHP_EOL;
                unset($notes['state']);
                unset($notes['message_ids']);
                unset($notes['course']);

                $notes_label = [
                    'course' => 'Курс',
                    'group' => 'Группа',
                ];


                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($notes_label[$k]) . ': ' . $v;
                }

                $out_text .= PHP_EOL;

                $docs = dirname(__DIR__, 2) . '/cache/cache.json';

                if (is_file($docs)) {
                    $docs = json_decode(file_get_contents($docs), true);
                }

                $keyboard = [];

                $button = function ($group) {
                    return ['text' => $group['name'], 'url' => $group['link']];
                };


                $line = 0;
                $return_docs = '';
                foreach ($docs as $block) {
                    if (is_array($block)) {
                        foreach ($block['items'] as $doc) {
                            if (in_array($notes['group'], $doc['groups'])) {
                                $return_docs = 1;
                                $keyboard[$line][] = $button($doc);
                                $line++;
                            }
                        }
                    }
                }


                if ($return_docs) {
                    $find = '';
                    if (isset($docs['cache_datetime']))
                        $find = ' (обновлено: ' . date('d.m.y H:i:s', $docs['cache_datetime']) . ')';

                    $out_text .= 'Пока нашел вот это' . $find . ': ' . PHP_EOL;

                } else {
                    $out_text .= '* Ничего не нашел *';
                }


                $out_text .= PHP_EOL . 'Нравится проект? [Поддержи любой суммой!](https://www.tinkoff.ru/cf/1MKy3Xce7sH)' . PHP_EOL;

                if ($keyboard) {
                    $data['reply_markup'] = new InlineKeyboard(...$keyboard);

                }
                $data['parse_mode'] = 'markdown';
				$data['disable_web_page_preview'] = 1;
                $data['text'] = $out_text;
                $result = Request::sendMessage($data);

                $this->deleteOldMessages($temp_file, $chat_id);

                if (is_file($temp_file)) {
                    unlink($temp_file);
                }

                $this->conversation->stop();
                //  $result = Request::sendPhoto($data);
                break;
        }

        return $result;
    }

    /**
     * @param string $temp_file
     * @param int $chat_id
     */
    public function deleteOldMessages(string $temp_file, int $chat_id): void
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
    }
}
