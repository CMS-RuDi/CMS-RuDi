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

class edit extends \cms\com_action {
    private $user_defaults = [
        'id' => 0,
        'login' => '',
        'nickname' => '',
        'email' => '',
        'group_id' => 1,
        'is_locked' => 0,
        'is_deleted' => 0,
        'rating' => 0
    ];
    private $group_defaults = [
        'id' => 0,
        'title' => '',
        'alias' => '',
        'is_admin' => 0,
        'access' => ''
    ];

    public function doBefore() {
        if (!\cmsUser::isAdminCan('admin/users', $this->admin_access)) {
            self::accessDenied();
        }
    }

    /**
     * Редактирование данных пользователя
     * @param int $user_id
     */
    public function actUser($user_id) {
        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => "javascript:document.addform.submit();", 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => "javascript:history.go(-1);", 'icon' => 'cancel.gif']
        ]);

        if ($user_id > 0) {
            $item = $this->model->getUser((int) $user_id);

            if (!$item) {
                \cmsCore::error404();
            }

            $item = array_merge($this->user_defaults, $item);

            $this->page->addPathway($item['nickname']);
        } else {
            $item = \cms\session::get('item');

            if ($item) {
                \cms\session::delete('item');
            }

            $item = array_merge($this->user_defaults, is_array($item) ? $item : []);

            $this->page->addPathway($this->lang->ad_user_add);
        }

        $ostatok = \cms\session::has('users_edit_list') ? \cms\session::get('users_edit_list') : [];

        $this->page->addHeadJS('components/registration/js/check.js')
                ->initTemplate('components/users/backend', 'edit')
                ->assign('submit_uri', $this->genActionUrl('edit', ['save_user', $user_id]))
                ->assign('ostatok', $ostatok ? ('(' . $this->lang->ad_next_in . count($ostatok) . ')') : '')
                ->assign('edit_group_uri', $this->genActionUrl('edit', 'group'))
                ->assign('groups', $this->model->getGroupsIdTitle())
                ->assign('item', $item)
                ->display();
    }

    public function actSaveUser($user_id) {
        if (!\cms\csrf_token::checkToken()) {
            \cmsCore::error404();
        }

        $types = [
            'login' => ['login', 'str', ''],
            'nickname' => ['nickname', 'str', '', 'htmlspecialchars'],
            'email' => ['email', 'email', ''],
            'group_id' => ['group_id', 'int', 1],
            'is_locked' => ['is_locked', 'int', 0],
            'password' => ['pass', 'str', '', 'stripslashes'],
            'pass2' => ['pass2', 'str', '', 'stripslashes']
        ];

        $item = array_merge($this->user_defaults, $this->input->getArrayFromRequest($types));
        unset($item['id']);

        $errors = false;

        // проверяем логин
        if (mb_strlen($item['login']) < 2 ||
                mb_strlen($item['login']) > 15 ||
                is_numeric($item['login']) ||
                !preg_match("/^([a-zA-Z0-9])+$/ui", $item['login'])) {

            \cmsCore::addSessionMessage($this->lang->err_login, 'error');
            $errors = true;
        }

        // проверяем пароль
        if ($user_id == 0) {
            if (!$item['password']) {
                \cmsCore::addSessionMessage($this->lang->type_pass, 'error');
                $errors = true;
            }
        }
        if ($item['password'] && !$item['pass2']) {
            \cmsCore::addSessionMessage($this->lang->type_pass_twice, 'error');
            $errors = true;
        }
        if ($item['password'] && $item['pass2'] && mb_strlen($item['password']) < 6) {
            \cmsCore::addSessionMessage($this->lang->pass_short, 'error');
            $errors = true;
        }
        if ($item['password'] && $item['pass2'] && $item['password'] != $item['pass2']) {
            \cmsCore::addSessionMessage($this->lang->wrong_pass, 'error');
            $errors = true;
        }

        // никнейм
        if (mb_strlen($item['nickname']) < 2) {
            \cmsCore::addSessionMessage($this->lang->short_nickname, 'error');
            $errors = true;
        }
        // Проверяем email
        if (!$item['email']) {
            \cmsCore::addSessionMessage($this->lang->err_email, 'error');
            $errors = true;
        }

        // проверяем есть ли такой пользователь
        if ($user_id == 0) {
            $user_exist = $this->model->userExist($item['login'], $item['email']);
            if ($user_exist) {
                if (mb_strtolower($user_exist['login']) == mb_strtolower($item['login'])) {
                    \cmsCore::addSessionMessage($this->lang->login . ' "' . $item['login'] . '" ' . $this->lang->login_is_busy, 'error');
                    $errors = true;
                } else {
                    \cmsCore::addSessionMessage($this->lang->email_is_busy, 'error');
                    $errors = true;
                }
            }
        }

        if ($errors) {
            if ($user_id == 0) {
                \cms\session::set('item', $item);
            }
            \cmsCore::redirectBack();
        }

        if ($user_id == 0) {
            $item['regdate'] = date('Y-m-d H:i:s');
            $item['logdate'] = date('Y-m-d H:i:s');
            $item['last_ip'] = \cmsUser::getIp();
            $item['password'] = md5($item['password']);

            $item['user_id'] = $this->model->createUser($item);
            if (!$item['user_id']) {
                \cmsCore::error404();
            }

            $this->model->createProfile($item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirect($this->genActionUrl());
        } else {
            if ($user_id == 1 && $this->user->id != $user_id) {
                \cmsCore::error404();
            }

            if ($user_id == 1) {
                unset($item['group_id']);
                unset($item['is_locked']);
            }

            if (!$item['password']) {
                unset($item['password']);
            } else {
                $item['password'] = md5($item['password']);
            }

            $this->model->updateUser($user_id, $item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            if (!\cms\session::has('users_edit_list')) {
                \cmsCore::redirect($this->genActionUrl());
            } else {
                \cmsCore::redirect($this->genActionUrl('edit', 'users'));
            }
        }
    }

    /**
     * Редактирование группы пользователей
     * @param int $group_id
     */
    public function actGroup($group_id) {
        $this->page->addPathway($this->lang->ad_users_group, $this->genActionUrl('groups'));

        self::addToolMenuItems([
            ['title' => $this->lang->save, 'link' => "javascript:document.addform.submit();", 'icon' => 'save.gif'],
            ['title' => $this->lang->cancel, 'link' => "javascript:history.go(-1);", 'icon' => 'cancel.gif']
        ]);

        if ($group_id == 0) {
            $this->page->addPathway($this->lang->ad_create_group);
            $item = $this->group_defaults;
        } else {
            $item = $this->model->getGroup($group_id);
            if (!$item) {
                \cmsCore::error404();
            }
            $item = array_merge($this->group_defaults, $item);

            $this->page->addPathway($this->lang->ad_group . ': ' . $item['title']);
        }

        if (isset($item['access'])) {
            $item['access'] = str_replace(', ', ',', $item['access']);
            $item['access'] = explode(',', $item['access']);
        }

        $components = [];
        $coms = $this->getAllComponents();
        foreach ($coms as $com) {
            if (!file_exists(PATH . '/admin/components/' . $com['link'] . '/backend.php') ||
                    !file_exists(PATH . '/components/' . $com['link'] . '/backend.php')) {
                continue;
            }
            $components[] = $com;
        }

        $group_access = $this->model->getUserGroupsAccess();
        if (isset($item['alias']) && $item['alias'] == 'guest') {
            foreach ($group_access as $k => $ga) {
                if (!empty($ga['hide_for_guest'])) {
                    unset($group_access[$k]);
                }
            }
        }

        $ostatok = \cms\session::has('users_edit_list') ? \cms\session::get('groups_edit_list') : [];

        $this->page->initTemplate('components/users/backend', 'edit_group')
                ->assign('submit_uri', $this->genActionUrl('edit', ['save_group', $group_id]))
                ->assign('ostatok', $ostatok ? ('(' . $this->lang->ad_next_in . count(\cms\session::get('groups_edit_list')) . ')') : '')
                ->assign('components', $components)
                ->assign('group_access', $group_access)
                ->assign('item', $item)
                ->display();
    }

    public function actSaveGroup($group_id) {
        if (!\cms\csrf_token::checkPost()) {
            \cmsCore::error404();
        }

        $types = [
            'title' => ['title', 'str', ''],
            'alias' => ['alias', 'str', ''],
            'is_admin' => ['is_admin', 'int', 0],
            'access' => [
                'access',
                'array_str',
                [],
                function ($a_list) {
                    return implode(',', $a_list);
                }
            ]
        ];

        $item = array_merge($this->group_defaults, $this->input->getArrayFromRequest($types));
        unset($item['id']);

        if ($group_id == 0) {
            $this->model->createGroup($item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirect($this->genActionUrl('groups'));
        } else {
            $this->model->updateGroup($group_id, $item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            if (!\cms\session::has('groups_edit_list')) {
                \cmsCore::redirect($this->genActionUrl('groups'));
            } else {
                \cmsCore::redirect($this->genActionUrl('edit', 'groups'));
            }
        }
    }

    /**
     * Массовое редактирование данных пользователей
     */
    public function actUsers() {
        $this->actUser($this->getMultipleItem('users_edit_list'));
    }

    /**
     * Массовое редактирование групп пользователей
     */
    public function actGroups() {
        $this->actGroup($this->getMultipleItem('groups_edit_list'));
    }
}