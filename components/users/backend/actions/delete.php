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

class delete extends \cms\com_action {

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }
    }

    public function actUser($user_id) {
        if ($user_id > 0) {
            $this->model->deleteUser($user_id);
            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirectBack();
        }
        \cmsCore::error404();
    }

    public function actUsers() {
        if ($this->input->has('item')) {
            $this->model->deleteUsers($this->input->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirectBack();
        }
        \cmsCore::error404();
    }

    public function actUserForever($user_id) {
        if ($user_id > 0) {
            $this->model->deleteUser($user_id, true);
            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirectBack();
        }
        \cmsCore::error404();
    }

    public function actGroup($group_id) {
        if ($group_id > 0) {
            $this->model->deleteGroup($group_id);
            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirect($this->genActionUrl('groups'));
        }
    }

    public function actGroups() {
        if ($this->input->has('item')) {
            $this->model->deleteGroups($this->input->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            return \cmsCore::redirect($this->genActionUrl('groups'));
        }
        \cmsCore::error404();
    }
}