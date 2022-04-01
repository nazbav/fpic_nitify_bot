<?php

namespace bot\Notifications;

use bot\DB\NotificationDB;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * Class Conversation
 *
 * Only one conversation can be active at any one time.
 * A conversation is directly linked to a user, chat and the command that is managing the conversation.
 */
class Notification
{
    /**
     * All information fetched from the database
     *
     * @var array|null
     */
    protected $data;

    /**
     * Update the status of the current conversation
     *
     * @param string $status
     *
     * @return bool
     * @throws TelegramException
     */
    public static function getGroup(string $chat_id)
    {
        $data = NotificationDB::selectGroup($chat_id, 1);
        if (isset($data[0])) {
            return $data;
        }

        return false;
    }

    public static function getByGroupList(array $group_list)
    {
        $data = NotificationDB::selectChatsByGroupsList($group_list);
        if (isset($data[0])) {
            return $data;
        }

        return false;
    }

    public static function deleteAll()
    {
        $data = NotificationDB::deleteAll();
        if (isset($data[0])) {
            return $data;
        }

        return false;
    }


    /**
     * Update the status of the current conversation
     *
     * @param string $status
     *
     * @return bool
     * @throws TelegramException
     */
    public static function setGroup(string $chat_id, $group): bool
    {
        $data = self::getGroup($chat_id);
        if ($data) {
            $fields = [
                'group' => $group
            ];
            $where = [
                'status' => 'active',
                'chat_id' => $chat_id,
            ];
            if (NotificationDB::updateGroup($fields, $where)) {
                return true;
            }
        } else {
            if (NotificationDB::insertGroup($group, $chat_id)) {
                return true;
            }
        }

        return false;
    }
}
