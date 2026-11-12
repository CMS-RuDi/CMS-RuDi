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

function mod_user_image($mod, $cfg)
{
    $inDB = \cms\db::getInstance();

    $db = clone $inDB;
    $db->select('u.id', 'uid');
    $db->select('u.nickname', 'author');
    $db->select('u.login', 'login');
    $db->select('i.imageurl');
    $db->select('i.title');
    $db->select('i.id');
    $db->select('pr.gender', 'gender');
    $db->joinLeft('users', 'u', 'u.id = i.user_id');
    $db->joinLeft('user_profiles', 'pr', 'pr.user_id = u.id');
    $db->joinLeft('user_albums', 'a', 'a.id = i.album_id');
    $db->filterEqual('i.allow_who', 'all');
    $db->filterEqual('u.is_deleted', 0);
    $db->filterEqual('u.is_locked', 0);
    $db->filterGt('i.album_id', 0);
    $db->filterEqual('a.allow_who', 'all');
    $db->orderBy('RAND()');
    $db->limit(0, 1);

    $usr = $db->getItem('user_photos');

    if ( !$usr ) {
        return false;
    }

    $usr['genderlink'] = cmsUser::getGenderLink($usr['uid'], $usr['author'], $usr['gender'], $usr['login']);

    $users   = array();
    $users[] = $usr;

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('users', $users)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
