<?php

namespace components\content;

class model extends \cms\model
{

    const CATEGORY_TABLE = 'category';
    const CONTENT_TABLE  = 'content';

    protected $category_joined         = false;
    protected $users_joined            = false;
    protected $only_published_filtered = false;
    protected $is_arhived_filtered     = false;
    protected $old_indb_where_inserted = false;

    /**
     * Опции компонента
     *
     * @var array
     */
    public $config = [];

    /**
     * @var \cmsDatabase
     */
    public $inDB;

    public function __construct()
    {
        parent::__construct();

        $this->config = \cms\controller::loadOptions($this->name);
        $this->inDB   = \cmsDatabase::getInstance();

        \cms\lang::getInstance()->loadComponentLang($this->name);

        \cmsCore::loadLib('tags');
        \cmsCore::loadLib('karma');
    }

    public static function getDefaultConfig()
    {
        return [
            'readdesc'        => 0,
            'is_url_cyrillic' => 0,
            'rating'          => 1,
            'perpage'         => 15,
            'pt_show'         => 1,
            'pt_disp'         => 1,
            'pt_hide'         => 1,
            'autokeys'        => 1,
            'img_small_w'     => 100,
            'img_big_w'       => 200,
            'img_sqr'         => 1,
            'img_users'       => 1,
            'hide_root'       => 0,
            'watermark'       => 1
        ];
    }

    public function getCommentTarget($target, $target_id)
    {
        $result = [];

        switch ( $target ) {
            case 'article':
                $article = $this->db->getFields(self::CONTENT_TABLE, "id='" . $target_id . "'", 'seolink, title');

                if ( !$article ) {
                    return false;
                }

                $result['link']  = $this->getArticleURL(null, $article['seolink']);
                $result['title'] = $article['title'];

                break;
        }

        return ($result ? $result : false);
    }

    public function updateRatingHook($target, $item_id, $points)
    {
        if ( !$item_id || abs($points) != 1 ) {
            return false;
        }

        switch ( $target ) {
            case 'content':
                $sql = "UPDATE {#}" . self::CONTENT_TABLE . " SET rating = rating + (" . $points . ") WHERE id = '" . $item_id . "'";
                break;
        }

        $this->db->query($sql);

        return true;
    }

    public function getCategory($id_or_link, $id = null, $by_field = 'id')
    {
        if ( !$id_or_link ) {
            return false;
        }

        $cat = $this->inDB->getNsCategory($this->inDB->prefix . self::CATEGORY_TABLE, $id_or_link);

        return \cms\events::call('content.get_category', $cat);
    }

    /**
     * Возвращает подкатегории категории
     *
     * @return array|false
     */
    public function getSubCats($parent_id, $recurse = false, $left_key = 0, $right_key = 0)
    {
        if ( $recurse ) {
            $this->filterGt('NSLeft', $left_key)->
                    filterLt('NSRight', $right_key);
        }
        else {
            $this->filterEqual('parent_id', $parent_id);
        }

        $this->filterEqual('published', 1);
        $this->orderBy('NSLeft');

        $items = $this->get(self::CATEGORY_TABLE, function($item, $model) {
            $item['content_count'] = $model->getArticleCountFromCat($item['NSLeft'], $item['NSRight']);
            $item['url']           = $model->getCategoryURL(null, $item['seolink']);
            return $item;
        });

        if ( !empty($items) ) {
            $items = \translations::process(\cmsConfig::getConfig('lang'), 'content_category', $items);

            $items = \cms\events::call('content.get_sub_categories', $items);
        }

        return $items;
    }

    /**
     * Возвращает количество статей в категории и подкатегориях
     *
     * @return int
     */
    public function getArticleCountFromCat($left_key, $right_key)
    {
        $count = $this->resetFilters()->
                filterEqual('published', 1)->
                filterEqual('is_arhive', 0)->
                joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id = i.category_id AND cat.NSLeft >= ' . $left_key . ' AND cat.NSRight <= ' . $right_key)->
                getCount(self::CONTENT_TABLE);

        $this->resetFilters();

        return $count;
    }

    /**
     * Возвращает дерево категорий
     *
     * @return array
     */
    public function getCatsTree()
    {
        $this->selectList([ 'id', 'title', 'NSLeft', 'NSRight', 'NSLevel', 'seolink' ]);

        $this->filterGt('NSLevel', 0);

        $this->orderBy('NSLeft');

        $items = $this->get(self::CATEGORY_TABLE);

        if ( !empty($items) ) {
            $items = \cms\events::call('content.get_categories_tree', $items);

            $items = \translations::process(\cmsConfig::getConfig('lang'), 'content_category', $items);
        }

        return $items;
    }

    /**
     * Возвращает категории, доступные для публикования в них
     *
     * @return array
     */
    public function getPublicCats()
    {
        $inCore = \cmsCore::getInstance();
        $inUser = \cmsUser::getInstance();

        $nested_sets = $inCore->nestedSetsInit($this->inDB->prefix . self::CATEGORY_TABLE);
        $rootid      = $this->inDB->getNsRootCatId($this->inDB->prefix . self::CATEGORY_TABLE);

        $rs_rows = $nested_sets->SelectSubNodes($rootid);

        if ( $rs_rows ) {
            while ( $node = $this->inDB->fetch_assoc($rs_rows) ) {
                if ( $inUser->is_admin || \cmsCore::checkUserAccess('category', $node['id']) &&
                        ($node['is_public'] ||
                        ($node['modgrp_id'] && $node['modgrp_id'] == $inUser->group_id && \cmsUser::isUserCan('content/autoadd'))) ) {
                    $subcats[] = $node;
                }
            }
        }

        $subcats = \cms\events::call('content.get_publishing_categories', $subcats);

        return \translations::process(\cmsConfig::getConfig('lang'), 'content_category', $subcats);
    }

    /**
     * Условия выборки
     */
    public function whereCatIs($category_id)
    {
        return $this->filterEqual('category_id', $category_id);
    }

    public function whereUserIs($user_id)
    {
        return $this->filterEqual('user_id', $user_id);
    }

    public function whereThisAndNestedCats($left_key, $right_key)
    {
        $this->joinCategory();
        return $this->filter("cat.NSLeft >= '" . $left_key . "' AND cat.NSRight <= '" . $right_key . "' AND cat.parent_id > 0");
    }

    /**
     * Получаем статьи по заданным параметрам
     *
     * @return array
     */
    public function getArticlesList($only_published = true)
    {
        $this->selectList([
            'cat.title'   => 'cat_title',
            'cat.seolink' => 'catseolink',
            'cat.showdesc',
            'u.nickname'  => 'author',
            'u.login'     => 'user_login'
        ]);

        $this->joinCategory()->
                joinUsers()->
                filterIsArhived()->
                filterOldInDBWhere();

        if ( $only_published ) {
            $this->filterOnlyPublished();
        }

        if ( !empty($this->inDB->order_by) && empty($this->order_by) ) {
            $this->orderBy(trim(str_replace([ 'ORDER BY', 'con.' ], [ '', 'i.' ], $this->inDB->order_by)));
        }

        if ( !empty($this->inDB->group_by) && empty($this->group_by) ) {
            $this->groupBy(trim(str_replace([ 'GROUP BY', 'con.' ], [ '', 'i.' ], $this->inDB->group_by)));
        }

        if ( $this->inDB->limit && empty($this->limits) ) {
            $this->limit = $this->inDB->limit;
        }

        $this->inDB->resetConditions();

        $items = $this->get(self::CONTENT_TABLE, function($item, $model) {
            $item['fpubdate']  = \cmsCore::dateFormat($item['pubdate']);
            $item['ffpubdate'] = date('c', strtotime($item['pubdate']));
            $item['tagline']   = \cmsTagLine('content', $item['id'], true);
            $item['comments']  = \cmsCore::getCommentsCount('article', $item['id']);
            $item['url']       = $model->getArticleURL(null, $item['seolink']);
            $item['cat_url']   = $model->getCategoryURL(null, $item['catseolink']);
            $item['image']     = (file_exists(PATH . '/images/photos/small/article' . $item['id'] . '.jpg') ? 'article' . $item['id'] . '.jpg' : '');
            return $item;
        });

        if ( !empty($items) ) {
            $items = \cms\events::call('content.get_items', $items);

            $items = \translations::process(\cmsConfig::getConfig('lang'), 'content_content', $items);
        }

        return $items;
    }

    /**
     * Возвращает количество статей по заданным параметрам
     *
     * @return int
     */
    public function getArticlesCount($only_published = true)
    {
        if ( $only_published ) {
            $this->filterOnlyPublished();
        }

        $this->filterIsArhived()->
                filterOldInDBWhere();

        if ( !empty($this->inDB->group_by) && empty($this->group_by) ) {
            $this->groupBy(trim(str_replace([ 'GROUP BY', 'con.' ], [ '', 'i.' ], $this->inDB->group_by)));
        }

        return $this->getCount(self::CONTENT_TABLE);
    }

    /**
     * Переносит просроченые статьи в архив
     *
     * @return bool
     */
    public function moveArticlesToArchive()
    {
        return $this->db->update(self::CONTENT_TABLE, 'is_end = 1 AND enddate < NOW()', [ 'is_arhive' => 1 ]);
    }

    /**
     * Получает статью
     *
     * @return array
     */
    public function getArticle($id_or_link)
    {
        $this->selectList([
            'cat.title'    => 'cat_title',
            'cat.id'       => 'cat_id',
            'cat.NSLeft'   => 'leftkey',
            'cat.NSRight'  => 'rightkey',
            'cat.modgrp_id',
            'cat.showtags' => 'showtags',
            'cat.seolink'  => 'catseolink',
            'cat.cost',
            'u.nickname'   => 'author',
            'u.login'      => 'user_login'
        ]);

        $this->joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id = i.category_id');
        $this->joinLeft('users', 'u', 'u.id = i.user_id');

        if ( is_numeric($id_or_link) ) {
            return $this->getItemById(self::CONTENT_TABLE, $id_or_link);
        }
        else {
            return $this->getItemByField(self::CONTENT_TABLE, 'seolink', $id_or_link);
        }
    }

    /**
     * Изменяет порядок статей
     *
     * @return bool
     */
    public function moveItem($item_id, $cat_id, $dir)
    {
        $sign = $dir > 0 ? '+' : '-';

        $current = $this->db->getField(self::CONTENT_TABLE, 'id = ' . $item_id, 'ordering');

        if ( $current === false ) {
            return false;
        }

        if ( $dir > 0 ) {
            // движение вверх
            // у элемента следующего за текущим нужно уменьшить порядковый номер
            $this->db->query("UPDATE {#}" . self::CONTENT_TABLE . " SET ordering = ordering-1 WHERE category_id = '" . $cat_id . "' AND ordering = " . $current + 1 . " LIMIT 1");
        }
        if ( $dir < 0 ) {
            // движение вниз
            // у элемента предшествующего текущему нужно увеличить порядковый номер
            $this->db->query("UPDATE {#}" . self::CONTENT_TABLE . " SET ordering = ordering+1 WHERE category_id = '" . $cat_id . "' AND ordering = " . $current - 1 . " LIMIT 1");
        }

        $this->db->query('UPDATE {#}' . self::CONTENT_TABLE . ' SET ordering = ordering ' . $sign . ' 1 WHERE id = ' . $item_id . ' LIMIT 1');

        return true;
    }

    /**
     * Обновляет ссылки на статьи в категории и вложенных в нее
     * Подразумевается, что заголовок категории или поле url изменен заранее
     *
     * @return bool
     */
    public function updateArticlesSeoLink($cat_id)
    {
        // получаем все статьи категории и вложенных в нее
        $items = $this->getNestedArticles($cat_id);

        if ( !$items ) {
            return false;
        }

        foreach ( $items as $item ) {
            $seolink = $this->getSeoLink($item);

            $this->db->update(self::CONTENT_TABLE, 'id=' . $item['id'] . ' LIMIT 1', [ 'seolink' => $seolink ], false);

            $this->updateContentCommentsLink($item['id']);
        }

        // Обновляем ссылки меню на статьи
        $this->updateContentMenu();

        return true;
    }

    /**
     * генерирует сеолинк для статьи
     *
     * @param array $article Полный массив данных, включая id
     *
     * @return str
     */
    public function getSeoLink($article)
    {
        $seolink = '';

        $cat = $this->inDB->getNsCategory($this->inDB->prefix . self::CATEGORY_TABLE, $article['category_id']);

        $path_list = $this->inDB->getNsCategoryPath($this->inDB->prefix . self::CATEGORY_TABLE, $cat['NSLeft'], $cat['NSRight'], 'id, title, NSLevel, seolink, url');

        if ( $path_list ) {
            foreach ( $path_list as $pcat ) {
                $seolink .= \cmsCore::strToURL(($pcat['url'] ? $pcat['url'] : $pcat['title']), $this->config['is_url_cyrillic']) . '/';
            }
        }

        $seolink .= \cmsCore::strToURL(($article['url'] ? $article['url'] : $article['title']), $this->config['is_url_cyrillic']);

        if ( !empty($article['id']) ) {
            $where = ' AND id<>' . $article['id'];
        }
        else {
            $where = '';
        }

        $is_exists = $this->db->getField(self::CONTENT_TABLE, "seolink='" . $this->db->escape($seolink) . "'" . $where, 'id');

        if ( $is_exists ) {
            $seolink .= '-' . (!empty($article['id']) ? $article['id'] : uniqid());
        }

        return $seolink;
    }

    /**
     * Возвращает урл статьи параметр $menuid устаревший, оставлен для совместимости
     *
     * @return str
     */
    public static function getArticleURL($menuid, $seolink, $page = 1)
    {
        if ( (is_numeric($page) && $page > 1) || is_string($page) ) {
            $page_section = '/page-' . $page;
        }
        else {
            $page_section = '';
        }

        $url = '/' . $seolink . $page_section . '.html';

        return $url;
    }

    /**
     * Возвращает урл категории параметр $menuid устаревший, оставлен для совместимости
     *
     * @return str
     */
    public static function getCategoryURL($menuid, $seolink, $page = 1, $pagetag = false)
    {
        if ( !$pagetag ) {
            $page_section = ($page > 1 ? '/page-' . $page : '');
        }
        else {
            $page_section = '/page-%page%';
        }

        $url = '/' . $seolink . $page_section;

        return $url;
    }

    /**
     * Удаляет статью
     *
     * @return true
     */
    public function deleteArticle($item_id)
    {
        \cms\events::call('content.delete_item', $item_id);

        $this->db->delete(self::CONTENT_TABLE, 'id=' . $item_id, 1);
        $this->db->delete('tags', "target='content' AND item_id=" . $item_id);

        \cmsCore::clearAccess($item_id, 'material');

        \cmsActions::removeObjectLog('add_article', $item_id);

        @unlink(PATH . '/images/photos/small/article' . $item_id . '.jpg');
        @unlink(PATH . '/images/photos/medium/article' . $item_id . '.jpg');

        \cmsCore::deleteRatings('content', $item_id);
        \cmsCore::deleteComments('article', $item_id);

        \translations::deleteTargetTranslation('content_content', $item_id);

        return true;
    }

    /**
     * Удаляет список статей
     *
     * @param array $items Массив с id материалов для удаления
     *
     * @return true
     */
    public function deleteArticles($items)
    {
        foreach ( $items as $item_id ) {
            $this->deleteArticle($item_id);
        }

        return true;
    }

    /**
     * Добавляет статью
     *
     * @param array $article Данные материала
     *
     * @return int
     */
    public function addArticle($article)
    {
        $article = \cms\events::call('content.add_item', $article);

        if ( $article['url'] ) {
            $article['url'] = \cmsCore::strToURL($article['url'], $this->config['is_url_cyrillic']);
        }

        // получаем значение порядка последней статьи
        $last_ordering       = (int) $this->db->getField(self::CONTENT_TABLE, "category_id = '" . $article['category_id'] . "' ORDER BY ordering DESC", 'ordering');
        $article['ordering'] = $last_ordering + 1;

        $article['id'] = $this->db->insert(self::CONTENT_TABLE, $article);

        if ( $article['id'] ) {
            $article['seolink'] = $this->getSeoLink($article);

            $this->db->update(self::CONTENT_TABLE, 'id=' . $article['id'] . ' LIMIT 1', [ 'seolink' => $article['seolink'] ]);

            \cmsInsertTags($article['tags'], 'content', $article['id']);

            if ( $article['published'] ) {
                \cms\events::call('content.add_item_done', $article);
            }
        }

        return $article['id'] ? $article['id'] : false;
    }

    /**
     * Обновляет статью
     *
     * @return true
     */
    public function updateArticle($item_id, $article, $not_upd_seo = false)
    {
        $article['id'] = $item_id;

        if ( !$not_upd_seo ) {
            if ( @$article['url'] ) {
                $article['url'] = \cmsCore::strToURL($article['url'], $this->config['is_url_cyrillic']);
            }

            $article['seolink'] = $this->getSeoLink($article);
        }
        else {
            unset($article['seolink']);
            unset($article['url']);
        }

        if ( !$article['user_id'] ) {
            $article['user_id'] = \cmsUser::getInstance()->id;
        }

        $article = \cms\events::call('content.update_item', $article);

        $this->db->update(self::CONTENT_TABLE, 'id=' . $item_id, $article);

        if ( !$not_upd_seo ) {
            $this->updateContentCommentsLink($id);
        }

        \cmsInsertTags($article['tags'], 'content', $item_id);

        return true;
    }

    /**
     * Обновляет ссылки меню на категории
     *
     * @return bool
     */
    public function updateCatMenu()
    {
        return $this->db->query("UPDATE cms_menu m, {#}" . self::CATEGORY_TABLE . " cat SET m.link = CONCAT('/', cat.seolink) WHERE m.linkid = cat.id AND m.linktype = 'category'");
    }

    /**
     * Обновляет ссылки меню на статьи
     *
     * @return bool
     */
    public function updateContentMenu()
    {
        return $this->db->query("UPDATE cms_menu m, {#}" . self::CONTENT_TABLE . " con SET m.link = CONCAT('/', con.seolink, '.html') WHERE m.linkid = con.id AND m.linktype = 'content'");
    }

    /**
     * Обновляет ссылки меню на статьи
     *
     * @return true
     */
    public function updateContentCommentsLink($item_id)
    {
        // Обновляем ссылки в комменатриях
        $this->db->query("UPDATE {#}comments c, {#}" . self::CONTENT_TABLE . " a SET c.target_link = CONCAT('/', a.seolink, '.html') WHERE a.id = '" . $item_id . "' AND c.target = 'article' AND c.target_id = a.id");

        // Обновляем ссылки в action
        $action = \cmsActions::getAction('add_comment');

        if ( $action ) {
            $this->db->query("UPDATE {#}actions_log log, {#}" . self::CONTENT_TABLE . " a SET log.target_url = CONCAT('/', a.seolink, '.html'), log.object_url = CONCAT('/', a.seolink, '.html#c', log.object_id) WHERE a.id = '" . $item_id . "' AND log.action_id = '" . $action['id'] . "' AND log.target_id = '" . $item_id . "'");
        }

        return true;
    }

    /**
     * Возвращает массив связанных статей с категорией
     *
     * @return array
     */
    public function getNestedArticles($category_id)
    {
        $cat = $this->inDB->getNsCategory($this->inDB->prefix . self::CATEGORY_TABLE, $category_id);

        $sql = "SELECT con.id, con.title, con.seolink, con.url, con.category_id
FROM {#}" . self::CONTENT_TABLE . " con
JOIN {#}" . self::CATEGORY_TABLE . " cat ON cat.id = con.category_id AND cat.NSLeft >= " . $cat['NSLeft'] . " AND cat.NSRight <= " . $cat['NSRight'];

        $result = $this->db->query($sql);

        if ( !$this->db->numRows($result) ) {
            return false;
        }

        $articles = array();

        while ( $article = $this->db->fetchAssoc($result) ) {
            $articles[] = $article;
        }

        return $articles ? $articles : false;
    }

    /**
     * Удаляет категорию
     *
     * @return bool
     */
    public function deleteCategory($id, $is_with_content = false)
    {
        $articles = $this->getNestedArticles($id);
        $rootid   = $this->inDB->getNsRootCatId($this->inDB->prefix . self::CATEGORY_TABLE);

        if ( $articles ) {
            foreach ( $articles as $article ) {
                // удаляем все вложенные статьи
                if ( $is_with_content ) {
                    $this->deleteArticle($article['id']);
                }
                else { // или переносим в корень и в архив
                    $this->db->query("UPDATE {#}" . self::CONTENT_TABLE . " SET category_id = '" . $rootid . "', is_arhive = 1, seolink = SUBSTRING_INDEX(seolink, '/', -1) WHERE id = '" . $article['id'] . "'");
                }
            }
        }

        \translations::deleteTargetTranslation('content_category', $id);

        return $this->inDB->deleteNS($this->inDB->prefix . self::CATEGORY_TABLE, $id);
    }

    /**
     * Возвращает фотографии из привязанного альбома
     *
     * @param str $album
     *
     * @return array
     */
    public function getCatPhotoAlbum($album)
    {
        if ( !$album ) {
            return [];
        }

        $album = @unserialize($album);
        if ( !$album || !is_array($album) || !@$album['id'] ) {
            return [];
        }

        $inPhoto = \cmsPhoto::getInstance();

        $p_a = $this->inDB->getNsCategory('cms_photo_albums', (int) $album['id']);
        if ( !$p_a ) {
            return [];
        }

        $p_a['title']   = $album['header'];
        $p_a['maxcols'] = $album['maxcols'];

        $inPhoto->whereAlbumIs((int) $album['id']);

        if ( !in_array($album['orderby'], [ 'title', 'pubdate', 'rating', 'hits' ]) ) {
            $album['orderby'] = 'pubdate';
        }

        if ( !in_array($album['orderto'], [ 'asc', 'desc' ]) ) {
            $album['orderto'] = 'desc';
        }

        $this->inDB->orderBy('f.' . $album['orderby'], $album['orderto']);

        $this->inDB->limit((int) $album['max']);

        $photos = $inPhoto->getPhotos();

        if ( !$photos ) {
            return [];
        }

        return [ 'album' => $p_a, 'photos' => $photos ];
    }

    public function moveToArhive($item_id)
    {
        return $this->db->query("UPDATE {#}" . self::CONTENT_TABLE . " SET is_arhive = 1 WHERE id = '" . $item_id . "' LIMIT 1");
    }

    public function moveFromArhive($item_id)
    {
        return $this->db->query("UPDATE {#}" . self::CONTENT_TABLE . " SET is_arhive = 0 WHERE id = '" . $item_id . "' LIMIT 1");
    }

    public function joinCategory()
    {
        if ( $this->category_joined ) {
            return $this;
        }

        $this->category_joined = true;

        return $this->joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id=i.category_id');
    }

    public function joinUsers()
    {
        if ( $this->users_joined ) {
            return $this;
        }

        $this->users_joined = true;

        return $this->joinLeft('users', 'u', 'u.id = i.user_id');
        ;
    }

    public function filterOnlyPublished()
    {
        if ( $this->only_published_filtered ) {
            return $this;
        }

        $this->only_published_filtered = true;

        $today = date('Y-m-d H:i:s');

        return $this->filter("i.published = 1 AND i.pubdate <= '" . $today . "' AND (i.is_end = 0 OR (i.is_end = 1 AND i.enddate >= '" . $today . "'))");
    }

    public function filterIsArhived()
    {
        if ( $this->is_arhived_filtered ) {
            return $this;
        }

        $this->is_arhived_filtered = true;

        return $this->filterEqual('is_arhive', 0);
    }

    protected function filterOldInDBWhere()
    {
        if ( $this->old_indb_where_inserted || empty($this->inDB->where) ) {
            return $this;
        }

        $this->old_indb_where_inserted = true;

        return $this->filter('1 ' . str_replace('con.', 'i.', $this->inDB->where));
    }

    public function resetFilters()
    {
        $this->category_joined         = false;
        $this->users_joined            = false;
        $this->is_arhived_filtered     = false;
        $this->only_published_filtered = false;
        $this->old_indb_where_inserted = false;

        return parent::resetFilters();
    }

}
