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

class backend extends \cms\backend {

    public function __construct($request = null) {
        parent::__construct($request);

        if (!\cmsUser::isAdminCan('admin/content', self::$admin_access)) {
            self::accessDenied();
        }

        $this->page->setTitle($this->lang->ad_articles);
        $this->page->addPathway($this->lang->ad_articles, $this->genActionUrl());
    }

    public function routeAction($action_name) {
        if (is_numeric($action_name)) {
            array_unshift($this->current_params, $action_name);

            return 'view';
        }

        return $action_name;
    }

    //========================================================================//

    public function actionView($category_id = 0) {
        $this->page->addHeadJS('/admin/js/content.js');
        $this->page->addHeadJsLang(['AD_NO_SELECTED_ARTICLES', 'AD_DELETE_SELECTED_ARTICLES', 'AD_PIECES', 'AD_CATEGORY_DELETE', 'AD_AND_SUB_CATS', 'AD_DELETE_SUB_ARTICLES']);

        self::addToolMenuItems([
            ['title' => $this->lang->ad_setup_category, 'link' => $this->genActionUrl('config'), 'icon' => 'config.gif'],
            ['title' => $this->lang->ad_help, 'link' => "https://cmsrudi.ru/help/com_content", 'icon' => 'help.gif']
        ]);

        $only_hidden = $this->input->get('only_hidden', 'int', 0);

        $title_part = $this->input->get('title', 'str', '');

        $def_order = $category_id ? 'ordering' : 'pubdate';
        $orderby = $this->input->get('orderby', 'str', $def_order);
        $orderto = $this->input->get('orderto', 'str', 'asc');
        $page = $this->input->get('page', 'int', 1);
        $perpage = 20;

        $hide_cats = $this->input->get('hide_cats', 'int', 0);

        $cats = $this->model->getCatsTree();

        if ($category_id) {
            $this->model->whereCatIs($category_id);
        }

        if ($title_part) {
            $this->model->filterLike('i.title', '%' . mb_strtolower($title_part) . '%');
        }

        if ($only_hidden) {
            $this->model->filterEqual('published', 0);
        }

        $this->model->orderBy($orderby, $orderto);

        $this->model->limitPage($page, $perpage);

        $total = $this->model->getArticlesCount(false);

        $items = $this->model->getArticlesList(false);

        $pages = ceil($total / $perpage);

        $tpl = $this->page->initTemplate('components/content/backend', 'list')->
                assign('base_uri', $this->genActionUrl())->
                assign('add_cat_uri', $this->genActionUrl('add', 'category'))->
                assign('edit_cat_uri', $this->genActionUrl('edit', 'category'))->
                assign('hidden_uri', $this->genActionUrl('view', $category_id ? $category_id : false, ['only_hidden' => 1]))->
                assign('hide_uri', $this->genActionUrl('hide'))->
                assign('show_uri', $this->genActionUrl('show'))->
                assign('arhive_on_uri', $this->genActionUrl('arhive_on'))->
                assign('add_item_uri', $this->genActionUrl('add', ['content', $category_id]))->
                assign('edit_item_uri', $this->genActionUrl('edit', 'content'))->
                assign('delete_uri', $this->genActionUrl('delete', 'content'))->
                assign('hide_cats', $hide_cats)->
                assign('only_hidden', $only_hidden)->
                assign('orderby', $orderby)->
                assign('orderto', $orderto)->
                assign('title_part', $title_part)->
                assign('category_id', $category_id)->
                assign('root_id', 1)->
                assign('cats', $cats)->
                assign('items', $items)->
                assign('cats_opt', $this->core->getListItemsNS(\components\content\model::CATEGORY_TABLE, $category_id));

        if ($pages > 1) {
            $tpl->assign('pagebar', $this->page->getPagebar($total, $page, $perpage, $this->genActionUrl('view', $category_id ? $category_id : false, ['hide_cats' => $hide_cats, 'title' => $title_part, 'orderby' => $orderby, 'orderto' => $orderto, 'page' => '%page%'])));
        }

        $tpl->display();
    }

    public function actionArhiveOn($item_id) {
        $this->model->moveToArhive($item_id);

        \cmsCore::addSessionMessage($this->lang->ad_articles_to_arhive, 'success');
        \cmsCore::redirectBack();
    }

    public function actionMoveToCat() {
        $items = $this->input->get('item', 'array_int');
        $to_cat_id = $this->input->get('obj_id', 'int', 0);

        if ($items && $to_cat_id) {
            $last_ordering = (int) $this->model->db->filterEqual('category_id', (int) $to_cat_id)->
                            orderBy('ordering', 'desc')->
                            getFieldFiltered(\components\content\model::CONTENT_TABLE, 'ordering');

            foreach ($items as $item_id) {
                $article = $this->model->getArticle($item_id);

                if (!$article) {
                    continue;
                }

                $last_ordering++;

                $this->model->updateArticle($article['id'], [
                    'category_id' => $to_cat_id,
                    'ordering' => $last_ordering,
                    'url' => $article['url'],
                    'title' => $article['title'],
                    'id' => $article['id'],
                    'user_id' => $article['user_id']
                ]);
            }

            \cmsCore::addSessionMessage($this->lang->ad_articles_to, 'success');
        }

        $this->redirectToAction('view', $to_cat_id);
    }

    public function actionShow($item_id = false) {
        if (!empty($item_id)) {
            $this->model->db->update(\components\content\model::CONTENT_TABLE, (int) $item_id, ['published' => 1]);
            \cmsCore::halt(1);
        } else {
            $items = $this->input->get('item', 'array_int');

            if (!empty($items)) {
                $this->model->db->filterIn('id', $items)->
                        updateFiltered(\components\content\model::CONTENT_TABLE, ['published' => 1]);
            }
        }

        \cmsCore::redirectBack();
    }

    public function actionHide($item_id = false) {
        if (!empty($item_id)) {
            $this->model->db->update(\components\content\model::CONTENT_TABLE, (int) $item_id, ['published' => 0]);
            \cmsCore::halt(1);
        } else {
            $items = $this->input->get('item', 'array_int');

            if (!empty($items)) {
                $this->model->db->filterIn('id', $items)->
                        updateFiltered(\components\content\model::CONTENT_TABLE, ['published' => 0]);
            }
        }

        \cmsCore::redirectBack();
    }

    //========================================================================//

    public function createMenuItem($menu, $id, $title, $linktype = 'content') {
        $rootid = $this->model->db->getNsRootCatId('menu');

        $ns = $this->core->nestedSetsInit('menu');
        $myid = $ns->AddNode($rootid);

        $link = $this->core->getMenuLink($linktype, $id);

        $this->model->db->update('menu', (int) $id, [
            'menu' => $menu,
            'title' => $title,
            'link' => $link,
            'linktype' => $linktype,
            'linkid' => $id,
            'target' => '_self',
            'published' => 1,
            'template' => 0,
            'access_list' => '',
            'iconurl' => ''
        ]);

        return true;
    }
}