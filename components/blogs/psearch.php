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

function search_blogs($query, $look)
{
    $inDB        = \cms\db::getInstance();
    $searchModel = cms_model_search::initModel();

    global $_LANG;

    $db = clone $inDB;
    $db->select('i.*');
    $db->select('cat.title', 'cat_title');
    $db->select('cat.id', 'cat_id');
    $db->select('cat.owner');
    $db->select('cat.user_id');
    $db->select('cat.seolink', 'bloglink');
    $db->select('img.fileurl');
    $db->joinInner('blogs', 'cat', "cat.id = i.blog_id AND cat.allow_who = 'all' AND cat.owner = 'user'");
    $db->joinLeft('upload_images', 'img', "img.target_id = i.id AND img.target = 'blog_post' AND img.component = 'blogs'");
    $db->filter("MATCH(i.title, i.content) AGAINST ('" . $query . "' IN BOOLEAN MODE)");
    $db->filterEqual('i.published', 1);
    $db->limit(0, 100);

    $items = $db->get('blog_posts');

    if ( $items ) {
        cmsCore::loadLanguage('components/blogs');

        $model = new cms_model_blogs();

        foreach ( $items as $item ) {
            $result_array = array();

            $result_array['link']        = $model->getPostURL($item['bloglink'], $item['seolink']);
            $result_array['place']       = $_LANG['BLOG'] . ' &laquo;' . $item['cat_title'] . '&raquo;';
            $result_array['placelink']   = $model->getBlogURL($item['bloglink']);
            $result_array['description'] = $searchModel->getProposalWithSearchWord($item['content_html']);
            $result_array['title']       = $item['title'];
            $result_array['pubdate']     = $item['pubdate'];
            $result_array['imageurl']    = $item['fileurl'];
            $result_array['session_id']  = session_id();

            $searchModel->addResult($result_array);
        }
    }

    return;
}
