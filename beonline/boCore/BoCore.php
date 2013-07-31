<?php
/**
 * Объект, инициирующий ядро плагина
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2013, Андрей Г. Воронов<br>
 *              Является частью плагина Beonline<br>
 * @version 1.0 от 12.05.13 17:39    - Создание основного класса плагина.<br>
 *
 * @package plugins/beonline
 */
class BoCore {
    function __construct() {
        require_once dirname(__FILE__) . '/RollingCurl.php';
        require_once dirname(__FILE__) . '/GcmPayload.php';
        require_once dirname(__FILE__) . '/GcmSender.php';
    }
}

/** Запускаем ядро */
new BoCore();