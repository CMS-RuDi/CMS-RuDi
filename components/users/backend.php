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
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
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
            ['title' => $this->lang->ad_is_locked, 'field' => 'is_locked', 'width' => '95', 'prc' => [$this, 'viewAct']],
            ['title' => $this->lang->ad_is_deleted, 'field' => 'is_deleted', 'width' => '70', 'prc' => [$this, 'viewDel']],
        ];

        $actions = [
            ['title' => $this->lang->ad_profile, 'icon' => 'profile.gif', 'link' => '/users/%login%'],
            ['title' => $this->lang->ad_banned, 'icon' => 'ban.gif', 'link' => '/cp/components/users/add_ban/%id%'],
            ['title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_is_user_delete, 'link' => '/cp/components/users/delete/%id%'],
            ['title' => $this->lang->ad_forever_user_delete, 'icon' => 'off.gif', 'confirm' => $this->lang->ad_if_forever_user_delete, 'link' => '/cp/components/users/delete_full/%id%'],
        ];

        $this->initListTable('users', $fields, $actions);
    }

    public function actionConfig() {
        $this->page->addPathway($this->lang->ad_settings);

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => 'javascript:document.optform.submit();', 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => '/cp/components', 'icon' => 'cancel.gif']
        ]);

        cpCheckWritable('/images/users/avatars', 'folder');
        cpCheckWritable('/images/users/avatars/small', 'folder');
        cpCheckWritable('/images/users/photos', 'folder');
        cpCheckWritable('/images/users/photos/small', 'folder');
        cpCheckWritable('/images/users/photos/medium', 'folder');

        $this->page->initTemplate('components/users/backend', 'config')
                ->assign('submit_uri', $this->genActionUrl('save_config'))
                ->assign('base_uri', $this->genActionUrl())
                ->assign('options', $this->options)
                ->assign('forum_enabled', \cms\controller::enabled('forum'))
                ->assign('blogs_enabled', $this->enabled('board'))
                ->assign('forms', $this->model->db->get('forms'))
                ->display();
    }

    public function actionSaveConfig() {
        if (!\cmsUser::checkCsrfToken()) {
            \cmsCore::error404();
        }

        $options = [
            'sw_comm' => $this->input->get('sw_comm', 'int', 0),
            'sw_search' => $this->input->get('sw_search', 'int', 0),
            'sw_forum' => $this->input->get('sw_forum', 'int', 0),
            'sw_photo' => $this->input->get('sw_photo', 'int', 0),
            'sw_wall' => $this->input->get('sw_wall', 'int', 0),
            'sw_blogs' => $this->input->get('sw_blogs', 'int', 0),
            'sw_clubs' => $this->input->get('sw_clubs', 'int', 0),
            'sw_feed' => $this->input->get('sw_feed', 'int', 0),
            'sw_awards' => $this->input->get('sw_awards', 'int', 0),
            'sw_board' => $this->input->get('sw_board', 'int', 0),
            'sw_msg' => $this->input->get('sw_msg', 'int', 0),
            'sw_guest' => $this->input->get('sw_guest', 'int', 0),
            'sw_files' => $this->input->get('sw_files', 'int', 0),
            'karmatime' => $this->input->get('karmatime', 'int', 0),
            'karmaint' => $this->input->get('karmaint', ['MINUTE', 'HOUR', 'DAY', 'MONTH'], 'DAY'),
            'photosize' => $this->input->get('photosize', 'int', 0),
            'watermark' => $this->input->get('watermark', 'int', 0),
            'smallw' => $this->input->get('smallw', 'int', 64),
            'medw' => $this->input->get('medw', 'int', 200),
            'medh' => $this->input->get('medh', 'int', 200),
            'filessize' => $this->input->get('filessize', 'int', 0)
        ];

        $filetype = mb_strtolower($this->input->get('filestype', 'str', 'jpeg,gif,png,jpg,bmp,zip,rar,tar'));
        $filetype = explode(',', $filetype);
        foreach ($filetype as $k => $v) {
            if (mb_strpos($v, 'htm') || mb_strpos($v, 'php') || mb_strpos($v, 'ht')) {
                unset($filetype[$k]);
            }
        }
        $options['filetype'] = implode(',', $filetype);

        $options['privforms'] = $this->input->get('privforms', 'array_int');
        $options['deltime'] = $this->input->get('deltime', 'int', 0);
        $options['users_perpage'] = $this->input->get('users_perpage', 'int', 10);
        $options['wall_perpage'] = $this->input->get('wall_perpage', 'int', 10);

        $this->saveOptions($this->name, $options);
        $this->core->addSessionMessage($this->lang->ad_config_save_success, 'success');
        $this->redirectToAction('config');
    }

    #================================================================

    function viewAct($value) {
        if (!$value) {
            return '<span style="color:green;">' . $this->lang->yes . '</span>';
        }

        return '<span style="color:red;">' . $this->lang->no . '</span>';
    }

    function viewDel($value) {
        if ($value) {
            return '<span style="color:red;">' . $this->lang->yes . '</span>';
        }

        return '<span style="color:green;">' . $this->lang->no . '</span>';
    }
}