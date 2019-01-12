<?php

namespace components\cp\actions;

class modules extends \cms\com_action
{

    protected $default_action = 'list';

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/modules', $this->admin_access) ) {
            \cms\backend::accessDenied();
        }

        if ( $action_name == 'list' ) {
            $id = $this->request()->get('id', 'int', -1);
            $do = $this->request()->get('do', 'str');

            if ( in_array($do, [ 'move_up', 'move_down', 'show', 'hide', 'delete' ]) ) {
                $method = \cms\helper\str::toCamel('_', 'act_' . $do);

                $this->{$method}($id);

                return false;
            }
        }

        $this->page()->setTitle($this->lang->ad_modules);
        $this->page()->addPathway($this->lang->ad_modules, $this->genActionUrl('modules'));

        $this->page()->addHeadJS('admin/js/modules.js');
    }

    public function actList()
    {
        self::addToolMenuItem($this->lang->ad_module_add, $this->genActionUrl('modules', 'add'), 'new.gif');
        self::addToolMenuItem($this->lang->ad_modules_setup, $this->genActionUrl('install', 'module'), 'install.gif');
        self::addToolMenuItem($this->lang->ad_edit_selected, "javascript:checkSel('" . $this->genActionUrl('modules', 'edit', [ 'multiple' => 1 ]) . "');", 'edit.gif');
        self::addToolMenuItem($this->lang->ad_delete_selected, "javascript:checkSel('" . $this->genActionUrl('modules', 'delete', [ 'multiple' => 1 ]) . "');", 'delete.gif');
        self::addToolMenuItem($this->lang->ad_allow_selected, "javascript:checkSel('" . $this->genActionUrl('modules', 'show', [ 'multiple' => 1 ]) . "');", 'show.gif');
        self::addToolMenuItem($this->lang->ad_disallow_selected, "javascript:checkSel('" . $this->genActionUrl('modules', 'hide', [ 'multiple' => 1 ]) . "');", 'hide.gif');
        self::addToolMenuItem($this->lang->ad_module_order, $this->genActionUrl('modules', 'autoorder'), 'autoorder.gif');

        self::addToolMenuItem($this->lang->ad_save_order, "javascript:checkSel('" . $this->genActionUrl('modules', 'saveorder') . "');", 'reorder.gif');
        self::addToolMenuItem($this->lang->ad_help, '/cp/help/modules', 'help.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '30' ],
            [
                'title' => $this->lang->ad_title,
                'field' => array( 'title', 'titles' ), 'width' => '',
                'link'  => $this->genActionUrl('modules', [ 'edit', '%id%' ]),
                'prc'   => function ($i) {
                    $i['titles'] = \cms\model::yamlToArray($i['titles']);

                    // переопределяем название пункта меню в зависимости от языка
                    if ( !empty($i['titles'][\cmsConfig::getConfig('lang')]) ) {
                        $i['title'] = $i['titles'][\cmsConfig::getConfig('lang')];
                    }

                    return $i['title'];
                }
            ],
            [ 'title' => $this->lang->title, 'field' => 'name', 'width' => '220', 'filter' => '15' ],
            [ 'title' => $this->lang->ad_version, 'field' => 'version', 'width' => '55' ],
            [ 'title' => $this->lang->ad_author, 'field' => 'author', 'width' => '110' ],
            [ 'title' => $this->lang->show, 'field' => 'published', 'width' => '65' ],
            [ 'title' => $this->lang->ad_order, 'field' => 'ordering', 'width' => '75' ],
            [ 'title' => $this->lang->ad_position, 'field' => 'position', 'width' => '70', 'filter' => '10', 'filterlist' => cpGetList('positions') ]
        ];

        $actions = [
            [ 'title' => $this->lang->ad_config, 'icon' => 'config.gif', 'link' => $this->genActionUrl('modules', [ 'config', '%id%' ]), 'condition' => [ $this, 'hasConfig' ] ],
            [ 'title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('modules', [ 'edit', '%id%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_module_delete, 'link' => $this->genActionUrl('modules', [ 'delete', '%id%' ]) ],
        ];

        cpListTable('cms_modules', $fields, $actions, '', 'published DESC, position, ordering ASC');
    }

    public function actMoveUp($id = 0)
    {
        $co = $this->request()->get('co', 'int', -1);

        if ( $id >= 0 ) {
            dbMoveUp('cms_modules', $id, $co);
        }

        \cmsCore::redirectBack();
    }

    public function actMoveDown($id = 0)
    {
        $co = $this->request()->get('co', 'int', -1);

        if ( $id >= 0 ) {
            dbMoveDown('cms_modules', $id, $co);
        }

        \cmsCore::redirectBack();
    }

    public function actShow($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                dbShow('cms_modules', $id);
            }
            echo '1';
            exit;
        }
        else {
            dbShowList('cms_modules', $this->request()->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirectBack();
        }
    }

    public function actHide($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                dbHide('cms_modules', $id);
            }
            echo '1';
            exit;
        }
        else {
            dbHideList('cms_modules', $this->request()->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirectBack();
        }
    }

    public function actDelete($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                $this->core->removeModule($id);
            }
        }
        else {
            $this->core->removeModule($this->request()->get('item', 'array_int', []));
        }

        \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
        \cmsCore::redirectBack();
    }

    public function actAutoorder()
    {
        $result = $this->model->db->query('SELECT id, position FROM {#}modules ORDER BY position');

        if ( $result->num_rows ) {
            $ord = 1;

            while ( $item = $result->fetch_assoc() ) {
                if ( isset($latest_pos) ) {
                    if ( $latest_pos != $item['position'] ) {
                        $ord = 1;
                    }
                }

                $this->model->db->query("UPDATE {#}modules SET ordering = " . $ord . " WHERE id='" . $item['id'] . "'");

                $ord++;
                $latest_pos = $item['position'];
            }
        }

        $this->redirectToAction('modules');
    }

    public function actSaveorder()
    {
        if ( $this->request()->has('ordering') ) {
            $ord = $this->request()->get('ordering', 'array_int', []);
            $ids = $this->request()->get('ids', 'array_int', []);

            foreach ( $ord as $id => $ordering ) {
                $this->model->db->query("UPDATE {#}modules SET ordering = '" . (int) $ordering . "' WHERE id = '" . (int) $ids[$id] . "'");
            }
        }

        $this->redirectToAction('modules');
    }

    //========================================================================//

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_module_add);
        \cms\backend::setTitle($this->lang->ad_module_add);

        return $this->actEdit(false, 'add');
    }

    public function actEdit($item_id = false, $do = 'edit')
    {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page()->addHead(jwHeader());

        $langs = \cmsCore::getDirsList('/languages');

        $bind     = [];
        $bind_pos = [];

        if ( $do == 'add' ) {
            $mod = [
                'content'         => '',
                'hidden_menu_ids' => [],
                'cachetime'       => 0
            ];

            $show_all = false;
        }
        else {
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

            $mod = $this->model->db->getFields('modules', "id = '" . $item_id . "'", '*');

            if ( empty($mod) ) {
                \cmsCore::error404();
            }

            $mod['hidden_menu_ids'] = \cms\model::yamlToArray($mod['hidden_menu_ids']);
            $mod['titles']          = \cms\model::yamlToArray($mod['titles']);

            if ( $this->model->db->getField('modules_bind', 'module_id=' . $item_id . ' AND menu_id=0', 'id') ) {
                $show_all = true;
            }
            else {
                $show_all = false;
            }

            \cms\backend::setTitle($this->lang->AD_EDIT_MODULE . $ostatok . ' "' . $mod['name'] . '"');

            $this->page()->addPathway($mod['name']);

            $bind_res = $this->model->db->query('SELECT * FROM cms_modules_bind WHERE module_id = ' . $mod['id']);

            while ( $r = $this->model->db->fetchAssoc($bind_res) ) {
                $bind[]                  = $r['menu_id'];
                $bind_pos[$r['menu_id']] = $r['position'];
            }
        }

        $menu_items = $this->model->get('menu', function($item, $model) use ($do, $bind, $bind_pos) {
            if ( $do == 'edit' ) {
                if ( in_array($item['id'], $bind) ) {
                    $item['selected'] = true;
                    $item['position'] = $bind_pos[$item['id']];
                }
            }

            $item['titles'] = \cms\model::yamlToArray($item['titles']);

            // переопределяем название пункта меню в зависимости от языка
            if ( !empty($item['titles'][\cmsConfig::getConfig('lang')]) ) {
                $item['title'] = $item['titles'][\cmsConfig::getConfig('lang')];
            }

            $item['title'] = str_replace(\cms\lang::getInstance()->ad_root_pages, \cms\lang::getInstance()->ad_main, $item['title']);

            return $item;
        });

        self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
        self::addToolMenuItem($this->lang->cancel, 'javascript:history.go(-1);', 'cancel.gif');

        if ( !empty($mod['is_external']) ) {
            if ( file_exists(PATH . '/admin/modules/' . $mod['content'] . '/backend.php') || file_exists(PATH . '/admin/modules/' . $mod['content'] . '/backend.xml') ) {
                self::addToolMenuItem($this->lang->CONFIG_MODULE, $this->genActionUrl('modules', [ 'config', $mod['id'] ]), 'config.gif');
            }
        }

        $tpl = \cmsPage::initTemplate('cp/applets/modules', 'addmodule')->
                assign('mod', $mod)->
                assign('do', $do)->
                assign('langs', $langs)->
                assign('langs_count', count($langs))->
                assign('pos', cpModulePositions(\cmsConfig::getConfig('template')))->
                assign('tpls', $this->core->getModuleTemplates())->
                assign('panel', \cms\backend::getPanelHtml())->
                assign('modules_list', $this->core->getListItems('cms_modules'))->
                assign('menu_items', $menu_items)->
                assign('groups', \cmsUser::getGroups());

        if ( file_exists(PATH . '/templates/' . TEMPLATE . '/positions.jpg') ) {
            $tpl->assign('positions_image', '/templates/' . TEMPLATE . '/positions.jpg');
        }

        if ( !empty($bind) ) {
            $tpl->assign('bind', $bind);
            $tpl->assign('bind_pos', $bind_pos);
        }

        if ( $do == 'edit' ) {
            if ( $this->core->isCached('module', $mod['id'], $mod['cachetime'], $mod['cacheint']) ) {
                $t     = 'module' . $mod['id'];
                $cfile = PATH . '/cache/' . md5($t) . '.html';

                if ( file_exists($cfile) ) {
                    $tpl->assign('cfkb', round(filesize($cfile) / 1024, 2));
                }
            }

            if ( !empty($mod['access_list']) ) {
                $tpl->assign('access_list', \cms\model::yamlToArray($mod['access_list']));
            }
        }

        echo jwTabs($tpl->fetch());
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $item_id = $this->request()->get('item_id', 'int');

        $name       = $this->request()->get('name', 'str', '');
        $title      = $this->request()->get('title', 'str', '');
        $titles     = $this->request()->get('titles', 'array_str', []);
        $position   = $this->request()->get('position', 'str', '');
        $showtitle  = $this->request()->get('showtitle', 'int', 0);
        $content    = $this->request()->get('content', 'html', '');
        $published  = $this->request()->get('published', 'int', 0);
        $css_prefix = $this->request()->get('css_prefix', 'str', '');

        $is_strict_bind        = $this->request()->get('is_strict_bind', 'int', 0);
        $is_strict_bind_hidden = $this->request()->get('is_strict_bind_hidden', 'int', 0);

        $is_public   = $this->request()->get('is_public', 'int', 0);
        $access_list = '';

        if ( !$is_public ) {
            $access_list = $this->request()->get('allow_group', 'array_int', []);
        }

        $template  = $this->request()->get('template', 'str', '');
        $cache     = $this->request()->get('cache', 'int', 0);
        $cachetime = $this->request()->get('cachetime', 'int', 0);
        $cacheint  = $this->request()->get('cacheint', 'str', '');

        if ( empty($item_id) ) {
            $maxorder = $this->model->db->getField('modules', 1, 'ordering', 'ordering DESC') + 1;

            $operate = $this->request()->get('operate', 'str', [ 'user', 'clone' ]);

            if ( $operate == 'user' ) {
                // Пользовательский модуль
                $item_id = $this->model->db->insert('modules', [
                    'position'              => $position,
                    'name'                  => $name,
                    'title'                 => $title,
                    'titles'                => $titles,
                    'is_external'           => 0,
                    'content'               => $content,
                    'ordering'              => $maxorder,
                    'showtitle'             => $showtitle,
                    'published'             => $published,
                    'user'                  => 1,
                    'original'              => 1,
                    'css_prefix'            => $css_prefix,
                    'access_list'           => $access_list,
                    'template'              => $template,
                    'is_strict_bind'        => $is_strict_bind,
                    'is_strict_bind_hidden' => $is_strict_bind_hidden
                ]);
            }
            else {
                // Дубликат модуля
                $mod_id   = $this->request()->get('clone_id', 'int', 0);
                $original = $this->model->db->getFields('modules', 'id=' . $mod_id);

                if ( empty($original) ) {
                    \cmsCore::error404();
                }

                $is_original = (int) $this->request()->get('del_orig', 'bool');

                $item_id = $this->model->db->insert('modules', [
                    'position'              => $position,
                    'name'                  => $original['name'],
                    'title'                 => $title,
                    'titles'                => $titles,
                    'is_external'           => $original['is_external'],
                    'content'               => $original['content'],
                    'ordering'              => $maxorder,
                    'showtitle'             => $showtitle,
                    'published'             => $published,
                    'original'              => $is_original,
                    'user'                  => $original['user'],
                    'config'                => $original['config'],
                    'css_prefix'            => $css_prefix,
                    'template'              => $template,
                    'access_list'           => $access_list,
                    'is_strict_bind'        => $is_strict_bind,
                    'is_strict_bind_hidden' => $is_strict_bind_hidden,
                    'cache'                 => $cache,
                    'cachetime'             => $cachetime,
                    'cacheint'              => $cacheint,
                    'version'               => $original['version']
                ]);

                if ( $is_original ) {
                    $this->model->db->delete('modules', 'id=' . $mod_id, 1);
                }
            }

            \cmsCore::addSessionMessage($this->lang->ad_module_add_site, 'success');

            $redirect_url = $this->genActionUrl('modules');
        }
        else {
            $data = [
                'name'                  => $name,
                'title'                 => $title,
                'titles'                => $titles,
                'position'              => $position,
                'template'              => $template,
                'showtitle'             => $showtitle,
                'published'             => $published,
                'css_prefix'            => $css_prefix,
                'access_list'           => $access_list,
                'hidden_menu_ids'       => '',
                'cachetime'             => $cachetime,
                'cacheint'              => $cacheint,
                'cache'                 => $cache,
                'is_strict_bind'        => $is_strict_bind,
                'is_strict_bind_hidden' => $is_strict_bind_hidden
            ];

            if ( !empty($content) ) {
                $data['content'] = $content;
            }

            $this->model->db->update('modules', 'id=' . $item_id, $data);

            $this->model->db->delete('modules_bind', 'module_id=' . $item_id);

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

            if ( empty($_SESSION['editlist']) || (!is_array($_SESSION['editlist'])) ) {
                $redirect_url = $this->genActionUrl('modules');
            }
            else {
                $redirect_url = $this->genActionUrl('modules', 'edit');
            }
        }

        if ( $this->request()->get('show_all', 'bool') ) {
            $this->model->db->insert('modules_bind', [
                'module_id' => $item_id,
                'menu_id'   => 0,
                'position'  => $position
            ]);

            $hidden_menu_ids = $this->request()->get('hidden_menu_ids', 'array_int', []);

            if ( !empty($hidden_menu_ids) ) {
                $this->model->db->update('modules', 'id=' . $item_id, [
                    'hidden_menu_ids' => $hidden_menu_ids
                ]);
            }
        }
        else {
            $showin  = $this->request()->get('showin', 'array_int', []);
            $showpos = $this->request()->get('showpos', 'array_str', []);

            if ( !empty($showin) ) {
                foreach ( $showin as $key => $value ) {
                    $this->model->db->insert('modules_bind', [
                        'module_id' => $item_id,
                        'menu_id'   => $value,
                        'position'  => $showpos[$value]
                    ]);
                }
            }
        }

        \cmsCore::redirect($redirect_url);
    }

    public function actConfig($item_id)
    {
        $module_name  = $this->model->getModuleContentById($item_id);
        $module_title = $this->model->getModuleNameById($item_id);

        if ( !$module_name ) {
            $this->redirectToAction('modules', [ 'edit', $item_id ]);
        }

        $xml_file = PATH . '/admin/modules/' . $module_name . '/backend.xml';
        $php_file = PATH . '/admin/modules/' . $module_name . '/backend.php';

        if ( !file_exists($xml_file) ) {
            if ( file_exists($php_file) ) {
                include $php_file;
                return;
            }

            \cmsCore::error404();
        }

        $cfg = $this->core->loadModuleConfig($item_id);

        $this->page()->addPathway($module_title, $this->genActionUrl('modules', [ 'edit', $item_id ]));
        $this->page()->addPathway($this->lang->ad_settings);

        self::setTitle($module_title);

        self::addToolMenuItem($this->lang->save, 'javascript:submitModuleConfig();', 'save.gif');
        self::addToolMenuItem($this->lang->cancel, $this->genActionUrl('modules'), 'cancel.gif');
        self::addToolMenuItem($this->lang->ad_edit_module_view, $this->genActionUrl('modules', [ 'edit', $item_id ]), 'edit.gif');

        $this->page()->initTemplate('cp/applets/modules', 'configxml')->
                assign('formGen', new \cmsFormGen($xml_file, $cfg))->
                assign('submit_url', $this->genActionUrl('modules', [ 'save_auto_config', $item_id ]))->
                display();
    }

    public function actSaveAutoConfig($item_id)
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $module_name = $this->model->getModuleContentById($item_id);

        $is_ajax = $this->request()->has('ajax') || $this->request()->isAjax();

        if ( $is_ajax ) {
            $data = [
                'title'     => $this->request()->get('title', 'str', ''),
                'published' => $this->request()->get('published', 'int', 0)
            ];

            if ( $this->request()->has('content') ) {
                $data['content'] = $this->request()->get('content', 'html');
            }

            if ( $this->request()->has('css_prefix') ) {
                $data['css_prefix'] = $this->request()->get('css_prefix', 'str', '');
            }

            $this->model->db->update('modules', 'id=' . $item_id, $data);
        }

        if ( $this->request()->has('title_only') ) {
            \cmsCore::redirectBack();
        }

        $xml_file = PATH . '/admin/modules/' . $module_name . '/backend.xml';

        if ( !file_exists($xml_file) ) {
            \cmsCore::error404();
        }

        $cfg = [];

        $backend = \simplexml_load_file($xml_file);

        foreach ( $backend->params->param as $param ) {
            $name    = (string) $param['name'];
            $type    = (string) $param['type'];
            $default = (string) $param['default'];

            switch ( $param['type'] ) {
                case 'number': $value = $this->request()->get($name, 'int', $default);
                    break;
                case 'string': $value = $this->request()->get($name, 'str', $default);
                    break;
                case 'html': $value = \cmsCore::badTagClear($this->request()->get($name, 'html', $default));
                    break;
                case 'flag': $value = $this->request()->get($name, 'int', 0);
                    break;
                case 'list': $value = (is_array($_POST[$name]) ? $this->request()->get($name, 'array_str', $default) : $this->request()->get($name, 'str', $default));
                    break;
                case 'list_function': $value = $this->request()->get($name, 'str', $default);
                    break;
                case 'list_db': $value = (is_array($_POST[$name]) ? $this->request()->get($name, 'array_str', $default) : $this->request()->get($name, 'str', $default));
                    break;
            }

            $cfg[$name] = $value;
        }

        $this->core->saveModuleConfig($item_id, $cfg);

        if ( !$is_ajax ) {
            \cmsCore::addSessionMessage($this->lang->ad_config_save_success, 'success');
            \cmsCore::redirectBack();
        }
        else {
            \cmsCore::halt();
        }
    }

    //========================================================================//

    public function hasConfig($item)
    {
        if ( file_exists(PATH . '/admin/modules/' . $item['content'] . '/backend.php') ) {
            return true;
        }

        if ( file_exists(PATH . '/admin/modules/' . $item['content'] . '/backend.xml') ) {
            return true;
        }

        return false;
    }

}
