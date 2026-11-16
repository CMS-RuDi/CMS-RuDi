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

class rerating extends \cms\com_action {

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }
    }

    public function actView($user_id) {
        if (empty($user_id)) {
            \cmsCore::redirectBack();
        }

        $rating = $this->user->getRating($user_id);

        $this->model->db->update('users', (int) $user_id, ['rating' => $rating]);

        \cmsCore::redirectBack();
    }
}