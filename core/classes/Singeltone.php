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

    public static function getInstance()
    {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

}