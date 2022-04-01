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
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;

class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = '2callbackquery';

    /**
     * @var string
     */
    protected $description = 'Handle the callback query';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws \Exception
     */
    public function execute(): ServerResponse
    {

        $callback_query = $this->getCallbackQuery();

        $message = $callback_query->getMessage();

        var_dump($message->getChat()->getId());

        $user_id = $message->getFrom()->getId();
        $chat_id = $message->getChat()->getId();

        if ($callback_query)
            $callback_data = $callback_query->getData();
        else
            $callback_data = false;


        //var_dump($callback_data);

//        switch ($state) {
//            case 0:
//                if ($callback_data) {
//                    $notes['course'] = $callback_data;
//                }
//                break;
//        }

        $callback_query->answer([
            'text' => 'Вы выбрали: ' . $callback_data,
            'show_alert' => 0, // Randomly show (or not) as an alert.
            'cache_time' => 5,
        ]);

        return $this->getTelegram()->executeCommand('group');

        //global $telegram;


//          return $callback_query->answer([
//               'text' => ': ' . $callback_data,
//            'show_alert' => (bool)random_int(0, 1), // Randomly show (or not) as an alert.
//           'cache_time' => 5,
//           ]);
    }
}
