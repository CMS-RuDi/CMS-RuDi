<?php

trait Singeltone
{

    protected static $_instance;

    protected function __construct()
    {

    }

    protected function __clone()
    {

    }

    protected function __sleep()
    {

    }

    protected function __wakeup()
    {

    }

    /**
     * Возвращает объект текущего класса
     *
     * @return self
     */
    public static function getInstance(...$params)
    {
        if ( self::$_instance === null ) {
            self::$_instance = new self(...$params);
        }

        return self::$_instance;
    }

}
