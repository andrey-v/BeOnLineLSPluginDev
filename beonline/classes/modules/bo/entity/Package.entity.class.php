<?php
/**
 * Класс, описывающий пакет пользовательского сообщения
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 10.05.13 15:00    - Создание класса.<br>
 *
 * @method ModuleUser_EntityUser User_GetUserById
 *
 * @package plugins/beonline
 */
class PluginBeonline_ModuleBo_EntityPackage extends Entity {

    /**
     * Возвращает ID сообщения
     * @return int|null
     */
    public function getId() {
        return $this->_getDataOne('id');
    }

    /**
     * Возвращает пользователя
     * @return string|null
     */
    public function getUser() {
        return $this->User_GetUserById($this->_getDataOne('user_id'));
    }

    /**
     * Возвращает пакет сообщения
     * @return string|null
     */
    public function getPackage() {
        return $this->_getDataOne('package');
    }

    /**
     * Возвращает ошибку
     * @return string|null
     */
    public function getError() {
        return $this->_getDataOne('error');
    }

    /**
     * Возвращает статус пакета
     * @return string
     */
    public function getStatus() {
        return $this->_getDataOne('status');
    }
}