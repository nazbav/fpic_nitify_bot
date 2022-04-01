<?php

namespace bot\DB;

use Exception;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Exception\TelegramException;
use PDO;

class NotificationDB extends DB
{
    /**
     * Initialize conversation table
     */
    public static function initializeNotification(): void
    {
        if (!defined('TB_NOTIFICATION')) {
            define('TB_NOTIFICATION', self::$table_prefix . 'notification');
        }
    }

    /**
     * Select a conversation from the DB
     *
     * @param int $user_id
     * @param int $chat_id
     * @param int $limit
     *
     * @return array|bool
     * @throws TelegramException
     */
    public static function selectGroup(int $chat_id, $limit = 0)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql = '
              SELECT *
              FROM `' . TB_NOTIFICATION . '`
               WHERE `status` = :status
                AND `chat_id` = :chat_id
            ';

            if ($limit > 0) {
                $sql .= ' LIMIT :limit';
            }

            $sth = self::$pdo->prepare($sql);

            $sth->bindValue(':status', 'active');
            $sth->bindValue(':chat_id', $chat_id);

            if ($limit > 0) {
                $sth->bindValue(':limit', $limit, PDO::PARAM_INT);
            }

            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }


    public static function selectChatsByGroupsList(array $group_list, $limit = 0)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $groups = [];
        foreach ($group_list as $group) {
            $groups[] = "'$group'";
        }

        try {
            $sql = '
              SELECT * FROM `' . TB_NOTIFICATION . '` WHERE `group` IN (' . implode(',', $groups) . ')';

            $sth = self::$pdo->prepare($sql);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    /**
     * Insert the conversation in the database
     *
     * @param int $user_id
     * @param int $chat_id
     * @param string $command
     *
     * @return bool
     * @throws TelegramException
     */
    public static function insertGroup($group, int $chat_id): bool
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('INSERT INTO `' . TB_NOTIFICATION . '`
                (`status`, `chat_id`, `group`, `created_at`, `updated_at`)
                VALUES
                (:status, :chat_id, :group_u, :created_at, :updated_at)
            ');

            $date = self::getTimestamp();

            $sth->bindValue(':status', 'active');
            $sth->bindValue(':chat_id', $chat_id);
            $sth->bindValue(':group_u', $group);
            $sth->bindValue(':created_at', $date);
            $sth->bindValue(':updated_at', $date);

            return $sth->execute();
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    /**
     * Update a specific conversation
     *
     * @param array $fields_values
     * @param array $where_fields_values
     *
     * @return bool
     * @throws TelegramException
     */
    public static function updateGroup(array $fields_values, array $where_fields_values): bool
    {
        // Auto update the update_at field.
        $fields_values['updated_at'] = self::getTimestamp();

        return self::update(TB_NOTIFICATION, $fields_values, $where_fields_values);
    }

    public static function deleteAll()
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql = 'DELETE FROM `notification` WHERE 1';

            $sth = self::$pdo->prepare($sql);

            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }
}
