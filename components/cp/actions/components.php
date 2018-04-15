<?php

namespace components\cp\actions;

class components extends \cms\com_action
{

    protected static $com_title_tpl;

    public function run(...$params)
    {
        if ( !\cmsUser::isAdminCan('admin/components', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_components);
        $this->page()->addPathway($this->lang->ad_components, $this->genActionUrl('components'));

        if ( empty($params) ) {
            $do = $this->request()->get('do', 'str');

            if ( !empty($do) ) {
                $id = $this->request()->get('id', 'int', -1);

                if ( $do == 'show' ) {
                    dbShow('cms_components', $id);
                }
                else if ( $do == 'hide' ) {
                    dbHide('cms_components', $id);
                }
                else {
                    \cmsCore::error404();
                }

                \cmsCore::halt('1');
            }

            return $this->componentsList();
        }
        else {
            $com_link = array_shift($params);
        }

        if ( !\cmsUser::isAdminCan('admin/com_' . $com_link, $this->admin_access) ) {
            self::accessDenied();
        }

        return $this->componentBackend($com_link, $params);
    }

    public function componentsList()
    {
        self::addToolMenuItem($this->lang->ad_install_components, $this->genActionUrl('install', 'component'), 'install.gif');
        self::addToolMenuItem($this->lang->ad_help, $this->genActionUrl('help', 'components'), 'help.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [
                'title' => $this->lang->title,
                'field' => [ 'title', 'link' ],
                'prc'   => [ $this, 'prepareComBackendUrl' ],
                'width' => ''
            ],
            [ 'title' => $this->lang->ad_version, 'field' => 'version', 'width' => '60' ],
            [ 'title' => $this->lang->ad_enable, 'field' => 'published', 'width' => '65' ],
            [ 'title' => $this->lang->ad_author, 'field' => 'author', 'width' => '200' ],
            [ 'title' => $this->lang->ad_link, 'field' => 'link', 'width' => '100' ]
        ];

        $actions = [
            [ 'title' => $this->lang->ad_config, 'icon' => 'config.gif', 'link' => $this->genActionUrl('components', [ '%link%', 'config' ]), 'condition' => [ $this, 'hasBackend' ] ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'link' => [ $this, 'genUninstallLink' ], 'condition' => [ $this, 'canRemove' ], 'confirm' => $this->lang->ad_deleted_component_from ],
        ];

        $where = '';

        if ( $this->user()->id > 1 ) {
            foreach ( $this->admin_access as $key => $value ) {
                if ( mb_strstr($value, 'admin/com_') ) {
                    if ( $where ) {
                        $where .= ' OR ';
                    }

                    $value = str_replace('admin/com_', '', $value);
                    $where .= "link = '" . $value . "'";
                }
            }
        }

        cpListTable('cms_components', $fields, $actions, !empty($where) ? $where : 1);
    }

    //========================================================================//

    protected function componentBackend($component, $params)
    {
        if ( $component == 'cp' ) {
            $this->redirectToAction('config');
        }

        if ( self::componentHasNewBackend($component) ) {
            return $this->initNewBacked($component, $params);
        }

        if ( self::componentHasOldBackend($component) ) {
            return $this->initOldBacked($component, $params);
        }

        $this->redirectToAction('components');
    }

    protected function initNewBacked($component, $params)
    {
        if ( empty($params) ) {
            $action_name = 'index';
        }
        else {
            $action_name = array_shift($params);
        }

        \cmsCore::includeFile('components/' . $component . '/backend.php');

        $class_name = '\\components\\' . $component . '\\backend';

        $backend = new $class_name();

        $result = $backend->runAction($action_name, $params);

        if ( $result === false ) {
            \cmsCore::error404();
        }

        if ( is_string($result) ) {
            echo $result;
        }
    }

    protected function initOldBacked($component, $params)
    {
        $component = $this->core->getComponent($this->core->getComponentId($component));

        $this->page()->addPathway($component['title'] . ' v' . $component['version'], $this->genActionUrl('components', $component['link']));

        $this->lang->load('components/' . $component['link']);
        $this->lang->load('admin/components/' . $component['link']);

        $inCore = $this->core;
        $inUser = $this->user;
        $inDB   = \cmsDatabase::getInstance();

        $do   = $this->request('do', 'str', 'list');
        $link = $this->request('link', 'str', '');

        $id             = $_REQUEST['id'] = $component['id'];

        global $_LANG;
        $adminAccess = $this->admin_access;

        ob_start();

        include(PATH . '/admin/components/' . $component['link'] . '/backend.php');

        echo str_replace([ '/admin/index.php', 'index.php' ], '', ob_get_clean());

        return;
    }

    //========================================================================//

    public function prepareComBackendUrl($item)
    {
        if ( empty(self::$com_title_tpl) ) {
            self::$com_title_tpl = $this->page()->initTemplate('cp/special', 'com_title')->fetch();
        }

        $html = str_replace([ '%link%', '%src%', '%title%' ], [ $this->genActionUrl('components', $item['link']), '/admin/images/components/' . $item['link'] . '.png', $item['title'] ], self::$com_title_tpl);

        if ( $item['link'] == 'cp' ) {
            $html = strip_tags($html, '<span>');
        }

        return $html;
    }

    public function hasBackend($item)
    {
        if ( self::componentHasOldBackend($item['link']) || self::componentHasNewBackend($item['link']) ) {
            return true;
        }

        return false;
    }

    public function canRemove($item)
    {
        if ( $item['system'] ) {
            return false;
        }

        return \cmsUser::isAdminCan('admin/com_' . $item['link'], $this->admin_access);
    }

    public function genUninstallLink($item)
    {
        return $this->genActionUrl('install', [ 'uninstall_component', $item['link'] ], [ 'csrf_token' => \cms\csrf_token::get() ]);
    }

}
