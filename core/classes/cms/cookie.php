<?php

namespace cms;

class cookie
{

    /**
     * Устанавливает кукис посетителю
     * @param string $name Название
     * @param string $value Значение
     * @param integer $expire Время жизни
     * @param boolean $httponly Если задано true, cookie не будут доступны javascript
     */
    public static function set($name, $value, $expire = 0, $httponly = true)
    {
        if (mb_substr(HOST, 0, 8) == 'https://') {
            $secure = true;
        } else {
            $secure = false;
        }
        
        setcookie(\cmsConfig::getConfig('cookie_key') .'['. $name .']', $value, $expire, '/', null, $secure, $httponly);
    }

    /**
     * Удаляет кукис пользователя
     * @param string $name Название
     */
    public static function delete($name)
    {
        setcookie(\cmsConfig::getConfig('cookie_key') . '[' . $name . ']', '', time() - 3600, '/');
    }

    /**
     * Возвращает значение кукиса
     * @param string $name Название
     * @return mixed
     */
    public static function get($name)
    {
        $key_name = \cmsConfig::getConfig('cookie_key');

        if ( isset($_COOKIE[$key_name][$name]) ) {
            return $_COOKIE[$key_name][$name];
        }
        else {
            return false;
        }
    }
}