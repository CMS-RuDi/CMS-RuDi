<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Based on InstantCMS v1.10.7 by InstantSoft
 *          (Original code © InstantCMS Team, 2007-2016)
 *          (Modifications © CMS RuDi Team / DagIT, 2026)
 *
 *                      LICENSED BY GNU/GPL v2
 */

namespace cms;

class backend extends controller {
    /**
     * @var \cmsUser
     */
    protected $user;

    /**
     * @var \cmsConfig
     */
    protected $config;

    /**
     * @var array|false
     */
    public static $admin_access = false;

    /**
     * @var array
     */
    protected static $toolmenu = [];

    /**
     * @var string|null
     */
    protected static $ptitle;

    /**
     * @var string|null
     */
    protected static $sub_title;

    public function __construct($request = null) {
        parent::__construct($request);

        $this->core = \cmsAdmin::getInstance();
        $this->user = \cmsUser::getInstance();
        $this->config = \cmsConfig::getInstance();

        if (!$this->user->update()) {
            \cmsCore::error404();
        }

        if (!\cmsCore::checkAccessByIp($this->config->allow_ip)) {
            \cmsCore::error404();
        }

        if (!$this->user->is_admin) {
            \cmsCore::redirect('/cp/login');
        }

        self::$admin_access = \cmsUser::getAdminAccess();

        $this->model = self::getModel($this->name, true);
        $this->root_path .= 'backend/';
        $this->root_url = 'cp/components/' . $this->root_url;
        $this->lang->load('admin/components/' . $this->name);
    }

    public static function accessDenied() {
        \cmsCore::redirect('/cp/noaccess');
    }

    public static function setTitle($title) {
        self::$ptitle = $title;
    }

    public static function setSubTitle($sub_title) {
        self::$sub_title = $sub_title;
    }

    public static function printTitle() {
        if (!empty(self::$ptitle)) {
            echo '<h1>' . self::$ptitle . '' . (!empty(self::$sub_title) ? ': <span>' . self::$sub_title . '</span>' : '') . '</h1>';
        }
    }

    public static function addToolMenuItems($items) {
        if (is_array($items)) {
            foreach ($items as $item) {
                self::addToolMenuItem($item['title'], $item['link'], $item['icon'], isset($item['target']) ? $item['target'] : false);
            }
        }
    }

    public static function addToolMenuItem($title, $link, $icon, $target = false) {
        if (is_array($title)) {
            self::$toolmenu[] = $title;
        } else {
            self::$toolmenu[] = [
                'title' => $title,
                'link' => $link,
                'icon' => $icon,
                'target' => $target
            ];
        }
    }

    public static function addToolMenuSeparator() {
        self::$toolmenu[] = false;
    }

    public static function getToolMenuItems() {
        return self::$toolmenu;
    }

    public static function clearToolMenu() {
        self::$toolmenu = [];
    }

    public static function printToolMenu() {
        if (!empty(self::$toolmenu)) {
            $action = \cmsCore::getInstance()->do;

            $component = self::getComponent();

            $toolmenu = \cms\events::call('admin.toolmenu_' . $action . (!empty($component) ? '_' . strtolower($component) : ''), self::$toolmenu);

            if (!empty($toolmenu)) {
                \cmsPage::initTemplate('cp', 'toolmenu')->
                        assign('toolmenu', self::$toolmenu)->
                        assign('uri', $_SERVER['REQUEST_URI'])->
                        display();
            }
        }

        self::clearToolMenu();
    }

    public static function getComponent() {
        $action = \cmsCore::getInstance()->do;

        if ($action == 'components') {
            $uri = \cmsCore::getInstance()->getUri();

            $segments = explode('/', $uri);

            if (!empty($segments[2])) {
                return $segments[2];
            }
        }

        return false;
    }

    public static function getPanelHtml() {
        $p_html = \cms\events::call('admin.replace_panel', ['html' => '']);

        if ($p_html['html']) {
            return $p_html['html'];
        }

        $tpl = \cmsPage::initTemplate('cp/special', 'panel')->
                assign('banners_enabled', \cms\controller::enabled('banners'))->
                assign('forms_list', \cmsCore::getInstance()->getListItems('cms_forms'));

        if (\cms\controller::enabled('banners')) {
            $tpl->assign('banners_list', \cms_model_banners::getBannersListHTML());
        }

        return $tpl->fetch();
    }

    public static function getLangPanel($target, $target_id, $field) {
        $langs = \cmsCore::getDirsList('/languages');

        if (empty($target_id) || count($langs) < 1) {
            return '';
        }

        return \cmsPage::initTemplate('cp/special', 'lang_panel')->
                        assign('langs', \cmsCore::getDirsList('/languages'))->
                        assign('target', $target)->
                        assign('target_id', $target_id)->
                        assign('field', $field)->
                        fetch();
    }

    public static function componentHasOldBackend($component) {
        return file_exists(PATH . '/admin/components/' . $component . '/backend.php');
    }

    public static function componentHasNewBackend($component) {
        return file_exists(PATH . '/components/' . $component . '/backend.php');
    }

    public static function showDebugInfo() {
        \cmsPage::initTemplate('cp/special', 'debug')->
                assign('time', \cms\debug::getTime('cms'))->
                assign('memory', round(@memory_get_usage() / 1024 / 1024, 2))->
                assign('debug', \cms\debug::getDebugInfo())->
                assign('debug_tabs', \cms\debug::getDebagTargets())->
                assign('debug_times', \cms\debug::getTotalRunTime())->
                display();
    }

    public function initListTable($table, $fields, $actions, $orderby = 'id') {
        $event = 'admin.listtable_' . strtolower($table) . '_' . $this->current_action . '_' . $this->name;
        list($table, $fields, $actions, $orderby) = \cms\events::call($event, array($table, $fields, $actions, $orderby));

        foreach ($fields as $key => $value) {
            if (isset($fields[$key]['fdate'])) {
                $this->model->db->select("DATE_FORMAT(" . $fields[$key]['field'] . ", '" . $fields[$key]['fdate'] . "')", $fields[$key]['field']);
            }
        }

        if (\cms\session::has('filter_table') && \cms\session::get('filter_table') != $table) {
            \cms\session::delete('filter');
        }

        $query = $this->core->getUriQuery();

        if ($this->request->has('nofilter')) {
            \cms\session::delete('filter');
            unset($query['nofilter']);
            $query_str = http_build_query($query);
            \cmsCore::redirect($this->genActionUrl() . ($query_str ? '?' . $query_str : ''));
        }

        $query_str = $query_clear = $query ? http_build_query($query) : '';

        $filter = false;
        \cms\session::delete('filter');

        if ($this->request->has('filter')) {
            $filter = $this->request->get('filter', 'array_str', '');
            \cms\session::set('filter', $filter);
        } elseif (\cms\session::has('filter')) {
            $filter = \cms\session::get('filter');
        }

        $perpage = 60;
        $fcount = 0;

        if ($filter) {
            foreach ($filter as $key => $value) {
                if (!$value || $value == '-100') {
                    continue;
                }

                // фильтровать можно только по именам полей таблицы
                if (!preg_match('/^[a-zA-Z0-9_\.]+$/', (string) $key)) {
                    continue;
                }

                if (!is_numeric($value)) {
                    $this->model->db->filterLike($key, '%' . $value . '%');
                } else {
                    $this->model->db->filterEqual($key, $value);
                }

                $fcount++;
            }

            if (!\cms\session::has('filter')) {
                \cms\session::set('filter', $filter);
            }
        }

        $has_filters = false;
        foreach ($fields as $key => $value) {
            if (isset($value['filter'])) {
                $has_filters = true;
                unset($query['filter'][$value['field']]);
            }
        }
        if ($has_filters) {
            $query_clear = $query ? http_build_query($query) : '';
        }

        $sort = $this->request->get('sort', 'str', '');

        if ($sort == false) {
            if ($orderby) {
                $sort = $orderby;
            } else {
                foreach ($_fields as $key => $value) {
                    if ($_fields[$key]['field'] == 'ordering' && $sort != 'NSLeft') {
                        $sort = 'ordering';
                        $so = 'asc';
                    }
                }
            }
        }

        $order = '';

        if ($sort) {
            // допускаем только список полей с необязательным направлением сортировки
            if (preg_match('/^[a-zA-Z0-9_\.]+(?:\s+(?:asc|desc))?(?:\s*,\s*[a-zA-Z0-9_\.]+(?:\s+(?:asc|desc))?)*$/i', trim($sort))) {
                $order = trim($sort);

                if ($this->request->has('so')) {
                    $so = strtolower(trim($this->request->get('so', 'str', '')));

                    if (($so === 'asc' || $so === 'desc') && !preg_match('/\s+(asc|desc)$/i', $order)) {
                        $order .= ' ' . $so;
                    }
                }
            }
        }

        $page = $this->request->get('page', 'int', 1);

        $total = $this->model->{'getCount' . ucfirst($table)}();
        $this->model->db->limitPage($page, $perpage);

        if ($order) {
            $this->model->db->orderBy(...array_pad(explode(' ', $order), 2, ''));
        }

        $items = $this->model->{'get' . ucfirst($table)}();

        foreach ($items as $id => $item) {
            $items[$id]['_f_links_'] = [];
            $items[$id]['_f_otstup_'] = [];
            $items[$id]['_a_links_'] = [];
            $items[$id]['_a_print_'] = [];

            foreach ($fields as $key => $field) {
                if (isset($field['link'])) {
                    $link = $field['link'];
                    foreach ($item as $f => $v) {
                        $link = str_replace("%$f%", $v, $link);
                    }
                    $items[$id]['_f_links_'][$key] = $link;

                    if (isset($field['prc'])) {
                        // функция обработки под названием $field['prc']
                        // какие параметры передать функции - один ключ или произвольный массив ключей
                        if (is_array($field['field'])) {
                            foreach ($field['field'] as $func_field) {
                                $in_func_array[$func_field] = $item[$func_field];
                            }
                            $data = call_user_func($field['prc'], $in_func_array);
                        } else {
                            $data = call_user_func($field['prc'], $item[$field['field']]);
                        }
                    } else {
                        $data = $item[$field['field']];
                        if (isset($field['maxlen'])) {
                            if (mb_strlen($data) > $field['maxlen']) {
                                $data = mb_substr($data, 0, $field['maxlen']) . '...';
                            }
                        }
                    }

                    $items[$id][$field['field']] = $data;

                    //nested sets otstup
                    if (isset($item['NSLevel']) && ($field['field'] == 'title' || (is_array($field['field']) && in_array('title', $field['field'])))) {
                        $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                        if ($item['NSLevel'] - 1 > 0) {
                            $otstup .= ' &raquo; ';
                        }
                    } else {
                        $otstup = '';
                    }

                    $items[$id]['_f_otstup_'][$key] = $otstup;
                } else {
                    if ($field['field'] != 'ordering') {
                        if ($field['field'] == 'published') {
                            $do = isset($field['do']) ? $field['do'] : 'do';
                            list($dos, $ids) = isset($field['do_suffix']) ? [$field['do_suffix'], 'item_id'] : ['', 'id'];

                            if (!empty($field['uri'])) {
                                $qs = str_replace(['%id%', '%do%'], [$item['id'], 'hide'], $field['uri']);
                                $qs2 = str_replace(['%id%', '%do%'], [$item['id'], 'show'], $field['uri']);
                            } else {
                                $qs = cpAddParam($query_clear, $do, 'hide' . $dos);
                                $qs = $this->genActionUrl() . '?' . cpAddParam($qs, $ids, $item['id']);
                                $qs2 = cpAddParam($query_clear, $do, 'show' . $dos);
                                $qs2 = $this->genActionUrl() . '?' . cpAddParam($qs2, $ids, $item['id']);
                            }

                            $items[$id]['_f_links_']['qs'] = $qs;
                            $items[$id]['_f_links_']['qs2'] = $qs2;
                        } else {
                            if (isset($field['prc'])) {
                                // функция обработки под названием $field['prc']
                                // какие параметры передать функции - один ключ или произвольный массив ключей
                                if (is_array($field['field'])) {
                                    foreach ($field['field'] as $func_field) {
                                        $in_func_array[$func_field] = $item[$func_field];
                                    }
                                    $data = call_user_func($field['prc'], $in_func_array);
                                } else {
                                    $data = call_user_func($field['prc'], $item[$field['field']]);
                                }
                            } else {
                                $data = $item[$field['field']];
                                if (isset($field['maxlen'])) {
                                    if (mb_strlen($data) > $field['maxlen']) {
                                        $data = mb_substr($data, 0, $field['maxlen']) . '...';
                                    }
                                }
                            }

                            $items[$id][$field['field']] = $data;

                            //nested sets otstup
                            if (isset($item['NSLevel']) && ($field['field'] == 'title' || (is_array($field['field']) && in_array('title', $field['field'])))) {
                                $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                                if ($item['NSLevel'] - 1 > 0) {
                                    $otstup .= ' &raquo; ';
                                }
                            } else {
                                $otstup = '';
                            }

                            $items[$id]['_f_otstup_'][$key] = $otstup;
                        }
                    } else {

                    }
                }
            }

            foreach ($actions as $key => $action) {
                $link = $action['link'];
                if (is_array($action['link'])) {
                    $link = call_user_func($link, $item);
                } else {
                    foreach ($item as $f => $v) {
                        $link = str_replace("%$f%", $v, $link);
                    }
                }
                $items[$id]['_a_links_'][$key] = $link;

                if (isset($action['condition'])) {
                    $items[$id]['_a_print_'][$key] = call_user_func($action['condition'], $item);
                } else {
                    $items[$id]['_a_print_'][$key] = true;
                }
            }
        }

        \cms\session::set('filter_table', $table);

        if ($this->model->db->error()) {
            \cms\session::delete('filter');
            \cmsCore::redirect('/' . $this->genActionUrl() . '?' . $query_str);
        }

        $this->page->initTemplate('cp', 'cp_list')
                ->assign('component', $this->name)
                ->assign('uri', $this->genActionUrl())
                ->assign('query_uri', $this->genActionUrl() . '?' . $query_clear)
                ->assign('has_filters', $has_filters)
                ->assign('fcount', $fcount)
                ->assign('filter', $filter)
                ->assign('table', $table)
                ->assign('fields', $fields)
                ->assign('actions', $actions)
                ->assign('items', $items)
                ->assign('pagebar', $this->page->getPagebar($total, $page, $perpage, $this->genActionUrl() . '?' . cpAddParam($query_clear, 'page', '%page%')))
                ->display();
    }

    //========================================================================//

    /**
     * Возвращает объект контроллера компонента
     *
     * @param string $component
     * @param bool $reinit
     * @param \cms\request $request
     *
     * @return \self|false
     */
    public static function getBackend($component, $reinit = false, $request = false) {
        if (!isset(self::$factory['backends'][$component]) || $reinit === true) {
            $class_name = '\\components\\' . $component . '\\backend';

            if (\cmsCore::includeFile('components/' . $component . '/backend.php') && class_exists($class_name, false)) {
                self::$factory['backends'][$component] = new $class_name($request instanceof \cms\request ? $request : \cms\request::getInstance());
            }
        }

        return isset(self::$factory['backends'][$component]) ? self::$factory['backends'][$component] : false;
    }

    //========================================================================//

    public static function getModelClassName($component_name) {
        $class_name = '\\components\\' . $component_name . '\\backend\\model';

        if (!class_exists($class_name)) {
            return parent::getModelClassName($component_name);
        }

        return $class_name;
    }

    public function user() {
        return $this->user;
    }

    public function core() {
        return $this->core;
    }
}