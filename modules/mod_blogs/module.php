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

function mod_blogs($mod, $cfg) {
    $inDB = \cms\db::getInstance();

    $default_cfg = array(
        'sort' => 'pubdate',
        'owner' => 'user',
        'shownum' => 5,
        'minrate' => 0,
        'blog_id' => 0,
        'showrss' => 1
    );

    $cfg = array_merge($default_cfg, $cfg);

    $inBlog = cmsBlogs::getInstance();
    $inBlog->owner = $cfg['owner'];

    if ($cfg['owner'] == 'club') {
        $model = new cms_model_clubs();
        $inDB->select('b.user_id', 'bloglink');
    } else {
        $model = new cms_model_blogs();
    }

    // получаем аватары владельцев
    $inDB->select('MIN(up.imageurl)', 'imageurl');
    $inDB->select('MIN(img.fileurl)', 'fileurl');
    $inDB->joinLeft('user_profiles', 'up', 'up.user_id = i.user_id');
    $inDB->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'blogs'");

    $inBlog->whereOnlyPublic();

    if ($cfg['minrate']) {
        $inBlog->ratingGreaterThan($cfg['minrate']);
    }

    if ($cfg['blog_id']) {
        $inBlog->whereBlogIs($cfg['blog_id']);
    }

    $inDB->orderBy('i.' . $cfg['sort'], 'DESC')->groupBy('i.id');
    $inDB->limit($cfg['shownum']);

    $posts = $model->getPosts(false);
    if (!$posts) {
        return false;
    }

    cmsPage::initTemplate('modules', $cfg['tpl'])->
            assign('posts', $posts)->
            assign('cfg', $cfg)->
            display($cfg['tpl']);

    return true;
}
