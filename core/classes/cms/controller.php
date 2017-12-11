<?php

namespace cms;

class controller
{

    /**
     * @var null|array
     */
    protected static $mapping;

    /**
     * @var array
     */
    protected static $components;

    /**
     * @var \cms\request
     */
    protected $request;

    /**
     * @var \cmsCore
     */
    protected $core;

    /**
     * @var \cmsPage
     */
    protected $page;

    /**
     * @var \cms\lang
     */
    protected $lang;

    /**
     * Ссылка компонена
     *
     * @var string
     */
    protected $root_url;

    /**
     * Путь к каталогу компонента
     *
     * @var string
     */
    protected $root_path;

    /**
     * @var \cms\model
     */
    public $model;

    /**
     * Опции компонента
     *
     * @var array
     */
    protected $options = [];

    /**
     * Флаг наличия SEO параметров для index экшена
     *
     * @var boolean
     */
    public $use_seo_options = false;

    /**
     * @var string
     */
    public $current_action;

    /**
     * @var array
     */
    public $current_params;

    public function __construct($request = null)
    {
        self::loadComponents();

        if ( $request instanceof \cms\request ) {
            $this->request = $request;
        }
        else {
            $this->request = \cms\request::getInstance();
        }

        $this->core = \cmsCore::getInstance();
        $this->page = \cmsPage::getInstance();
        $this->lang = lang::getInstance();

        // components\Название_Компонента\frontend;
        $part       = explode('\\', get_called_class());
        $this->name = isset($part[1]) ? $part[1] : null;

        if ( $this->name ) {
            $this->root_url  = $this->name;
            $this->root_path = PATH . '/components/' . $this->name . '/';

            lang::loadComponentLang($this->name);

            $this->title = $this->lang->get('component_' . $this->name . '_title');

            if ( empty($this->title) ) {
                $this->title = $this->name;
            }

            $this->model = \cmsCore::getModel($this->name);

            $this->options = $this->getOptions();
        }
    }

    public function setRootURL($root_url)
    {
        $this->root_url = $root_url;
    }

    public static function loadComponents()
    {
        if ( !isset(self::$components) ) {
            $model = new model();

            self::$components = $model->useCache('components')->get('components', function ($item, $model) {
                $item['config'] = model::yamlToArray($item['config']);
                return $item;
            }, 'link');
        }
    }

    public static function getAllComponents()
    {
        self::loadComponents();

        return self::$components;
    }

    public function hasSlug()
    {
        return !empty(self::$controllers[$this->name]['slug']) ? self::$controllers[$this->name]['slug'] : false;
    }

    /**
     * @see \cms\controller::enabled()
     */
    public function isEnabled()
    {
        return $this->enabled($this->name);
    }

    /**
     * Проверяет активен ли компонент
     *
     * @param string $name Название компонента
     *
     * @return bool
     */
    public static function enabled($name)
    {
        self::loadComponents();

        return !empty(self::$components[$name]['published']);
    }

    /**
     * Проверяет, установлен ли компонент
     *
     * @param string $name Название компонента
     *
     * @return bool
     */
    public static function installed($name)
    {
        self::loadComponents();

        return isset(self::$components[$name]);
    }

    /**
     * @see \cms\controller::getOptions()
     */
    public function getConfig()
    {
        return $this->getOptions();
    }

    /**
     * Загружает и возвращает опции текущего компонента,
     *
     * @return array
     */
    public function getOptions()
    {
        return (array) self::loadOptions($this->name);
    }

    public function setOption($key, $val)
    {
        $this->options[$key] = $val;
        return $this;
    }

    /**
     * Загружает опции компонента
     *
     * @param string $component_name
     *
     * @return array
     */
    public static function loadOptions($component_name)
    {
        self::loadComponents();

        if ( !isset(self::$components[$component_name]) ) {
            return [];
        }

        if ( empty(self::$components[$component_name]['options']) ) {
            self::$components[$component_name]['options'] = self::$components[$component_name]['config'];

            $default_config = \cmsCore::getComponentDefaultConfig($component_name);

            self::$components[$component_name]['options'] = array_merge($default_config, self::$components[$component_name]['options']);
        }

        return self::$components[$component_name]['options'];
    }

    /**
     * Сохраняет опции компонента
     *
     * @return bool
     */
    public function saveConfig()
    {
        return self::saveOptions($this->name, $this->options);
    }

    /**
     * Сохраняет опции для указанного компонента
     *
     * @param string $component_name
     * @param array $options
     *
     * @return bool
     */
    public static function saveOptions($component_name, $options)
    {
        $model = new model();

        $model->filterEqual('link', $component_name);

        $model->updateFiltered('components', array( 'config' => $options ));

        cache::getInstance()->clean('components');

        return true;
    }

    public static function getComponentsMapping()
    {
        if ( self::$mapping !== null ) {
            return self::$mapping;
        }

        self::$mapping = [];

        self::loadComponents();

        foreach ( self::$components as $component ) {
            if ( !empty($component['slug']) ) {
                self::$mapping[$component['link']] = $component['slug'];
            }
        }

        return self::$mapping;
    }

    //========================================================================//

    /**
     * Вызывается до начала работы экшена
     */
    public function before($action_name)
    {
        if ( $this->use_seo_options && $action_name == 'index' ) {
            if ( !empty($this->options['seo_keys']) ) {
                $this->page->setKeywords($this->options['seo_keys']);
            }

            if ( !empty($this->options['seo_desc']) ) {
                $this->page->setDescription($this->options['seo_desc']);
            }
        }

        return true;
    }

    /**
     * Вызывается после работы экшена
     */
    public function after($action_name)
    {
        return true;
    }

    /**
     * Проверяет существование экшена
     *
     * @param string $action_name
     *
     * @return boolean
     */
    public function isActionExists($action_name)
    {
        $method_name = 'action' . \cms\helper\str::toCamel('_', $action_name, true);

        if ( method_exists($this, $method_name) ) {
            return true;
        }

        $action_file = $this->root_path . 'actions/' . $action_name . '.php';

        if ( is_readable($action_file) ) {
            return true;
        }

        return false;
    }

    /**
     * Находит и запускает требуемый экшен
     *
     * @param string $action_name
     * @param array $params
     *
     * @return bool
     */
    public function runAction($action_name, $params = [])
    {
        if ( $this->before($action_name) === false ) {
            return false;
        }

        $this->current_params = $params;

        $action_name = $this->routeAction($action_name);

        // проверяем наличие экшена в отдельном файле
        $action_file = $this->root_path . 'actions/' . $action_name . '.php';

        if ( is_readable($action_file) ) {
            // вызываем экшен из отдельного файла
            $result = $this->runExternalAction($action_name, $this->current_params);
        }
        else {
            $method_name = 'action' . helper\str::toCamel('_', $action_name, true);

            // Если файла нет, ищем метод класса
            if ( method_exists($this, $method_name) ) {
                $result = $this->{$method_name}(...$this->current_params);
            }
            else {
                // если нет экшена в отдельном файле,
                // проверяем метод route()
                $route_uri = $action_name;

                if ( $this->current_params ) {
                    $route_uri .= '/' . implode('/', $this->current_params);
                }

                $result = $this->route($route_uri);
            }
        }

        $this->after($action_name);

        return $result;
    }

    /**
     * Выполняет экшен, находящийся в отдельном файле ./actions/$action_name.php
     *
     * @param string $action_name
     * @param array $params
     * @param bool $exit_if_error
     */
    public function runExternalAction($action_name, $params = array(), $exit_if_error = false)
    {
        $action_file = $this->root_path . 'actions/' . $action_name . '.php';

        $class_name = '\\components\\' . $this->name . '\\actions\\' . $action_name;

        if ( !is_readable($action_file) ) {
            if ( $exit_if_error ) {
                \cmsCore::halt($this->lang->file_not_found . ': ' . str_replace(PATH, '', $action_file));
//            \cmsCore::error($this->lang->file_not_found . ': ' . str_replace(PATH, '', $action_file));
            }

            return false;
        }

        include_once $action_file;

        if ( !class_exists($class_name, false) ) {
            if ( $exit_if_error ) {
                \cmsCore::halt($this->lang->vsprintf('class_not_defined', str_replace(PATH, '', $action_file), $class_name));
//            cmsCore::error($this->lang->vsprintf('class_not_defined', str_replace(PATH, '', $action_file), $class_name));
            }
            return false;
        }

        return (new $class_name($this))->run(...$params);
    }

    //========================================================================//

    /**
     * Вызывается до начала работы хука
     */
    public function beforeHook($event_name)
    {
        return true;
    }

    /**
     * Вызывается после работы хука
     */
    public function afterHook($event_name)
    {
        return true;
    }

    /**
     * Находит и запускает хук для указанного события
     * @param string $event_name
     */
    public function runHook($event_name, $params = array())
    {
        if ( $this->beforeHook($event_name) === false ) {
            return false;
        }

        $method_name = 'on' . \cms\helper\str::toCamel('_', $event_name, true);

        if ( method_exists($this, $method_name) ) {
            $result = $this->{$method_name}($params);
        }
        else {
            // если метода хука нет, проверяем наличие его в отдельном файле
            $hook_file = $this->root_path . 'hooks/' . $event_name . '.php';

            if ( is_readable($hook_file) ) {
                // вызываем хук из отдельного файла
                $result = $this->runExternalHook($event_name, $params);
            }
            else {
                // хука нет вообще, возвращаем данные запроса без изменений
                return $this->request->getData();
            }
        }

        $this->afterHook($event_name);

        return $result;
    }

    /**
     * Выполняет хук, находящийся в отдельном файле ./hooks/$event_name.php
     *
     * @param str $event_name
     */
    public function runExternalHook($event_name, $params = array())
    {
        $class_name = 'on' . \cms\helper\str::toCamel('_', $this->name, true) . \cms\helper\str::toCamel('_', $event_name, true);

        if ( !class_exists($class_name, false) ) {

            $hook_file = $this->root_path . 'hooks/' . $event_name . '.php';

            include_once $hook_file;
        }

        return (new $class_name($this))->run($params);
    }

    //========================================================================//

    public function loadRoutes()
    {
        $file = $this->root_path . 'routes.php';

        if ( !is_readable($file) ) {
            return [];
        }

        include_once($file);

        $routes_func = 'routes_' . $this->name;

        $routes = call_user_func($routes_func);

        if ( !is_array($routes) ) {
            return [];
        }

        return $routes;
    }

    public function halt($text = '')
    {
        die((string) $text);
    }

    /**
     * Позволяет переопределить экшен перед вызовом
     *
     * @param string $action_name
     *
     * @return string
     */
    public function routeAction($action_name)
    {
        return $action_name;
    }

    public function route($uri)
    {
        $action_name = $this->parseRoute($uri);

        if ( !$action_name ) {
            return false;
        }

        $this->runAction($action_name);
    }

    /**
     * Определяет экшен, по списку маршрутов из файла router.php контроллера
     *
     * @param string $uri
     *
     * @return boolean
     */
    public function parseRoute($uri)
    {
        $routes = $this->loadRoutes();

        // Флаг удачного перебора
        $is_found = false;

        // Название найденного экшена
        $action_name = false;

        // перебираем все маршруты
        if ( $routes ) {
            foreach ( $routes as $route ) {
                // сравниваем шаблон маршрута с текущим URI
                preg_match($route['pattern'], $uri, $matches);

                // Если найдено совпадение
                if ( $matches ) {
                    $action_name = $route['action'];

                    // удаляем шаблон и экшен из параметров маршрута,
                    // чтобы не мешали при переборе параметров запроса
                    unset($route['pattern']);
                    unset($route['action']);

                    // перебираем параметры маршрута в виде ключ=>значение
                    foreach ( $route as $key => $value ) {
                        if ( is_integer($key) ) {
                            // Если ключ - целое число, то значением является сегмент URI
                            $this->request->set($value, $matches[$key]);
                        }
                        else {
                            // иначе, значение берется из маршрута
                            $this->request->set($key, $value);
                        }
                    }

                    // совпадение есть
                    $is_found = true;

                    // раз найдено совпадение, прерываем цикл
                    break;
                }
            }
        }

        // Если в маршруте нет совпадений
        if ( !$is_found ) {
            return false;
        }

        return $action_name;
    }

    //========================================================================//

    public function validate_required($value)
    {
        if ( $value === '0' ) {
            return true;
        }

        if ( empty($value) ) {
            return ERR_VALIDATE_REQUIRED;
        }

        return true;
    }

    public function validate_min($min, $value)
    {
        if ( (int) $value < $min ) {
            return sprintf(ERR_VALIDATE_MIN, $min);
        }

        return true;
    }

    public function validate_max($max, $value)
    {
        if ( (int) $value > $max ) {
            return sprintf(ERR_VALIDATE_MAX, $max);
        }

        return true;
    }

    public function validate_min_length($length, $value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( mb_strlen($value) < $length ) {
            return sprintf(ERR_VALIDATE_MIN_LENGTH, $length);
        }

        return true;
    }

    public function validate_max_length($length, $value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( mb_strlen($value) > $length ) {
            return sprintf(ERR_VALIDATE_MAX_LENGTH, $length);
        }

        return true;
    }

    public function validate_array_key($array, $value)
    {
        if ( is_array($value) ) {
            $result = true;
            foreach ( $value as $val ) {
                if ( !isset($array[$val]) ) {
                    $result = ERR_VALIDATE_INVALID;
                    break;
                }
            }
            return $result;
        }

        if ( !isset($array[$value]) ) {
            return ERR_VALIDATE_INVALID;
        }

        return true;
    }

    public function validate_array_keys($array, $values)
    {
        if ( empty($values) ) {
            return true;
        }

        if ( !is_array($values) ) {
            return ERR_VALIDATE_INVALID;
        }

        foreach ( $values as $value ) {
            if ( !isset($array[$value]) ) {
                return ERR_VALIDATE_INVALID;
            }
        }

        return true;
    }

    public function validate_in_array($array, $value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !in_array($value, $array) ) {
            return ERR_VALIDATE_INVALID;
        }

        return true;
    }

    public function validate_email($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !is_string($value) || !preg_match("/^([a-z0-9\._-]+)@([a-z0-9\._-]+)\.([a-z]{2,6})$/i", $value) ) {
            return ERR_VALIDATE_EMAIL;
        }

        return true;
    }

    public function validate_alphanumeric($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !is_string($value) || !preg_match("/^([a-z0-9]*)$/i", $value) ) {
            return ERR_VALIDATE_ALPHANUMERIC;
        }

        return true;
    }

    public function validate_sysname($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !is_string($value) || !preg_match("/^([a-z0-9\_]*)$/", $value) ) {
            return ERR_VALIDATE_SYSNAME;
        }

        return true;
    }

    public function validate_slug($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !is_string($value) || !preg_match("/^([a-z0-9\-\/]*)$/", $value) ) {
            return ERR_VALIDATE_SLUG;
        }

        return true;
    }

    public function validate_digits($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !in_array(gettype($value), array( 'integer', 'string' )) || !preg_match("/^([0-9]+)$/i", $value) ) {
            return ERR_VALIDATE_DIGITS;
        }

        return true;
    }

    public function validate_number($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !in_array(gettype($value), array( 'integer', 'string', 'double' )) || !preg_match("/^([\-]?)([0-9\.,]+)$/i", $value) ) {
            return ERR_VALIDATE_NUMBER;
        }

        return true;
    }

    public function validate_color($value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !is_string($value) ) {
            return ERR_VALIDATE_INVALID;
        }

        $value = ltrim($value, '#');

        if ( ctype_xdigit($value) && (strlen($value) == 6 || strlen($value) == 3) ) {
            return true;
        }

        return ERR_VALIDATE_INVALID;
    }

    public function validate_regexp($regexp, $value)
    {
        if ( empty($value) ) {
            return true;
        }

        if ( !in_array(gettype($value), array( 'integer', 'string', 'double' )) || !preg_match($regexp, $value) ) {
            return ERR_VALIDATE_REGEXP;
        }

        return true;
    }

//    public function validate_unique($table_name, $field_name, $value)
//    {
//        if ( empty($value) ) {
//            return true;
//        }
//
//        if ( !in_array(gettype($value), array( 'integer', 'string', 'double' )) ) {
//            return ERR_VALIDATE_INVALID;
//        }
//
//        $result = $this->db->isFieldUnique($table_name, $field_name, $value);
//
//        if ( !$result ) {
//            return ERR_VALIDATE_UNIQUE;
//        }
//
//        return true;
//    }
//
//    public function validate_unique_exclude($table_name, $field_name, $exclude_row_id, $value)
//    {
//        if ( empty($value) ) {
//            return true;
//        }
//
//        if ( !in_array(gettype($value), array( 'integer', 'string', 'double' )) ) {
//            return ERR_VALIDATE_INVALID;
//        }
//
//        $result = $this->db->isFieldUnique($table_name, $field_name, $value, $exclude_row_id);
//
//        if ( !$result ) {
//            return ERR_VALIDATE_UNIQUE;
//        }
//
//        return true;
//    }
//
//    public function validate_unique_ctype_field($ctype_name, $value)
//    {
//        if ( empty($value) ) {
//            return true;
//        }
//
//        if ( !in_array(gettype($value), array( 'integer', 'string' )) ) {
//            return ERR_VALIDATE_INVALID;
//        }
//
//        $content_model = \cmsCore::getModel('content');
//        $table_name    = $content_model->table_prefix . $ctype_name;
//
//        if ( $content_model->db->isFieldExists($table_name, $value) ) {
//            return ERR_VALIDATE_UNIQUE;
//        }
//
//        return true;
//    }
//
//    public function validate_unique_ctype_dataset($ctype_id, $value)
//    {
//        if ( empty($value) ) {
//            return true;
//        }
//
//        if ( !in_array(gettype($value), array( 'integer', 'string' )) ) {
//            return ERR_VALIDATE_INVALID;
//        }
//
//        $value = $this->db->escape($value);
//
//        if ( is_numeric($ctype_id) ) {
//            $where = "ctype_id='{$ctype_id}' AND name='{$value}'";
//        }
//        else {
//            $where = "target_controller='{$ctype_id}' AND name='{$value}'";
//        }
//
//        $result = !$this->db->getRow('content_datasets', $where);
//
//        if ( !$result ) {
//            return ERR_VALIDATE_UNIQUE;
//        }
//
//        return true;
//    }
}
