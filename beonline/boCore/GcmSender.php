<?php
/**
 * Class GcmSender
 * Основной класс отправки сообщений в GCM
 *
 * @author  Андрей Г. Воронов <andreyv@gladcode.ru>
 * @copyrights  Copyright &copy; 2011-2012, Андрей Г. Воронов<br>
 *              Является частью плагина BeOnline<br>
 * @version 1.0.1 от 10.05.13 06:07    - Создание основного класса плагина.<br>
 * @see http://habrahabr.ru/post/161305/
 *
 * @package plugins/carousel
 */

class GcmSender {
    /**
     * Ключ API. Нужно получить на странице "Api Access" в консоли Google APIs Console.
     * @see http://stackoverflow.com/questions/13151054/how-to-create-api-key-for-gcm
     */
    private /** @noinspection SpellCheckingInspection */
        $GCM_API_KEY = 'AIzaSyDl8LL2-WOaCkRM2ce2UJ8gaJubwmfA8lM';
    /** Адрес GCM сервера */
    private /** @noinspection SpellCheckingInspection */
        $GCM_SERVER_URL = 'https://android.googleapis.com/gcm/send';
    /** Таймаут соединения в сервером Гугл в секундах */
    const CURL_TIMEOUT = 10;
    /** Лимит на отправляемые данные в байтах */
    const GCM_MAX_DATA_SIZE = 4096;
    /** Количество параллельных запросов */
    const GCM_MAX_CONNECTIONS = 10;
    /** Ключ получателей в json запросе */
    const KEY_REG_IDS = 'registration_ids';
    /** Ключ с данными в json запросе */
    const KEY_DATA = 'data';
    /** Ключ в объекте data, содержащий наш массив данных */
    const KEY_MESSAGE = 'message';
    /** Плэйсхолдер для RegistrationId в json шаблоне запроса */
    const REG_ID_PLACEHOLDER = '__REG_ID__';
    /** Плэйсхолдер для данных в json шаблоне запроса */
    const MESSAGE_PLACEHOLDER = '_MESSAGE_';
    /** Константа для ошибки, если пользователь удалил приложение */
    const GCM_ERROR_NOT_REGISTERED = 'NotRegistered';

    /** @var array Колбэк для обработки ошибки отправки сообщения */
    private $callbackError = array();
    /** @var array колбэк для обработки ошибочного id пользователя */
    private $callbackUserUpdateRegId = array();
    /** @var array Удаляет regId пользователя - он (пользователь) удалил приложение */
    private $callbackDeleteRegId = array();

    /** @var string json шаблон запроса */
    protected $_template;

    /**
     * Конструктор объекта
     * @param $gcm_api_key
     * @param $gcm_server_url
     * @param $callbackError
     * @param $callbackUserUpdateRegId
     * @param $callbackDeleteRegId
     */
    public function __construct($gcm_api_key, $gcm_server_url, $callbackError, $callbackUserUpdateRegId, $callbackDeleteRegId) {
        /** Определим параметры из конфига плагина */
        $this->GCM_API_KEY = $gcm_api_key;
        $this->GCM_SERVER_URL = $gcm_server_url;
        $this->callbackError = $callbackError;
        $this->callbackUserIdFailed = $callbackUserUpdateRegId;
        $this->callbackDeleteRegId = $callbackDeleteRegId;

        /** и шаблон сообщения */
        $this->_template = '{
        "' . self::KEY_REG_IDS . '": ["' . self::REG_ID_PLACEHOLDER . '"],
        "' . self::KEY_DATA . '": ' . self::MESSAGE_PLACEHOLDER . '}';
    }

    /**
     * Отправка сообщения на телефон пользователя
     * @param GcmPayload[] $payloads
     * @return boolean
     */
    public function send($payloads) {
        /** @var array $packages Пакеты сообщений для пользователя из расчета - один пакет - одно сообщение */
        $packages = self::getPackages($payloads);
        /** Если пакетов нет, завершимся */
        if (!$packages || count($packages) == 0) return false;

        foreach ($packages as $package) {
            /** @var RollingCurl $rc Объект параллельного выполнения асинхронных http-запросов */
            $rc = new RollingCurl(array($this, 'onResponse'));
            /** Установим заголовок запроса */
            $rc->__set('headers', array('Authorization: key=' . $this->GCM_API_KEY, 'Content-Type: application/json'));
            /** @var array options Параметры запроса - одинаковые для всех пакетов */
            $rc->options = array(
                CURLOPT_SSL_VERIFYPEER => false, //отключаем проверку сертификата
                CURLOPT_RETURNTRANSFER => true, //указываем, что хотим получить ответ в виде строки
                CURLOPT_CONNECTTIMEOUT => self::CURL_TIMEOUT, // сколько секунд пытаться установить соединение
                CURLOPT_TIMEOUT => self::CURL_TIMEOUT); //сколько времени должны выполняться функции curl

            /** Передадим пакеты запросов в RollingCurl */
            $rc->request($this->GCM_SERVER_URL, 'POST', $package);

            /** Выполним запросы */
            $rc->execute(self::GCM_MAX_CONNECTIONS);
        }
        return true;
    }

    /**
     * Сформируем пакеты сообщений
     * @param GcmPayload[] $payloads
     * @return string[]
     */
    protected function getPackages($payloads) {
        $packages = array();
        /** Для каждого payload пользователя */
        foreach ($payloads as $payload) {
            /** @var string $template Установим его regId */
            $template = str_replace(self::REG_ID_PLACEHOLDER, $payload->regId, $this->_template);
            /** @var string $jsonMessage Получим сообщение, гарантированно не превышающее  4096 байт */
            $jsonMessage = self::getResizingJsonMessage($payload->jsonMessage);
            /** Добавим сообщение в пакет */
            $packages[] = str_replace(self::MESSAGE_PLACEHOLDER, $jsonMessage, $template);
        }

        return $packages;
    }

    /**
     * Функция сокращает текст передаваемого сообщения до максимально-допустимого @see GCM_MAX_DATA_SIZE
     * @param string $jsonMessage Исходное сообщение
     * @return string
     */
    protected function getResizingJsonMessage($jsonMessage) {
        if (strlen($jsonMessage) > self::GCM_MAX_DATA_SIZE) {
            /** @var array $data Передаваемые данные */
            $data = json_decode($jsonMessage);
            /** @var int $magicNumber Магическое число, которое немного больше всех скобок, пробелов и идентификаторов
             * в передаваемых данных формата json. */
            $magicNumber = 100;
            /** @var int $delta Число убираемых символов */
            $delta = self::GCM_MAX_DATA_SIZE - strlen($data['header']) - $magicNumber;
            /** @var string $newMessage Новое сообщение */
            $newMessage = substr($data['message'], 0, strlen($data['message']) - $delta) . '...';
            /** Установим новое сообщение */
            $data['message'] = $newMessage;
            /** Закодируем и возвратим результат */
            return json_encode($data);
        }
        /** Размеры сообщения не превышают допустимых - никаких изменений не будет */
        return $jsonMessage;
    }

    /**
     * Колбэк-функция для обработки результата отправвки сообщений пользователю. Выполняется после отправки пакетов
     * @param string $response Ответ сервера Google
     * @param array $info Информация о результате исполнения запроса согласно спецификации протокола
     * @param RollingCurlRequest $request Выполняемый запрос
     */
    public function onResponse($response, $info, RollingCurlRequest $request) {
        // Этот флаг показывает успешно ли отправлено сообщение
        $success = true;
        $error = 'Нет ошибок';

        // Декодирует json, который мы отправили в post
        $post = json_decode($request->post_data, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $success = false;
            $error = 'Ошибка в синтаксисе отправляемого пакета!';
        } else {
            // Получаем код ответа
            $code = $info != null && isset($info['http_code']) ? $info['http_code'] : 0;
            // Определяем группу кода: 2, 3, 4, 5
            $codeGroup = (int)($code / 100);
            if ($codeGroup == 5) {
                $success = false;
                $error = 'GCM сервер временно недоступен, сообщение не доставлено!';
            }
            if ($code !== 200) {
                if ($response) {
                    $json = json_decode($response, true);
                    if (json_last_error() != JSON_ERROR_NONE) {
                        // Ошибка парсинга json ответа, на всякий случай считаем что сообщение не доставлено
                        $success = false;
                        $error = 'Невозможно прочитать ответ GCM. Считаем что сообщение не доставлено!';
                    }
                } else {
                    if (strlen(trim($response)) == null) {
                        $success = false;
                        $error = 'Пустой ответ, значит что-то пошло не так, сообщение не доставлено!';
                    } else {
                        $success = false;
                        $error = 'Недождались ответа от GCM. Считаем что сообщение не доставлено!';
                    }
                }
            }
            $json = json_decode($response, true);
            // $failure содержит количество недоставленных сообщений (в нашем случае получатель один, поэтому failure будет содержать либо 0 либо 1)
            $failure = isset($json['failure']) ? $json['failure'] : null;
            // $canonical_ids содержит количество получателей, для которых нужно обновить RegistrationId (как и в случае с failure - значение либо 0 либо 1).
            $canonicalIds = isset($json['canonical_ids']) ? $json['canonical_ids'] : null;
            // Если оба параметра равны нулю, то дальнейший анализ результата не требуется. При условии $success=true можно считать что сообщение успешно доставлено
            if ($failure || $canonicalIds) {
                // results содержит массив объектов. Так как у нас получатель один, то результат тоже будет один (в случае ошибки или смены RegistrationId)
                $results = isset($json['results']) ? $json['results'] : array();
                foreach ($results as $result) {
                    $newRegId = isset($result['registration_id']) ? $result['registration_id'] : null;
                    $error = isset($result['error']) ? $result['error'] : null;
                    if ($newRegId) {
                        /** Вызовем колбэк для замены $regId на $newRegId; */
                        $callback = $this->callbackUserIdFailed;
                        if (is_callable($this->callbackUserIdFailed)) {
                            $oldRegId = $post[self::KEY_REG_IDS][0];
                            call_user_func($callback, $newRegId, $oldRegId);
                            return;
                        }
                    } else if ($error) {
                        /** Вызовем колбэк для удаления $regId */
                        $callback = $this->callbackDeleteRegId;
                        if (is_callable($this->callbackDeleteRegId)) {
                            $oldRegId = $post[self::KEY_REG_IDS][0];
                            call_user_func($callback, $oldRegId);
                            return;
                        }
                    }
                }
            }

        }

        //Теперь мы знаем, доставлено ли сообщение для конкретного получателя или нет.
        if (!$success) {
            /** Вызовем колбэк для обработки ошибок */
            $callback = $this->callbackError;
            if (is_callable($this->callbackError)) {
                $package = json_decode($request->post_data, true);
                $regId = $post[self::KEY_REG_IDS][0];
                call_user_func($callback, $regId, $package, $error);
            }
        }
    }
}