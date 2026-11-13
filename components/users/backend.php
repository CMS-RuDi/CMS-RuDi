<?php

namespace components\users;

class backend extends \cms\backend {

    public function before($action_name) {
        parent::before($action_name);

        $this->page->setTitle($this->lang->ad_users);
        $this->page->addPathway($this->lang->ad_users, $this->genActionUrl());
    }

    /**
     * @var \components\users\model
     */
    public $model;

    public function actionIndex() {
        if (!\cmsUser::isAdminCan('admin/users', \cmsUser::getAdminAccess())) {
            self::accessDenied();
        }

        self::addToolMenuItems([
            ['title' => $this->lang->ad_user_add, 'link' => $this->genActionUrl('add'), 'icon' => 'useradd.gif'],
            ['title' => $this->lang->ad_edit_selected, 'link' => "javascript:checkSel('" . $this->genActionUrl('edit', [], ['multiple' => 1]) . "');", 'icon' => 'useredit.gif'],
            ['title' => $this->lang->ad_edit_selected, 'link' => "javascript:if(confirm('{$this->lang->ad_if_users_select_remove}')) { checkSel('{$this->genActionUrl('delete', [], ['multiple' => 1])}'); };", 'icon' => 'userdelete.gif'],
            ['title' => $this->lang->ad_users_group, 'link' => $this->genActionUrl('usergroups'), 'icon' => 'usergroup.gif'],
            ['title' => $this->lang->ad_banlist, 'link' => $this->genActionUrl('userbanlist'), 'icon' => 'userbanlist.gif'],
            ['title' => $this->lang->ad_users_select_activate, 'link' => "javascript:if(confirm('{$this->lang->ad_users_select_activate}')) { checkSel('{$this->genActionUrl('activate', [], ['multiple' => 1])}'); }", 'icon' => 'user_go.png'],
            ['title' => $this->lang->ad_help, 'link' => "javascript:if(confirm('{$this->lang->ad_users_select_activate}')) { checkSel('https://cmsrudi.ru/help/com_users'); }", 'icon' => 'help.gif']
        ]);

        $fields = [
            ['title' => 'id', 'field' => 'id', 'width' => '20'],
            ['title' => $this->lang->ad_login, 'field' => 'login', 'width' => '100', 'link' => '/cp/components/users/edit/%id%', 'filter' => 12],
            ['title' => $this->lang->ad_nickname, 'field' => 'nickname', 'width' => '', 'link' => '/cp/components/users/edit/%id%', 'filter' => 12],
            ['title' => $this->lang->ad_rating, 'field' => 'rating', 'width' => '60', 'link' => '/cp/components/users/recalculate_rating/%id%'],
            ['title' => $this->lang->ad_group, 'field' => 'group_title', 'width' => '110', 'link' => '/cp/components/users/group_edit/%group_id%', 'filter' => 1, 'filter_name' => 'group_id', 'filterlist' => cpGetList('user_groups')],
            ['title' => $this->lang->email, 'field' => 'email', 'width' => '120'],
            ['title' => $this->lang->ad_registration_date, 'field' => 'regdate', 'width' => '100'],
            ['title' => $this->lang->ad_last_login, 'field' => 'logdate', 'width' => '100'],
            ['title' => $this->lang->ad_last_ip, 'field' => 'last_ip', 'width' => '90', 'link' => 'https://geoiplookup.io/geoip/%last_ip%'],
            ['title' => $this->lang->ad_is_locked, 'field' => 'is_locked', 'width' => '95'],
            ['title' => $this->lang->ad_is_deleted, 'field' => 'is_deleted', 'width' => '70'],
        ];

        $actions = [
            ['title' => $this->lang->ad_profile, 'icon' => 'profile.gif', 'link' => '/users/%login%'],
            ['title' => $this->lang->ad_banned, 'icon' => 'ban.gif', 'link' => '/cp/components/users/add_ban/%id%'],
            ['title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_is_user_delete, 'link' => '/cp/components/users/delete/%id%'],
            ['title' => $this->lang->ad_forever_user_delete, 'icon' => 'off.gif', 'confirm' => $this->lang->ad_if_forever_user_delete, 'link' => '/cp/components/users/delete_full/%id%'],
        ];

        $this->initListTable('users', $fields, $actions);
    }

    public function actionArhiveOff($item_id) {
        $this->model('content')->moveFromArhive($item_id);

        $this->redirectToAction();
    }

    public function actionConfig() {
        $this->page->addPathway($this->lang->ad_settings);

        self::addToolMenuItem($this->lang->ad_list_of_articles, $this->genActionUrl(), 'folders.gif');

        $this->page->initTemplate('components/arhive/backend', 'config')->
                assign('submit_uri', $this->genActionUrl('save_config'))->
                assign('base_uri', $this->genActionUrl())->
                assign('options', $this->options)->
                display();
    }

    public function actionSaveConfig() {
        if (!\cmsUser::checkCsrfToken()) {
            \cmsCore::error404();
        }
    }
}