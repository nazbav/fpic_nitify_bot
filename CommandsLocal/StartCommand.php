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
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 *
 * When using deep-linking, the parameter can be accessed by getting the command text.
 *
 * @see https://core.telegram.org/bots#deep-linking
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Команда запуска';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {

        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();

        // If you use deep-linking, get the parameter like this:
        // $deep_linking_parameter = $this->getMessage()->getText(true);
        // Preparing response
        $data = [
            'chat_id' => $chat_id,
            // Remove any keyboard by default
            'reply_markup' => Keyboard::remove(['selective' => true]),
            'parse_mode' => 'markdown',
			'disable_web_page_preview' => 1
        ];

        $data['caption'] = 'Привет студент!' . PHP_EOL .
            'Я чат-бот, который отслеживает расписание занятий и экзаменов студентов ФПИК.' . PHP_EOL . PHP_EOL .
            'Введи /group чтобы подключить уведомления о новых документах для твоей группы!' . PHP_EOL . PHP_EOL .
            'Я не связан напрямую с ФПИК и ВолгГТУ, работаю на общедоступной информации.' . PHP_EOL . PHP_EOL .
            'Техническая поддержка: @nazbav' . PHP_EOL .
            'Нравится проект? [Поддержи любой суммой!](https://www.tinkoff.ru/cf/1MKy3Xce7sH) - деньги пойдут на оплату сервера.' . PHP_EOL;


        $data['photo'] = dirname(__DIR__) . '/Contetnt/c3b970586317ccb30e4d6d8b1dcb3a93.jpg';

        return Request::sendPhoto($data);
    }
}
