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

function mod_user_friend($mod, $cfg)
{
    $inDB   = \cms\db::getInstance();
    $inUser = cmsUser::getInstance();

    if ( !$inUser->id ) {
        return false;
    }

    $user_id = (int) $inUser->id;
    $view_type = $cfg['view_type'];

    $db = clone $inDB;
    $db->select('CASE WHEN i.from_id = ' . $user_id . ' THEN i.to_id WHEN i.to_id = ' . $user_id . ' THEN i.from_id END', 'user_id');
    $db->select('u.login');
    $db->select('u.nickname');

    if ( $view_type == 'table' ) {
        $db->select('u.is_deleted');
        $db->select('p.imageurl');
        $db->joinLeft('user_profiles', 'p', 'p.user_id = u.id');
    }

    $db->joinInner('online', 'o', 'o.user_id = CASE WHEN i.from_id = ' . $user_id . ' THEN i.to_id WHEN i.to_id = ' . $user_id . ' THEN i.from_id END');
    $db->joinLeft('users', 'u', 'u.id = o.user_id');
    $db->filterStart()
            ->filterEqual('i.from_id', $user_id)
            ->filterOr()
            ->filterEqual('i.to_id', $user_id)
            ->filterEnd()
            ->filterEqual('i.is_accepted', 1);

    $db->limit(0, (int) $cfg['limit']);

    $friends = array();
    $total   = 0;

    foreach ( $db->get('user_friends', false, false) as $friend ) {
        $friend['avatar']            = ($view_type == 'table') ? cmsUser::getUserAvatarUrl($friend['user_id'], 'small', $friend['imageurl'], $friend['is_deleted']) : false;
        $friend['user_link']         = cmsUser::getProfileLink($friend['login'], $friend['nickname']);
        $friends[$friend['user_id']] = $friend;
        $total++;
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('friends', $friends)->
            assign('total', $total)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
