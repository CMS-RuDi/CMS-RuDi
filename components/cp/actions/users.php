<?php

namespace components\cp\actions;

class users extends \cms\com_action
{

    protected $default_action = 'list';

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/users', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_users);
        $this->page()->addPathway($this->lang->ad_users, $this->genActionUrl('users'));
    }

    public function actList()
    {
        self::addToolMenuItem($this->lang->ad_user_add, $this->genActionUrl('users', 'add'), 'useradd.gif');
        self::addToolMenuItem($this->lang->ad_edit_selected, "javascript:checkSel('" . $this->genActionUrl('users', 'edit', [ 'multiple' => 1 ]) . "');", 'useredit.gif');
        self::addToolMenuItem($this->lang->ad_delete_selected, "javascript:if(confirm('" . $this->lang->ad_if_users_select_remove . "')) { checkSel('" . $this->genActionUrl('users', 'delete', [ 'multiple' => 1 ]) . "'); }", 'userdelete.gif');
        self::addToolMenuItem($this->lang->ad_users_group, $this->genActionUrl('users_groups'), 'usergroup.gif');
        self::addToolMenuItem($this->lang->ad_banlist, $this->genActionUrl('users_banlist'), 'userbanlist.gif');
        self::addToolMenuItem($this->lang->ad_users_select_activate, "javascript:if(confirm('" . $this->lang->ad_if_users_select_activate . "')) { checkSel('" . $this->genActionUrl('users', 'activate', [ 'multiple' => 1 ]) . "'); }", 'user_go.png');
        self::addToolMenuItem($this->lang->ad_help, $this->genActionUrl('help', 'users'), 'help.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '20' ],
            [ 'title' => $this->lang->login, 'field' => 'login', 'width' => '100', 'link' => $this->genActionUrl('users', [ 'edit', '%id%' ]), 'filter' => 12 ],
            [ 'title' => $this->lang->nickname, 'field' => 'nickname', 'width' => '', 'link' => $this->genActionUrl('users', [ 'edit', '%id%' ]), 'filter' => 12 ],
            [ 'title' => $this->lang->ad_rating, 'field' => [ 'rating', 'id' ], 'width' => '60', 'prc' => [ $this, 'setRating' ] ],
            [ 'title' => $this->lang->AD_GROUP, 'field' => 'group_id', 'width' => '110', 'prc' => 'cpGroupById', 'filter' => 1, 'filterlist' => cpGetList('cms_user_groups') ],
            [ 'title' => $this->lang->email, 'field' => 'email', 'width' => '120' ],
            [ 'title' => $this->lang->ad_registration_date, 'field' => 'regdate', 'width' => '100' ],
            [ 'title' => $this->lang->ad_last_login, 'field' => 'logdate', 'width' => '100' ],
            [ 'title' => $this->lang->ad_last_ip, 'field' => 'last_ip', 'width' => '90', 'prc' => [ $this, 'getIpLink' ] ],
            [ 'title' => $this->lang->ad_is_locked, 'field' => 'is_locked', 'width' => '95', 'prc' => [ $this, 'yesno' ] ],
            [ 'title' => $this->lang->ad_is_deleted, 'field' => 'is_deleted', 'width' => '70', 'prc' => 'yesno' ]
        ];

        $actions = [
            [ 'title' => $this->lang->ad_profile, 'icon' => 'profile.gif', 'link' => '/users/%login%' ],
            [ 'title' => $this->lang->ad_banned, 'icon' => 'ban.gif', 'link' => $this->genActionUrl('users_banlist', [ 'add', '%id%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_is_user_delete, 'link' => $this->genActionUrl('users', [ 'delete', '%id%' ]) ],
            [ 'title' => $this->lang->ad_forever_user_delete, 'icon' => 'off.gif', 'confirm' => $this->lang->ad_if_forever_user_delete, 'link' => $this->genActionUrl('users', [ 'delete_full', '%id%' ]) ]
        ];

        cpListTable('cms_users', $fields, $actions, '1=1', 'regdate DESC');
    }

    public function actRerating($user_id)
    {
        if ( empty($user_id) ) {
            \cmsCore::redirectBack();
        }

        $rating = \cmsUser::getRating($user_id);

        $this->model->db->update('users', 'id=' . $user_id . ' LIMIT 1', [ 'rating' => $rating ]);

        \cmsCore::redirectBack();
    }

    public function actActivate()
    {
        $user_ids = $this->request()->get('item', 'array_int');

        if ( empty($user_ids) ) {
            \cmsCore::redirectBack();
        }

        foreach ( $user_ids as $user_id ) {
            $code = $this->model->db->getField('users_activate', 'user_id=' . $user_id, 'code');

            $this->model->db->update('users', 'id=' . $user_id . ' LIMIT 1', [ 'is_locked' => 0 ]);

            $this->model->db->delete('users_activate', "code='" . $code . "'");

            \cms\events::call('users.activated', $user_id);

            // Регистрируем событие
            \cmsActions::log('add_user', [
                'object'      => '',
                'user_id'     => $user_id,
                'object_url'  => '',
                'object_id'   => $user_id,
                'target'      => '',
                'target_url'  => '',
                'target_id'   => 0,
                'description' => ''
            ]);
        }

        \cmsCore::redirectBack();
    }

    public function actDelete($user_id = false)
    {
        if ( $user_id === false ) {
            $this->model('users')->deleteUsers($this->request()->get('item', 'array_int', []));
        }
        else if ( $user_id > 1 ) {
            $this->model('users')->deleteUser($user_id);
        }

        \cmsCore::redirectBack();
    }

    public function actDeleteFull($user_id)
    {
        $this->model('users')->deleteUser($user_id, true);

        \cmsCore::redirectBack();
    }

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_user_add);
        \cms\backend::setTitle($this->lang->ad_user_add);

        return $this->actEdit(false, 'add');
    }

    public function actEdit($user_id, $do = 'edit')
    {
        self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
        self::addToolMenuItem($this->lang->cancel, 'javascript:history.go(-1);', 'cancel.gif');

        if ( $do == 'edit' ) {
            if ( $this->request()->has('multiple') ) {
                if ( $this->request()->has('item') ) {
                    $_SESSION['editlist'] = $this->request()->get('item', 'array_int', []);
                }
                else {
                    \cmsCore::addSessionMessage($this->lang->ad_no_select_objects, 'error');
                    \cmsCore::redirectBack();
                }
            }

            $ostatok = '';

            if ( isset($_SESSION['editlist']) ) {
                $user_id = array_shift($_SESSION['editlist']);

                if ( sizeof($_SESSION['editlist']) == 0 ) {
                    unset($_SESSION['editlist']);
                }
                else {
                    $ostatok = '(' . $this->lang->ad_next_in . sizeof($_SESSION['editlist']) . ')';
                }
            }

            if ( empty($user_id) ) {
                $user_id = $this->request()->get('id', 'int', 0);
            }

            $user = $this->model->getFields('users', 'id=' . $user_id);

            if ( empty($user) ) {
                \cmsCore::error404();
            }

            $this->page()->addPathway($mod['nickname']);
            \cms\backend::setTitle($this->lang->ad_user_edit . $ostatok);
        }
        else {
            if ( \cmsUser::sessionGet('items') ) {
                \cmsUser::sessionDel('items');
            }
        }

        $this->page()->addHeadJS('components/registration/js/check.js');

        $tpl = $this->page()->initTemplate('cp/applets', 'user_edit')->
                assign('submit_uri', $this->genActionUrl('users', 'submit'))->
                assign('do', $do)->
                assign('groups_list', $this->core->getListItems('cms_user_groups', !empty($user['group_id']) ? $user['group_id'] : 0));

        if ( $do == 'edit' ) {
            $tpl->assign('user', $user)->
                    assign('groups_edit_uri', $this->genActionUrl('users_groups', [ 'edit', $user['group_id'] ]));
        }

        $tpl->display();
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $types = [
            'login'     => [ 'login', 'str', '' ],
            'nickname'  => [ 'nickname', 'str', '', 'htmlspecialchars' ],
            'email'     => [ 'email', 'email', '' ],
            'group_id'  => [ 'group_id', 'int', 1 ],
            'is_locked' => [ 'is_locked', 'int', 0 ],
            'password'  => [ 'pass', 'str', '', 'stripslashes' ],
            'pass2'     => [ 'pass2', 'str', '', 'stripslashes' ]
        ];

        $items = $this->request()->getArrayFromRequest($types);

        $errors = false;

        // проверяем логин
        if ( mb_strlen($items['login']) < 2 ||
                mb_strlen($items['login']) > 15 ||
                is_numeric($items['login']) ||
                !preg_match("/^([a-zA-Z0-9])+$/ui", $items['login']) ) {
            \cmsCore::addSessionMessage($this->lang->err_login, 'error');
            $errors = true;
        }

        $item_id = $this->request()->get('item_id', 'int');

        // проверяем пароль
        if ( empty($item_id) ) {
            if ( !$items['password'] ) {
                \cmsCore::addSessionMessage($this->lang->type_pass, 'error');
                $errors = true;
            }
        }

        if ( $items['password'] && !$items['pass2'] ) {
            \cmsCore::addSessionMessage($this->lang->type_pass_twice, 'error');
            $errors = true;
        }

        if ( $items['password'] && $items['pass2'] && mb_strlen($items['password']) < 6 ) {
            \cmsCore::addSessionMessage($this->lang->pass_short, 'error');
            $errors = true;
        }

        if ( $items['password'] && $items['pass2'] && $items['password'] != $items['pass2'] ) {
            \cmsCore::addSessionMessage($this->lang->wrong_pass, 'error');
            $errors = true;
        }

        // никнейм
        if ( mb_strlen($items['nickname']) < 2 ) {
            \cmsCore::addSessionMessage($this->lang->short_nickname, 'error');
            $errors = true;
        }

        // Проверяем email
        if ( !$items['email'] ) {
            \cmsCore::addSessionMessage($this->lang->err_email, 'error');
            $errors = true;
        }

        // проверяем есть ли такой пользователь
        if ( empty($item_id) ) {
            $user_exist = $this->model->db->getField('users', "(login LIKE '" . $items['login'] . "' OR email LIKE '" . $items['email'] . "') AND is_deleted = 0", 'login');

            if ( $user_exist ) {
                if ( $user_exist['login'] == $items['login'] ) {
                    \cmsCore::addSessionMessage($this->lang->login . ' "' . $items['login'] . '" ' . $this->lang->is_busy, 'error');
                    $errors = true;
                }
                else {
                    \cmsCore::addSessionMessage($this->lang->email_is_busy, 'error');
                    $errors = true;
                }
            }
        }

        if ( $errors ) {
            if ( empty($items) ) {
                \cmsUser::sessionPut('items', $items);
            }

            \cmsCore::redirectBack();
        }

        if ( empty($item_id) ) {
            $items['regdate']  = date('Y-m-d H:i:s');
            $items['logdate']  = date('Y-m-d H:i:s');
            $items['password'] = md5($items['password']);

            $items['user_id'] = $this->model->db->insert('users', $items);

            if ( !$items['user_id'] ) {
                \cmsCore::error404();
            }

            $this->model->db->insert('user_profiles', $items);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            $this->redirectToAction('users');
        }
        else {
            // главного админа может редактировать только он сам
            if ( $item_id == 1 && $this->user()->id != $item_id ) {
                \cmsCore::error404();
            }

            if ( $item_id == 1 ) {
                unset($items['group_id']);
                unset($items['is_locked']);
            }

            if ( !$items['password'] ) {
                unset($items['password']);
            }
            else {
                $items['password'] = md5($items['password']);
            }

            $this->model->db->update('users', 'id=' . $item_id, $items);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            if ( empty($_SESSION['editlist']) ) {
                $this->redirectToAction('users');
            }
            else {
                $this->redirectToAction('users', 'edit');
            }
        }
    }

    //========================================================================//

    public function setRating($item)
    {
        return '<a href="' . $this->genActionUrl('users', [ 'rerating', $item['id'] ]) . '" title="' . $this->lang->ad_rating_calculate . '">' . $item['rating'] . '</a>';
    }

    public function getIpLink($ip)
    {
        return '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . $ip . '">' . $ip . '</a>';
    }

}
