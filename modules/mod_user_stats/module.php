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

function mod_user_stats($mod, $cfg)
{
    $inDB = \cms\db::getInstance();
    cmsCore::loadLanguage('components/users');

    global $_LANG;

    if ( !isset($cfg['show_total']) ) {
        $cfg['show_total'] = 1;
    }

    if ( !isset($cfg['show_online']) ) {
        $cfg['show_online'] = 1;
    }

    if ( !isset($cfg['show_gender']) ) {
        $cfg['show_gender'] = 1;
    }

    if ( !isset($cfg['show_city']) ) {
        $cfg['show_city'] = 1;
    }

    $total_usr = cmsUser::getCountAllUsers();

    if ( $cfg['show_gender'] ) {
        $gender_stats            = array();
        //male
        $gender_stats['male']    = \cms\db::cloneOf($inDB)->joinInner('user_profiles', 'p', 'p.user_id = i.id')
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->filterEqual('p.gender', 'm')
                ->getCount('users');
        //female
        $gender_stats['female']  = \cms\db::cloneOf($inDB)->joinInner('user_profiles', 'p', 'p.user_id = i.id')
                ->filterEqual('i.is_locked', 0)
                ->filterEqual('i.is_deleted', 0)
                ->filterEqual('p.gender', 'f')
                ->getCount('users');
        //unknown
        $gender_stats['unknown'] = $total_usr - $gender_stats['male'] - $gender_stats['female'];
    }

    if ( $cfg['show_city'] ) {
        $db = clone $inDB;
        $db->select("IF (p.city != '', p.city, '" . $inDB->escape($_LANG['NOT_DECIDE']) . "')", 'city');
        $db->select('COUNT(p.user_id)', 'count');
        $db->joinLeft('user_profiles', 'p', 'p.user_id = i.id');
        $db->filterEqual('i.is_locked', 0);
        $db->filterEqual('i.is_deleted', 0);
        $db->groupBy('p.city');

        $city_stats = array();

        foreach ( $db->get('users', false, false) as $row ) {
            if ( $row['city'] != $_LANG['NOT_DECIDE'] ) {
                $row['href'] = '/users/city/' . urlencode($row['city']);
            }
            else {
                $row['href'] = '';
            }
            $row['city']  = icms_ucfirst(mb_strtolower($row['city']));
            $city_stats[] = $row;
        }
    }

    if ( $cfg['show_online'] ) {
        $people = cmsUser::getOnlineCount();
    }

    if ( $cfg['show_bday'] ) {
        $bday = cmsUser::getBirthdayUsers();
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('cfg', $cfg)->
            assign('total_usr', $total_usr)->
            assign('gender_stats', $gender_stats)->
            assign('city_stats', $city_stats)->
            assign('usr_online', cmsUser::sessionGet('usr_online'))->
            assign('people', $people)->
            assign('bday', $bday)->
            display($cfg['tpl']);

    return true;
}
