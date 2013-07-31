<?php
/**
 * Class PluginBeonline_ActionBeonline
 *
 * @method boolean PluginBeonline_ModuleBo_login
 * @method boolean PluginBeonline_ModuleBo_logout
 */
class PluginBeonline_ActionBeonline extends ActionPlugin {
    const REG_ID_TOKEN = 'reg_id';
    const PASSWORD_TOKEN = 'password';
    const LOGIN_TOKEN = 'login';
    const HASH_TOKEN = 'hash';
    const DATA_TOKEN = 'data';
    const OK_DATA_VALUE = 'ok';
    const ERROR_DATA_VALUE = 'error';
    const LOGOUT_TOKEN = 'logout';
    const ERROR_ACTIVATION = "error activation";
    const ERROR_LOGIN = "error login";

    /** Абстрактный метод инициализации экшена */
    public function Init() {
    }

    /** Регистрируем экшен */
    protected function RegisterEvent() {
        $this->AddEventPreg('/^[a-z_]*$/i', 'EventExecute');
    }

    /** Выполнение экшена */
    /** @noinspection SpellCheckingInspection */
    protected function EventExecute() {
        $sCommand = $this->sCurrentEvent;
        switch ($sCommand) {
            /** @noinspection SpellCheckingInspection */
            case self::LOGIN_TOKEN:
                // http://asp-gladcode-local/beonline/login?login=Fentor&password=avmiracle0202av&reg_id=2&reg_id=22222
                if (getRequest(self::LOGIN_TOKEN) && getRequest(self::PASSWORD_TOKEN) && getRequest(self::REG_ID_TOKEN)) {
                    /** @var array $data Переданные данные для авторизации */
                    $data = array(
                        self::LOGIN_TOKEN => getRequest(self::LOGIN_TOKEN),
                        self::PASSWORD_TOKEN => getRequest(self::PASSWORD_TOKEN),
                        self::REG_ID_TOKEN => getRequest(self::REG_ID_TOKEN)
                    );
                    /** @var string $sHash Получим хэш для пользователя и если все успешно вернем его */
                    $sHash = $this->PluginBeonline_ModuleBo_login($data);
                    if ($sHash == self::ERROR_ACTIVATION) {
                        /** Пользователь не активирован */
                        echo json_encode(array(self::DATA_TOKEN => self::ERROR_ACTIVATION));
                    } elseif ($sHash == self::ERROR_LOGIN) {
                        /** Ошибочный логин или пароль */
                        echo json_encode(array(self::DATA_TOKEN => self::ERROR_LOGIN));
                    } elseif ($sHash) {
                        /** Если мы попали сюда, то пользователь успешно зарегистрировал приложение в GCM, успешно
                         * авторизовался на нашем сайте, получил и сохранил у себя хэш для дальнейшего обмена данными */
                        echo json_encode(array(
                            self::DATA_TOKEN => self::OK_DATA_VALUE,
                            self::HASH_TOKEN => $sHash,
                        ));
                    } else {
                        /** Фэйл (ошибка неизвестна), попробуем позже */
                        echo json_encode(array(self::DATA_TOKEN => self::ERROR_DATA_VALUE));
                    }
                }
                exit();
            /** @noinspection SpellCheckingInspection */
            case self::LOGOUT_TOKEN:
                /** http://asp-gladcode-local/beonline/logout?hash=3961ced1a0025c63c35402d7fd31e0cce6b61f25c1 */
                if (getRequest(self::HASH_TOKEN)) {
                    /** @var string $result Получим хэш для пользователя и если все успешно вернем его */
                    $result = $this->PluginBeonline_ModuleBo_logout(getRequest(self::HASH_TOKEN));
                    if ($result) {
                        echo json_encode(array(self::DATA_TOKEN => self::OK_DATA_VALUE));
                    } else {
                        echo json_encode(array(self::DATA_TOKEN => self::ERROR_DATA_VALUE));
                    }
                }
                exit();
            default :
                echo json_encode(array(self::DATA_TOKEN => self::ERROR_DATA_VALUE));
                exit();
        }

    }

}