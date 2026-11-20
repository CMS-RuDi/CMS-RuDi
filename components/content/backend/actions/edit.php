<?php

namespace components\content\backend\actions;

class edit extends \cms\com_action {
    private $art_defaults = [
        'id' => 0,
        'title' => '',
        'description' => '',
        'content' => '',
        'meta_keys' => '',
        'meta_desc' => '',
        'showpath' => 1,
        'tpl' => 'com_content_read.tpl'
    ];
    private $cat_defaults = [
        'id' => 0,
        'orderby' => 'pubdate',
        'orderto' => 'ASC',
        'maxcols' => 1,
        'tpl' => 'com_content_view.tpl'
    ];

    public function actCategory($category_id = 0) {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page->addHead(jwHeader());

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => 'javascript:document.addform.submit();', 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => 'javascript:history.go(-1);', 'icon' => 'cancel.gif']
        ]);

        if ($category_id == 0) {
            $item = $this->cat_defaults;
            $this->page->addPathway($this->lang->ad_create_section);
            $this->page->setTitle($this->lang->ad_create_section);
        } else {
            $item = $this->model->getItemById(\components\content\model::CATEGORY_TABLE, (int) $category_id);

            if (empty($item)) {
                \cmsCore::error404();
            }

            if (!empty($item['photoalbum'])) {
                $item['photoalbum'] = unserialize($item['photoalbum']);
            }

            $this->page->addPathway($item['title']);
            $this->page->setTitle($this->lang->ad_edit_section);
        }

        $tpl = $this->page->initTemplate('components/content/backend', 'edit_category')->
                assign('item', $item)->
                assign('submit_uri', $this->genActionUrl('save', ['category', $category_id]))->
                assign('title_lang_panel', self::getLangPanel('content_category', $category_id, 'title'))->
                assign('description_lang_panel', self::getLangPanel('content_category', $category_id, 'description'))->
                assign('pagetitle_lang_panel', self::getLangPanel('content_category', $category_id, 'pagetitle'))->
                assign('meta_keys_lang_panel', self::getLangPanel('content_category', $category_id, 'meta_keys'))->
                assign('meta_desc_lang_panel', self::getLangPanel('content_category', $category_id, 'meta_desc'))->
                assign('root_id', $this->model->db->getNsRootCatId(\components\content\model::CATEGORY_TABLE))->
                assign('cats_list', $this->core->getListItemsNS(\components\content\model::CATEGORY_TABLE, !empty($item['parent_id']) ? $item['parent_id'] : 0))->
                assign('billing_installed', \cms\controller::installed('billing'))->
                assign('groups_list', $this->core->getListItems('user_groups', !empty($item['modgrp_id']) ? $item['modgrp_id'] : 0, 'id', 'ASC', 'is_admin = 0'))->
                assign('albums_list', $this->core->getListItemsNS('photo_albums', !empty($item['photoalbum']['id']) ? $item['photoalbum']['id'] : 0))->
                assign('groups', \cmsUser::getGroups());

        if ($category_id == 0) {
            $tpl->assign('menu_list', cpGetList('menu'));
        } else {
            $this->model->filterEqual('content_id', $item['id'])->
                    filterEqual('content_type', 'category')->
                    selectOnly('i.group_id');

            $tpl->assign('access', $this->model->get('content_access', false, 'group_id'));
        }

        echo jwTabs($tpl->fetch());
    }

    #================================================================

    public function actContent($article_id = 0, $category_id = 0) {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page->addHead(jwHeader());

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => 'javascript:document.addform.submit();', 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => 'javascript:history.go(-1);', 'icon' => 'cancel.gif']
        ]);

        if ($article_id == 0) {
            $item = array_merge($this->art_defaults, [
                'category_id' => $category_id,
                'pubdate' => date('d.m.Y'),
                'enddate' => date('d.m.Y'),
            ]);
        } else {
            if ($category_id) {
                \cmsCore::error404();
            }

            $this->model->select('(TO_DAYS(i.enddate) - TO_DAYS(CURDATE()))', 'daysleft');

            $item = $this->model->getArticle($article_id);

            if (empty($item)) {
                \cmsCore::error404();
            }

            // getArticle() делает SELECT i.*, поэтому одноимённый DATE_FORMAT-
            // алиас в результате перекрывается исходным полем. Форматируем
            // даты после выборки — это не зависит от порядка колонок в SQL.
            $item['pubdate'] = !empty($item['pubdate'])
                    ? date('d.m.Y', strtotime($item['pubdate']))
                    : date('d.m.Y');

            $item['enddate'] = !empty($item['enddate'])
                    ? date('d.m.Y', strtotime($item['enddate']))
                    : '';

            $ostatok = \cms\session::has('users_edit_list') ? \cms\session::get('groups_edit_list') : [];

            $this->page->addPathway($item['title']);
            $this->page->setTitle($this->lang->ad_edit_article . $ostatok);
        }

        $tpl = $this->page->initTemplate('components/content/backend', 'edit');

        $tpl->assign('submit_uri', $this->genActionUrl('save', ['content', $article_id]))
                ->assign('item', $item)
                ->assign('ostatok', !empty($ostatok) ? ('(' . $this->lang->ad_next_in . count(\cms\session::get('groups_edit_list')) . ')') : '')
                ->assign('panel', $this->getPanelHtml())
                ->assign('title_lang_panel', $this->getLangPanel('content_content', $article_id, 'title'))
                ->assign('description_lang_panel', $this->getLangPanel('content_content', $article_id, 'description'))
                ->assign('content_lang_panel', $this->getLangPanel('content_content', $article_id, 'content'))
                ->assign('pagetitle_lang_panel', $this->getLangPanel('content_content', $article_id, 'pagetitle'))
                ->assign('meta_keys_lang_panel', $this->getLangPanel('content_content', $article_id, 'meta_keys'))
                ->assign('meta_desc_lang_panel', $this->getLangPanel('content_content', $article_id, 'meta_desc'))
                ->assign('tag_line', !empty($item['id']) ? \cmsTagLine('content', $item['id'], false) : '')
                ->assign('options', $this->options)
                ->assign('cats_list', $this->core->getListItemsNS(\components\content\model::CATEGORY_TABLE, $item['category_id']))
                ->assign('users_list', $this->core->getListItems('users', !empty($item['user_id']) ? $item['user_id'] : $this->user->id, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname'))
                ->assign('groups', \cmsUser::getGroups());

        if ($item['id'] > 0) {
            $this->model->filterEqual('content_id', $item['id'])
                    ->filterEqual('content_type', 'material')
                    ->selectOnly('i.group_id');

            $tpl->assign('photo_exist', file_exists(PATH . '/images/photos/small/article' . $item['id'] . '.jpg'))
                    ->assign('access', $this->model->get('content_access', false, 'group_id'));
        } else {
            $tpl->assign('menu_list', cpGetList('menu'));
        }

        echo jwTabs($tpl->fetch());
    }

    public function actContents() {
        $this->actContent($this->getMultipleItem('content_edit_list'));
    }
}