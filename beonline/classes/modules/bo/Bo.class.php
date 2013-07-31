<?php
/**
 * Модуль BeOnline.
 *
 * @author      Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version     1.0 от 10.05.13 14:48    - Создание модуля.<br>
 *
 * @method ModuleUser_EntityUser User_GetUserById
 * @method ModuleUser_EntityUser User_GetUserByMail
 * @method ModuleUser_EntityUser User_GetUserByLogin
 *
 * @package     plugins/beonline
 */
class PluginBeonline_ModuleBo extends Module {

    /**
     * Маппер идентификторов пользователей
     * @var PluginBeonline_ModuleBo_MapperBo
     */
    protected $oMapper;

    /**
     * Маппер недоставленных сообщений пользователей
     * @var PluginBeonline_ModuleBo_MapperPackage
     */
    protected $oMapperPackage;

    /** Инициализация модуля */
    public function Init() {
        $this->oMapper = Engine::GetMapper(__CLASS__);
        $this->oMapperPackage = Engine::GetMapper(__CLASS__, 'Package');
    }

    /**
     * Метод обработки ошибки отправления сообщения пользователю
     * @param $regId
     * @param $aPackage
     * @param $sError
     */
    public function onError($regId, $aPackage, $sError) {
        $iUserId = $this->oMapper->getUserId($regId);
        $oUser = $oUser = $this->User_GetUserById($iUserId);
        $jsonPackage = json_encode($aPackage);
        $this->oMapperPackage->addPackage($oUser, $jsonPackage, $sError);
    }

    /**
     * Метод обновления regId пользователя
     *
     * @param $newRegId
     * @param $oldRegId
     */
    public function onUpdateRegId($newRegId, $oldRegId) {
        $this->oMapper->updateRegId($newRegId, $oldRegId);

    }

    /**
     * Метод удаления regId пользователя
     *
     * @param $oldRegId
     */
    public function onDeleteRegId($oldRegId) {
        $this->oMapper->deleteByRegId($oldRegId);
    }

    /**
     * Получает объект-сообщение для указанного пользователя с приведенным в параметрах текстом
     * @param string $sHeader  Заголовок сообщения
     * @param string $sMessage Текст сообщения
     * @param int    $iUserId  Ид. пользователя, кому отправляем сообщение
     *
     * @return array
     */
    public function getPayloads($sMessage, $sHeader, $iUserId) {
        /** @var string $sRegId Получим регИд пользователя из дазы банной */
        $sRegId = $this->oMapper->getUserRegId($iUserId);
        /** Если он там все таки был, то  */
        if (!is_null($sRegId)) {
            /** @var array PluginBeonline_ModuleBo_EntityPackage[] Ранее не доставленные сообщения */
            $aOldPackages = $this->oMapperPackage->getPackages($iUserId);
            /** @var GcmPayload[] $aPayloads Массив сообщений для отправки пользователю */
            $aPayloads = array();
            /** @var PluginBeonline_ModuleBo_EntityPackage $oldPackage */
            foreach ($aOldPackages as $oldPackage) {
                /** @var $oOldPackageData Старые данные для отправки */
                $oOldPackageData = json_decode($oldPackage->getPackage());
                $aPayloads[] = new GcmPayload(
                    json_encode(array(
                            'message' => $oOldPackageData->data->message,
                            'header' => $oOldPackageData->data->header,
                            'time' => date("d.m.Y H:i:s"))
                    ),
                    $sRegId);
            }
            $aPayloads[] = new GcmPayload(
                json_encode(array(
                        'message' => $sMessage,
                        'header' => $sHeader,
                        'time' => date("d.m.Y H:i:s"))
                ),
                $sRegId);

            return $aPayloads;
        }

        return array();
    }

    /**
     * Метод проводит авторизацию пользователя по запросу от android-приложения
     *
     * @param array $data
     *
     * @return bool
     */
    public function login($data) {
        /** Получим переданные данные */
        $sLogin = $data[PluginBeonline_ActionBeonline::LOGIN_TOKEN];
        $sPassword = $data[PluginBeonline_ActionBeonline::PASSWORD_TOKEN];
        $sRegId = $data[PluginBeonline_ActionBeonline::REG_ID_TOKEN];

        /** @var ModuleUser_EntityUser $oUser Текущий пользователь, полученный по логину */
        $oUser = $this->User_GetUserByMail($sLogin);
        if (is_null($oUser)) {
            $oUser = $this->User_GetUserByLogin($sLogin);
        }
        if (is_null($oUser)) $oUser = FALSE;

//        return "=". ($oUser!=false?"Y":"n") . '=' . (($oUser->getPassword() == $sPassword)?"Y":"n") ."=" . (($oUser->getActivate()!=false)?"y":"n");

        /** Если найден пользователь с валидным паролем и еще и активирован на сайте, то */
        if ($oUser && $oUser->getPassword() == $sPassword) {
            if ($oUser->getActivate()) {
                if ($sKey = $this->oMapper->getUserKey($oUser->getId()) == NULL) {
                    /** @var string $sKey Создадим сессию для пользователя и получим ее ключ */
                    $sKey = $this->oMapper->createRegId($oUser->getId(), $sRegId);
                }
                /** @var string $sHash Получим возвращаемый пользователю хэш */
                $sHash = $sKey . md5($oUser->getPassword());

                return $sHash;
            } else {
                /** Пользователь не активирован */
                return PluginBeonline_ActionBeonline::ERROR_ACTIVATION;
            }
        } else {
            /** Ошибка имени пользователя или пароля */
            return PluginBeonline_ActionBeonline::ERROR_LOGIN;
        }
    }

    /**
     * Отключение устройства пользователя от обслуживания на сайте
     **/
    public function logout($sHash) {
        /** @var string $sKey Ключ пользователя */
        $sKey = substr($sHash, 0, 10);
        /** @var string $sPasswordHash md5(ПарольПользователяИзБазы) */
        $sPasswordHash = substr($sHash, 10);

        /** Если не получилось - false */
        if (!$sKey || !$sPasswordHash) {
            return FALSE;
        }
        /** @var array $iUserId Если пользователя нет - false */
        $iUserId = $this->oMapper->getUserIdByKey($sKey);
        if (!$iUserId) {
            return FALSE;
        }
        /** @var ModuleUser_EntityUser $oUser Если не найден пользователь по ключу, то  - false */
        $oUser = $this->User_getUserById($iUserId);
        if (!$oUser) {
            return FALSE;
        }
        /** Если пароли не совпадают, то  - false */
        if ($sPasswordHash != md5($oUser->getPassword())) {
            return FALSE;
        }

        return $this->oMapper->deleteByUserKey($sKey);
    }

}