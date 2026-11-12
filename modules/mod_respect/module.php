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

function mod_respect($mod, $cfg) {
    $inDB = \cms\db::getInstance();

    if (!isset($cfg['view_aw'])) {
        $cfg['view_aw'] = 0;
    }

    if (!isset($cfg['limit'])) {
        $cfg['limit'] = 5;
    }

    if (!isset($cfg['order'])) {
        $cfg['order'] = 'desc';
    }

    if (!isset($cfg['show_awards'])) {
        $cfg['show_awards'] = 1;
    }

    $db = clone $inDB;
    $db->select('MAX(i.id)', 'id');
    $db->select('MIN(i.nickname)', 'nickname');
    $db->select('MIN(i.login)', 'login');
    $db->select('COUNT(a.id)', 'awards_count');
    $db->select('MIN(p.imageurl)', 'imageurl');
    $db->select('MIN(i.is_deleted)', 'is_deleted');
    $db->joinInner('user_profiles', 'p', 'p.user_id = i.id');
    $db->joinInner('user_awards', 'a', 'a.user_id = i.id');
    $db->filterEqual('i.is_deleted', 0);
    $db->filterEqual('i.is_locked', 0);

    if ($cfg['view_aw']) {
        $db->filterEqual('a.title', $cfg['view_aw']);
    }

    $db->groupBy('a.user_id');

    if ($cfg['order'] == 'rand') {
        $db->orderBy('RAND()');
    } else {
        $db->orderBy('awards_count', 'DESC');
    }

    $db->limit(0, (int) $cfg['limit']);

    $users = $db->get('users', false, false);

    if (!$users) {
        return false;
    }

    foreach ($users as $key => $user) {
        $user['avatar'] = cmsUser::getUserAvatarUrl($user['id'], 'small', $user['imageurl'], $user['is_deleted']);

        if ($cfg['show_awards']) {
            $user['awards'] = $inDB->getRows('user_awards', 'user_id = ' . (int) $user['id'], 'id, title');
        }

        $users[$key] = $user;
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('users', $users)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
