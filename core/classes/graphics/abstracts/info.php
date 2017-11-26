<?php

namespace graphics\abstracts;

abstract class info
{

    protected $data = [];
    protected $raw_data;

    public static function load($bytes)
    {
        return false;
    }

    public static function read($file)
    {
        return false;
    }

    public function get($name)
    {
        if ( isset($this->data[$name]) ) {
            return $this->data[$name];
        }

        return false;
    }

    public function getRaw($name)
    {
        if ( isset($this->raw_data[$name]) ) {
            return $this->raw_data[$name];
        }

        return false;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRawData()
    {
        return $this->raw_data;
    }

    public function __call($name, $arguments)
    {
        if ( substr($name, 0, 3) == 'get' ) {
            $name = substr($name, 3);

            return $this->get($name);
        }

        return false;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

}
