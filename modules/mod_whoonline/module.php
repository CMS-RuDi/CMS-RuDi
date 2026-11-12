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

function mod_whoonline($mod, $cfg)
{
    $inDB = \cms\db::getInstance();

    if ( !isset($cfg['color_admin']) ) {
        $cfg['color_admin'] = '#FF0000';
    }

    if ( !isset($cfg['color_editor']) ) {
        $cfg['color_editor'] = '#009900';
    }

    $online_count = cmsUser::getOnlineCount();

    $users       = array();
    $today_users = array();

    if ( $online_count['users'] ) {
        $db = clone $inDB;
        $db->select('i.user_id', 'id');
        $db->select('MAX(u.login)', 'login');
        $db->select('MAX(u.nickname)', 'nickname');
        $db->select('MAX(p.gender)', 'gender');
        $db->joinLeft('users', 'u', 'u.id = i.user_id');
        $db->joinLeft('user_profiles', 'p', 'p.user_id = u.id');
        $db->filterEqual('u.is_locked', 0);
        $db->filterEqual('u.is_deleted', 0);
        $db->groupBy('i.user_id');

        $users = getUsersArray($db->get('online', false, false), $cfg);
    }

    if ( $cfg['show_today'] ) {
        $today = date("Y-m-d");

        $db = clone $inDB;
        $db->select('i.id', 'id');
        $db->select('i.nickname', 'nickname');
        $db->select('i.login', 'login');
        $db->select('p.gender', 'gender');
        $db->joinLeft('user_profiles', 'p', 'p.user_id = i.id');
        $db->filterEqual('i.is_locked', 0);
        $db->filterEqual('i.is_deleted', 0);
        $db->filter("DATE_FORMAT(i.logdate, '%Y-%m-%d') = '" . $inDB->escape($today) . "'");
        $db->orderBy('i.logdate', 'DESC');

        $today_users = getUsersArray($db->get('users', false, false), $cfg);
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('cfg', $cfg)->
            assign('users', $users)->
            assign('guests', $online_count['guests'])->
            assign('today_users', $today_users)->
            display($cfg['tpl']);

    return true;
}

function getUsersArray($rows, $cfg)
{
    $users = array();

    foreach ( $rows as $usr ) {
        if ( $cfg['admin_editor'] ) {
            if ( cmsUser::userIsAdmin($usr['id']) ) {

                $usr['genderlink'] = cmsUser::getGenderLink($usr['id'], $usr['nickname'], $usr['gender'], $usr['login'], "color:{$cfg['color_admin']}");
            }
            elseif ( cmsUser::userIsEditor($usr['id']) ) {
                $usr['genderlink'] = cmsUser::getGenderLink($usr['id'], $usr['nickname'], $usr['gender'], $usr['login'], "color:{$cfg['color_editor']}");
            }
            else {
                $usr['genderlink'] = cmsUser::getGenderLink($usr['id'], $usr['nickname'], $usr['gender'], $usr['login']);
            }
        }
        else {
            $usr['genderlink'] = cmsUser::getGenderLink($usr['id'], $usr['nickname'], $usr['gender'], $usr['login']);
        }

        $users[] = $usr['genderlink'];
    }

    return $users;
}
