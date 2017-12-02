<?php

namespace cms;

class plugin
{

    /**
     * Массив со списком активных плагинов
     *
     * @var array
     */
    protected static $plugins = [];

    /**
     * Массив активных событий, в качестве ключа идет название события, в качестве
     * значения массив плагинов подписанных на это событие
     *
     * @var array
     */
    protected static $plugin_events = [];

    /**
     * Массив из названий событий, где в качестве ключа идет новое название а в
     * качестве значения старое название
     *
     * @var array
     */
    protected static $old_events_names = [
        'admin.main_menu'                   => 'CPMENU',
        'admin.replace_panel'               => 'REPLACE_PANEL',
        'actions.get_action'                => 'GET_ACTION',
        'actions.log_action'                => 'LOG_ACTION',
        'actions.delete_log'                => 'DELETE_LOG',
        'actions.update_log'                => 'UPDATE_LOG',
        'actions.delete_object_log'         => 'DELETE_OBJECT_LOG',
        'actions.delete_target_log'         => 'DELETE_TARGET_LOG',
        'actions.get_before_actions'        => 'GET_BEFORE_ACTIONS',
        'actions.get_actions'               => 'GET_ACTIONS',
        'arhive.get_items'                  => 'GET_ARHIVE',
        'banners.get_banner'                => 'GET_BANNER',
        'banners.click_banner'              => 'CLICK_BANNER',
        'board.add_item'                    => 'ADD_BOARD_RECORD',
        'board.update_item'                 => 'UPDATE_BOARD_RECORD',
        'board.add_item_done'               => 'ADD_BOARD_DONE',
        'board.get_item'                    => 'GET_BOARD_RECORD',
        'board.get_items'                   => 'GET_BOARD_RECORDS',
        'board.get_category'                => 'GET_BOARD_CAT',
        'board.get_sub_categories'          => 'GET_BOARD_SUBCATS',
        'board.delete_item'                 => 'DELETE_BOARD_RECORD',
        'blog.add'                          => 'ADD_BLOG',
        'blog.update'                       => 'UPDATE_BLOG',
        'blog.get_blog'                     => 'GET_BLOG',
        'blog.get_blogs'                    => 'GET_BLOGS',
        'blog.add_post'                     => 'ADD_POST',
        'blog.update_post'                  => 'UPDATE_POST',
        'blog.delete_post'                  => 'DELETE_POST',
        'blog.get_post'                     => 'GET_POST',
        'blog.get_posts'                    => 'GET_POSTS',
        'blog.is_writer'                    => 'IS_BLOG_WRITER',
        'blog.get_categories'               => 'GET_BLOG_CATS',
        'blog.get_category'                 => 'GET_BLOG_CAT',
        'blog.add_category'                 => 'ADD_BLOG_CAT',
        'blog.update_category'              => 'UPDATE_BLOG_CAT',
        'blog.delete_category'              => 'DELETE_BLOG_CAT',
        'blog.get_authors'                  => 'GET_BLOG_AUTHORS',
        'blog.update_authors'               => 'UPDATE_BLOG_AUTHORS',
        'blog.delete'                       => 'DELETE_BLOG',
        'blogs.add_post_done'               => 'ADD_POST_DONE',
        'catalog.add_item'                  => 'ADD_CATALOG_ITEM',
        'catalog.add_item_done'             => 'ADD_CATALOG_DONE',
        'catalog.update_item'               => 'UPDATE_CATALOG_ITEM',
        'catalog.renew_item'                => 'RENEW_CATALOG_ITEM',
        'catalog.copy_item'                 => 'COPY_CATALOG_ITEM',
        'catalog.get_item_image'            => 'GET_CATALOG_ITEM_IMAGE',
        'catalog.add_category'              => 'ADD_CATALOG_CAT',
        'catalog.copy_category'             => 'COPY_CATALOG_CAT',
        'catalog.delete_category'           => 'DELETE_CATALOG_CAT',
        'catalog.update_category'           => 'UPDATE_CATALOG_CAT',
        'catalog.get_sub_categories'        => 'GET_CATALOG_SUBCATS',
        'catalog.add_discount'              => 'ADD_CATALOG_DISCOUNT',
        'catalog.update_discount'           => 'UPDATE_CATALOG_DISCOUNT',
        'catalog.delete_discount'           => 'DELETE_CATALOG_DISCOUNT',
        'clubs.create_club'                 => 'ADD_CLUB',
        'clubs.update_club'                 => 'UPDATE_CLUB',
        'clubs.delete_club'                 => 'DELETE_CLUB',
        'clubs.get_clubs'                   => 'GET_CLUBS',
        'clubs.get_club'                    => 'GET_CLUB',
        'clubs.add_post_done'               => 'ADD_POST_DONE',
        'clubs.leave'                       => 'LEAVE_CLUB',
        'clubs.join'                        => 'JOIN_CLUB',
        'clubs.view_photo'                  => 'VIEW_CLUB_PHOTO',
        'clubs.add_wall_item'               => 'ADD_WALL',
        'comments.get_items'                => 'GET_COMMENTS',
        'comments.before_show'              => 'BEFORE_SHOW_COMMENTS',
        'comments.get_item'                 => 'GET_COMMENT',
        'comments.add_item'                 => 'ADD_COMMENT',
        'comments.delete_item'              => 'DELETE_COMMENT',
        'comments.get_items_from_module'    => 'GET_COMMENTS_MODULE',
        'content.get_items'                 => 'GET_ARTICLES',
        'content.get_item'                  => 'GET_ARTICLE',
        'content.add_item'                  => 'ADD_ARTICLE',
        'content.add_item_done'             => 'ADD_ARTICLE_DONE',
        'content.pre_edit_item'             => 'PRE_EDIT_ARTICLE',
        'content.update_item'               => 'UPDATE_ARTICLE',
        'content.after_edit_item'           => 'AFTER_EDIT_ARTICLE',
        'content.delete_item'               => 'DELETE_ARTICLE',
        'content.get_category'              => 'GET_CONTENT_CAT',
        'content.get_categories_tree'       => 'GET_CONTENT_CATS_TREE',
        'content.get_sub_categories'        => 'GET_CONTENT_SUBCATS',
        'content.get_publishing_categories' => 'GET_CONTENT_PUBCATS',
        'forms.add_form'                    => 'ADD_FORM',
        'forms.update_form'                 => 'UPDATE_FORM',
        'forms.get_form'                    => 'GET_FORM',
        'forms.delete_form'                 => 'DELETE_FORM',
        'forms.add_field'                   => 'ADD_FORM_FIELD',
        'forms.update_field'                => 'UPDATE_FORM_FIELD',
        'forms.get_form_fields'             => 'GET_FORM_FIELDS',
        'forum.get_forum'                   => 'GET_FORUM',
        'forum.get_forums'                  => 'GET_FORUMS',
        'forum.get_thread'                  => 'GET_FORUM_THREAD',
        'forum.get_threads'                 => 'GET_THREADS',
        'forum.get_post'                    => 'GET_FORUM_POST',
        'forum.get_posts'                   => 'GET_FORUM_POSTS',
        'forum.move_post'                   => 'MOVE_FORUM_POST',
        'forum.get_category'                => 'GET_FORUM_CAT',
        'forum.add_post'                    => 'ADD_FORUM_POST',
        'forum.update_post'                 => 'UPDATE_FORUM_POST',
        'forum.delete_post'                 => 'DELETE_POST',
        'forum.add_thread'                  => 'ADD_THREAD',
        'forum.open_thread'                 => 'OPEN_THREAD',
        'forum.close_thread'                => 'CLOSE_THREAD',
        'forum.update_thread'               => 'UPDATE_THREAD',
        'forum.get_post_file'               => 'GET_POST_FILE',
        'forum.get_post_files'              => 'GET_POST_FILES',
        'forum.delete_post_file'            => 'DELETE_POST_FILE',
        'forum.add_poll'                    => 'ADD_FORUM_POLL',
        'forum.update_poll'                 => 'UPDATE_FORUM_POLL',
        'forum.delete_poll'                 => 'DELETE_FORUM_POLL',
        'forum.get_thread_poll'             => 'GET_THREAD_POLL',
        'photos.add_album'                  => 'ADD_ALBUM',
        'photos.get_album'                  => 'GET_PHOTO_ALBUM',
        'photos.get_albums'                 => 'GET_ALBUMS',
        'photos.delete_album'               => 'DELETE_ALBUM',
        'photos.add_photo'                  => 'ADD_PHOTO',
        'photos.add_photo_done'             => 'ADD_PHOTO_DONE',
        'photos.update_photo'               => 'UPDATE_PHOTO',
        'photos.get_photo'                  => 'GET_PHOTO',
        'photos.get_photos'                 => 'GET_PHOTOS',
        'photos.delete_photo'               => 'DELETE_PHOTO',
        'photos.publish_photo'              => 'PUBLISH_PHOTO',
        'poll.get_poll'                     => 'GET_POLL',
        'poll.delete_poll'                  => 'DELETE_POLL',
        'search.get_result'                 => 'GET_SEARCH_RESULT',
        'users.before_register'             => 'USER_BEFORE_REGISTER',
        'users.register'                    => 'USER_REGISTER',
        'users.activated'                   => 'USER_ACTIVATED',
        'users.view_profile'                => 'USER_PROFILE',
        'users.update_profile'              => 'UPDATE_USER_PROFILES',
        'users.update_data'                 => 'UPDATE_USER_USERS',
        'users.update_password'             => 'UPDATE_USER_PASSWORD',
        'users.send_message'                => 'USER_SEND_MESSEDGE',
        'users.accept_friend'               => 'USER_ACCEPT_FRIEND',
        'users.add_wall'                    => 'ADD_WALL',
        'users.get_user'                    => 'GET_USER',
        'users.delete_user'                 => 'DELETE_USER',
        'users.delete_group'                => 'DELETE_USER_GROUP',
        'users.add_photo_album'             => 'ADD_USER_PHOTO_ALBUM',
        'users.get_albums'                  => 'GET_USER_ALBUMS',
        'users.get_uploaded_photos'         => 'GET_USER_UPLOADED_PHOTOS',
        'users.load_user'                   => 'LOAD_USER',
        'users.login'                       => 'USER_LOGIN',
        'users.logout'                      => 'USER_LOGOUT',
        'users.signin'                      => 'SIGNIN_USER',
        'users.add_friend'                  => 'ADD_FRIEND',
        'users.delete_friend'               => 'DELETE_FRIEND',
        'users.get_wall_posts'              => 'GET_WALL_POSTS',
        'users.get_guest'                   => 'GET_GUEST',
        'users.give_award'                  => 'GIVE_AWARD',
        'captcha.get'                       => 'GET_CAPTCHA',
        'captcha.check'                     => 'CHECK_CAPTCHA',
        'bbcode.replace_buttons'            => 'REPLACE_BBCODE_BUTTONS',
        'bbcode.get_button'                 => 'GET_BBCODE_BUTTON',
        'smiles.replace'                    => 'REPLACE_SMILES',
        'page.print_head'                   => 'PRINT_PAGE_HEAD',
        'page.print_body'                   => 'PRINT_PAGE_BODY',
        'page.get_pagebar'                  => 'GET_PAGEBAR',
        'loginza.auth'                      => 'LOGINZA_AUTH',
        'loginza.button'                    => 'LOGINZA_BUTTON',
        'core.get_main_components'          => 'URL_WITHOUT_COM_NAME',
        'core.clear_cache'                  => 'CLEAR_CACHE',
        'wysiwyg'                           => 'INSERT_WYSIWYG',
        'get_index'                         => 'GET_INDEX',
    ];
    //========================================================================//

    /**
     * @var \cms\db
     */
    protected $db;

    /**
     * @var \cmsCore
     */
    protected $inCore;

    /**
     * @var \cmsPage
     */
    protected $inPage;

    /**
     * @var \cms\lang
     */
    protected $lang;

    /**
     * @var string
     */
    public $lang_prefix;

    /**
     * Название класса плагина
     * @var string
     */
    protected $name;

    /**
     * Версия плагина
     * @var string
     */
    protected $version;

    /**
     * Автор плагина
     * @var string
     */
    protected $author;

    /**
     * Email автора плагина
     * @var string
     */
    protected $author_email;

    /**
     * События на которые будет подписан плагин
     * @var array
     */
    protected $events = [];

    /**
     * Настройки плагина
     * @var array
     */
    protected $config = [];

    /**
     * Настройки плагина по умолчанию
     * @var array
     */
    protected $default_config = [];

    public function __construct()
    {
        $this->inCore = \cmsCore::getInstance();
        $this->db     = \cms\db::getInstance();
        $this->inPage = \cmsPage::getInstance();

        $this->name   = get_called_class();
        $this->config = array_merge($this->default_config, self::getConfig($this->name));

        $this->setLangPrefix();

        $this->lang = \cms\lang::loadPluginLang(get_called_class());
    }

    private function setLangPrefix()
    {
        $parts = explode('_', $this->name);

        foreach ( $parts as $part ) {
            $this->lang_prefix .= $part{0};
        }

        if ( mb_strlen($this->lang_prefix) == 1 ) {
            $this->lang_prefix .= $part{1} . $part{2};
        }
    }

    /**
     * Возвращает название класса плагина
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Возвращает версию плагина
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Возвращает имя автора плагина
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Возвращает email адресс авора
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->author_email;
    }

    /**
     * Возвращает название плагина
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->lang->get($this->lang_prefix . '_title');
    }

    /**
     * Возвращае описание плагина
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->lang->get($this->lang_prefix . '_description');
    }

    /**
     * Возвращает информацию о плагине
     *
     * @return array
     */
    public function getInfo()
    {
        return [
            'plugin'       => $this->getName(),
            'title'        => $this->getTitle(),
            'description'  => $this->getDescription(),
            'version'      => $this->getVersion(),
            'author'       => $this->getAuthor(),
            'author_email' => $this->getAuthorEmail(),
        ];
    }

    /**
     * Усановка плагина
     *
     * @return boolean
     */
    public function install()
    {
        $info = $this->getInfo();

        $info['config'] = \cms\model::arrayToYaml($this->config);

        if ( !$info['config'] ) {
            $info['config'] = '';
        }

        // добавляем плагин в базу
        $plugin_id = $this->db->insert('plugins', $info);

        // возвращаем ложь, если плагин не установился
        if ( !$plugin_id ) {
            return false;
        }

        // добавляем хуки событий для плагина
        foreach ( $this->events as $event ) {
            $this->db->insert('event_hooks', array( 'event' => $event, 'plugin_id' => $plugin_id ));
        }

        // возращаем ID установленного плагина
        return $plugin_id;
    }

    /**
     * Обновление плагина
     *
     * @return boolean
     */
    public function upgrade()
    {
        // находим ID установленной версии
        $plugin_id = $this->db->getField('plugins', "plugin='" . $this->name . "'");

        // если плагин еще не был установлен, выходим
        if ( !$plugin_id ) {
            return false;
        }

        // загружаем текущие настройки плагина
        $old_config = self::getConfig($this->name);

        // удаляем настройки, которые больше не нужны
        foreach ( $old_config as $param => $value ) {
            if ( !isset($this->default_config[$param]) ) {
                unset($old_config[$param]);
            }
        }

        $config = array_merge($this->default_config, $old_config);

        unset($old_config);

        $info = $this->getInfo();

        // конвертируем массив настроек в YAML
        $info['config'] = \cms\model::arrayToYaml($config);

        // обновляем плагин в базе
        $this->db->update('plugins', 'id=' . $plugin_id, $info);

        // Удаляем все события плагина
        $this->db->delete('event_hooks', 'plugin_id=' . $plugin_id);

        // добавляем хуки событий для плагина
        foreach ( $this->events as $event ) {
            $this->db->insert('event_hooks', array( 'event' => $event, 'plugin_id' => $plugin_id ));
        }

        // плагин успешно обновлен
        return true;
    }

    /**
     * Возвращает настройки плагина
     *
     * @param type $name
     *
     * @return type
     */
    public function getConfig()
    {
        if ( !empty($this->config) ) {
            return $this->config;
        }
        else {
            return self::getCfg($this->name);
        }
    }

    /**
     * Возвращает кофигурацию плагина по его названию
     *
     * @param string $name
     *
     * @return array
     */
    public static function getCfg($name)
    {
        self::loadConfig($name);

        if ( empty(self::$plugins[$name]) ) {
            return [];
        }

        $config = self::$plugins[$name]['config'];

        if ( !is_array($config) ) {
            $config                         = \cms\model::yamlToArray($config);
            self::$plugins[$name]['config'] = $config;
        }

        return $config;
    }

    /**
     * Возвращает настройки плагина из базы данных
     *
     * @param string $name
     *
     * @return array
     */
    protected static function loadConfig($name)
    {
        if ( !isset(self::$plugins[$name]) ) {
            $plugin = $this->db->getFields('plugins', "plugin='" . $name . "'", 'id, plugin, config');

            if ( !empty($plugin) ) {
                self::$plugins[$plugin['plugin']] = $plugin;
            }
        }
    }

    /**
     * Выставляет настройки плагина
     *
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Сохроняет настройки плагина
     *
     * @return boolean
     */
    public function saveConfig()
    {
        // конвертируем массив настроек в YAML
        $config_yaml = $this->db->escape(\cms\model::arrayToYaml($this->config));

        // обновляем плагин в базе
        $this->db->query("UPDATE {#}plugins SET config='" . $config_yaml . "' WHERE plugin = '" . $this->name . "'");

        return true;
    }

    /**
     * Получает список событий для включенных плагинов
     */
    public static function loadEvents()
    {
        if ( empty(self::$plugin_events) ) {
            $db = \cms\db::getInstance();

            $result = $db->query("SELECT p.id, p.plugin, p.config, e.event FROM {#}event_hooks e LEFT JOIN {#}plugins p ON e.plugin_id = p.id WHERE p.published = 1");

            if ( $result->num_rows ) {
                while ( $plugin = $result->fetch_assoc() ) {
                    self::$plugins[$plugin['plugin']]        = $plugin;
                    self::$plugin_events[$plugin['event']][] = $plugin['plugin'];
                }
            }
        }
    }

    /**
     * Возвращает массив с именами плагинов, привязанных к событию $event
     *
     * @param string $event
     *
     * @return array
     */
    public static function getEventPlugins($event)
    {
        $plugins = !empty(self::$plugin_events[$event]) ? self::$plugin_events[$event] : [];

        if ( isset(self::$old_events_names[$event]) && !empty(self::$plugin_events[self::$old_events_names[$event]]) ) {
            $plugins = array_merge($plugins, self::$plugin_events[self::$old_events_names[$event]]);
        }

        if ( mb_strpos($event, 'admin.toolmenu') !== false || mb_strpos($event, 'admin.listtable') !== false || mb_strpos($event, 'core.') !== false ) {
            $event = str_replace([ 'admin.toolmenu', 'admin.listtable', 'core.' ], [ 'cptoolmenu', 'admin_cplisttable', '' ], $event);
            $event = strtoupper($event);

            if ( !empty(self::$plugin_events[$event]) ) {
                $plugins = array_merge($plugins, self::$plugin_events[$event]);
            }
        }

        return $plugins;
    }

    /**
     * Производит событие, вызывая все назначенные на него плагины
     * в цикле перебирая все плагины и накладывая результат на исходный массив
     *
     * @param string $event Название эвента
     * @param mixed $data Исходные данные
     * @param string $mode
     *  normal - нормальная последовательная обработка данных плагинами,
     *  single - обработка только первым плагином,
     *  multi  - обработка всеми плагинами исходных данных и возврат массива с этими данными
     *
     * @return mixed Данные, после их обработки всеми плагинами или массив всех результатов выполнения плагинов
     */
    public static function callEvent($event, $data, $mode = 'normal')
    {
        $tkey = \cms\debug::startTimer();

        $results = [];

        //получаем все активные плагины, привязанные к указанному событию
        $plugins = self::getEventPlugins($event);

        if ( $plugins ) {
            //перебираем плагины и вызываем каждый из них, передавая элемент $item
            foreach ( $plugins as $plugin_name ) {
                $plugin = self::load($plugin_name);

                if ( $plugin !== false ) {
                    // для отладки запоминаем названия
                    if ( \cmsConfig::getConfig('debug') ) {
                        $enabled_plugins[] = $plugin->getTitle();
                    }

                    $result = $plugin->execute($event, $data);

                    if ( $mode == 'single' ) {
                        $data = $result;
                        break;
                    }

                    // если нужно вернуть для каждого плагина свой результат
                    if ( $mode == 'multi' ) {
                        if ( $result !== false ) {
                            if ( $plugin instanceof \cmsPlugin ) {
                                $results[] = array(
                                    'result' => $result,
                                    'info'   => $plugin->getInfo(),
                                    'config' => $plugin->getConfig()
                                );
                            }
                            else {
                                $results[] = $result;
                            }
                        }
                    }
                    else {
                        $data = $result;
                    }

                    unset($plugin);
                }
            }
        }

        if ( \cmsConfig::getConfig('debug') ) {
            \cms\debug::setDebugInfo('events', $event . (isset($enabled_plugins) ? PHP_EOL . implode(', ', $enabled_plugins) : ''), $plugins ? $tkey : false );
        }

        return $mode == 'multi' ? $results : $data;
    }

    /**
     * Загружает плагин и возвращает его объект
     *
     * @param string $plugin Название плагина
     *
     * @return self
     */
    public static function load($plugin)
    {
        if ( !class_exists($plugin) ) {
            return false;
        }

        return new $plugin();
    }

    //========================================================================//
    // Теперь об именовании методов для выполнения их при определенных событиях
    // Например событие contet.add_item - добавление материала в каталог статей
    // название метода для обработки этого события должен иметь название
    // contentAddItem тоесть первая буква стоящая после разделителя (точка или знак
    // подчеркивания) переводится в верхний регистр а все разделители убираются
    // из названия события. Принимать метод будет один единственный параметр $data
    // содержание которого будет зависить от события
    //
    // public function contentAddItem($item)
    // {
    //    return $item;
    // }
    //
    // в конце метод должен вернуть обработанные данные
    //========================================================================//

    public function execute($event, $data = [])
    {
        $method_name = \cms\helper\str::toCamel('.', $event);
        $method_name = \cms\helper\str::toCamel('_', $method_name);

        if ( method_exists($this, $method_name) ) {
            return $this->{$method_name}($data);
        }

        return $data;
    }

}
