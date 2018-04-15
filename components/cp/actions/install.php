<?php

namespace components\cp\actions;

class install extends \cms\com_action
{

    public function doBefore($action_name)
    {
        $this->page()->setTitle($this->lang->ad_setup_extension);
        \cms\backend::setTitle($this->lang->ad_setup_extension);

        $this->page()->addPathway($this->lang->ad_setup_extension, $this->genActionUrl('install'));
    }

    public function actView()
    {
        \cmsCore::addSessionMessage('Come back here in the next release.');
        \cmsCore::redirectBack();
    }

    public function actComponent($name = false)
    {
        if ( !\cmsUser::isAdminCan('admin/components', $this->admin_access) ) {
            self::accessDenied();
        }

        if ( !empty($name) ) {
            return $this->installComponent($name);
        }

        $this->page()->addPathway($this->lang->ad_setup_components);
        $this->page()->setTitle($this->lang->ad_setup_components);
        \cms\backend::setTitle($this->lang->ad_setup_components);

        $new_coms       = $this->core->getNewComponents();
        $new_components = [];

        if ( !empty($new_coms) ) {
            foreach ( $new_coms as $component ) {
                if ( $inCore->loadComponentInstaller($component) ) {
                    $new_components[] = call_user_func('info_component_' . $component);
                }
            }
        }

        $upd_coms       = $this->core->getUpdatedComponents();
        $upd_components = [];

        if ( !empty($upd_coms) ) {
            foreach ( $upd_coms as $component ) {
                if ( $inCore->loadComponentInstaller($component) ) {
                    $_component            = call_user_func('info_component_' . $component);
                    $_component['version'] = $this->core->getComponentVersion($component) . ' &rarr; ' . $_component['version'];
                    $upd_components[]      = $_component;
                }
            }
        }

        $this->page()->initTemplate('cp/applets', 'install_component')->
                assign('install_link', $this->genActionUrl('install', 'component'))->
                assign('update_link', $this->genActionUrl('install', 'update_component'))->
                assign('new_components', $new_components)->
                assign('upd_components', $upd_components)->
                display();
    }

    public function installComponent($name)
    {
        if ( empty($name) ) {
            \cmsCore::redirectBack();
        }

        if ( $this->core->loadComponentInstaller($name) ) {
            $_component = call_user_func('info_component_' . $name);
            $no_error   = call_user_func('install_component_' . $name);
        }
        else {
            $no_error = $this->lang->ad_component_wizard_failure;
        }

        if ( $no_error === true ) {
            $this->core->installComponent($_component, $_component['config']);

            $info_text = '<p>' . $this->lang->ad_component . ' <strong>"' . $_component['title'] . '"</strong> ' . $this->lang->ad_success . $this->lang->ad_is_install . '</p>';

            if ( !empty($_component['modules']) && is_array($_component['modules']) ) {
                $info_text .= '<p>' . $this->lang->ad_opt_install_modules . ':</p>';
                $info_text .= '<ul>';

                foreach ( $_component['modules'] as $module => $title ) {
                    $info_text .= '<li>' . $title . '</li>';
                }

                $info_text .= '</ul>';
            }

            if ( !empty($_component['plugins']) && is_array($_component['plugins']) ) {
                $info_text .= '<p>' . $this->lang->ad_opt_install_plugins . ':</p>';
                $info_text .= '<ul>';

                foreach ( $_component['plugins'] as $module => $title ) {
                    $info_text .= '<li>' . $title . '</li>';
                }

                $info_text .= '</ul>';
            }

            \cmsCore::addSessionMessage($info_text, 'success');
            $this->redirectToAction('components');
        }
        else {
            \cmsCore::addSessionMessage($no_error, 'error');
            \cmsCore::redirectBack();
        }
    }

    public function actUpdateComponent($name)
    {
        if ( !\cmsUser::isAdminCan('admin/components', $this->admin_access) ) {
            self::accessDenied();
        }

        if ( empty($name) ) {
            \cmsCore::redirectBack();
        }

        if ( !\cmsUser::isAdminCan('admin/com_' . $name, $this->admin_access) ) {
            self::accessDenied();
        }

        if ( $this->core->loadComponentInstaller($name) ) {
            $_component = call_user_func('info_component_' . $name);
            $no_error   = call_user_func('upgrade_component_' . $name);
        }
        else {
            $no_error = $this->lang->ad_component_wizard_failure;
        }

        if ( $no_error === true ) {
            $this->core->upgradeComponent($_component, $_component['config']);

            $info_text = $this->lang->ad_component . ' <strong>"' . $_component['title'] . '"</strong> ' . $this->lang->ad_success . $this->lang->ad_is_update;

            \cmsCore::addSessionMessage($info_text, 'success');
            $this->redirectToAction('components');
        }
        else {
            \cmsCore::addSessionMessage($no_error, 'error');
            \cmsCore::redirectBack();
        }
    }

    public function actUninstallComponent($name)
    {
        if ( !\cmsUser::isAdminCan('admin/components', $this->admin_access) ) {
            self::accessDenied();
        }

        if ( empty($name) ) {
            \cmsCore::redirectBack();
        }

        if ( !\cmsUser::isAdminCan('admin/com_' . $name, $this->admin_access) ) {
            self::accessDenied();
        }

        $component = \cms\controller::getComponentByName($name);

        if ( empty($component) || !\cms\csrf_token::checkToken() ) {
            \cmsCore::error404();
        }

        if ( $this->core->loadComponentInstaller($component['link']) ) {
            if ( function_exists('remove_component_' . $component['link']) ) {
                call_user_func('remove_component_' . $component['link']);
            }

            $_component = call_user_func('info_component_' . $component['link']);

            if ( isset($_component['modules']) ) {
                if ( is_array($_component['modules']) ) {
                    foreach ( $_component['modules'] as $module => $title ) {
                        $module_id = $this->core->getModuleId($module);

                        if ( $module_id ) {
                            $this->core->removeModule($module_id);
                        }
                    }
                }
            }

            // Удаляем компонент из базы, но только если он не системный
            $this->model->db->delete('components', "id = '" . $component['id'] . "' AND system = 0");
        }

        \cmsCore::addSessionMessage($this->lang->ad_component_is_deleted, 'success');

        $this->redirectToAction('components');
    }

    //========================================================================//

    public function actPlugin($name = false)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) ) {
            self::accessDenied();
        }

        if ( !empty($name) ) {
            return $this->installPlugin($name);
        }

        $this->page()->addPathway($this->lang->ad_setup_plugins);
        $this->page()->setTitle($this->lang->ad_setup_plugins);
        \cms\backend::setTitle($this->lang->ad_setup_plugins);

        $new_plugs   = $this->core->getNewPlugins();
        $new_plugins = [];

        if ( !empty($new_plugs) ) {
            foreach ( $new_plugs as $plug ) {
                $new_plugs[] = \cms\plugin::load($plug)->getInfo();
            }
        }

        $upd_plugs   = $this->core->getUpdatedPlugins();
        $upd_plugins = [];

        if ( !empty($upd_plugs) ) {
            foreach ( $upd_plugs as $plug ) {
                $info            = \cms\plugin::load($plug)->getInfo();
                $info['version'] = $this->core->getPluginVersion($plug) . ' &rarr; ' . $info['version'];
                $upd_plugins[]   = $info;
            }
        }

        $this->page()->initTemplate('cp/applets', 'install_plugin')->
                assign('install_link', $this->genActionUrl('install', 'plugin'))->
                assign('update_link', $this->genActionUrl('install', 'update_plugin'))->
                assign('new_plugins', $new_plugins)->
                assign('upd_plugins', $upd_plugins)->
                display();
    }

    public function installPlugin($name)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) ) {
            self::accessDenied();
        }

        $error = '';

        $plugin = \cms\plugin::load($name);

        if ( empty($plugin) ) {
            $error = $this->lang->ad_plugin_failure;
        }

        if ( !$error && $plugin->install() ) {
            \cmsCore::addSessionMessage($this->lang->ad_plugin . ' <strong>"' . $plugin->getTitle() . '"</strong> ' . $this->lang->ad_success . $this->lang->ad_is_install . '. ' . $this->lang->ad_enable_plugin, 'success');

            $this->redirectToAction('plugins');
        }

        if ( $error ) {
            \cmsCore::addSessionMessage($error, 'error');
        }

        \cmsCore::redirectBack();
    }

    public function actUpdatePlugin($name)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) ) {
            self::accessDenied();
        }

        $error = '';

        $plugin = \cms\plugin::load($name);

        if ( empty($plugin) ) {
            $error = $this->lang->ad_plugin_failure;
        }

        if ( !$error && $plugin->upgrade() ) {
            \cmsCore::addSessionMessage($this->lang->ad_plugin . ' <strong>"' . $plugin->getTitle() . '"</strong> ' . $this->lang->ad_success . $this->lang->ad_is_update, 'success');

            $this->redirectToAction('plugins');
        }

        if ( $error ) {
            \cmsCore::addSessionMessage($error, 'error');
        }

        \cmsCore::redirectBack();
    }

    public function actUninstallPlugin($name)
    {
        if ( !\cmsUser::isAdminCan('admin/plugins', $this->admin_access) ) {
            self::accessDenied();
        }

        $plugin_id = $this->model->db->getField('plugins', "plugin='" . $this->model->db->escape($name) . "'", 'id');

        if ( empty($plugin_id) || !\cms\csrf_token::checkToken() ) {
            \cmsCore::error404();
        }

        // Удаляем плагин из базы
        $inDB->delete('cms_plugins', "id = '" . $plugin_id . "'");

        // Удаляем хуки событий плагина
        $inDB->delete('cms_event_hooks', "plugin_id = '" . $plugin_id . "'");

        cmsCore::addSessionMessage($_LANG['AD_REMOVE_PLUGIN_OK'], 'success');
        cmsCore::redirect('/admin/index.php?view=plugins');
    }

    //========================================================================//

    public function actModule($name = false)
    {
        if ( !\cmsUser::isAdminCan('admin/modules', $this->admin_access) ) {
            self::accessDenied();
        }

        if ( !empty($name) ) {
            return $this->installModule($name);
        }

        $this->page()->addPathway($this->lang->ad_setup_modules);
        $this->page()->setTitle($this->lang->ad_setup_modules);
        \cms\backend::setTitle($this->lang->ad_setup_modules);

        $new_mods    = $this->core->getNewModules();
        $new_modules = [];

        if ( !empty($new_mods) ) {
            foreach ( $new_mods as $mod ) {
                if ( $this->core->loadModuleInstaller($mod) ) {
                    $new_modules[] = call_user_func('info_module_' . $mod);
                }
            }
        }

        $upd_mods    = $this->core->getUpdatedModules();
        $upd_modules = [];

        if ( !empty($upd_mods) ) {
            foreach ( $upd_mods as $mod ) {
                if ( $this->core->loadModuleInstaller($mod) ) {
                    $info            = call_user_func('info_module_' . $mod);
                    $info['version'] = $this->core->getModuleVersion($mod) . ' &rarr; ' . $info['version'];
                    $upd_modules[]   = $info;
                }
            }
        }

        $this->page()->initTemplate('cp/applets', 'install_module')->
                assign('install_link', $this->genActionUrl('install', 'module'))->
                assign('update_link', $this->genActionUrl('install', 'update_module'))->
                assign('new_modules', $new_modules)->
                assign('upd_modules', $upd_modules)->
                display();
    }

    public function installModule($name)
    {
        if ( !\cmsUser::isAdminCan('admin/modules', $this->admin_access) ) {
            self::accessDenied();
        }

        $error = '';

        if ( $this->core->loadModuleInstaller($name) ) {
            $_module = call_user_func('info_module_' . $name);

            $error = call_user_func('install_module_' . $name);
        }
        else {
            $error = $this->lang->ad_module_wizard_failure;
        }

        if ( $error === true ) {
            $this->core->installModule($_module, $_module['config']);

            \cmsCore::addSessionMessage($this->lang->ad_module . ' <strong>"' . $_module['title'] . '"</strong> ' . $this->lang->d_success . $this->lang->ad_is_install, 'success');

            $this->redirectToAction('modules');
        }
        else {
            \cmsCore::addSessionMessage($error, 'error');

            \cmsCore::redirectBack();
        }
    }

    public function actUpdateModule($name)
    {
        if ( !\cmsUser::isAdminCan('admin/modules', $this->admin_access) ) {
            self::accessDenied();
        }

        $error = '';

        if ( empty($name) ) {
            \cmsCore::redirectBack();
        }

        if ( $this->core->loadModuleInstaller($name) ) {
            $_module = call_user_func('info_module_' . $name);

            if ( isset($_module['link']) ) {
                $_module['content'] = $_module['link'];
            }

            $error = call_user_func('upgrade_module_' . $name);
        }
        else {
            $error = $this->lang->ad_setup_wizard_failure;
        }

        if ( $error === true ) {
            $this->core->upgradeModule($_module, $_module['config']);

            \cmsCore::addSessionMessage($this->lang->ad_module . ' <strong>"' . $_module['title'] . '"</strong> ' . $this->lang->ad_success . $this->lang->ad_is_update, 'success');

            $this->redirectToAction('modules');
        }
        else {
            \cmsCore::addSessionMessage($error, 'error');

            \cmsCore::redirectBack();
        }
    }

}
