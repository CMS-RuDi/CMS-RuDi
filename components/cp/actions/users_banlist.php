<?php

namespace components\cp\actions;

class users_banlist extends \cms\com_action
{

    protected $default_action = 'list';

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/users', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_banlist);
        $this->page()->addPathway($this->lang->ad_users, $this->genActionUrl('users'));
        $this->page()->addPathway($this->lang->ad_banlist, $this->genActionUrl('users_banlist'));
    }

    public function actList()
    {
        self::addToolMenuItem($this->lang->ad_to_banlist_add, $this->genActionUrl('users_banlist', 'add'), 'useradd.gif');
        self::addToolMenuItem($this->lang->ad_edit_selected, "javascript:checkSel('" . $this->genActionUrl('users_banlist', 'edit', [ 'multiple' => 1 ]) . "');", 'edit.gif');
        self::addToolMenuItem($this->lang->ad_delete_selected, $this->genActionUrl('users_banlist', 'delete', [ 'multiple' => 1 ]), 'delete.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [ 'title' => $this->lang->ad_is_active, 'field' => 'status', 'width' => '55', 'prc' => [ $this, 'yesno' ] ],
            [ 'title' => $this->lang->ad_banlist_user, 'field' => 'user_id', 'width' => '120', 'filter' => '12', 'prc' => 'cpUserNick' ],
            [ 'title' => $this->lang->ad_banlist_ip, 'field' => 'ip', 'width' => '100', 'link' => $this->genActionUrl('users_banlist', [ 'edit', '%id%' ]), 'filter' => '12' ],
            [ 'title' => $this->lang->date, 'field' => 'bandate', 'width' => '', 'fdate' => '%d/%m/%Y %H:%i:%s', 'filter' => '12' ],
            [ 'title' => $this->lang->ad_banlist_time, 'field' => 'int_num', 'width' => '55' ],
            [ 'title' => '', 'field' => 'int_period', 'width' => '70' ],
            [ 'title' => $this->lang->ad_autoremove, 'field' => 'autodelete', 'width' => '90', 'prc' => [ $this, 'yesno' ] ]
        ];

        $actions = [
            [ 'title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('users_banlist', [ 'edit', '%id%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_remove_rule, 'link' => $this->genActionUrl('users_banlist', [ 'delete', '%id%' ]) ]
        ];

        cpListTable('cms_banlist', $fields, $actions, '1=1', 'ip DESC');
    }

    public function actDelete($user_id = false)
    {
        if ( $user_id === false ) {
            dbDeleteList('cms_banlist', $this->request()->get('item', 'array_int', []));
        }
        else if ( $user_id > 1 ) {
            dbDelete('cms_banlist', $user_id);
            $this->model('users')->deleteUser($user_id);
        }

        \cmsCore::redirectBack();
    }

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_to_banlist_add);
        \cms\backend::setTitle($this->lang->ad_to_banlist_add);

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

            $item = $this->model->getFields('banlist', 'id=' . $item_id);

            if ( empty($item) ) {
                \cmsCore::error404();
            }

            $this->page()->addPathway($this->lang->ad_edit_rule);
            \cms\backend::setTitle($this->lang->ad_edit_rule . $ostatok);
        }
        else {
            $item_id = $this->request()->get('to', 'int');

            if ( !empty($item_id) ) {
                $item = [
                    'user_id' => $item_id,
                    'ip'      => $this->model->db->getField('users', 'id=' . $item_id, 'last_ip')
                ];

                \cmsUser::sessionPut('back_url', \cmsCore::getBackURL());
            }
            else {
                $item = [
                    'ip'         => '',
                    'cause'      => '',
                    'int_num'    => 0,
                    'int_period' => 'HOUR',
                ];
            }
        }

        $this->page()->addHeadJS('admin/js/banlist.js');

        $this->page()->initTemplate('cp/applets', 'user_ban_edit')->
                assign('submit_uri', $this->genActionUrl('users_banlist', 'submit'))->
                assign('do', $do)->
                assign('item', $item)->
                assign('users_list', $this->core->getListItems('cms_users', !empty($item['user_id']) ? $item['user_id'] : 0, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname'))->
                display();
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $types = [
            'user_id'    => [ 'user_id', 'int', 0 ],
            'ip'         => [ 'ip', 'str', '' ],
            'cause'      => [ 'cause', 'str', '' ],
            'autodelete' => [ 'autodelete', 'int', 0 ],
            'int_num'    => [ 'int_num', 'int', 0 ],
            'int_period' => [ 'int_period', 'str', '', function($p) {
                    return !in_array($p, [ "MONTH", "DAY", "HOUR", "MINUTE" ]) ? 'MINUTE' : $p;
                } ]
        ];

        $item = $this->request()->getArrayFromRequest($types);

        $error = false;

        if ( empty($item['ip']) ) {
            $error = true;
            \cmsCore::addSessionMessage($this->lang->ad_need_ip, 'error');
        }

        if ( $item['ip'] == $_SERVER['REMOTE_ADDR'] || $item['user_id'] == $this->user()->id ) {
            $error = true;
            \cmsCore::addSessionMessage($this->lang->ad_its_your_ip, 'error');
        }

        if ( \cmsUser::userIsAdmin($item['user_id']) ) {
            $error = true;
            \cmsCore::addSessionMessage($this->lang->ad_its_admin, 'error');
        }

        if ( $error ) {
            \cmsCore::redirectBack();
        }

        $item_id = $this->request()->get('item_id', 'int');

        if ( !empty($item_id) ) {
            $this->model->db->update('banlist', 'id=' . $item_id . ' LIMIT 1', $item);

            $this->redirectToAction('users_banlist', empty($_SESSION['editlist']) ? '' : 'edit');
        }

        $this->model->db->insert('banlist', $item);

        $back_url = \cmsUser::sessionGet('back_url');

        \cmsUser::sessionDel('back_url');

        \cmsCore::redirect($back_url ? $back_url : $this->genActionUrl('users_banlist'));
    }

}
