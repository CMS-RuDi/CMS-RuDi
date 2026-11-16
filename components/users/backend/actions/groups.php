<?php

/*
 *                      CMS RuDi v1.10.9
 *                    https://cmsrudi.ru/
 *
 *              Written by CMS RuDi Team, 2026
 *           produced by DagIT, (https://dagit.su)
 *
 *                  LICENSED BY GNU/GPL v2
 */

namespace components\users\backend\actions;

class groups extends \cms\com_action {

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }
    }

    public function actView() {
        $this->page->addPathway($this->lang->ad_users_group);
        $this->page->setTitle($this->lang->ad_users_group);

        self::addToolMenuItems([
            ['title' => $this->lang->ad_create_group, 'link' => $this->genActionUrl('add', 'group'), 'icon' => 'usergroupadd.gif'],
            ['title' => $this->lang->ad_edit_selected, 'link' => "javascript:checkSel('" . $this->genActionUrl('edit', 'groups') . "');", 'icon' => 'edit.gif'],
            ['title' => $this->lang->ad_delete_selected, 'link' => "javascript:if(confirm('" . $this->lang->ad_remove_group . "')) { checkSel('" . $this->genActionUrl('delete', 'groups') . "'); };", 'icon' => 'delete.gif']
        ]);

        $fields = [
            ['title' => 'id', 'field' => 'id', 'width' => '30'],
            ['title' => $this->lang->title, 'field' => 'title', 'width' => '', 'link' => $this->genActionUrl('edit', ['group', '%id%']), 'filter' => 12],
            ['title' => $this->lang->ad_from_users, 'field' => 'id', 'width' => '100', 'prc' => [$this, 'getCountUsers']],
            ['title' => $this->lang->ad_if_admin, 'field' => 'is_admin', 'width' => '110', 'prc' => [$this, 'yesNo']],
            ['title' => $this->lang->ad_alias, 'field' => 'alias', 'width' => '75', 'filter' => 12]
        ];

        $actions = [
            ['title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('edit', ['group', '%id%'])],
            ['title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_remove_group, 'link' => $this->genActionUrl('delete', ['group', '%id%'])]
        ];

        $this->initListTable('user_groups', $fields, $actions);
    }

    public function getCountUsers($group_id) {
        $this->model->db->resetFilters()
                ->filterEqual('group_id', $group_id);
        return '<a href="' . $this->genActionUrl('users', '', ['filter[group_id]' => $group_id]) . '">' . $this->model->getCountUsers() . '</a>';
    }
}