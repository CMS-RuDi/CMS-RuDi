<?php

namespace components\cp\actions;

class menu extends \cms\com_action
{

    protected $default_action = 'list';

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/menu', $this->admin_access) ) {
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

        $this->page()->setTitle($this->lang->ad_menu);
        $this->page()->addPathway($this->lang->ad_menu, $this->genActionUrl('menu'));

        if ( $action_name == 'list' ) {
            self::addToolMenuItem($this->lang->ad_menu_point_add, $this->genActionUrl('menu', 'add'), 'new.gif');
            self::addToolMenuItem($this->lang->ad_menu_add, $this->genActionUrl('menu', 'addmenu'), 'newmenu.gif');
            self::addToolMenuItem($this->lang->ad_edit_selected, "javascript:checkSel('" . $this->genActionUrl('menu', 'edit', [ 'multiple' => 1 ]) . "');", 'edit.gif');
            self::addToolMenuItem($this->lang->ad_delete_selected, "javascript:checkSel('" . $this->genActionUrl('menu', 'delete', [ 'multiple' => 1 ]) . "');", 'delete.gif');
            self::addToolMenuItem($this->lang->ad_allow_selected, "javascript:checkSel('" . $this->genActionUrl('menu', 'show', [ 'multiple' => 1 ]) . "');", 'show.gif');
            self::addToolMenuItem($this->lang->ad_disallow_selected, "javascript:checkSel('" . $this->genActionUrl('menu', 'hide', [ 'multiple' => 1 ]) . "');", 'hide.gif');
            self::addToolMenuItem($this->lang->ad_help, '/cp/help/menu', 'help.gif');
        }
        else {
            self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
            self::addToolMenuItem($this->lang->cancel, $this->genActionUrl('menu'), 'cancel.gif');
        }

        if ( in_array($action_name, [ 'addmenu', 'add', 'edit' ]) ) {
            $this->page()->addHeadJS('admin/js/menu.js');
            $this->page()->addHeadJsLang('AD_SPECIFY_LINK_MENU');
        }
    }

    //========================================================================//

    public function actList()
    {
        $fields = [
            [ 'title' => 'Lt', 'field' => 'NSLeft', 'width' => '30' ],
            [
                'title' => $this->lang->title,
                'field' => [ 'title', 'titles' ], 'width' => '',
                'link'  => $this->genActionUrl('menu', [ 'edit', '%id%' ]),
                'prc'   => function ($i) {
                    $i['titles'] = \cms\model::yamlToArray($i['titles']);

                    // переопределяем название пункта меню в зависимости от языка
                    if ( !empty($i['titles'][\cmsConfig::getConfig('lang')]) ) {
                        $i['title'] = $i['titles'][\cmsConfig::getConfig('lang')];
                    }

                    return $i['title'];
                }
            ],
            [ 'title' => $this->lang->show, 'field' => 'published', 'width' => '60' ],
            [ 'title' => $this->lang->ad_order, 'field' => 'ordering', 'width' => '100' ],
            [ 'title' => $this->lang->ad_link, 'field' => [ 'linktype', 'linkid', 'link' ], 'width' => '240', 'prc' => [ $this, 'typeById' ] ],
            [
                'title'      => $this->lang->ad_menu,
                'field'      => 'menu',
                'width'      => '70',
                'filter'     => '10',
                'filterlist' => cpGetList('menu'),
                'prc'        => function($menu) {
                    $m = \cms\model::yamlToArray($menu);
                    return implode(', ', $m);
                }
            ],
            [ 'title' => $this->lang->template, 'field' => 'template', 'width' => '70', 'prc' => 'cpTemplateById' ]
        ];

        $actions = [
            [ 'title' => $this->lang->edit, 'icon' => 'edit.gif', 'link' => $this->genActionUrl('menu', [ 'edit', '%id%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'confirm' => $this->lang->ad_menu_point_confirm, 'link' => $this->genActionUrl('menu', [ 'delete', '%id%' ]) ]
        ];

        cpListTable('cms_menu', $fields, $actions, 'parent_id>0', 'NSLeft, ordering');
    }

    public function actMoveUp($id = 0)
    {
        \cmsDatabase::getInstance()->moveNsCategory('cms_menu', $id, 'up');
        \cmsCore::redirectBack();
    }

    public function actMoveDown($id = 0)
    {
        \cmsDatabase::getInstance()->moveNsCategory('cms_menu', $id, 'down');
        \cmsCore::redirectBack();
    }

    public function actShow($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                dbShow('cms_menu', $id);
            }
            echo '1';
            exit;
        }
        else {
            dbShowList('cms_menu', $this->request()->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirectBack();
        }
    }

    public function actHide($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                dbHide('cms_menu', $id);
            }
            echo '1';
            exit;
        }
        else {
            dbHideList('cms_menu', $this->request()->get('item', 'array_int', []));

            \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
            \cmsCore::redirectBack();
        }
    }

    public function actDelete($id = 0)
    {
        if ( !$this->request()->has('item') ) {
            if ( $id >= 0 ) {
                \cmsDatabase::getInstance()->deleteNS('cms_menu', (int) $id);
            }
        }
        else {
            $items = $this->request()->get('item', 'array_int', []);

            foreach ( $items as $item_id ) {
                \cmsDatabase::getInstance()->deleteNS('cms_menu', $item_id);
            }
        }

        \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');
        \cmsCore::redirectBack();
    }

    //========================================================================//

    public function actAddmenu()
    {
        $this->page()->setTitle($this->lang->ad_menu_add);
        $this->page()->addPathway($this->lang->ad_menu_add);

        \cmsPage::initTemplate('cp/applets/menu', 'addmenumod')->
                assign('menu_list', cpGetList('menu'))->
                assign('pos', cpModulePositions(\cmsConfig::getConfig('template')))->
                assign('groups', \cmsUser::getGroups())->
                display();
    }

    public function actAdd()
    {
        $this->page()->addPathway($this->lang->ad_menu_point_add);

        return $this->actEdit(false, 'add');
    }

    public function actEdit($item_id = false, $do = 'edit')
    {
        \cmsCore::includeFile('includes/jwtabs.php');
        $this->page()->addHead(jwHeader());

        $langs = \cmsCore::getDirsList('/languages');

        if ( $do == 'add' ) {
            $mod['menu'] = [ 'mainmenu' ];
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

            $mod = $this->model->db->getFields('menu', "id = '" . $item_id . "'", '*');

            if ( empty($mod) ) {
                \cmsCore::error404();
            }

            $mod['menu']   = \cms\model::yamlToArray($mod['menu']);
            $mod['titles'] = \cms\model::yamlToArray($mod['titles']);

            $this->page()->addPathway($this->lang->ad_menu_point_edit . $ostatok . ' "' . $mod['title'] . '"');
        }

        $tpl = \cmsPage::initTemplate('cp/applets/menu', 'addmenu')->
                assign('do', $do)->
                assign('mod', $mod)->
                assign('langs', $langs)->
                assign('langs_count', count($langs))->
                assign('templates', \cmsCore::getDirsList('/templates'))->
                assign('icon_list', $this->iconList())->
                assign('rootid', $this->model->db->getField('menu', 'parent_id=0', 'id'))->
                assign('groups', \cmsUser::getGroups())->
                assign('access_list', !empty($mod['access_list']) ? \cms\model::yamlToArray($mod['access_list']) : [])->
                assign('menu_list', cpGetList('menu'))->
                assign('parents_list', $this->core->getListItemsNS('cms_menu', isset($mod['parent_id']) ? $mod['parent_id'] : 0));

        if ( \cms\controller::installed('video') ) {
            $tpl->assign('video_cat_list', $this->core->getListItemsNS('cms_video_category', isset($mod['linktype']) && $mod['linktype'] == 'video_cat') ? $mod['linkid'] : 0);
        }

        if ( \cms\controller::installed('catalog') ) {
            $tpl->assign('uccat_list', $this->core->getListItems('cms_uc_cats', (isset($mod['linktype']) && $mod['linktype'] == 'uccat') ? $mod['linkid'] : 0));
        }

        $tpl->assign('com_video_installed', \cms\controller::installed('video'))->
                assign('com_catalog_installed', \cms\controller::installed('catalog'))->
                assign('content_list', $this->core->getListItems('cms_content', (isset($mod['linktype']) && $mod['linktype'] == 'content') ? $mod['linkid'] : 0))->
                assign('content_cat_list', $this->core->getListItemsNS('cms_category', (isset($mod['linktype']) && $mod['linktype'] == 'category') ? $mod['linkid'] : 0))->
                assign('blogs_list', $this->core->getListItems('cms_blogs', (isset($mod['linktype']) && $mod['linktype'] == 'blog') ? $mod['linkid'] : 0, 'title', 'asc', "owner='user'"))->
                assign('photoalbums_list', $this->core->getListItems('cms_photo_albums', (isset($mod['linktype']) && $mod['linktype'] == 'photoalbum') ? $mod['linkid'] : 0, 'id', 'ASC', 'NSDiffer = ""'))->
                assign('components_list', $this->core->getListItems('cms_components', (isset($mod['linktype']) && $mod['linktype'] == 'component') ? $mod['linkid'] : 0, 'title', 'asc', 'internal=0', 'link'));

        echo jwTabs($tpl->fetch());
    }

    //========================================================================//

    public function actSubmitmenu()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $maxorder = $this->model->db->getField('modules', '1', 'ordering', 'ordering DESC') + 1;

        $is_public = $this->request()->get('is_public', 'int', '');

        $access_list = [];

        if ( !$is_public ) {
            $access_list = $this->request()->get('allow_group', 'array_int');
        }

        $cfg_str = \cms\model::arrayToYaml([ 'menu' => $this->request()->get('menu', 'str', '') ]);

        $newid = $this->model->db->insert('modules', [
            'position'    => $this->request()->get('position', 'str', ''),
            'name'        => $this->lang->ad_menu,
            'title'       => $this->request()->get('title', 'str', ''),
            'is_external' => 1,
            'content'     => 'mod_menu',
            'ordering'    => $maxorder,
            'showtitle'   => 1,
            'published'   => $this->request()->get('published', 'int', 0),
            'user'        => 0,
            'config'      => $cfg_str,
            'css_prefix'  => $this->request()->get('css_prefix', 'str', ''),
            'access_list' => \cms\model::arrayToYaml($access_list)
        ]);

        \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

        $this->redirectToAction('modules', [ 'edit', $newid ]);
    }

    public function actSubmit()
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $item_id = $this->request()->get('item_id', 'int', 0);

        $title     = $this->request()->get('title', 'str', '');
        $titles    = \cms\model::arrayToYaml($this->request()->get('titles', 'array_str', []));
        $menu      = \cms\model::arrayToYaml($this->request()->get('menu', 'array_str', ''));
        $linktype  = $this->request()->get('mode', 'str', '');
        $linkid    = $this->request()->get($linktype, 'str', '');
        $link      = $this->core->getMenuLink($linktype, $linkid);
        $target    = $this->request()->get('target', 'str', '');
        $published = $this->request()->get('published', 'int', 0);
        $template  = $this->request()->get('template', 'str', '');
        $iconurl   = $this->request()->get('iconurl', 'str', '');
        $parent_id = $this->request()->get('parent_id', 'int', 0);
        $is_lax    = $this->request()->get('is_lax', 'int', 0);
        $css_class = $this->request()->get('css_class', 'str', '');

        $oldparent = $this->request()->get('oldparent', 'int', 0);

        $is_public = $this->request()->get('is_public', 'int', '');
        if ( !$is_public ) {
            $access_list = \cms\model::arrayToYaml($this->request()->get('allow_group', 'array_int'));
        }

        $ns = $this->core->nestedSetsInit('cms_menu');

        if ( empty($item_id) ) {
            $item_id = $ns->AddNode($parent_id);
        }
        else {
            if ( $oldparent != $parent_id ) {
                $ns->MoveNode($item_id, $parent_id);
            }
        }

        $this->model->db->update('menu', 'id=' . $item_id, [
            'title'       => $title,
            'titles'      => $titles,
            'css_class'   => $css_class,
            'menu'        => $menu,
            'link'        => $link,
            'linktype'    => $linktype,
            'linkid'      => $linkid,
            'target'      => $target,
            'published'   => $published,
            'template'    => $template,
            'access_list' => $access_list,
            'is_lax'      => $is_lax,
            'iconurl'     => $iconurl
        ]);

        \cmsCore::addSessionMessage($this->lang->ad_do_success, 'success');

        if ( !isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist']) == 0 ) {
            $this->redirectToAction('menu');
        }
        else {
            $this->redirectToAction('menu', 'edit');
        }
    }

    //========================================================================//

    public function typeById($item)
    {
        $maxlen = 35;

        $type  = '';
        $title = '';

        switch ( $item['linktype'] ) {
            case 'link':
                $type  = $this->lang->ad_type_link;
                $title = $item['linkid'];
                break;
            case 'component':
                $type  = $this->lang->ad_type_component;
                $title = $this->model->db->getField('components', "link='" . $item['linkid'] . "'", 'title');
                break;
            case 'content':
                $type  = $this->lang->ad_type_article;
                $title = $this->model->db->getField('content', 'id=' . $item['linkid'], 'title');
                break;
            case 'category':
                $type  = $this->lang->ad_type_partition;
                $title = $this->model->db->getField('category', 'id=' . $item['linkid'], 'title');
                break;
            case 'video_cat':
                if ( \cms\controller::installed('video') ) {
                    $type  = $this->lang->ad_type_video_partition;
                    $title = $this->model->db->getField('video_category', 'id=' . $item['linkid'], 'title');
                }
                break;
            case 'uccat':
                $type  = $this->lang->ad_type_category;
                $title = $this->model->db->getField('uc_cats', 'id=' . $item['linkid'], 'title');
                break;
            case 'blog':
                $type  = $this->lang->ad_type_blog;
                $title = $this->model->db->getField('blogs', 'id=' . $item['linkid'], 'title');
                break;
            case 'photoalbum':
                $type  = $this->lang->ad_type_album;
                $title = $this->model->db->getField('photo_albums', 'id=' . $item['linkid'], 'title');
                break;
        }

        if ( !empty($type) ) {
            if ( mb_strlen($type . ' - ' . $title) > $maxlen ) {
                $dif = mb_strlen($type . ' - ' . $title) - $maxlen;

                if ( mb_strlen($title) > $dif ) {
                    $title = mb_substr($title, 0, mb_strlen($title) - $dif) . '...';
                }
                else {
                    $title = '...';
                }
            }

            return \cmsPage::initTemplate('cp/applets/menu', 'menutype')->
                            assign('link', $item['link'])->
                            assign('type', $type)->
                            assign('title', $title)->
                            fetch();
        }

        return '';
    }

    protected function iconList()
    {
        $icons = [];

        if ( $handle = opendir(PATH . '/images/menuicons') ) {
            while ( false !== ($file = readdir($handle)) ) {
                if ( $file != '.' && $file != '..' && mb_strstr($file, '.gif') ) {
                    $icons[] = [
                        'name' => $file,
                        'src'  => '/images/menuicons/' . $file
                    ];
                }
            }

            closedir($handle);
        }

        return \cmsPage::initTemplate('cp/special', 'icons_list')->
                        assign('icons', $icons)->
                        fetch();
    }

}
