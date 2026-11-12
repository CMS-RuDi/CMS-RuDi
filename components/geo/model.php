<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cms_model_geo
{

    public function __construct()
    {
        cmsCore::loadLanguage('components/geo');
        $this->inDB = \cms\db::getInstance();
    }

    public static function getDefaultConfig()
    {
        return array(
            'class'      => 'geo',
            'autodetect' => 1
        );
    }

    private function getItems($table, $first)
    {
        $c[0] = $first;

        $items = $this->inDB->selectOnly('id')->select('name')->get($table);

        foreach ( $items as $data ) {
            $c[$data['id']] = $data['name'];
        }

        return $c;
    }

    public function getCountries()
    {
        global $_LANG;

        $this->inDB->orderBy('ordering, name');

        return $this->getItems('geo_countries', $_LANG['GEO_SELECT_COUNTRY']);
    }

    public function getRegions($country_id = false)
    {
        global $_LANG;

        if ( $country_id ) {
            $this->inDB->filterEqual('country_id', $country_id);
        }

        $this->inDB->orderBy('name');

        return $this->getItems('geo_regions', $_LANG['GEO_SELECT_REGION']);
    }

    public function getCities($region_id = false)
    {
        global $_LANG;

        if ( $region_id ) {
            $this->inDB->filterEqual('region_id', $region_id);
        }

        $this->inDB->orderBy('name');

        return $this->getItems('geo_cities', $_LANG['GEO_SELECT_CITY']);
    }

    public function getCityParents($city_id)
    {
        // Совместимость
        if ( !is_numeric($city_id) ) {
            $city = $this->getCity($city_id);

            if ( !$city ) {
                return false;
            }

            $city_id = $city['id'];
        }

        $city = \cms\db::cloneOf($this->inDB)->select('i.*')
                ->select('r.name', 'region_name')
                ->select('c.name', 'country_name')
                ->select('r.id', 'region_id')
                ->select('c.id', 'country_id')
                ->joinInner('geo_regions', 'r', 'r.id = i.region_id')
                ->joinInner('geo_countries', 'c', 'c.id = r.country_id')
                ->filterEqual('i.id', (int) $city_id)
                ->getItem('geo_cities');

        return $city ? $city : false;
    }

    public function getCity($id)
    {
        if ( is_numeric($id) ) {
            return $this->inDB->getItemById('geo_cities', (int) $id);
        }

        return $this->inDB->filterLike('name', $id)->getItem('geo_cities');
    }

}
