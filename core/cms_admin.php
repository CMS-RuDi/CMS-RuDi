<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2017
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cmsAdmin extends cmsCore
{

    protected static $_instance;

    /**
     * Устаревший метод используйте _installPlugin
     */
    public function installPlugin($plugin, $events, $config)
    {
        return $this->_installPlugin($plugin, $config);
    }

    /**
     * Устанавливает указанный плагин и возвращает его ID или false при неудачной
     * установке
     *
     * @param string $name
     * @param false|array $config
     *
     * @return false|int
     */
    public function _installPlugin($name, $config = false)
    {
        $plugin = \cms\plugin::load($plugin);

        if ( !empty($plugin) ) {
            if ( !empty($config) ) {
                $plugin->setConfig($config);
            }

            if ( $id = $plugin->install() ) {
                return $id;
            }
        }

        return false;
    }

    /**
     * Устаревший метод используйте _updatePlugin
     */
    public function upgradePlugin($plugin, $events, $config)
    {
        return $this->_updatePlugin($plugin, $config);
    }

    /**
     * Обновляет указанный плагин
     *
     * @param string $name
     * @param array $config
     *
     * @return boolean
     */
    public function _updatePlugin($name, $config = false)
    {
        $plugin = \cms\plugin::load($plugin);

        if ( !empty($plugin) ) {
            if ( !empty($config) ) {
                $plugin->setConfig($config);
            }

            return $plugin->upgrade();
        }

        return false;
    }

    /**
     * Возвращает список плагинов, имеющихся на диске, но не установленных
     *
     * @return array
     */
    public function getNewPlugins()
    {
        $new_plugins = [];
        $all_plugins = $this->getPluginsDirs();

        if ( !$all_plugins ) {
            return false;
        }

        foreach ( $all_plugins as $plugin ) {
            $installed = cmsDatabase::getInstance()->rows_count('cms_plugins', "plugin='" . $plugin . "'", 1);
            if ( !$installed ) {
                $new_plugins[] = $plugin;
            }
        }

        if ( !$new_plugins ) {
            return false;
        }

        return $new_plugins;
    }

    /**
     * Возвращает список плагинов, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedPlugins()
    {
        $upd_plugins = array();
        $all_plugins = $this->getPluginsDirs();

        if ( !$all_plugins ) {
            return false;
        }

        foreach ( $all_plugins as $plugin ) {
            $plugin_obj = $this->loadPlugin($plugin);
            $version    = $this->getPluginVersion($plugin);
            if ( $version ) {
                if ( version_compare($plugin_obj->info['version'], $version) > 0 ) {
                    $upd_plugins[] = $plugin;
                }
            }
        }

        if ( !$upd_plugins ) {
            return false;
        }

        return $upd_plugins;
    }

    /**
     * Возвращает список папок с плагинами
     * 
     * @return array
     */
    public static function getPluginsDirs()
    {
        return \cms\helper\files::getDirsList(PATH . '/plugins');
    }

    /**
     * Возвращает ID плагина по названию
     * @param string $plugin
     * @return int
     */
    public function getPluginId($plugin)
    {
        return cmsDatabase::getInstance()->get_field('cms_plugins', "plugin='" . $plugin . "'", 'id');
    }

    /**
     * Возвращает название плагина по ID
     * @param int $plugin_id
     * @return string
     */
    public function getPluginById($plugin_id)
    {
        return cmsDatabase::getInstance()->get_field('cms_plugins', "id='" . $plugin_id . "'", 'plugin');
    }

    /**
     * Возвращает версию плагина по названию
     * @param string $plugin
     * @return float
     */
    public function getPluginVersion($plugin)
    {
        return cmsDatabase::getInstance()->get_field('cms_plugins', "plugin='" . $plugin . "'", 'version');
    }

    /**
     * Устанавливает компонент
     * Возвращает ID установленного плагина
     * @param array $component
     * @param array $config
     * @return int
     */
    public function installComponent($component, $config)
    {
        $inDB = cmsDatabase::getInstance();

        $config_yaml = cmsCore::arrayToYaml($config);

        if ( !$config_yaml ) {
            $config_yaml = '';
        }

        $component['config'] = $inDB->escape_string($config_yaml);

        //добавляем компонент в базу
        $component_id = $inDB->insert('cms_components', $component);

        //возращаем ID установленного компонента
        return $component_id ? $component_id : false;
    }

    /**
     * Делает апгрейд установленного компонента
     * @param array $component
     * @param array $config
     * @return bool
     */
    public function upgradeComponent($component, $config)
    {
        $inDB = cmsDatabase::getInstance();

        //находим ID установленной версии
        $component_id = $this->getComponentId($component['link']);

        //если компонент еще не был установлен, выходим
        if ( !$component_id ) {
            return false;
        }

        //загружаем текущие настройки компонента
        $old_config = $this->loadComponentConfig($component['link']);

        //удаляем настройки, которые больше не нужны
        foreach ( $old_config as $param => $value ) {
            if ( !isset($config[$param]) ) {
                unset($old_config[$param]);
            }
        }

        //добавляем настройки, которых раньше не было
        foreach ( $config as $param => $value ) {
            if ( !isset($old_config[$param]) ) {
                $old_config[$param] = $value;
            }
        }

        //конвертируем массив настроек в YAML
        $component['config'] = $inDB->escape_string(cmsCore::arrayToYaml($old_config));

        //обновляем компонент в базе
        return $inDB->update('cms_components', $component, $component_id);
    }

    /**
     * Возвращает список компонентов, имеющихся на диске, но не установленных
     * @return array
     */
    public function getNewComponents()
    {
        $new_components = array();
        $all_components = self::getComponentsDirs();

        if ( !$all_components ) {
            return false;
        }

        foreach ( $all_components as $component ) {
            $installer_file = PATH . '/components/' . $component . '/install.php';

            if ( file_exists($installer_file) ) {
                if ( !$this->isComponentInstalled($component) ) {
                    $new_components[] = $component;
                }
            }
        }

        if ( !$new_components ) {
            return false;
        }

        return $new_components;
    }

    /**
     * Возвращает список компонентов, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedComponents()
    {
        $components = \cms\controller::getAllComponents();

        $upd_components = array();

        foreach ( $components as $component ) {
            if ( $this->loadComponentInstaller($component['link']) ) {
                $version    = $component['version'];
                $_component = call_user_func('info_component_' . $component['link']);
                if ( $version ) {
                    if ( version_compare($_component['version'], $version) > 0 ) {
                        $upd_components[] = $component['link'];
                    }
                }
            }
        }

        if ( !$upd_components ) {
            return false;
        }

        return $upd_components;
    }

    /**
     * Возвращает список папок с компонентами
     * @return array
     */
    public static function getComponentsDirs()
    {
        return cmsCore::getDirsList('/components');
    }

    /**
     * Возвращает ID компонента по названию
     * @param string $component
     * @return int
     */
    public function getComponentId($component)
    {
        $components = \cms\controller::getAllComponents();

        $component_id = 0;

        foreach ( $components as $inst_component ) {
            if ( $inst_component['link'] == $component ) {
                $component_id = $inst_component['id'];
                break;
            }
        }

        return $component_id;
    }

    /**
     * Возвращает название компонента по ID
     * @param int $component_id
     * @return string
     */
    public function getComponentById($component_id)
    {
        $components = \cms\controller::getAllComponents();

        $link = '';

        foreach ( $components as $inst_component ) {
            if ( $inst_component['id'] == $component_id ) {
                $link = $inst_component['link'];
                break;
            }
        }

        return $link;
    }

    /**
     * Возвращает версию компонента по названию
     * @param string $component
     * @return float
     */
    public function getComponentVersion($component)
    {
        $components = \cms\controller::getAllComponents();

        $version = '';

        foreach ( $components as $inst_component ) {
            if ( $inst_component['link'] == $component ) {
                $version = $inst_component['version'];
                break;
            }
        }

        return $version;
    }

    /**
     * Подключает инсталлер компонента
     * @param str $component Название компонента
     * @return type
     */
    public function loadComponentInstaller($component)
    {
        return cmsCore::includeFile('components/' . $component . '/install.php');
    }

    /**
     * Устанавливает модуль
     * @param array $module
     * @param array $config
     * @return int id модуля
     */
    public function installModule($module, $config)
    {
        $inDB = cmsDatabase::getInstance();

        $config_yaml = cmsCore::arrayToYaml($config);

        if ( !$config_yaml ) {
            $config_yaml = '';
        }

        $module['config'] = $inDB->escape_string($config_yaml);

        // Помечаем, что модуль внешний
        $module['is_external'] = 1;

        // переходной костыль
        // в модулях теперь нужно вместо, например
        // $_module['link'] = 'mod_actions';
        // писать
        // $_module['content'] = 'mod_actions';
        if ( isset($module['link']) ) {
            $module['content'] = $module['link'];
        }

        //возращаем ID установленного модуля
        return $inDB->insert('cms_modules', $module);
    }

    /**
     * Делает апгрейд установленного модуля
     * @param array $module
     * @param array $config
     * @return bool
     */
    public function upgradeModule($module, $config)
    {
        $inDB = cmsDatabase::getInstance();

        // удалить в следующем обновлении
        if ( isset($module['link']) ) {
            $module['content'] = $module['link'];
        }

        //находим ID установленной версии
        $module_id = $this->getModuleId($module['content']);

        //если модуль еще не был установлен, выходим
        if ( !$module_id ) {
            return false;
        }

        //загружаем текущие настройки модуля
        $old_config = $this->loadModuleConfig($module_id);

        //удаляем настройки, которые больше не нужны
        foreach ( $old_config as $param => $value ) {
            if ( !isset($config[$param]) ) {
                unset($old_config[$param]);
            }
        }

        //добавляем настройки, которых раньше не было
        foreach ( $config as $param => $value ) {
            if ( !isset($old_config[$param]) ) {
                $old_config[$param] = $value;
            }
        }

        //конвертируем массив настроек в YAML
        $module['config'] = $inDB->escape_string(cmsCore::arrayToYaml($old_config));

        unset($module['position']);

        //обновляем модуль в базе
        $inDB->update('cms_modules', $module, $module_id);

        $inDB->query("UPDATE cms_modules SET version = '" . $module['version'] . "' WHERE content='" . $module['content'] . "' AND user=0");

        //модуль успешно обновлен
        return true;
    }

    /**
     * Удаляет установленный модуль
     * @param mixed $module_id
     * @return bool
     */
    public function removeModule($module_id)
    {
        if ( is_array($module_id) ) {
            foreach ( $module_id as $id ) {
                $this->removeModule((int) $id);
            }
        }

        $module = $this->getModuleById($module_id);

        if ( $this->loadModuleInstaller($module) ) {
            if ( function_exists('remove_module_' . $module) ) {
                call_user_func('remove_module_' . $module);
            }
        }

        return cmsDatabase::getInstance()->delete('cms_modules', "id = '" . $module_id . "'", 1);
    }

    /**
     * Возвращает список модулей, имеющихся на диске, но не установленных
     * @return array
     */
    public function getNewModules()
    {
        $new_modules = array();
        $all_modules = self::getModulesDirs();

        if ( !$all_modules ) {
            return false;
        }

        foreach ( $all_modules as $module ) {
            $installer_file = PATH . '/modules/' . $module . '/install.php';

            if ( file_exists($installer_file) ) {

                $installed = cmsDatabase::getInstance()->rows_count('cms_modules', "content='" . $module . "' AND user=0", 1);
                if ( !$installed ) {
                    $new_modules[] = $module;
                }
            }
        }

        if ( !$new_modules ) {
            return false;
        }

        return $new_modules;
    }

    /**
     * Возвращает список модулей, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedModules()
    {
        $upd_modules = array();
        $all_modules = cmsDatabase::getInstance()->get_table('cms_modules', 'user=0 AND original = 1');

        if ( !$all_modules ) {
            return false;
        }

        foreach ( $all_modules as $module ) {
            if ( $this->loadModuleInstaller($module['content']) ) {
                $version = $module['version'];
                $_module = call_user_func('info_module_' . $module['content']);

                if ( $version ) {
                    if ( version_compare($_module['version'], $version) > 0 ) {
                        $upd_modules[] = $module['content'];
                    }
                }
            }
        }

        if ( !$upd_modules ) {
            return false;
        }

        return $upd_modules;
    }

    /**
     * Возвращает список папок с модулями
     * @return array
     */
    public static function getModulesDirs()
    {
        return cmsCore::getDirsList('/modules');
    }

    /**
     * Возвращает ID модуля по названию
     * @param string $module
     * @return int
     */
    public function getModuleId($module)
    {
        return cmsDatabase::getInstance()->get_field('cms_modules', "content='" . $module . "' AND user=0", 'id');
    }

    /**
     * Возвращает название модуля по ID
     * @param int $module_id
     * @return string
     */
    public function getModuleById($module_id)
    {
        return cmsDatabase::getInstance()->get_field('cms_modules', "id='" . $module_id . "' AND user=0", 'content');
    }

    /**
     * Возвращает версию модуля по названию
     * @param string $module
     * @return float
     */
    public function getModuleVersion($module)
    {
        return cmsDatabase::getInstance()->get_field('cms_modules', "content='" . $module . "' AND user=0", 'version');
    }

    /**
     * Подключает файл инсталлера модуля
     * @param string $module
     * @return bool
     */
    public function loadModuleInstaller($module)
    {
        return cmsCore::includeFile('modules/' . $module . '/install.php');
    }

    /**
     * Проверяет привязку плагина к событию
     *
     * @param int $plugin_id
     * @param string $event
     *
     * @return bool
     */
    public function isPluginHook($plugin_id, $event)
    {
        $db = \cms\db::getInstance();

        if ( is_numeric($plugin_id) ) {
            $name = $db->getField('plugins', 'id=' . (int) $plugin_id, 'plugin');
        }
        else {
            $name = $plugin_id;
        }

        return $db->getRowsCount('events', "type='plugin' AND name='" . $db->escape($name) . "' AND event='" . $db->escape($event) . "'");
    }

    /**
     * Возвращает все шаблоны оберток модулей
     * @return boolean
     */
    public static function getModuleTemplates()
    {
        $tpl_dir = is_dir(TEMPLATE_DIR . 'modules') ? TEMPLATE_DIR . 'modules' : DEFAULT_TEMPLATE_DIR . 'modules';
        $pdir    = opendir($tpl_dir);

        $templates = array();

        while ( $nextfile = readdir($pdir) ) {
            if (
                    ($nextfile != '.') &&
                    ($nextfile != '..') &&
                    !is_dir($tpl_dir . '/' . $nextfile) &&
                    (mb_substr($nextfile, 0, 6) == 'module')
            ) {
                $templates[$nextfile] = $nextfile;
            }
        }

        if ( !sizeof($templates) ) {
            return false;
        }

        return $templates;
    }

    /**
     * Возвращает прямую ссылку на пункт меню по его типу и опции
     * @param string $linktype
     * @param string $linkid
     * @return string
     */
    public function getMenuLink($linktype, $linkid)
    {
        $inDB = cmsDatabase::getInstance();

        $menulink = '';

        if ( $linktype == 'component' ) {
            $menulink = '/' . $linkid;
        }

        if ( $linktype == 'link' ) {
            $menulink = $linkid;
        }

        if ( $linktype == 'category' || $linktype == 'content' ) {
            $model = new cms_model_content();

            switch ( $linktype ) {
                case 'category':
                    $menulink = $model->getCategoryURL(null, $inDB->get_field('cms_category', "id='" . $linkid . "'", 'seolink'));
                    break;
                case 'content':
                    $menulink = $model->getArticleURL(null, $inDB->get_field('cms_content', "id='" . $linkid . "'", 'seolink'));
                    break;
            }
        }

        if ( $linktype == 'blog' ) {
            $model    = new cms_model_blogs();
            $menulink = $model->getBlogURL($inDB->get_field('cms_blogs', "id='" . $linkid . "'", 'seolink'));
        }

        if ( $linktype == 'video_cat' ) {
            $model    = cms_model_video::initModel();
            $cat      = $inDB->get_fields('cms_video_category', "id='" . $linkid . "'", 'id, seolink');
            $menulink = $model->getCatLink($cat['seolink'], $cat['id']);
        }

        if ( $linktype == 'uccat' ) {
            $menulink = '/catalog/' . $linkid;
        }

        if ( $linktype == 'photoalbum' ) {
            $menulink = '/photos/' . $linkid;
        }

        return $menulink;
    }

}
