<?php

namespace graphics;

class info
{

    private $info            = [];
    private $formats         = [];
    private $support_formats = [ 'exif', 'info', 'iptc' ];

    public function __construct($formats = false)
    {
        $this->setFormats($formats);
    }

    public function setFormats($formats = false)
    {
        if ( is_array($formats) ) {
            $enformats = [];

            foreach ( $formats as $format ) {
                if ( in_array($format, $this->support_formats) ) {
                    $enformats[] = $format;
                }
            }
        }
        else {
            $enformats = [ $formats ];
        }

        $enformats = array_unique($enformats);

        $this->formats = $enformats;
    }

    public function load($bytes)
    {
        foreach ( $this->formats as $format ) {
            $class_name = '\\graphics\\info\\' . $format;

            if ( class_exists($class_name) ) {
                $class = $class_name::load($bytes);

                if ( $class !== false ) {
                    $this->info[$format] = $class;
                }
            }
        }
    }

    public function read($file)
    {
        foreach ( $this->formats as $format ) {
            $class_name = '\\graphics\\info\\' . $format;

            if ( class_exists($class_name) ) {
                $class = $class_name::read($file);

                if ( $class !== false ) {
                    $this->info[$format] = $class;
                }
            }
        }
    }

    //========================================================================//

    public function get($name, $format = false)
    {
        return $this->_get($name, $format);
    }

    public function getRaw($name, $format = false)
    {
        return $this->_get($name, $format, 'getRaw');
    }

    protected function _get($name, $format = false, $method = 'get')
    {
        $value = false;

        if ( $format === false ) {
            foreach ( $this->info as $info ) {
                $value = $info->{$method}($name);

                if ( !empty($value) ) {
                    break;
                }
            }
        }
        else {
            if ( isset($this->info[$format]) ) {
                $value = $this->info[$format]->{$method}($name);
            }
        }

        return $value;
    }

    //========================================================================//

    public function getData($format)
    {
        if ( isset($this->info[$format]) ) {
            return $this->info[$format]->getData();
        }

        return false;
    }

    public function getAllData()
    {
        $data = [];

        foreach ( $this->formats as $format ) {
            $dat = $this->getData($format);

            if ( is_array($dat) ) {
                $data[$format] = $dat;
            }
        }

        return $data;
    }

    //========================================================================//

    public function getRawData($format)
    {
        if ( isset($this->info[$format]) ) {
            return $this->info[$format]->getRawData();
        }

        return false;
    }

    public function getAllRawData()
    {
        $data = [];

        foreach ( $this->formats as $format ) {
            $dat = $this->getRawData($format);

            if ( is_array($dat) ) {
                $data[$format] = $dat;
            }
        }

        return $data;
    }

    //========================================================================//

    /**
     * Позволяет обращаться к нужному свойству нужного формата через обращение к переменной
     * вида getExifFnumber, getInfoMime
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        foreach ( $this->formats as $format ) {
            $prefix = 'get' . ucfirst($format);

            if ( substr($name, 0, strlen($prefix)) == $prefix ) {
                $nname = substr($name, strlen($prefix));

                return $this->get($nname, $format);
            }
        }

        return $this->get($name);
    }

}
