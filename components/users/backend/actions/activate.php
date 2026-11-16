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

class activate extends \cms\com_action {

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }
    }

    public function actUsers() {
        $user_ids = $this->input->get('item', 'array_int', []);

        if (empty($user_ids)) {
            \cmsCore::redirectBack();
        }

        foreach ($user_ids as $user_id) {
            $this->model->activateUser($user_id);
            \cms\events::call('users.activated', $user_id);

            // Регистрируем событие
            \cmsActions::log('add_user', [
                'object' => '',
                'user_id' => $user_id,
                'object_url' => '',
                'object_id' => $user_id,
                'target' => '',
                'target_url' => '',
                'target_id' => 0,
                'description' => ''
            ]);
        }

        \cmsCore::redirectBack();
    }
}