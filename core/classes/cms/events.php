<?php

namespace cms;

class events
{

    /**
     * Массив включенных событий и их слушателей
     *
     * @var array
     */
    protected static $events = [];

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

    /**
     * Формирует список слушателей
     */
    public static function loadListeners()
    {
        if ( empty(self::$events) ) {
            self::$events['plugin']    = [];
            self::$events['component'] = [];

            $events = db::getInstance()->getRows('events FORCE INDEX (is_enabled)', '`is_enabled` = 1', '*', 'ordering ASC', true);

            foreach ( $events as $event ) {
                self::$events[$event['type']][$event['event']]   = [];
                self::$events[$event['type']][$event['event']][] = $event['name'];
            }
        }
    }

    /**
     * Возвращает массив компонентов подписанных на указанное событие
     *
     * @param string $event
     *
     * @return array
     */
    public static function getEventComponents($event)
    {
        if ( isset(self::$events['component'][$event]) ) {
            return self::$events['component'][$event];
        }

        return [];
    }

    /**
     * Возвращает массив плагинов подписанных на указанное событие
     *
     * @param string $event
     *
     * @return array
     */
    public static function getEventPlugins($event)
    {
        $plugins = !empty(self::$events['plugin'][$event]) ? self::$events['plugin'][$event] : [];

        if ( isset(self::$old_events_names[$event]) && !empty(self::$events['plugin'][self::$old_events_names[$event]]) ) {
            $plugins = array_merge($plugins, self::$events['plugin'][self::$old_events_names[$event]]);
        }

        if ( mb_strpos($event, 'admin.toolmenu') !== false || mb_strpos($event, 'admin.listtable') !== false || mb_strpos($event, 'core.') !== false ) {
            $event = str_replace([ 'admin.toolmenu', 'admin.listtable', 'core.' ], [ 'cptoolmenu', 'admin_cplisttable', '' ], $event);
            $event = strtoupper($event);

            if ( !empty(self::$events['plugin'][$event]) ) {
                $plugins = array_merge($plugins, self::$events['plugin'][$event]);
            }
        }

        return $plugins;
    }

    /**
     * Выполняет событие сразу в режиме filter
     *
     * @param string $event
     * @param mixed $filter Данные для обработки
     * @param mixed $data Дополнительные данные
     *
     * @return mixed
     */
    public static function filter($event, $filter, $data = false)
    {
        $params['filter'] = $filter;

        if ( !empty($data) ) {
            $params['data'] = $data;
        }

        return self::call($event, $params, 'filter');
    }

    /**
     * Выполняет событие, вызывая все назначенные на него плагины и компоненты
     * в цикле перебирая все плагины и компоненты и накладывая результат на исходный массив
     *
     * @param string $event Название события
     * @param mixed $data Исходные данные
     * @param string $mode
     *  normal - последовательная обработка, каждый следующий плагин получает результат обработки данных предыдущими плагинами
     *  single - возвращается результат обработки данных первым плагином,
     *  multi  - каждый плагин получает исходные данные и возвращает обработанный результат, массив этих данных возвращается как результат выполнения события,
     *  filter - каждый плагин получает в качестве данных массив с ключами:
     *            filter   - данные для обработки, содержит данные обработанные последовательно всеми плагинами;
     *            original - изначальные данные для обработки, без изменений внесенных плагинами
     *            data     - дополнительные данные которые могут понадобиться для обратки данных, может отсутствовать
     *           каждый плагин должен вернуть обработанные данные filter, он же в конце возвращается как результат выполнения события
     *
     * @return mixed Данные, после их обработки всеми плагинами или массив всех результатов выполнения плагинов
     */
    public static function call($event, $data = false, $mode = 'normal')
    {
        $called = false;

        $results = [];

        if ( $mode == 'filter' ) {
            if ( !isset($data['filter']) ) {
                $data = [ 'filter' => $data ];
            }

            if ( !isset($data['original']) ) {
                $data['original'] = $data['filter'];
            }
        }

        // Сперва обрабатываем данные компонентами
        self::callComponents($event, $data, $results, $mode, $called);

        if ( !$called || $mode != 'single' ) {
            // Обрабатываем данные плагинами
            self::callPlugins($event, $data, $results, $mode, $called);
        }

        if ( $mode == 'filter' ) {
            return $data['filter'];
        }
        else if ( $mode == 'multi' ) {
            return $results;
        }

        return $data;
    }

    private static function callComponents($event, &$data, &$results, $mode, &$called)
    {
        $components = self::getEventComponents($event);

        foreach ( $components as $component ) {
            if ( !controller::enabled($component) ) {
                continue;
            }

            $class_name = '\\components\\' . $component . '\\frontend';

            \cmsCore::includeFile('components/' . $component . '/frontend.php');

            if ( !class_exists($class_name, false) ) {
                continue;
            }

            $controller = new $class_name();
        }
    }

    private static function callPlugins($event, &$data, &$results, $mode, &$called)
    {
        $enplugins = \cms\plugin::getEnabledPlugins();

        $plugins = self::getEventPlugins($event);

        if ( empty($plugins) ) {
            return;
        }

        foreach ( $plugins as $plugin_name ) {
            if ( !isset($enplugins[$plugin_name]) ) {
                continue;
            }

            $plugin = \cms\plugin::load($plugin_name);

            if ( $plugin !== false ) {
                if ( \cmsConfig::getConfig('debug') ) {
                    \cms\debug::startTimer($event);
                }

                if ( $plugin instanceof \cmsPlugin ) {
                    $result = $plugin->execute(self::$old_events_names[$event], $data);
                }
                else {
                    $result = $plugin->execute($event, $data);
                }

                if ( \cmsConfig::getConfig('debug') ) {
                    \cms\debug::setDebugInfo('events', $plugin_name . ': ' . $event, $event);
                }

                $called = true;

                if ( $mode == 'single' ) {
                    $data = $result;
                    break;
                }

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
                else if ( $mode == 'filter' ) {
                    $data['filter'] = $result;
                }
                else {
                    $data = $result;
                }
            }
        }
    }

}
