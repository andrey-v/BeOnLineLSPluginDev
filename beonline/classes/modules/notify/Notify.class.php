<?php
/** @noinspection PhpUndefinedClassInspection */
/**
 * Переопределенный объект.
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 06.05.13 15:42    - Создание основного класса плагина.<br>
 *
 * @property ModuleViewer oViewerLocal
 * @method array PluginBeonline_Bo_getPayloads
 * @method array PluginBeonline_Bo_onError
 * @method array PluginBeonline_Bo_onUpdateRegId
 * @method array PluginBeonline_Bo_onDeleteRegId
 * @method string Lang_Get
 *
 * @package plugins/beonline
 */
class PluginBeonline_ModuleNotify extends PluginBeonline_Inherit_ModuleNotify {
    /**
     * Метод обработки ошибки отправления сообщения пользователю
     * @param $regId
     * @param $package
     * @param $error
     */
    public function onError($regId, $package, $error) {
        $this->PluginBeonline_Bo_onError($regId, $package, $error);
    }

    /**
     * Метод обновления regId пользователя
     * @param $newRegId
     * @param $oldRegId
     */
    public function onUpdateRegId($newRegId, $oldRegId) {
        $this->PluginBeonline_Bo_onUpdateRegId($newRegId, $oldRegId);
    }

    /**
     * Метод удаления regId пользователя
     * @param $oldRegId
     */
    public function onDeleteRegId($oldRegId) {
        $this->PluginBeonline_Bo_onDeleteRegId($oldRegId);
    }

    /**
     * Основная функция отправки сообщений пользователю на смартфон
     * @param ModuleUser_EntityUser $oUserTo
     * @param string $sEventName Имя сработанного события
     * @return boolean
     */
    private function sendOnSmartPhone(ModuleUser_EntityUser $oUserTo, $sEventName) {
        /** Если не разрешена отправка сообщения по такому событию, свернемся и отдохнем */
        if (!Config::Get('plugin.beonline.' . $sEventName)) return false;

        /** @var string $sTemplateFileName Файл шаблона, который рендерится в отсылаемое сообщение */
        $sTemplateFileName = $sEventName . '.tpl';
        /** @var string $sHeader Текст заголовка который увидит пользователь в Notification телефона*/
        $sHeader = $this->Lang_Get('plugin.beonline.' . $sEventName);
        /** @var string $sMessage Текст отправляемого сообщения */
        $sMessage = $this->oViewerLocal->Fetch($this->GetTemplatePath($sTemplateFileName, 'beonline'));
        /** @var GcmPayload[] $payloads Получаем данные для отправки */
        $payloads = $this->PluginBeonline_Bo_getPayloads($sMessage, $sHeader, $oUserTo->getId());

        /** @var GcmSender $oGcmSender Объект отправляльщика */
        $oGcmSender = new GcmSender(
            Config::Get('plugin.beonline.server_api_key'), // Серверный ключ
            Config::Get('plugin.beonline.gcm_url'), // Урл GCM
            array($this, 'onError'), // Колбэк для обработки ошибки
            array($this, 'onUpdateRegId'),
            array($this, 'onDeleteRegId')
        );
        /** Отправим сообщение пользователю */
        return $oGcmSender->send($payloads);
    }

    /**
     * Отправляет юзеру уведомление об ответе на его комментарий.
     * Регулируется конфигурационным параметром: send.new.answer.on.comment.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя кому отправляем
     * @param ModuleTopic_EntityTopic $oTopic    Объект топика
     * @param ModuleComment_EntityComment $oComment    Объект комментария
     * @param ModuleUser_EntityUser $oUserComment    Объект пользователя, написавшего комментарий
     * @return bool
     */
    public function SendCommentReplyToAuthorParentComment(ModuleUser_EntityUser $oUserTo, ModuleTopic_EntityTopic $oTopic, ModuleComment_EntityComment $oComment, ModuleUser_EntityUser $oUserComment) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        if (parent::SendCommentReplyToAuthorParentComment($oUserTo, $oTopic, $oComment, $oUserComment)) {
            /** Отправим сообщение на смартфон */
            $this->sendOnSmartPhone($oUserTo, 'push_1_comment_reply');
            return true;
        }
        return false;
    }

    /**
     * Отправляет юзеру уведомление о новом топике в блоге, в котором он состоит.
     * Регулируется конфигурационным параметром: send.new.topic.in.my.blog.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя кому отправляем
     * @param ModuleTopic_EntityTopic $oTopic    Объект топика
     * @param ModuleBlog_EntityBlog $oBlog    Объект блога
     * @param ModuleUser_EntityUser $oUserTopic    Объект пользователя, написавшего топик
     * @return bool
     */
    public function SendTopicNewToSubscribeBlog(ModuleUser_EntityUser $oUserTo, ModuleTopic_EntityTopic $oTopic, ModuleBlog_EntityBlog $oBlog, ModuleUser_EntityUser $oUserTopic) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        if (parent::SendTopicNewToSubscribeBlog($oUserTo, $oTopic, $oBlog, $oUserTopic)) {
            /** Отправим сообщение на смартфон */
            $this->sendOnSmartPhone($oUserTo, 'push_2_topic_new');
            return true;
        }
        return false;
    }

    /**
     * Отправляет уведомление при новом личном сообщении.
     * Регулируется конфигурационным параметром: send.new.personal.message.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя, которому отправляем сообщение
     * @param ModuleUser_EntityUser $oUserFrom    Объект пользователя, который отправляет сообщение
     * @param ModuleTalk_EntityTalk $oTalk    Объект сообщения
     * @return bool
     */
    public function SendTalkNew(ModuleUser_EntityUser $oUserTo, ModuleUser_EntityUser $oUserFrom, ModuleTalk_EntityTalk $oTalk) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        if (parent::SendTalkNew($oUserTo, $oUserFrom, $oTalk)) {
            /** Отправим сообщение на смартфон */
            $this->sendOnSmartPhone($oUserTo, 'push_3_talk_new');
            return true;
        }
        return false;
    }

    /**
     * Отправляет уведомление о новом сообщение в личке.
     * Регулируется конфигурационным параметром: send.new.personal.comment.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя, которому отправляем уведомление
     * @param ModuleUser_EntityUser $oUserFrom    Объект пользователя, которыф написал комментарий
     * @param ModuleTalk_EntityTalk $oTalk    Объект сообщения
     * @param ModuleComment_EntityComment $oTalkComment    Объект комментария
     * @return bool
     */
    public function SendTalkCommentNew(ModuleUser_EntityUser $oUserTo, ModuleUser_EntityUser $oUserFrom, ModuleTalk_EntityTalk $oTalk, ModuleComment_EntityComment $oTalkComment) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        if (parent::SendTalkCommentNew($oUserTo, $oUserFrom, $oTalk, $oTalkComment)) {
            /** Отправим сообщение на смартфон */
            $this->sendOnSmartPhone($oUserTo, 'push_4_talk_comment_new');
            return true;
        }
        return false;
    }

    /**
     * Отправляет пользователю сообщение о добавлении его в друзья.
     * Регулируется конфигурационным параметром: send.new.friend.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя
     * @param ModuleUser_EntityUser $oUserFrom    Объект пользователя, которого добавляем в друзья
     * @param string $sText    Текст сообщения
     * @param string $sPath    URL для подтверждения дружбы
     * @return bool
     */
    public function SendUserFriendNew(ModuleUser_EntityUser $oUserTo, ModuleUser_EntityUser $oUserFrom, $sText, $sPath) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        if (parent::SendUserFriendNew($oUserTo, $oUserFrom, $sText, $sPath)) {
            /** Отправим сообщение на смартфон */
            $this->sendOnSmartPhone($oUserTo, 'push_5_user_friend_new');
            return true;
        }
        return false;
    }

    /**
     * Отправляет пользователю сообщение о приглашение его в закрытый блог.
     * Регулируется конфигурационным параметром: send.new.invite.in.closed.blog.
     *
     * @param ModuleUser_EntityUser $oUserTo    Объект пользователя, который отправляет приглашение
     * @param ModuleUser_EntityUser $oUserFrom    Объект пользователя, которого приглашаем
     * @param ModuleBlog_EntityBlog $oBlog    Объект блога
     * @param $sPath
     */
    public function SendBlogUserInvite(ModuleUser_EntityUser $oUserTo, ModuleUser_EntityUser $oUserFrom, ModuleBlog_EntityBlog $oBlog, $sPath) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        parent::SendBlogUserInvite($oUserTo, $oUserFrom, $oBlog, $sPath);
        /** Отправим сообщение на смартфон */
        $this->sendOnSmartPhone($oUserTo, 'push_6_blog_invite_new');
    }

    /**
     * Уведомление при ответе на сообщение на стене.
     * Регулируется конфигурационным параметром: send.new.answer.on.wall.
     *
     * @param ModuleWall_EntityWall $oWallParent    Объект сообщения на стене, на которое отвечаем
     * @param ModuleWall_EntityWall $oWall    Объект нового сообщения на стене
     * @param ModuleUser_EntityUser $oUser    Объект пользователя
     */
    public function SendWallReply(ModuleWall_EntityWall $oWallParent, ModuleWall_EntityWall $oWall, ModuleUser_EntityUser $oUser) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        parent::SendWallReply($oWallParent, $oWall, $oUser);
        /** Отправим сообщение на смартфон */
        $this->sendOnSmartPhone($oWall->getWallUser(), 'push_7_wall_reply');
    }

    /**
     * Уведомление о новом сообщение на стене.
     * Регулируется конфигурационным параметром: send.new.message.on.wall.
     *
     * @param ModuleWall_EntityWall $oWall    Объект нового сообщения на стене
     * @param ModuleUser_EntityUser $oUser    Объект пользователя
     */
    public function SendWallNew(ModuleWall_EntityWall $oWall, ModuleUser_EntityUser $oUser) {
        /** @noinspection PhpUndefinedClassInspection Отправим стандартное сообщение */
        parent::SendWallNew($oWall, $oUser);
        /** Отправим сообщение на смартфон */
        $this->sendOnSmartPhone($oWall->getWallUser(), 'push_8_wall_new');
    }

}