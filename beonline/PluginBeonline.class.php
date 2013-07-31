<?php
/**
 * Основной класс плагина
 *
 * @author  Андрей Г. Воронов <info@gladcode.ru>
 * @copyrights  Copyright &copy; 2011-2012, Андрей Г. Воронов<br>
 *              Является частью плагина Carousel<br>
 * @version 1.0.1 от 09.09.12 11:25    - Создание основного класса плагина.<br>
 *
 * @package plugins/carousel
 */

/**
 *  Запрещаем прямой доступ. В архитектуре LS, работа с плагинами реализуется через класс Plugin (они от него наследуются),
 * и поэтому здесь осуществляем логично сделать проверку на наличие родительского класса, если он доступен, то вероятно,
 * что загрузка ядра LS прошла успешно, иначе чего-то не того происходит и оно все равно правильно работать не будет. */
if (!class_exists('Plugin')) {
    die('You are bad hacker, try again, baby!');
}

/**
 * Сам класс плагина
 *
 * @see Plugin - там много public-методов, которые могут быть использованы или переопределены
 * @link http://docs.livestreetcms.com/api/1.0/Plugin - официальное описание этого класса
 *
 */
class PluginBeonline extends Plugin {

    /**
     * Флаг, показывающий необхоодимость создания и удаления таблиц при активации и деактивации плагина.
     * @var bool
     */
    private $updateDbTables = true;

    /** @var array Переопределяемые объекты */
    protected $aInherits = array(
        'module' => array(
            'ModuleNotify' => '_ModuleNotify',
        ),
    );

    /** Инициализация плагина */
    public function Init() {
        require_once dirname(__FILE__) . '/boCore/BoCore.php';
    }

    /**
     * Актвация плагина
     * @return bool
     */
    public function Activate() {
        if ($this->updateDbTables){
            if (!$this->isTableExists('prefix_beonline')) {
                $this->ExportSQL(dirname(__FILE__) . '/data/on.sql');
            }
        }

        return true;
    }

    /**
     * Деактивация плагина
     * @return bool
     */
    public function Deactivate() {
        if ($this->updateDbTables){
            $this->ExportSQL(dirname(__FILE__).'/data/off.sql');
        }

        return true;
    }

}