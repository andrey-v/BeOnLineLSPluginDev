<?php
/**
 * Конфигурация плагина
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Opinion<br>
 * @version 1.0 от 06.05.13 15:44    - Создание файла конфигурации.<br>
 *
 * @package plugins/beonline
 */

Config::Set('db.table.beonline', '___db.table.prefix___beonline');
Config::Set('db.table.beonline_package', '___db.table.prefix___beonline_package');

Config::Set('router.page.beonline', 'PluginBeonline_ActionBeonline');

/** @noinspection SpellCheckingInspection */
$config = array(

    /** Серверны ключ */
    'server_api_key' => '??????????????????????????????????????',

    /** Адрес GCM. Не менять если нет прямой необходимости! */
    'gcm_url' => 'https://android.googleapis.com/gcm/send',

    /** Хранить сообщения, которые не были успешно отправлены с первого раза */
    'keep_old_messages' => false,

    /**************************************************************************/
    /** Перечень видов сообщений, которые нужно/ненужно отсылать пользователю */
    /**************************************************************************/
    /** 1. Уведомление об ответе на его комментарий */
    'push_1_comment_reply' => true,
    /** 2_ Уведомление о новом топике в блоге, в котором он состоит */
    'push_2_topic_new' => true,
    /** 3_ Уведомление при новом личном сообщении*/
    'push_3_talk_new' => true,
    /** 4_ Уведомление о новом комментарии к письму в личке */
    'push_4_talk_comment_new' => true,
    /** 5_ Уведомление пользвателю о том, что его добавили в друзья */
    'push_5_user_friend_new' => true,
    /** 6_ Уведомление о приглашении пользователя в закрытый блог */
    'push_6_blog_invite_new' => true,
    /** 7_ Уведомление при ответе на сообщение на стене */
    'push_7_wall_reply' => true,
    /** 8_ Уведомление о новом сообщение на стене */
    'push_8_wall_new' => true,
);

return $config;