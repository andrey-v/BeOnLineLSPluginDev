<?php
/**
 * Маппер для работы с недоставленными пакетами сообщений пользователей
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 12.05.13 17:43    - Создание класса.<br>
 *
 * @package plugins/beonline
 */
class PluginBeonline_ModuleBo_MapperPackage extends Mapper {
    /** Статус недоставленного сообщения */
    const PACKAGE_STATUS_OPENED = 'opened';
    /** Статус бывшего недоставленного - уже доставлено */
    const PACKAGE_STATUS_CLOSED = 'closed';

    /**
     * Добавление недоставленного пакета во временное хранилище
     * @param $oUser ModuleUser_EntityUser
     * @param $jsonPackage
     * @param $sError
     * @return array|bool|null
     */
    public function addPackage($oUser, $jsonPackage, $sError) {
        $sql = "INSERT INTO " . Config::Get('db.table.beonline_package') . " (
                user_id,
                package,
                error,
                status) VALUES (?, ?, ?, ?)";

        if ($iId = $this->oDb->query($sql, $oUser->getId(), $jsonPackage, $sError, self::PACKAGE_STATUS_OPENED)) {
            return $iId;
        }
        return false;
    }

    /**
     * Получает массив недоставленных сообщений для пользователя
     * @param $iUserId int
     * @return null|boolean
     */
    public function getPackages($iUserId) {
        $sql = "SELECT * FROM " . Config::Get('db.table.beonline_package') . " WHERE user_id = ? and status = ? ORDER by id";

        $aPackages = array();
        if ($aRows = $this->oDb->select($sql, $iUserId, self::PACKAGE_STATUS_OPENED)) {
            $this->closePackages($iUserId);
            foreach ($aRows as $aPackage) {
                $aPackages[] = Engine::GetEntity('PluginBeonline_ModuleBo_EntityPackage', $aPackage);
            }
            return $aPackages;
        }

        return $aPackages;
    }

    /**
     * Закрытие неотправленного сообщения
     * @param $iUserId
     * @return array|null
     */
    private function closePackages($iUserId) {
        if (!Config::Get('plugin.beonline.keep_old_messages')) {
            $sql = "DELETE FROM " . Config::Get('db.table.beonline_package') . " WHERE user_id = ?d";
            return $this->oDb->query($sql, $iUserId);
        } else {
            $sql = "UPDATE " . Config::Get('db.table.beonline_package') . " SET status = ? WHERE user_id = ?d";
            return $this->oDb->query($sql, self::PACKAGE_STATUS_CLOSED, $iUserId);
        }
    }

}