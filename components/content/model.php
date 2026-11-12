<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

namespace components\content;

class model extends \cms\model {
    const CATEGORY_TABLE = 'category';
    const CONTENT_TABLE = 'content';

    protected $category_joined = false;
    protected $users_joined = false;
    protected $only_published_filtered = false;
    protected $is_arhived_filtered = false;
    protected $old_indb_where_inserted = false;

    /**
     * Опции компонента
     *
     * @var array
     */
    public $config = [];

    /**
     * @var \cms\db
     */
    public $inDB;

    public function __construct() {
        parent::__construct();

        $this->config = \cms\controller::loadOptions($this->name);
        $this->inDB = \cms\db::getInstance();

        \cms\lang::getInstance()->loadComponentLang($this->name);

        \cmsCore::loadLib('tags');
        \cmsCore::loadLib('karma');
    }

    public static function getDefaultConfig() {
        return [
            'readdesc' => 0,
            'is_url_cyrillic' => 0,
            'rating' => 1,
            'perpage' => 15,
            'pt_show' => 1,
            'pt_disp' => 1,
            'pt_hide' => 1,
            'autokeys' => 1,
            'img_small_w' => 100,
            'img_big_w' => 200,
            'img_sqr' => 1,
            'img_users' => 1,
            'hide_root' => 0,
            'watermark' => 1
        ];
    }

    public function getCommentTarget($target, $target_id) {
        $result = [];

        switch ($target) {
            case 'article':
                $article = $this->filterEqual('id', (int) $target_id)->getItem(self::CONTENT_TABLE);

                if (!$article) {
                    return false;
                }

                $result['link'] = $this->getArticleURL(null, $article['seolink']);
                $result['title'] = $article['title'];

                break;
        }

        return ($result ? $result : false);
    }

    public function updateRatingHook($target, $item_id, $points) {
        if (!$item_id || abs($points) != 1) {
            return false;
        }

        switch ($target) {
            case 'content':
                $this->db->filterEqual('id', (int) $item_id)->
                        increment(self::CONTENT_TABLE, 'rating', (int) $points);
                break;
        }

        return true;
    }

    public function getCategory($id_or_link, $id = null, $by_field = 'id') {
        if (!$id_or_link) {
            return false;
        }

        $cat = $this->db->getNsCategory(self::CATEGORY_TABLE, $id_or_link);

        return \cms\events::call('content.get_category', $cat);
    }

    /**
     * Возвращает подкатегории категории
     *
     * @return array|false
     */
    public function getSubCats($parent_id, $recurse = false, $left_key = 0, $right_key = 0) {
        if ($recurse) {
            $this->filterGt('NSLeft', $left_key)->
                    filterLt('NSRight', $right_key);
        } else {
            $this->filterEqual('parent_id', $parent_id);
        }

        $this->filterEqual('published', 1);
        $this->orderBy('NSLeft');

        $items = $this->get(self::CATEGORY_TABLE, function ($item, $model) {
            $item['content_count'] = $model->getArticleCountFromCat($item['NSLeft'], $item['NSRight']);
            $item['url'] = $model->getCategoryURL(null, $item['seolink']);
            return $item;
        });

        if (!empty($items)) {
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
    public function getArticleCountFromCat($left_key, $right_key) {
        $count = $this->resetFilters()->
                filterEqual('published', 1)->
                filterEqual('is_arhive', 0)->
                joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id = i.category_id')->
                filterGtEqual('cat.NSLeft', $left_key)->
                filterLtEqual('cat.NSRight', $right_key)->
                getCount(self::CONTENT_TABLE);

        $this->resetFilters();

        return $count;
    }

    /**
     * Возвращает дерево категорий
     *
     * @return array
     */
    public function getCatsTree() {
        $this->selectList(['id', 'title', 'NSLeft', 'NSRight', 'NSLevel', 'seolink']);

        $this->filterGt('NSLevel', 0);

        $this->orderBy('NSLeft');

        $items = $this->get(self::CATEGORY_TABLE);

        if (!empty($items)) {
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
    public function getPublicCats() {
        $inCore = \cmsCore::getInstance();
        $inUser = \cmsUser::getInstance();

        $nested_sets = $inCore->nestedSetsInit(self::CATEGORY_TABLE);
        $rootid = $this->db->getNsRootCatId(self::CATEGORY_TABLE);

        $rs_rows = $nested_sets->SelectSubNodes($rootid);

        if ($rs_rows) {
            while ($node = $this->db->fetch_assoc($rs_rows)) {
                if ($inUser->is_admin || \cmsCore::checkUserAccess('category', $node['id']) &&
                        ($node['is_public'] ||
                        ($node['modgrp_id'] && $node['modgrp_id'] == $inUser->group_id && \cmsUser::isUserCan('content/autoadd')))) {
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
    public function whereCatIs($category_id) {
        return $this->filterEqual('category_id', $category_id);
    }

    public function whereUserIs($user_id) {
        return $this->filterEqual('user_id', $user_id);
    }

    public function whereThisAndNestedCats($left_key, $right_key) {
        $this->joinCategory();

        return $this->filterGtEqual('cat.NSLeft', $left_key)->
                        filterLtEqual('cat.NSRight', $right_key)->
                        filterGt('cat.parent_id', 0);
    }

    /**
     * Получаем статьи по заданным параметрам
     *
     * @return array
     */
    public function getArticlesList($only_published = true) {
        $this->selectList([
            'cat.title' => 'cat_title',
            'cat.seolink' => 'catseolink',
            'cat.showdesc',
            'u.nickname' => 'author',
            'u.login' => 'user_login'
        ]);

        $this->joinCategory()->
                joinUsers()->
                filterIsArhived()->
                filterOldInDBWhere();

        if ($only_published) {
            $this->filterOnlyPublished();
        }

        if (!empty($this->inDB->order_by) && empty($this->order_by)) {
            $this->orderBy(trim(str_replace(['ORDER BY', 'con.'], ['', 'i.'], $this->inDB->order_by)));
        }

        if (!empty($this->inDB->group_by) && empty($this->group_by)) {
            $this->groupBy(trim(str_replace(['GROUP BY', 'con.'], ['', 'i.'], $this->inDB->group_by)));
        }

        if ($this->inDB->limit && empty($this->limits)) {
            $this->limit = $this->inDB->limit;
        }

        $this->inDB->resetConditions();

        $items = $this->get(self::CONTENT_TABLE, function ($item, $model) {
            $item['fpubdate'] = \cmsCore::dateFormat($item['pubdate']);
            $item['ffpubdate'] = date('c', strtotime($item['pubdate']));
            $item['tagline'] = \cmsTagLine('content', $item['id'], true);
            $item['comments'] = \cmsCore::getCommentsCount('article', $item['id']);
            $item['url'] = $model->getArticleURL(null, $item['seolink']);
            $item['cat_url'] = $model->getCategoryURL(null, $item['catseolink']);
            $item['image'] = (file_exists(PATH . '/images/photos/small/article' . $item['id'] . '.jpg') ? 'article' . $item['id'] . '.jpg' : '');
            return $item;
        });

        if (!empty($items)) {
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
    public function getArticlesCount($only_published = true) {
        if ($only_published) {
            $this->filterOnlyPublished();
        }

        $this->filterIsArhived()->
                filterOldInDBWhere();

        if (!empty($this->inDB->group_by) && empty($this->group_by)) {
            $this->groupBy(trim(str_replace(['GROUP BY', 'con.'], ['', 'i.'], $this->inDB->group_by)));
        }

        return $this->getCount(self::CONTENT_TABLE);
    }

    /**
     * Переносит просроченые статьи в архив
     *
     * @return bool
     */
    public function moveArticlesToArchive() {
        return $this->db->filterEqual('is_end', 1)->
                        filterDateOlder('enddate', 0, 'DAY')->
                        updateFiltered(self::CONTENT_TABLE, ['is_arhive' => 1]);
    }

    /**
     * Получает статью
     *
     * @return array
     */
    public function getArticle($id_or_link) {
        $this->selectList([
            'cat.title' => 'cat_title',
            'cat.id' => 'cat_id',
            'cat.NSLeft' => 'leftkey',
            'cat.NSRight' => 'rightkey',
            'cat.modgrp_id',
            'cat.showtags' => 'showtags',
            'cat.seolink' => 'catseolink',
            'cat.cost',
            'u.nickname' => 'author',
            'u.login' => 'user_login'
        ]);

        $this->joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id = i.category_id');
        $this->joinLeft('users', 'u', 'u.id = i.user_id');

        if (is_numeric($id_or_link)) {
            return $this->getItemById(self::CONTENT_TABLE, $id_or_link);
        } else {
            return $this->getItemByField(self::CONTENT_TABLE, 'seolink', $id_or_link);
        }
    }

    /**
     * Изменяет порядок статей
     *
     * @return bool
     */
    public function moveItem($item_id, $cat_id, $dir) {
        $current = $this->db->filterEqual('id', (int) $item_id)->
                getFieldFiltered(self::CONTENT_TABLE, 'ordering');

        if ($current === false) {
            return false;
        }

        if ($dir > 0) {
            // движение вверх
            // у элемента следующего за текущим нужно уменьшить порядковый номер
            $neighbor = $this->db->filterEqual('category_id', (int) $cat_id)->
                    filterEqual('ordering', $current + 1)->
                    getItem(self::CONTENT_TABLE);

            if ($neighbor) {
                $this->db->filterEqual('id', (int) $neighbor['id'])->
                        decrement(self::CONTENT_TABLE, 'ordering');
            }
        }
        if ($dir < 0) {
            // движение вниз
            // у элемента предшествующего текущему нужно увеличить порядковый номер
            $neighbor = $this->db->filterEqual('category_id', (int) $cat_id)->
                    filterEqual('ordering', $current - 1)->
                    getItem(self::CONTENT_TABLE);

            if ($neighbor) {
                $this->db->filterEqual('id', (int) $neighbor['id'])->
                        increment(self::CONTENT_TABLE, 'ordering');
            }
        }

        $this->db->filterEqual('id', (int) $item_id)->
                increment(self::CONTENT_TABLE, 'ordering', ($dir > 0 ? 1 : -1));

        return true;
    }

    /**
     * Обновляет ссылки на статьи в категории и вложенных в нее
     * Подразумевается, что заголовок категории или поле url изменен заранее
     *
     * @return bool
     */
    public function updateArticlesSeoLink($cat_id) {
        // получаем все статьи категории и вложенных в нее
        $items = $this->getNestedArticles($cat_id);

        if (!$items) {
            return false;
        }

        foreach ($items as $item) {
            $seolink = $this->getSeoLink($item);

            $this->db->update(self::CONTENT_TABLE, (int) $item['id'], ['seolink' => $seolink]);

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
    public function getSeoLink($article) {
        $seolink = '';

        $cat = $this->db->getNsCategory(self::CATEGORY_TABLE, $article['category_id']);

        $path_list = $this->db->getNsCategoryPath(self::CATEGORY_TABLE, $cat['NSLeft'], $cat['NSRight'], 'id, title, NSLevel, seolink, url');

        if ($path_list) {
            foreach ($path_list as $pcat) {
                $seolink .= \cmsCore::strToURL(($pcat['url'] ? $pcat['url'] : $pcat['title']), $this->config['is_url_cyrillic']) . '/';
            }
        }

        $seolink .= \cmsCore::strToURL(($article['url'] ? $article['url'] : $article['title']), $this->config['is_url_cyrillic']);

        $exists_query = $this->db->filterEqual('seolink', $seolink);

        if (!empty($article['id'])) {
            $exists_query->filterNotEqual('id', (int) $article['id']);
        }

        $is_exists = $exists_query->getFieldFiltered(self::CONTENT_TABLE, 'id');

        if ($is_exists) {
            $seolink .= '-' . (!empty($article['id']) ? $article['id'] : uniqid());
        }

        return $seolink;
    }

    /**
     * Возвращает урл статьи параметр $menuid устаревший, оставлен для совместимости
     *
     * @return str
     */
    public static function getArticleURL($menuid, $seolink, $page = 1) {
        if ((is_numeric($page) && $page > 1) || is_string($page)) {
            $page_section = '/page-' . $page;
        } else {
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
    public static function getCategoryURL($menuid, $seolink, $page = 1, $pagetag = false) {
        if (!$pagetag) {
            $page_section = ($page > 1 ? '/page-' . $page : '');
        } else {
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
    public function deleteArticle($item_id) {
        \cms\events::call('content.delete_item', $item_id);

        $this->db->delete(self::CONTENT_TABLE, (int) $item_id);
        $this->db->filterEqual('target', 'content')->
                filterEqual('item_id', (int) $item_id)->
                deleteFiltered('tags');

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
    public function deleteArticles($items) {
        foreach ($items as $item_id) {
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
    public function addArticle($article) {
        $article = \cms\events::call('content.add_item', $article);

        if ($article['url']) {
            $article['url'] = \cmsCore::strToURL($article['url'], $this->config['is_url_cyrillic']);
        }

        // получаем значение порядка последней статьи
        $last_ordering = (int) $this->db->filterEqual('category_id', (int) $article['category_id'])->
                        orderBy('ordering', 'desc')->
                        getFieldFiltered(self::CONTENT_TABLE, 'ordering');
        $article['ordering'] = $last_ordering + 1;

        $article['id'] = $this->db->insert(self::CONTENT_TABLE, $article);

        if ($article['id']) {
            $article['seolink'] = $this->getSeoLink($article);

            $this->db->update(self::CONTENT_TABLE, (int) $article['id'], ['seolink' => $article['seolink']]);

            \cmsInsertTags($article['tags'], 'content', $article['id']);

            if ($article['published']) {
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
    public function updateArticle($item_id, $article, $not_upd_seo = false) {
        $article['id'] = $item_id;

        if (!$not_upd_seo) {
            if (@$article['url']) {
                $article['url'] = \cmsCore::strToURL($article['url'], $this->config['is_url_cyrillic']);
            }

            $article['seolink'] = $this->getSeoLink($article);
        } else {
            unset($article['seolink']);
            unset($article['url']);
        }

        if (!$article['user_id']) {
            $article['user_id'] = \cmsUser::getInstance()->id;
        }

        $article = \cms\events::call('content.update_item', $article);

        $article_data = $article;
        unset($article_data['id']);

        $this->db->update(self::CONTENT_TABLE, (int) $item_id, $article_data);

        if (!$not_upd_seo) {
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
    public function updateCatMenu() {
        $menus = $this->db->resetFilters()->
                filterEqual('linktype', 'category')->
                selectOnly('i.id, i.linkid')->
                get('menu', false, false);

        if (!$menus) {
            return true;
        }

        $cats = $this->db->selectOnly('i.id, i.seolink')->get(self::CATEGORY_TABLE);

        foreach ($menus as $menu) {
            $cat_id = (int) $menu['linkid'];

            if (!isset($cats[$cat_id])) {
                continue;
            }

            $this->db->update('menu', (int) $menu['id'], [
                'link' => '/' . $cats[$cat_id]['seolink']
            ]);
        }

        return true;
    }

    /**
     * Обновляет ссылки меню на статьи
     *
     * @return bool
     */
    public function updateContentMenu() {
        $menus = $this->db->resetFilters()->
                filterEqual('linktype', 'content')->
                selectOnly('i.id, i.linkid')->
                get('menu', false, false);

        if (!$menus) {
            return true;
        }

        $articles = $this->db->selectOnly('i.id, i.seolink')->get(self::CONTENT_TABLE);

        foreach ($menus as $menu) {
            $content_id = (int) $menu['linkid'];

            if (!isset($articles[$content_id])) {
                continue;
            }

            $this->db->update('menu', (int) $menu['id'], [
                'link' => '/' . $articles[$content_id]['seolink'] . '.html'
            ]);
        }

        return true;
    }

    /**
     * Обновляет ссылки меню на статьи
     *
     * @return true
     */
    public function updateContentCommentsLink($item_id) {
        $article_seolink = $this->db->filterEqual('id', (int) $item_id)->
                getFieldFiltered(self::CONTENT_TABLE, 'seolink');

        // Обновляем ссылки в комменатриях
        if ($article_seolink !== false) {
            $this->db->filterEqual('target', 'article')->
                    filterEqual('target_id', (int) $item_id)->
                    updateFiltered('comments', [
                        'target_link' => '/' . $article_seolink . '.html'
                    ]);
        }

        // Обновляем ссылки в action
        $action = \cmsActions::getAction('add_comment');

        if ($action) {
            $logs = $this->db->resetFilters()->
                    filterEqual('action_id', (int) $action['id'])->
                    filterEqual('target_id', (int) $item_id)->
                    getRows('actions_log', '1', 'id, object_id');

            foreach ($logs as $log) {
                $this->db->update('actions_log', (int) $log['id'], [
                    'target_url' => '/' . $article_seolink . '.html',
                    'object_url' => '/' . $article_seolink . '.html#c' . (int) $log['object_id']
                ]);
            }
        }

        return true;
    }

    /**
     * Возвращает массив связанных статей с категорией
     *
     * @return array
     */
    public function getNestedArticles($category_id) {
        $cat = $this->db->getNsCategory(self::CATEGORY_TABLE, $category_id);

        if (!$cat) {
            return false;
        }

        $articles = $this->db->resetFilters()->
                selectOnly('i.id, i.title, i.seolink, i.url, i.category_id')->
                joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id = i.category_id')->
                filterGtEqual('cat.NSLeft', $cat['NSLeft'])->
                filterLtEqual('cat.NSRight', $cat['NSRight'])->
                get(self::CONTENT_TABLE);

        return $articles ? $articles : false;
    }

    /**
     * Удаляет категорию
     *
     * @return bool
     */
    public function deleteCategory($id, $is_with_content = false) {
        $articles = $this->getNestedArticles($id);
        $rootid = $this->db->getNsRootCatId(self::CATEGORY_TABLE);

        if ($articles) {
            foreach ($articles as $article) {
                // удаляем все вложенные статьи
                if ($is_with_content) {
                    $this->deleteArticle($article['id']);
                } else { // или переносим в корень и в архив
                    $seolink = $article['seolink'];
                    $slash_pos = strrpos($seolink, '/');

                    if ($slash_pos !== false) {
                        $seolink = substr($seolink, $slash_pos + 1);
                    }

                    $this->db->update(self::CONTENT_TABLE, (int) $article['id'], [
                        'category_id' => (int) $rootid,
                        'is_arhive' => 1,
                        'seolink' => $seolink
                    ]);
                }
            }
        }

        \translations::deleteTargetTranslation('content_category', $id);

        return $this->db->deleteNS(self::CATEGORY_TABLE, $id);
    }

    /**
     * Возвращает фотографии из привязанного альбома
     *
     * @param str $album
     *
     * @return array
     */
    public function getCatPhotoAlbum($album) {
        if (!$album) {
            return [];
        }

        $album = @unserialize($album);
        if (!$album || !is_array($album) || !@$album['id']) {
            return [];
        }

        $inPhoto = \cmsPhoto::getInstance();

        $p_a = $this->db->getNsCategory('photo_albums', (int) $album['id']);
        if (!$p_a) {
            return [];
        }

        $p_a['title'] = $album['header'];
        $p_a['maxcols'] = $album['maxcols'];

        $inPhoto->whereAlbumIs((int) $album['id']);

        if (!in_array($album['orderby'], ['title', 'pubdate', 'rating', 'hits'])) {
            $album['orderby'] = 'pubdate';
        }

        if (!in_array($album['orderto'], ['asc', 'desc'])) {
            $album['orderto'] = 'desc';
        }

        /*
         * cmsPhoto использует общий экземпляр \cms\db, поэтому сортировку
         * и лимит альбома передаём ему же до вызова getPhotos().
         */
        $this->inDB->orderBy('i.' . $album['orderby'], $album['orderto']);

        $this->inDB->limit((int) $album['max']);

        $photos = $inPhoto->getPhotos();

        if (!$photos) {
            return [];
        }

        return ['album' => $p_a, 'photos' => $photos];
    }

    public function moveToArhive($item_id) {
        return $this->db->update(self::CONTENT_TABLE, (int) $item_id, ['is_arhive' => 1]);
    }

    public function moveFromArhive($item_id) {
        return $this->db->update(self::CONTENT_TABLE, (int) $item_id, ['is_arhive' => 0]);
    }

    public function joinCategory() {
        if ($this->category_joined) {
            return $this;
        }

        $this->category_joined = true;

        return $this->joinInner(self::CATEGORY_TABLE, 'cat', 'cat.id=i.category_id');
    }

    public function joinUsers() {
        if ($this->users_joined) {
            return $this;
        }

        $this->users_joined = true;

        return $this->joinLeft('users', 'u', 'u.id = i.user_id');
        ;
    }

    public function filterOnlyPublished() {
        if ($this->only_published_filtered) {
            return $this;
        }

        $this->only_published_filtered = true;

        $today = date('Y-m-d H:i:s');

        return $this->filter("i.published = 1 AND i.pubdate <= '" . $today . "' AND (i.is_end = 0 OR (i.is_end = 1 AND i.enddate >= '" . $today . "'))");
    }

    public function filterIsArhived() {
        if ($this->is_arhived_filtered) {
            return $this;
        }

        $this->is_arhived_filtered = true;

        return $this->filterEqual('is_arhive', 0);
    }

    protected function filterOldInDBWhere() {
        if ($this->old_indb_where_inserted || empty($this->inDB->where)) {
            return $this;
        }

        $this->old_indb_where_inserted = true;

        // $inDB->where() исторически пишет " AND (...)", builder-фильтры —
        // "(...)"; убираем ведущий AND и подстраховываемся константой 1,
        // чтобы условие осталось корректным в любом случае
        $condition = trim(str_replace('con.', 'i.', $this->inDB->where));
        $condition = preg_replace('/^AND\s+/i', '', $condition);

        return $this->filter('1 AND ' . $condition);
    }

    public function resetFilters() {
        $this->category_joined = false;
        $this->users_joined = false;
        $this->is_arhived_filtered = false;
        $this->only_published_filtered = false;
        $this->old_indb_where_inserted = false;

        return parent::resetFilters();
    }
}