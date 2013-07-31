<?php
/**
 * Класс пользовательского сообщения
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 10.05.13 15:00    - Создание класса.<br>
 *
 * @package plugins/beonline
 */
class GcmPayload {
    /**
     * Идентфикатор пользователя, кому отправляем сообщение
     * @var string
     */
    public $regId;
    /**
     * Закодированный в формате json текст сообщения
     * @var
     */
    public $jsonMessage;

    /**
     * Конструктор сообщения
     * @param $jsonMessage
     * @param $regId
     */
    function __construct($jsonMessage, $regId) {
        /** Инициализируем совойства объекта */
        $this->jsonMessage = $jsonMessage;
        $this->regId = $regId;
    }
}