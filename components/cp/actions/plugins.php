<?php

namespace components\cp\actions;

class plugins extends \cms\com_action
{

    public function doBefore($action_name)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) ) {
            self::accessDenied();
        }

        $this->page()->setTitle($this->lang->ad_plugins);
        $this->page()->addPathway($this->lang->ad_plugins, $this->genActionUrl('plugins'));
    }

    public function run(...$params)
    {
        if ( empty($params) ) {
            $do = $this->request()->get('do', 'str');

            if ( !empty($do) ) {
                $id = $this->request()->get('id', 'int', -1);

                if ( $do == 'show' ) {
                    dbShow('cms_plugins', $id);
                }
                else if ( $do == 'hide' ) {
                    dbHide('cms_plugins', $id);
                }
                else {
                    \cmsCore::error404();
                }

                if ( $this->request()->isAjax() ) {
                    \cmsCore::halt('1');
                }
                else {
                    $this->redirectToAction('plugins');
                }
            }
        }

        parent::run($params);
    }

    public function actView()
    {
        self::addToolMenuItem($this->lang->ad_install_plugins, $this->genActionUrl('install', 'plugin'), 'install.gif');

        $fields = [
            [ 'title' => 'id', 'field' => 'id', 'width' => '20' ],
            [ 'title' => $this->lang->title, 'field' => 'title', 'link' => $this->genActionUrl('plugins', [ 'config', '%plugin%' ]), 'width' => '250' ],
            [ 'title' => $this->lang->description, 'field' => 'description', 'width' => '' ],
            [ 'title' => $this->lang->ad_author, 'field' => 'author', 'width' => '160' ],
            [ 'title' => $this->lang->ad_version, 'field' => 'version', 'width' => '50' ],
            [ 'title' => $this->lang->ad_folder, 'field' => 'plugin', 'width' => '100' ],
            [ 'title' => $this->lang->ad_enable, 'field' => 'published', 'width' => '60' ]
        ];

        $actions = [
            [ 'title' => $this->lang->ad_config, 'icon' => 'config.gif', 'link' => $this->genActionUrl('plugins', [ 'config', '%plugin%' ]) ],
            [ 'title' => $this->lang->delete, 'icon' => 'delete.gif', 'link' => [ $this, 'genUninstallLink' ], 'confirm' => $this->lang->ad_remove_plugin_from ]
        ];

        cpListTable('cms_plugins', $fields, $actions);
    }

    public function actConfig($plugin_name)
    {
        $plugin = \cms\plugin::load($plugin_name);

        if ( empty($plugin) ) {
            \cmsCore::error404();
        }

        $config = \cms\plugin::loadConfig($plugin_name);

        $this->page()->setTitle($plugin->getTitle());
        $this->page()->addPathway($plugin->getTitle());

        $xml_file = file_exists(PATH . '/plugins/' . $plugin_name . '/backend.xml');

        if ( !empty($config) || !empty($xml_file) ) {
            self::addToolMenuItem($this->lang->save, 'javascript:document.addform.submit();', 'save.gif');
            self::addToolMenuItem($this->lang->cancel, $this->genActionUrl('plugins'), 'cancel.gif');
        }

        if ( !empty($xml_file) ) {
            $formGen     = new cmsFormGen(PATH . '/plugins/' . $plugin_name . '/backend.xml', $config);
            $fromGenHtml = $formGen->getHTML();
        }

        $this->page()->initTemplate('cp/applets', 'plugin_config')->
                assign('submit_uri', $this->genActionUrl('plugins', [ 'save_config', $plugin_name ]))->
                assign('form_html', !empty($fromGenHtml) ? $fromGenHtml : '')->
                assign('config', $config)->
                display();
    }

    public function actSaveConfig($plugin_name)
    {
        if ( !\cmsUser::checkCsrfToken() ) {
            \cmsCore::error404();
        }

        $plugin = \cms\plugin::load($plugin_name);

        if ( empty($plugin) ) {
            \cmsCore::error404();
        }

        $config = [];

        $xml_file = file_exists(PATH . '/plugins/' . $plugin_name . '/backend.xml');

        if ( $xml_file ) {
            $data = simplexml_load_file(PATH . '/plugins/' . $plugin_name . '/backend.xml');

            foreach ( $data->params->param as $param ) {
                $name    = (string) $param['name'];
                $type    = (string) $param['type'];
                $default = (string) $param['default'];

                switch ( $param['type'] ) {
                    case 'number':
                        $value = $this->request()->get($name, 'int', $default);
                        break;
                    case 'string':
                        $value = $this->request()->get($name, 'str', $default);
                        break;
                    case 'html':
                        $value = \cmsCore::badTagClear($this->request()->get($name, 'html', $default));
                        break;
                    case 'flag':
                        $value = $this->request()->get($name, 'int', 0);
                        break;
                    case 'list':
                        $value = (is_array($_POST[$name]) ? $this->request()->get($name, 'array_str', $default) : $this->request()->get($name, 'str', $default));
                        break;
                    case 'list_function':
                        $value = $this->request()->get($name, 'str', $default);
                        break;
                    case 'list_db':
                        $value = (is_array($_POST[$name]) ? $this->request()->get($name, 'array_str', $default) : $this->request()->get($name, 'str', $default));
                        break;
                }

                $config[$name] = $value;
            }
        }
        else {
            $config = $this->request()->get('config', 'array_str');
        }

        if ( empty($config) ) {
            \cmsCore::redirectBack();
        }

        $plugin->setConfig($config)->saveConfig();

        \cmsCore::addSessionMessage($lang->ad_config_save_success, 'success');

        $this->redirectToAction('plugins');
    }

    public function genUninstallLink($item)
    {
        return $this->genActionUrl('install', [ 'uninstall_plugin', $item['plugin'] ], [ 'csrf_token' => \cms\csrf_token::get() ]);
    }

}
