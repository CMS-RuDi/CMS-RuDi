<?php

namespace components\content;

class backend extends \cms\backend
{

    /**
     * @var \components\content\model
     */
    public $model;

    public function __construct($request = null)
    {
        parent::__construct($request);

        if ( !\cmsUser::isAdminCan('admin/content', self::$admin_access) ) {
            self::accessDenied();
        }
    }

    public function before($action_name)
    {
        parent::before($action_name);

        $this->page->setTitle($this->lang->ad_articles);
        $this->page->addPathway($this->lang->ad_articles, $this->genActionUrl());
    }

    public function routeAction($action_name)
    {
        if ( is_numeric($action_name) ) {
            array_unshift($this->current_params, $action_name);

            return 'index';
        }

        return $action_name;
    }

    //========================================================================//

    public function actionIndex($category_id = 0)
    {
        $this->page->setTitle($this->lang->ad_articles);
        $this->page->addPathway($this->lang->ad_articles);

        $this->page->addHeadJS('/admin/js/content.js');
        $this->page->addHeadJsLang([ 'AD_NO_SELECTED_ARTICLES', 'AD_DELETE_SELECTED_ARTICLES', 'AD_PIECES', 'AD_CATEGORY_DELETE', 'AD_AND_SUB_CATS', 'AD_DELETE_SUB_ARTICLES' ]);

        self::addToolMenuItem($this->lang->ad_setup_category, $this->genActionUrl('config'), 'config.gif');
        self::addToolMenuItem($this->lang->ad_help, '/cp/help/content', 'help.gif');

        $only_hidden = $this->request->get('only_hidden', 'int', 0);

        $title_part = $this->request->get('title', 'str', '');

        $def_order = $category_id ? 'ordering' : 'pubdate';
        $orderby   = $this->request->get('orderby', 'str', $def_order);
        $orderto   = $this->request->get('orderto', 'str', 'asc');
        $page      = $this->request->get('page', 'int', 1);
        $perpage   = 20;

        $hide_cats = $this->request->get('hide_cats', 'int', 0);

        $cats = $this->model->getCatsTree();

        if ( $category_id ) {
            $this->model->whereCatIs($category_id);
        }

        if ( $title_part ) {
            $this->model->filterLike('LOWER(i.title)', '%' . mb_strtolower($title_part) . '%');
        }

        if ( $only_hidden ) {
            $this->model->filterEqual('published', 0);
        }

        $this->model->orderBy($orderby, $orderto);

        $this->model->limitPage($page, $perpage);

        $total = $this->model->getArticlesCount(false);

        $items = $this->model->getArticlesList(false);

        $pages = ceil($total / $perpage);

        $tpl = $this->page->initTemplate('components/content/backend', 'list')->
                assign('base_uri', $this->genActionUrl())->
                assign('add_cat_uri', $this->genActionUrl('add_category'))->
                assign('edit_cat_uri', $this->genActionUrl('edit_category'))->
                assign('hidden_uri', $this->genActionUrl('index', $category_id ? $category_id : false, [ 'only_hidden' => 1 ]))->
                assign('hide_uri', $this->genActionUrl('hide'))->
                assign('show_uri', $this->genActionUrl('show'))->
                assign('arhive_on_uri', $this->genActionUrl('arhive_on'))->
                assign('add_item_uri', $this->genActionUrl('add', $category_id ? $category_id : false))->
                assign('edit_item_uri', $this->genActionUrl('edit'))->
                assign('delete_uri', $this->genActionUrl('delete'))->
                assign('hide_cats', $hide_cats)->
                assign('only_hidden', $only_hidden)->
                assign('orderby', $orderby)->
                assign('orderto', $orderto)->
                assign('title_part', $title_part)->
                assign('category_id', $category_id)->
                assign('root_id', 1)->
                assign('cats', $cats)->
                assign('items', $items)->
                assign('cats_opt', $this->core->getListItemsNS($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, $category_id));

        if ( $pages > 1 ) {
            $tpl->assign('pagebar', $this->page->getPagebar($total, $page, $perpage, $this->genActionUrl('index', $category_id ? $category_id : false, [ 'hide_cats' => $hide_cats, 'title' => $title_part, 'orderby' => $orderby, 'orderto' => $orderto, 'page' => '%page%' ])));
        }

        $tpl->display();
    }

    public function actionArhiveOn($item_id)
    {
        $this->model->moveToArhive($item_id);

        \cmsCore::addSessionMessage($this->lang->ad_articles_to_arhive, 'success');
        \cmsCore::redirectBack();
    }

    public function actionMoveToCat()
    {
        $items     = $this->request->get('item', 'array_int');
        $to_cat_id = $this->request->get('obj_id', 'int', 0);

        if ( $items && $to_cat_id ) {
            $last_ordering = (int) $this->model->getField(\components\content\model::CONTENT_TABLE, "category_id = '" . $to_cat_id . "' ORDER BY ordering DESC", 'ordering');

            foreach ( $items as $item_id ) {
                $article = $this->model->getArticle($item_id);

                if ( !$article ) {
                    continue;
                }

                $last_ordering++;

                $this->model->updateArticle($article['id'], [
                    'category_id' => $to_cat_id,
                    'ordering'    => $last_ordering,
                    'url'         => $article['url'],
                    'title'       => $article['title'],
                    'id'          => $article['id'],
                    'user_id'     => $article['user_id']
                ]);
            }

            \cmsCore::addSessionMessage($this->lang->ad_articles_to, 'success');
        }

        $this->redirectToAction('index', $to_cat_id);
    }

    public function actionShow($item_id = false)
    {
        if ( !empty($item_id) ) {
            dbShow('cms_' . \components\content\model::CONTENT_TABLE, $item_id);
            \cmsCore::halt(1);
        }
        else {
            $items = $this->request->get('item', 'array_int');

            if ( !empty($items) ) {
                dbShowList('cms_' . \components\content\model::CONTENT_TABLE, $items);
            }
        }

        \cmsCore::redirectBack();
    }

    public function actionHide($item_id = false)
    {
        if ( !empty($item_id) ) {
            dbHide('cms_' . \components\content\model::CONTENT_TABLE, $item_id);
            \cmsCore::halt(1);
        }
        else {
            $items = $this->request->get('item', 'array_int');

            if ( !empty($items) ) {
                dbHideList('cms_' . \components\content\model::CONTENT_TABLE, $items);
            }
        }

        \cmsCore::redirectBack();
    }

    public function actionDelete($item_id = false)
    {
        if ( !empty($item_id) ) {
            $this->model->deleteArticle($item_id);
            \cmsCore::addSessionMessage($this->lang->ad_article_remove, 'success');
        }
        else {
            $items = $this->request->get('item', 'array_int');

            if ( !empty($items) ) {
                $this->model->deleteArticles($items);
                \cmsCore::addSessionMessage($this->lang->ad_articles_remove, 'success');
            }
        }

        \cmsCore::redirectBack();
    }

    public function actionDeleteCategory($cat_id)
    {
        $is_with_content = $this->request->get('content');

        $this->model->deleteCategory($cat_id, $is_with_content);

        \cmsCore::addSessionMessage(($is_with_content ? $this->lang->ad_category_removed : $this->lang->ad_category_removed_not_article), 'success');

        $this->redirectToAction();
    }

    //========================================================================//

    public function actionAdd()
    {
        $this->page->addPathway($this->lang->ad_create_article);
        \cms\backend::setTitle($this->lang->ad_create_article);

        return $this->actionEdit(false, 'add');
    }

    public function actionEdit($item_id = false, $do = 'edit')
    {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page->addHead(jwHeader());

        self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
        self::addToolMenuItem($this->lang->CANCEL, 'javascript:history.go(-1);', 'cancel.gif');

        if ( $do == 'add' ) {
            $item = [
                'category_id' => $this->request->get('to', 'int', 0),
                'showpath'    => 1,
                'pubdate'     => date('d.m.Y'),
                'enddate'     => date('d.m.Y'),
                'tpl'         => 'com_content_read.tpl'
            ];
        }
        else {
            if ( $this->request->has('item') ) {
                $_SESSION['editlist'] = $this->request->get('item', 'array_int', []);
            }

            $ostatok = '';

            if ( isset($_SESSION['editlist']) ) {
                $item_id = array_shift($_SESSION['editlist']);

                if ( sizeof($_SESSION['editlist']) == 0 ) {
                    unset($_SESSION['editlist']);
                }
                else {
                    $ostatok = '(' . $this->lang->ad_next_in . sizeof($_SESSION['editlist']) . ')';
                }
            }

            $this->model->select('(TO_DAYS(i.enddate) - TO_DAYS(CURDATE()))', 'daysleft');
            $this->model->select("DATE_FORMAT(i.pubdate, '%d.%m.%Y')", 'pubdate');
            $this->model->select("DATE_FORMAT(i.enddate, '%d.%m.%Y')", 'enddate');

            $item = $this->model->getArticle($item_id);

            $this->page->addPathway($item['title']);
            \cms\backend::setTitle($this->lang->AD_EDIT_ARTICLE . $ostatok);
        }

        $tpl = $this->page->initTemplate('components/content/backend', 'edit');

        $tpl->assign('submit_uri', $this->genActionUrl('submit'))->
                assign('item', $item)->
                assign('do', $do)->
                assign('panel', \cms\backend::getPanelHtml())->
                assign('title_lang_panel', self::getLangPanel('content_content', $item_id, 'title'))->
                assign('description_lang_panel', self::getLangPanel('content_content', $item_id, 'description'))->
                assign('content_lang_panel', self::getLangPanel('content_content', $item_id, 'content'))->
                assign('pagetitle_lang_panel', self::getLangPanel('content_content', $item_id, 'pagetitle'))->
                assign('meta_keys_lang_panel', self::getLangPanel('content_content', $item_id, 'meta_keys'))->
                assign('meta_desc_lang_panel', self::getLangPanel('content_content', $item_id, 'meta_desc'))->
                assign('tag_line', !empty($item['id']) ? \cmsTagLine('content', $item['id'], false) : '')->
                assign('options', $this->options)->
                assign('cats_list', $this->core->getListItemsNS($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, $item['category_id']))->
                assign('users_list', $this->core->getListItems('cms_users', !empty($item['user_id']) ? $item['user_id'] : $this->user->id, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname'))->
                assign('groups', \cmsUser::getGroups());

        if ( $do == 'edit' ) {
            $this->model->filterEqual('content_id', $item['id'])->
                    filterEqual('content_type', 'material')->
                    selectOnly('i.group_id');

            $tpl->assign('photo_exist', file_exists(PATH . '/images/photos/small/article' . $item['id'] . '.jpg'))->
                    assign('access', $this->model->get('content_access', false, 'group_id'));
        }
        else {
            $tpl->assign('menu_list', cpGetList('menu'));
        }

        echo jwTabs($tpl->fetch());
    }

    public function actionSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $item_id = $this->request->get('id', 'int', 0);

        $olddate = $this->request->get('olddate', 'str', ''); // Нигде не используется, проверить нужно ли, если нет удалить

        $article['category_id'] = $this->request->get('category_id', 'int', 1);
        $article['title']       = $this->request->get('title', 'str');
        $article['url']         = $this->request->get('url', 'str');
        $article['showtitle']   = $this->request->get('showtitle', 'int', 0);
        $article['description'] = $this->request->get('description', 'html', '');
        $article['content']     = $this->request->get('content', 'html', '');

        $article['published']  = $this->request->get('published', 'int', 0);
        $article['showdate']   = $this->request->get('showdate', 'int', 0);
        $article['showlatest'] = $this->request->get('showlatest', 'int', 0);
        $article['showpath']   = $this->request->get('showpath', 'int', 0);
        $article['comments']   = $this->request->get('comments', 'int', 0);
        $article['canrate']    = $this->request->get('canrate', 'int', 0);

        $enddate            = explode('.', $this->request->get('enddate', 'str'));
        $article['enddate'] = $enddate[2] . '-' . $enddate[1] . '-' . $enddate[0];

        $article['is_end']    = $this->request->get('is_end', 'int', 0);
        $article['pagetitle'] = $this->request->get('pagetitle', 'str', '');
        $article['tags']      = $this->request->get('tags', 'str');

        $article['pubdate'] = $this->request->get('pubdate', 'str');
        $date               = explode('.', $article['pubdate']);
        $article['pubdate'] = (int) $date[2] . '-' . sprintf("%02d", (int) $date[1]) . '-' . sprintf("%02d", (int) $date[0]) . ' ' . date('H:i');

        $article['user_id'] = $this->request->get('user_id', 'int', $this->user->id);
        $article['tpl']     = $this->request->get('tpl', 'str', 'com_content_read.tpl');

        $autokeys = $this->request->get('autokeys', 'int');

        switch ( $autokeys ) {
            case 1: $article['meta_keys'] = $this->core->getKeywords($article['content']);
                $article['meta_desc'] = $article['title'];
                break;

            case 2: $article['meta_desc'] = strip_tags($article['description']);
                $article['meta_keys'] = $article['tags'];
                break;

            case 3: $article['meta_desc'] = $this->request->get('meta_desc', 'str');
                $article['meta_keys'] = $this->request->get('meta_keys', 'str');
                break;
        }

        if ( empty($item_id) ) {
            $item_id = $this->model->addArticle($article);

            $inmenu = $this->request->get('createmenu', 'str', '');

            if ( $inmenu ) {
                $this->createMenuItem($inmenu, $item_id, $article['title']);
            }

            \cmsCore::addSessionMessage($this->lang->ad_article_save, 'success');

            if ( !isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist']) == 0 ) {
                $redirect_uri = $this->genActionUrl('index', $article['category_id']);
            }
            else {
                $redirect_uri = $this->genActionUrl('edit');
            }
        }
        else {
            $this->model->updateArticle($item_id, $article);

            \cmsCore::addSessionMessage($this->lang->ad_article_add, 'success');

            $redirect_uri = $this->genActionUrl('index', $article['category_id']);
        }

        if ( !$this->request->get('is_public', 'int', 0) ) {
            $showfor = $this->request->get('showfor', 'array_int', []);
            \cmsCore::setAccess($item_id, $showfor, 'material');
        }
        else {
            \cmsCore::clearAccess($item_id, 'material');
        }

        $file = 'article' . $item_id . '.jpg';

        if ( $this->request->get('delete_image', 'int', 0) ) {
            @unlink(PATH . '/images/photos/small/' . $file);
            @unlink(PATH . '/images/photos/medium/' . $file);
        }
        else {
            $inUploadPhoto = \cmsUploadPhoto::getInstance();

            // Выставляем конфигурационные параметры
            $inUploadPhoto->upload_dir    = PATH . '/images/photos/';
            $inUploadPhoto->small_size_w  = $this->options['img_small_w'];
            $inUploadPhoto->medium_size_w = $this->option['img_big_w'];
            $inUploadPhoto->thumbsqr      = $this->option['img_sqr'];
            $inUploadPhoto->is_watermark  = $model->config['watermark'];
            $inUploadPhoto->input_name    = 'picture';
            $inUploadPhoto->filename      = $file;

            // Процесс загрузки фото
            $inUploadPhoto->uploadPhoto();
        }

        \cmsCore::redirect($redirect_uri);
    }

    //========================================================================//

    public function actionAddCategory()
    {
        $this->page->addPathway($this->lang->ad_create_section);
        self::setTitle($this->lang->ad_create_section);

        return $this->actionEditCategory(false, 'add');
    }

    public function actionEditCategory($item_id = false, $do = 'edit')
    {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page->addHead(jwHeader());

        self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
        self::addToolMenuItem($this->lang->cancel, 'javascript:history.go(-1);', 'cancel.gif');

        if ( $do == 'add' ) {
            $item = [
                'orderby' => 'pubdate',
                'orderto' => 'ASC',
                'maxcols' => 1,
                'tpl'     => 'com_content_view.tpl'
            ];
        }
        else {
            if ( $this->request->has('multiple') ) {
                if ( $this->request->has('item') ) {
                    $_SESSION['editlist'] = $this->request->get('item', 'array_int', []);
                }
            }

            $ostatok = '';

            if ( isset($_SESSION['editlist']) ) {
                $item_id = array_shift($_SESSION['editlist']);

                if ( sizeof($_SESSION['editlist']) == 0 ) {
                    unset($_SESSION['editlist']);
                }
                else {
                    $ostatok = '(' . $this->lang->ad_next_in . sizeof($_SESSION['editlist']) . ')';
                }
            }

            $item = $this->model->getItemById('category', (int) $item_id);

            if ( empty($item) ) {
                \cmsCore::error404();
            }

            if ( !empty($item['photoalbum']) ) {
                $item['photoalbum'] = unserialize($item['photoalbum']);
            }

            $this->page->addPathway($item['title']);
            self::setTitle($this->lang->ad_edit_section . $ostatok);
        }

        $tpl = $this->page->initTemplate('components/content/backend', 'edit_category')->
                assign('item', $item)->
                assign('do', $do)->
                assign('submit_uri', $this->genActionUrl('submit_category'))->
                assign('title_lang_panel', self::getLangPanel('content_category', $item_id, 'title'))->
                assign('description_lang_panel', self::getLangPanel('content_category', $item_id, 'description'))->
                assign('pagetitle_lang_panel', self::getLangPanel('content_category', $item_id, 'pagetitle'))->
                assign('meta_keys_lang_panel', self::getLangPanel('content_category', $item_id, 'meta_keys'))->
                assign('meta_desc_lang_panel', self::getLangPanel('content_category', $item_id, 'meta_desc'))->
                assign('root_id', $this->model->inDB->getNsRootCatId($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE))->
                assign('cats_list', $this->core->getListItemsNS($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, !empty($item['parent_id']) ? $item['parent_id'] : 0))->
                assign('billing_installed', \cms\controller::installed('billing'))->
                assign('groups_list', $this->core->getListItems('cms_user_groups', !empty($item['modgrp_id']) ? $item['modgrp_id'] : 0, 'id', 'ASC', 'is_admin = 0'))->
                assign('albums_list', $this->core->getListItemsNS('cms_photo_albums', !empty($item['photoalbum']['id']) ? $item['photoalbum']['id'] : 0))->
                assign('groups', \cmsUser::getGroups());

        if ( $do == 'add' ) {
            $tpl->assign('menu_list', cpGetList('menu'));
        }
        else {
            $this->model->filterEqual('content_id', $item['id'])->
                    filterEqual('content_type', 'category')->
                    selectOnly('i.group_id');

            $tpl->assign('access', $this->model->get('content_access', false, 'group_id'));
        }

        echo jwTabs($tpl->fetch());
    }

    public function actionSubmitCategory()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $item_id = $this->request->get('item_id', 'int', 0);

        if ( !empty($item_id) ) {
            // получаем старую категорию
            $old = $this->model->db->getFields(\components\content\model::CATEGORY_TABLE, 'id=' . $item_id);

            if ( !$old ) {
                \cmsCore::error404();
            }
        }

        $category['title'] = $this->request->get('title', 'str', $this->lang->ad_category_untitled);

        $category['url'] = $this->request->get('url', 'str');
        if ( $category['url'] ) {
            $category['url'] = \cms\lang::slug($category['url'], !$this->options['is_url_cyrillic']);
        }

        $category['parent_id']   = $this->request->get('parent_id', 'int');
        $category['description'] = $this->request->get('description', 'html');
        $category['published']   = $this->request->get('published', 'int', 0);
        $category['showdate']    = $this->request->get('showdate', 'int', 0);
        $category['showcomm']    = $this->request->get('showcomm', 'int', 0);
        $category['orderby']     = $this->request->get('orderby', 'str');
        $category['orderto']     = $this->request->get('orderto', 'str');
        $category['modgrp_id']   = $this->request->get('modgrp_id', 'int', 0);
        $category['maxcols']     = $this->request->get('maxcols', 'int', 0);
        $category['showtags']    = $this->request->get('showtags', 'int', 0);
        $category['showrss']     = $this->request->get('showrss', 'int', 0);
        $category['showdesc']    = $this->request->get('showdesc', 'int', 0);
        $category['is_public']   = $this->request->get('is_public', 'int', 0);
        $category['tpl']         = $this->request->get('tpl', 'str', 'com_content_view.tpl');
        $category['pagetitle']   = $this->request->get('pagetitle', 'str', '');
        $category['meta_desc']   = $this->request->get('meta_desc', 'str');
        $category['meta_keys']   = $this->request->get('meta_keys', 'str');

        $category['cost'] = $this->request->get('cost', 'str', 0);
        if ( !is_numeric($category['cost']) ) {
            $category['cost'] = '';
        }

        $album = [
            'id'      => $this->request->get('album_id', 'int', 0),
            'header'  => $this->request->get('album_header', 'str', ''),
            'orderby' => $this->request->get('album_orderby', 'str', ''),
            'orderto' => $this->request->get('album_orderto', 'str', ''),
            'maxcols' => $this->request->get('album_maxcols', 'int', 0),
            'max'     => $this->request->get('album_max', 'int', 0)
        ];

        if ( $album['id'] ) {
            $category['photoalbum'] = serialize($album);
        }
        else {
            $category['photoalbum'] = '';
        }

        if ( empty($item_id) ) {
            $ns = $this->core->nestedSetsInit($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE);

            $item_id = $ns->AddNode($category['parent_id']);

            $category['seolink'] = \cmsCore::generateCatSeoLink($category, $this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, $this->options['is_url_cyrillic']);

            $this->model->db->update(\components\content\model::CATEGORY_TABLE, 'id=' . $item_id, $category);

            $inmenu = $this->request->get('createmenu', 'str', '');

            if ( $inmenu ) {
                $this->createMenuItem($inmenu, $item_id, $category['title'], 'category');
            }

            \cmsCore::addSessionMessage($this->lang->ad_category_add, 'success');
        }
        else {
            // если сменили категорию
            if ( $old['parent_id'] != $category['parent_id'] ) {
                // перемещаем ее в дереве
                $this->core->nestedSetsInit($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE)->MoveNode($category['id'], $category['parent_id']);

                // обновляем сеолинки категорий
                $this->model->inDB->updateNsCategorySeoLink($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, $category['id'], $this->options['is_url_cyrillic']);

                // Обновляем ссылки меню на категории
                $this->model->updateCatMenu();

                // обновляем сеолинки всех вложенных статей
                $this->model->updateArticlesSeoLink($category['id']);

                \cmsCore::addSessionMessage($this->lang->ad_category_new_url, 'info');
            }

            $this->model->db->update(\components\content\model::CATEGORY_TABLE, 'id=' . $item_id, $category);

            // если пришел запрос на обновление ссылок
            // и категория не менялась - если менялась, мы выше все обновили
            if ( $this->request->has('update_seolink') && ($old['parent_id'] == $category['parent_id']) ) {
                // обновляем сеолинки категорий
                $this->model->inDB->updateNsCategorySeoLink($this->model->inDB->prefix . \components\content\model::CATEGORY_TABLE, $item, $this->options['is_url_cyrillic']);

                // Обновляем ссылки меню на категории
                $this->model->updateCatMenu();

                // обновляем сеолинки всех вложенных статей
                $this->model->updateArticlesSeoLink($item_id);

                \cmsCore::addSessionMessage($this->lang->ad_section_and_articles_new_url, 'info');
            }

            \cmsCore::addSessionMessage($this->lang->ad_category_saved, 'success');
        }

        if ( !$this->request->get('is_access', 'int', 0) ) {
            $showfor = $this->request->get('showfor', 'array_int', []);
            \cmsCore::setAccess($item_id, $showfor, 'category');
        }
        else {
            \cmsCore::clearAccess($item_id, 'category');
        }

        $this->redirectToAction('index', $item_id);
    }

    //========================================================================//

    public function createMenuItem($menu, $id, $title, $linktype = 'content')
    {
        $rootid = $this->model->inDB->getNsRootCatId('cms_menu');

        $ns   = $this->core->nestedSetsInit('cms_menu');
        $myid = $ns->AddNode($rootid);

        $link = $this->core->getMenuLink($linktype, $id);

        $this->model->db->update('menu', 'id=' . $id, [
            'menu'        => $menu,
            'title'       => $title,
            'link'        => $link,
            'linktype'    => $linktype,
            'linkid'      => $id,
            'target'      => '_self',
            'published'   => 1,
            'template'    => 0,
            'access_list' => '',
            'iconurl'     => ''
        ]);

        return true;
    }

}
