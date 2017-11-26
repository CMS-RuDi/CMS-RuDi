<?php

namespace graphics\info;

class info extends \graphics\abstracts\info
{

    protected $types = [
        1  => 'gif',
        2  => 'jpg',
        3  => 'png',
        4  => 'swf',
        5  => 'psd',
        6  => 'bmp',
        7  => 'tiff_i',
        8  => 'tiff_m',
        9  => 'jpc',
        10 => 'jp2',
        11 => 'jpx'
    ];

    protected function __construct($raw_data)
    {
        $this->raw_data = $raw_data;

        $this->data['orientation'] = 'square';
        $this->data['width']       = $this->raw_data[0];
        $this->data['height']      = $this->raw_data[1];
        $this->data['type']        = $this->types[$this->raw_data[2]];
        $this->data['mime']        = $this->raw_data['mime'];
        $this->data['bits']        = $this->raw_data['bits'];
        $this->data['channels']    = $this->raw_data['channels'];

        if ( $this->raw_data[0] > $this->raw_data[1] ) {
            $this->data['orientation'] = 'landscape';
        }

        if ( $this->raw_data[0] < $this->raw_data[1] ) {
            $this->data['orientation'] = 'portrait';
        }
    }

    public static function load($bytes)
    {
        return new self(getimagesizefromstring($bytes));
    }

    public static function read($file)
    {
        return new self(getimagesize($file));
    }

}
