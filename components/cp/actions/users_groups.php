<?php

namespace components\cp\actions;

class users_groups extends \cms\com_action
{

    protected $default_action = 'list';

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/users', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_users_group);
        $this->page()->addPathway($this->lang->ad_users, $this->genActionUrl('users'));
        $this->page()->addPathway($this->lang->ad_users_group, $this->genActionUrl('users_groups'));
    }

    public function actList()
    {
        self::addToolMenuItem($this->lang->ad_create_group, $this->genActionUrl('users_groups', 'add'), 'usergroupadd.gif');
        self::addToolMenuItem($this->lang->ad_edit_selected, "javascript:checkSel('" . $this->genActionUrl('users_groups', 'edit', [ 'multiple' => 1 ]) . "');", 'edit.gif');
        self::addToolMenuItem($this->lang->ad_delete_selected, "javascript:if(confirm('" . $this->lang->ad_remove_group . "')) { checkSel('" . $this->genActionUrl('users_groups', 'delete', [ 'multiple' => 1 ]) . "'); }", 'delete.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [ 'title' => $this->lang->title, 'field' => 'title', 'width' => '', 'link' => $this->genActionUrl('users_groups', [ 'edit', '%id%' ]), 'filter' => '12' ],
            [ 'title' => $this->lang->ad_from_users, 'field' => 'id', 'width' => '100', 'prc' => [ $this, 'getCountUsers' ] ],
            [ 'title' => $this->lang->ad_if_admin, 'field' => 'is_admin', 'width' => '110', 'prc' => [ $this, 'yesno' ] ],
            [ 'title' => $this->lang->ad_alias, 'field' => 'alias', 'width' => '75', 'filter' => '12' ]
        ];

        $actions = [
            [ 'title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('users_groups', [ 'edit', '%id%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_remove_group, 'link' => $this->genActionUrl('users_groups', [ 'delete', '%id%' ]) ]
        ];

        cpListTable('cms_user_groups', $fields, $actions);
    }

    public function actDelete($group_id = false)
    {
        if ( $group_id === false ) {
            $this->model('users')->deleteGroup($this->request()->get('item', 'array_int', []));
        }
        else if ( $group_id > 1 ) {
            $this->model('users')->deleteGroup($group_id);
        }

        \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

        \cmsCore::redirectBack();
    }

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_create_group);
        \cms\backend::setTitle($this->lang->ad_create_group);

        return $this->actEdit(false, 'add');
    }

    public function actEdit($item_id, $do = 'edit')
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
                $item_id = array_shift($_SESSION['editlist']);

                if ( sizeof($_SESSION['editlist']) == 0 ) {
                    unset($_SESSION['editlist']);
                }
                else {
                    $ostatok = '(' . $this->lang->ad_next_in . sizeof($_SESSION['editlist']) . ')';
                }
            }

            if ( empty($item_id) ) {
                $item_id = $this->request()->get('id', 'int', 0);
            }

            $item = $this->model->getFields('groups', 'id=' . $item_id);

            if ( empty($item) ) {
                \cmsCore::error404();
            }

            $this->page()->addPathway($this->lang->ad_edit_group . $item['title']);
            \cms\backend::setTitle($this->lang->ad_edit_group . $ostatok);
        }
        else {
            $item = [
                'title' => '',
                'alias' => '',
            ];
        }

        if ( isset($item['access']) ) {
            $item['access'] = str_replace(', ', ',', $item['access']);
            $item['access'] = explode(',', $item['access']);
        }
        else {
            $item['access'] = [];
        }

        $this->page()->initTemplate('cp/applets', 'user_group_edit')->
                assign('submit_uri', $this->genActionUrl('users_groups', 'submit'))->
                assign('do', $do)->
                assign('item', $item)->
                assign('components', $this->getAllComponents())->
                assign('group_access', $this->model->db->getRows('user_groups_access', 1, '*', 'access_type'))->
                display();
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $types = [
            'title'    => [ 'title', 'str', '' ],
            'alias'    => [ 'alias', 'str', '' ],
            'is_admin' => [ 'is_admin', 'int', 0 ],
            'access'   => [ 'access', 'array_str', [], function($a_list) {
                    return implode(', ', $a_list);
                } ]
        ];

        $item = $this->request()->getArrayFromRequest($types);

        $item_id = $this->request()->get('item_id', 'int');

        if ( empty($item_id) ) {
            $this->model->db->insert('user_groups', $item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            $this->redirectToAction('users_groups');
        }
        else {
            $this->model->db->update('user_groups', 'id=' . $id, $item);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            $this->redirectToAction('users_groups', empty($_SESSION['editlist']) ? '' : 'edit');
        }
    }

    //========================================================================//

    public function getCountUsers($id)
    {
        $count = $this->model->db->getRowsCount('users', 'group_id=' . $id);

        return '<a href="' . $this->genActionUrl('users', '', [ 'filter[group_id]' => $id ]) . '">' . $count . '</a>';
    }

    public function getAllComponents()
    {
        $items = [];

        $components = \cms\controller::getAllComponents();

        foreach ( $components as $component ) {
            if ( !self::componentHasNewBackend($component) || !self::componentHasOldBackend($component) ) {
                continue;
            }

            $items[] = $component;
        }

        return $items;
    }

}
