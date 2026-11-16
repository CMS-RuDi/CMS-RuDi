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

class banlist extends \cms\com_action {
    private $ban_defaults = [
        'id' => 0,
        'user_id' => 0,
        'ip' => '',
        'int_num' => 0,
        'group_id' => 1,
        'int_period' => 'HOUR',
        'cause' => ''
    ];

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }

        $this->page->setTitle($this->lang->ad_banlist);
        $this->page->addPathway($this->lang->ad_banlist, $this->genActionUrl('banlist'));
    }

    public function actView() {
        self::addToolMenuItems([
            ['title' => $this->lang->ad_to_banlist_add, 'link' => $this->genActionUrl('banlist', 'add'), 'icon' => 'useradd.gif'],
            ['title' => $this->lang->ad_edit_selected, 'link' => "javascript:checkSel('" . $this->genActionUrl('banlist', 'edits') . "');", 'icon' => 'edit.gif'],
            ['title' => $this->lang->ad_delete_selected, 'link' => "javascript:checkSel('" . $this->genActionUrl('banlist', 'deletes') . "');", 'icon' => 'delete.gif']
        ]);

        $fields = [
            ['title' => 'id', 'field' => 'id', 'width' => '30'],
            ['title' => $this->lang->ad_is_active, 'field' => 'status', 'width' => '55', 'prc' => [$this, 'yesno']],
            ['title' => $this->lang->ad_banlist_user, 'field' => 'user_nickname', 'width' => '120', 'filter' => '12', 'prc' => 'cpUserNick'],
            ['title' => $this->lang->ad_banlist_ip, 'field' => 'ip', 'width' => '100', 'link' => $this->genActionUrl('banlist', ['edit', '%id%']), 'filter' => '12'],
            ['title' => $this->lang->date, 'field' => 'bandate', 'width' => '', 'fdate' => '%d/%m/%Y %H:%i:%s', 'filter' => '12'],
            ['title' => $this->lang->ad_banlist_time, 'field' => 'int_num', 'width' => '55'],
            ['title' => '', 'field' => 'int_period', 'width' => '70'],
            ['title' => $this->lang->ad_autoremove, 'field' => 'autodelete', 'width' => '90', 'prc' => [$this, 'yesno']]
        ];

        $actions = [
            ['title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('banlist', ['edit', '%id%'])],
            ['title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_remove_rule, 'link' => $this->genActionUrl('banlist', ['delete', '%id%'])]
        ];

        $this->initListTable('banlist', $fields, $actions);
    }

    public function actAdd($user_id = 0) {
        $this->page()->addPathway($this->lang->ad_to_banlist_add);
        \cms\backend::setTitle($this->lang->ad_to_banlist_add);

        if ($user_id > 0) {
            $item = $this->ban_defaults;
            $item['user_id'] = $user_id;
            $item['ip'] = $this->model->db->getField('users', $user_id, 'last_ip');
            \cmsUser::sessionPut('back_url', \cmsCore::getBackURL());
        } else {
            $item = $this->ban_defaults;
        }

        $this->page->initTemplate('components/users/backend', 'ban_edit')->
                assign('submit_uri', $this->genActionUrl('banlist', ['submit', '0']))->
                assign('item', $item)->
                assign('users_list', $this->core->getListItems('cms_users', !empty($item['user_id']) ? $item['user_id'] : 0, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname'))->
                display();
    }

    public function actEdit($id) {
        $item = $this->model->db->getItemById('banlist', $id);

        if (empty($item)) {
            \cmsCore::error404();
        }

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => "javascript:document.addform.submit();", 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => "javascript:history.go(-1);", 'icon' => 'cancel.gif']
        ]);

        $this->page->addPathway($this->lang->ad_edit_rule);

        $this->page->addHeadJS('admin/js/banlist.js');

        $this->page->initTemplate('components/users/backend', 'ban_edit')->
                assign('submit_uri', $this->genActionUrl('banlist', ['submit', $item['id']]))->
                assign('item', $item)->
                assign('users_list', $this->core->getListItems('cms_users', !empty($item['user_id']) ? $item['user_id'] : 0, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname'))->
                display();
    }

    public function actSubmit($ban_id = 0) {
        if (!\cms\csrf_token::checkToken()) {
            \cmsCore::error404();
        }

        $types = array(
            'user_id' => array('user_id', 'int', 0),
            'ip' => array('ip', 'str', ''),
            'cause' => array('cause', 'str', ''),
            'autodelete' => array('autodelete', 'int', 0),
            'int_num' => array('int_num', 'int', 0),
            'int_period' => array('int_period', 'str', '',
                function ($p) {
                    if (!in_array($p, array("MONTH", "DAY", "HOUR", "MINUTE"))) {
                        $p = "MINUTE";
                    } return $p;
                }
            )
        );

        $item = $this->input->getArrayFromRequest($types);

        $error = false;

        if (!$item['ip']) {
            $error = true;
            \cmsCore::addSessionMessage($this->lang->ad_need_ip, 'error');
        }
        if ($item['ip'] == $_SERVER['REMOTE_ADDR'] || $item['user_id'] == $this->user->id) {
            $error = true;
            \cmsCore::addSessionMessage($this->lang->ad_its_your_ip, 'error');
        }

        if (\cmsUser::userIsAdmin($item['user_id'])) {
            $error = true;
            \cmsCore::addSessionMessage($this->ad_its_admin, 'error');
        }

        if ($error) {
            \cmsCore::redirectBack();
        }

        if ($ban_id > 0) {
            $this->model->updateBan($ban_id, $item);

            if (!\cms\session::has('ban_edit_list')) {
                \cmsCore::redirect($this->genActionUrl('banlist'));
            } else {
                \cmsCore::redirect($this->genActionUrl('banlist', 'edits'));
            }
        } else {
            $this->model->createBan($item);
            $back_url = \cms\session::get('back_url');
            \cms\session::delete('back_url');
            \cmsCore::redirect($back_url ? $back_url : $this->genActionUrl('banlist'));
        }
    }

    public function actEdits() {
        $this->actEdit($this->getMultipleItem('ban_edit_list'));
    }

    public function actDelete($id) {
        if ($id > 0) {
            $this->model->db->delete('banlist', $id);
            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirectBack();
        }
        \cmsCore::error404();
    }

    public function actDeletes() {
        if ($this->input->has('item')) {
            $items = $this->input->get('item', 'array_int', []);
            foreach ($items as $id) {
                $this->model->db->delete('banlist', $id);
            }
            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirectBack();
        }

        \cmsCore::error404();
    }
}