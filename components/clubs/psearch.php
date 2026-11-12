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


if ( !defined('VALID_CMS') ) {
    die('ACCESS DENIED');
}

function search_clubs($query, $look)
{
    $inDB        = \cms\db::getInstance();
    $searchModel = cms_model_search::initModel();

    global $_LANG;

    $model = new cms_model_clubs();

    /////// поиск по клубным блогам //////////

    $db = clone $inDB;
    $db->select('i.*');
    $db->select('cat.title', 'cat_title');
    $db->select('cat.id', 'cat_id');
    $db->select('cat.owner');
    $db->select('cat.user_id');
    $db->select('img.fileurl');
    $db->joinInner('blogs', 'cat', "cat.id = i.blog_id AND cat.allow_who = 'all' AND cat.owner = 'club'");
    $db->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'clubs'");
    $db->filter("MATCH(i.title, i.content) AGAINST ('" . $query . "' IN BOOLEAN MODE)");
    $db->filterEqual('i.published', 1);
    $db->limit(0, 100);

    foreach ( $db->get('blog_posts') as $item ) {
        $result_array = array();

        $result_array['link']        = $model->getPostURL($item['user_id'], $item['seolink']);
        $result_array['place']       = ' &laquo;' . $item['cat_title'] . '&raquo;';
        $result_array['placelink']   = $model->getBlogURL($item['user_id']);
        $result_array['description'] = $searchModel->getProposalWithSearchWord($item['content_html']);
        $result_array['title']       = $item['title'];
        $result_array['pubdate']     = $item['pubdate'];
        $result_array['imageurl']    = $item['fileurl'];
        $result_array['session_id']  = session_id();

        $searchModel->addResult($result_array);
    }

    /////// поиск по клубным фоткам //////////

    $db = clone $inDB;
    $db->select('i.*');
    $db->select('a.title', 'cat');
    $db->select('a.id', 'cat_id');
    $db->joinInner('photo_albums', 'a', "a.id = i.album_id AND a.published = 1 AND a.NSDiffer != ''");
    $db->filter("MATCH(i.title, i.description) AGAINST ('" . $query . "' IN BOOLEAN MODE)");
    $db->filterEqual('i.published', 1);

    foreach ( $db->get('photo_files') as $item ) {
        $result_array = array();

        $result_array['link']        = "/clubs/photo" . $item['id'] . ".html";
        $result_array['place']       = $_LANG['CLUBS_PHOTOALBUM'] . ' &laquo;' . $item['cat'] . '&raquo;';
        $result_array['placelink']   = '/clubs/photoalbum' . $item['cat_id'];
        $result_array['description'] = $searchModel->getProposalWithSearchWord($item['description']);
        $result_array['title']       = $item['title'];
        $result_array['pubdate']     = $item['pubdate'];
        $result_array['imageurl']    = HOST . '/images/photos/medium/' . $item['file'] ? '/images/photos/medium/' . $item['file'] : '';
        $result_array['session_id']  = session_id();

        $searchModel->addResult($result_array);
    }

    return;
}
