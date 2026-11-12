<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2017
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class translations
{

    private $inDB;
    private $fieldsset_id     = 0;
    private $fields           = array();
    private $lang             = '';
    private $id_field;
    private $field_compliance;
    private static $instances = array();

    private function __construct($target)
    {
        // Отдельное состояние builder'а на каждый набор переводов
        $this->inDB = clone \cms\db::getInstance();
        $this->loadFieldSet($target);
    }

    private static function init($target, $lang = '', $id_field = 'id', $field_compliance = array())
    {
        if ( !isset(self::$instances[$target]) ) {
            self::$instances[$target] = new self($target, $id_field, $field_compliance);
        }

        self::$instances[$target]->lang             = $lang;
        self::$instances[$target]->id_field         = $id_field;
        self::$instances[$target]->field_compliance = $field_compliance;

        return self::$instances[$target];
    }

    private function loadFieldSet($target)
    {
        $f = $this->inDB->filterEqual('target', $target)->getItem('translations_fields');

        // getItem() восстанавливает переданные ему фильтры, поэтому чистим состояние
        $this->inDB->resetFilters();

        if ( $f ) {
            $this->fieldsset_id = $f['id'];
            $this->fields       = cmsCore::yamlToArray($f['fields']);
        }
    }

    /**
     * Регистрирует поля подлежащие переводу
     * @param str $target Цель
     * @param array $fields массив подлежащих переводу полей и их типов 'поле'=>'str|html'
     */
    public static function registerFields($target, $fields)
    {
        \cms\db::getInstance()->insert('translations_fields', array(
            'target' => $target,
            'fields' => cmsCore::arrayToYaml($fields)
                ), false, 'json', true);
    }

    public static function getFields($target)
    {
        return self::init($target)->fields;
    }

    public static function getFieldsetId($target)
    {
        return self::init($target)->fieldsset_id;
    }

    /**
     * Ищет и применяет перевод для возможных полей
     * @param str $lang Язык возвращаемого перевода
     * @param str $target Для какой цели ищем
     * @param array $item Массив, в котором нужно заменить значения полей
     * @return array
     */
    public static function process($lang, $target, $item, $id_field = 'id', $field_compliance = array())
    {
        if ( !cmsConfig::getConfig('is_change_lang') ) {
            return $item;
        }

        $thisObj = self::init($target, $lang, $id_field, $field_compliance);

        if ( !$thisObj->fieldsset_id || !$item || !is_array($item) ) {
            return $item;
        }

        return $thisObj->get($item);
    }

    /**
     *
     * @param str $lang  Язык перевода
     * @param str $target Для какой цели ищем
     * @param int $target_id ID цели
     * @return mixed
     */
    public static function getTranslation($lang, $target, $target_id)
    {
        $thisObj = self::init($target, $lang);

        if ( !$thisObj->fieldsset_id || !$target_id ) {
            return false;
        }

        $d = $thisObj->inDB
                ->filterEqual('target_id', $target_id)
                ->filterEqual('fieldsset_id', $thisObj->fieldsset_id)
                ->filterEqual('lang', $thisObj->lang)
                ->getItem('translations');

        // getItem() восстанавливает переданные ему фильтры, поэтому чистим состояние
        $thisObj->inDB->resetFilters();

        if ( !$d ) {
            return false;
        }

        $d['data'] = cmsCore::yamlToArray($d['data']);

        return $d;
    }

    public static function deleteTargetTranslation($target, $target_id)
    {
        $thisObj = self::init($target);

        if ( !$thisObj->fieldsset_id || !$target_id ) {
            return false;
        }

        $thisObj->inDB
                ->filterEqual('target_id', $target_id)
                ->filterEqual('fieldsset_id', $thisObj->fieldsset_id)
                ->deleteFiltered('translations');
    }

    private function get($item)
    {
        // флаг, что массив многомерный
        $first               = current($item);
        $is_multidimensional = is_array($first);

        // простой массив
        if ( !$is_multidimensional && !empty($item[$this->id_field]) ) {
            $this->inDB->filterEqual('target_id', $item[$this->id_field]);
            $has_filter = true;
        }

        // многомерный массив
        else {
            $has_filter = false;

            foreach ( $item as $k => $i ) {
                if ( !empty($i[$this->id_field]) ) {
                    $ids[$k] = $i[$this->id_field];
                }
            }

            if ( !empty($ids) ) {
                $target_ids = array_unique($ids);

                $this->inDB->filterIn('target_id', $target_ids);
                $has_filter = true;
            }
        }

        if ( !$has_filter ) {
            return $item;
        }

        $rows = $this->inDB
                ->selectOnly('target_id')
                ->select('data')
                ->filterEqual('fieldsset_id', $this->fieldsset_id)
                ->filterEqual('lang', $this->lang)
                ->get('translations', false, false);

        if ( !$rows ) {
            return $item;
        }

        // простой массив
        if ( !$is_multidimensional ) {
            $d    = $rows[0];
            $data = $this->processData($d['data']);

            return array_merge($item, $data);
        }
        // многомерный массив
        else {
            $translations = array();

            foreach ( $rows as $d ) {
                $translations[$d['target_id']] = $this->processData($d['data']);
            }

            foreach ( $ids as $key => $value ) {
                if ( isset($item[$key]) && isset($translations[$value]) ) {
                    // меняем поля
                    $item[$key] = array_merge($item[$key], $translations[$value]);
                }
            }

            return $item;
        }
    }

    private function processData($data)
    {
        $data = cmsCore::yamlToArray($data);

        // меняем названия полей при необходимости
        if ( $this->field_compliance ) {
            $intersect = array_intersect_key($data, $this->field_compliance);
            $data      = array_combine(array_values($this->field_compliance), array_values($intersect));
        }

        return $data;
    }

}
