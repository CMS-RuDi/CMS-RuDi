<?php

namespace graphics\info;

class iptc extends \graphics\abstracts\info
{

    /**
     * @var array
     */
    protected $fields = [
        'headline'               => '2#105',
        'caption'                => '2#120',
        'location'               => '2#092',
        'city'                   => '2#090',
        'state'                  => '2#095',
        'country'                => '2#101',
        'countryCode'            => '2#100',
        'photographerName'       => '2#080',
        'credit'                 => '2#110',
        'photographerTitle'      => '2#085',
        'source'                 => '2#115',
        'copyright'              => '2#116',
        'objectName'             => '2#005',
        'captionWriters'         => '2#122',
        'instructions'           => '2#040',
        'category'               => '2#015',
        'supplementalCategories' => '2#020',
        'transmissionReference'  => '2#103',
        'urgency'                => '2#010',
        'keywords'               => '2#025',
        'date'                   => '2#055',
        'time'                   => '2#060',
    ];

    protected function __construct($raw_data)
    {
        $this->raw_data = $raw_data;

        $this->charsetDecode();

        foreach ( $this->fields as $field => $key ) {
            if ( isset($this->raw_data[$key]) ) {
                $this->data[$field] = $this->raw_data[$key];
            }
        }
    }

    public static function load($bytes)
    {
        getimagesizefromstring($bytes, $info);

        $iptc = (isset($info['APP13'])) ? iptcparse($info['APP13']) : [];

        return (empty($iptc) ? false : new self($iptc));
    }

    public static function read($file)
    {
        getimagesize($file, $info);

        $iptc = (isset($info['APP13'])) ? iptcparse($info['APP13']) : [];

        return (empty($iptc) ? false : new self($iptc));
    }

    public function getDateCreated()
    {
        $date = $this->get('date');
        $time = $this->get('time');

        if ( $date && $time ) {
            return new \DateTime($date . ' ' . $time);
        }

        return null;
    }

    public function get($name, $single = true)
    {
        $name = lcfirst($name);

        if ( isset($this->data[$name]) ) {
            if ( $single && is_array($this->data[$name]) ) {
                return $this->data[$name][0];
            }

            return $this->data[$name];
        }

        return false;
    }

    //========================================================================//

    protected function charsetDecode()
    {
        $data = [];

        foreach ( $this->raw_data as $field => $values ) {
            // convert values to UTF-8 if needed
            for ( $i = 0; $i < count($values); $i++ ) {
                if ( !self::seemsUtf8($values[$i]) ) {
                    $values[$i] = utf8_decode($values[$i]);
                }
            }

            $data[$field] = $values;
        }

        $this->raw_data = $data;
    }

    protected static function seemsUtf8($str)
    {
        $length = strlen($str);

        for ( $i = 0; $i < $length; $i++ ) {
            $c = ord($str[$i]);

            if ( $c < 0x80 ) {
                $n = 0; // 0bbbbbbb
            }
            elseif ( ($c & 0xE0) == 0xC0 ) {
                $n = 1; // 110bbbbb
            }
            elseif ( ($c & 0xF0) == 0xE0 ) {
                $n = 2; // 1110bbbb
            }
            elseif ( ($c & 0xF8) == 0xF0 ) {
                $n = 3; // 11110bbb
            }
            elseif ( ($c & 0xFC) == 0xF8 ) {
                $n = 4; // 111110bb
            }
            elseif ( ($c & 0xFE) == 0xFC ) {
                $n = 5; // 1111110b
            }
            else {
                return false; // Does not match any model
            }

            for ( $j = 0; $j < $n; $j++ ) { # // bytes matching 10bbbbbb follow ?
                if ( ( ++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80) ) {
                    return false;
                }
            }
        }

        return true;
    }

}
