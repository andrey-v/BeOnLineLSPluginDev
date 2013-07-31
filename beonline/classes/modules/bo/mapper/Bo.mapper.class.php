<?php
/**
 * Маппер для работы с идентификаторами регистрации пользователей
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 12.05.13 17:42    - Создание класса.<br>
 *
 * @package plugins/beonline
 */
class PluginBeonline_ModuleBo_MapperBo extends Mapper {

    /**
     * Создание идентифицирующей пользователя записи
     * @param $iUserId
     * @param $sRegId
     * @return string
     */
    public function createRegId($iUserId, $sRegId) {
        /** @var string $sUserKey Идентификатор пользователя на устройстве (является частью отправляемого ему хэша)*/
        $sUserKey = func_generator();
        $sql = 'INSERT INTO ' . Config::Get('db.table.beonline') . ' SET `user_id` = ?d, `reg_id` = ?,  `user_key` = ?';
        $this->oDb->query($sql, $iUserId, $sRegId, $sUserKey);
        return $sUserKey;
    }

    /**
     * Получение RegId пользователя по его идентификатору
     * @param $iUserId
     * @return null|string
     */
    public function getUserRegId($iUserId) {
        $sql = "SELECT
                    reg_id
                FROM
                    " . Config::Get('db.table.beonline') . "
                WHERE
                    user_id = ?
                ";
        if ($aRow = $this->oDb->selectRow($sql, $iUserId)) {
            return $aRow['reg_id'];
        }
        return null;
    }

    /**
     * Получение user_key пользователя по его идентификатору
     * @param $iUserId
     * @return null|string
     */
    public function getUserKey($iUserId) {
        $sql = "SELECT
                    user_key
                FROM
                    " . Config::Get('db.table.beonline') . "
                WHERE
                    user_id = ?
                ";
        if ($aRow = $this->oDb->selectRow($sql, $iUserId)) {
            return $aRow['user_key'];
        }
        return null;
    }

    /**
     * Получение идентификатора пользователя по его ключу
     * @param $sKey
     * @return bool|int
     */
    public function getUserIdByKey($sKey) {
        $sql = 'SELECT * FROM '. Config::Get('db.table.beonline') . ' WHERE `user_key` = ?';
        if ($aRow = $this->oDb->selectRow($sql, $sKey)) {
            return $aRow['user_id'];
        }
        return false;
    }

    /**
     * Получение идентификатора пользователя по его $sRegId
     * @param string $sRegId GCM-идентификатор пользователя
     * @return int|null
     */
    public function getUserId($sRegId) {
        $sql = "SELECT
                    user_id
                FROM
                    " . Config::Get('db.table.beonline') . "
                WHERE
                    reg_id = ?
                ";
        if ($aRow = $this->oDb->selectRow($sql, $sRegId)) {
            return $aRow['user_id'];
        }
        return null;
    }

    /**
     * удаляем запись с пользовательским ключем, если он (пользователь) удалил приложение
     * @param $sRegId
     * @return array|null
     */
    public function deleteByRegId($sRegId) {
        $sql = "
			DELETE FROM " . Config::Get('db.table.beonline') . "
			WHERE
				reg_id = ?
		";
        return $this->oDb->query($sql, $sRegId);
    }

    /**
     * удаляем запись с пользовательским ключем, если он (пользователь) удалил приложение
     * @param $sUserKey
     * @return array|null
     */
    public function deleteByUserKey($sUserKey) {
        $sql = "
			DELETE FROM " . Config::Get('db.table.beonline') . "
			WHERE
				user_key = ?
		";
        return $this->oDb->query($sql, $sUserKey);
    }

    /**
     * Обновление данных регистрационного ключа пользователя при переустановке приложения
     * @param $newRegId
     * @param $oldRegId
     * @return array|null
     */
    public function updateRegId($newRegId, $oldRegId) {
        $sql = "UPDATE " . Config::Get('db.table.beonline') . " SET reg_id = ? WHERE reg_id = ?";
        return $this->oDb->query($sql, $newRegId, $oldRegId);
    }

}