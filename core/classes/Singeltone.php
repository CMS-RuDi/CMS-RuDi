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

    public function __sleep()
    {

    }

    public function __wakeup()
    {

    }

    /**
     * Возвращает объект текущего класса
     *
     * @return self
     */
    public static function getInstance(...$params)
    {
        if ( static::$_instance === null ) {
            static::$_instance = new static(...$params);
        }

        return static::$_instance;
    }
}
