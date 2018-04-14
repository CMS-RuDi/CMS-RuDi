<?php

namespace cms;

/**
 * @package Classes
 */
class session
{

    /**
     * Название ключа для хранения данных в массиве $_SESSION
     */
    const NAME_SPACE = 'cmsRuDi';

    /**
     * Сохраняет значение переменной в сессии
     *
     * @param string $name Название переменной
     * @param string $value Значение переменной
     * @param string $namespace Пространство имен
     */
    public static function set($name, $value, $namespace = self::NAME_SPACE)
    {
        if ( strpos($name, ':') === false ) {
            $_SESSION[$namespace][$name] = $value;
        }
        else {
            helper\arrs::setValueRecursive($name, $_SESSION[$namespace], $value);
        }

        $_SESSION[$namespace][$name] = $value;
    }

    /**
     * Возвращает значение указанной переменной из сессии или null при ее отсутствии
     *
     * @param string $name Название переменной
     * @param string $namespace Пространтсво имен
     *
     * @return mixed
     */
    public static function get($name, $namespace = self::NAME_SPACE)
    {
        if ( strpos($name, ':') === false ) {
            if ( self::has($name, $namespace) ) {
                return $_SESSION[$namespace][$name];
            }
        }
        else {
            return helper\arrs::getValueRecursive($name, $_SESSION[$namespace]);
        }

        return null;
    }

    /**
     * Проверяет наличие переменной в указанном пространстве имен в сессии
     *
     * @param string $name Название переменной
     * @param string $namespace Пространство имен
     *
     * @return bool
     */
    public static function has($name, $namespace = self::NAME_SPACE)
    {
        if ( is_array($_SESSION[$namespace]) ) {
            if ( strpos($name, ':') === false ) {
                return array_key_exists($name, $_SESSION[$namespace]);
            }
            else {
                $value = helper\arrs::getValueRecursive($name, $_SESSION[$namespace]);

                if ( $value !== null ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Удаляет указанную переменную из сессии
     *
     * @param string $name Название переменной
     * @param string $namespace Пространство имен
     */
    public static function delete($name, $namespace = self::NAME_SPACE)
    {
        if ( strpos($name, ':') === false ) {
            if ( self::has($name, $namespace) ) {
                unset($_SESSION[$namespace][$name]);
            }
        }
        else {
            helper\arrs::unsetKeysListValue(explode(':', $name), $_SESSION[$namespace]);
        }
    }

    /**
     * Очищает данные сессии всего указанного пространства имен
     *
     * @param string $namespace Пространство имен
     */
    public static function clear($namespace = self::NAME_SPACE)
    {
        $_SESSION[$namespace] = [];
    }

}
