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

define('CORE_VERSION', '1.10.7');
define('CORE_BUILD', '1');
define('CORE_VERSION_DATE', '2016-07-26');
define('CORE_BUILD_DATE', '2016-07-26');
define('CMS_RUDI', 1);

class cmsCore
{

    use \Singeltone;

    private static $jevix;
    protected $menu_item;
    protected $menu_id;
    protected $menu_struct;
    protected $is_menu_id_strict;
    protected $uri;
    protected $real_uri;
    public $component;
    public $do;
    public $components;
    protected $url_without_com_name = false;
    protected $module_configs;
    protected $template;

    /**
     * ========= DEPRECATED =========
     */
    public $events             = [];
    public $plugins            = [];
    public $plugin_events      = [];
    public $single_run_plugins = [];

    protected function __construct($install_mode = false)
    {
        \cms\debug::startTimer('cms');

        // проверяем для совместимости
        if ( !defined('HOST') ) {
            define('HOST', \cms\request::getScheme() . '://' . \cms\request::getHost());
        }

        if ( $install_mode ) {
            return;
        }

        $inConf = cmsConfig::getInstance();

        //проверяем был ли переопределен язык через сессию
        if ( isset($_SESSION['lang']) ) {
            $inConf->lang = $_SESSION['lang'];
        }

        \cms\lang::getInstance()->setLocale();

        // определяем контекст использования
        \cms\request::getInstance()->setContext();

        // загрузим структуру меню в память
        $this->loadMenuStruct();

        // получим URI
        $this->uri = $this->detectURI();

        // загрузим все компоненты в память
        $this->components = $this->getAllComponents();

        // определим компонент
        $this->component = $this->detectComponent();

        // загрузим все события плагинов в память
        \cms\events::loadListeners();

        // массив текущего пункта меню
        $this->menu_item = $this->getMenuItem($this->menuId());

        // проверяем шаблон пункта меню
        $menu_template = $this->menuTemplate();

        if ( $menu_template ) {
            $inConf->template = $menu_template;
        }

        //проверяем был ли переопределен шаблон через сессию
        if ( isset($_SESSION['template']) ) {
            $inConf->template = $_SESSION['template'];
        }

        if ( !defined('TEMPLATE') ) {
            define('TEMPLATE', $inConf->template);
            define('TEMPLATE_DIR', PATH . '/templates/' . $inConf->template . '/');
            define('DEFAULT_TEMPLATE_DIR', PATH . '/templates/_default_/');
        }
    }

    /**
     * Сохраняет настройки плагина в базу
     *
     * @param string $plugin_name Название плагина
     * @param array $config Массив настроек плагина
     *
     * @return bool
     */
    public function savePluginConfig($plugin_name, $config)
    {
        $obj = \cms\plugin::load($plugin_name);

        $obj->setConfig($config)->saveConfig();

        return true;
    }

    /**
     * Возвращает полный список компонентов
     *
     * @return array
     */
    public function getAllComponents()
    {
        $components = \cms\controller::getAllComponents();

        if ( !$components ) {
            die('kernel panic');
        }

        return $components;
    }

    /**
     * Возвращает массив компонента по ID
     *
     * @param int $id ID компонента
     *
     * @return array
     */
    public function getComponent($id)
    {
        $components = \cms\controller::getAllComponents();

        foreach ( $components as $component ) {
            if ( $component['id'] == $id ) {
                return $component;
            }
        }

        return [];
    }

    /**
     * Загружает библиотеку из файла /core/lib_XXX.php, где XXX = $lib
     *
     * @param string $lib
     *
     * @return bool
     */
    public static function loadLib($lib)
    {
        return self::includeFile('core/lib_' . $lib . '.php');
    }

    /**
     * Подключает внешний файл
     *
     * @param string $file Путь от корня сайта без начального слеша
     *
     * @return bool
     */
    public static function includeFile($file)
    {
        if ( file_exists(PATH . '/' . $file) ) {
            include_once PATH . '/' . $file;
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Подключает функции для работы с графикой
     */
    public static function includeGraphics()
    {
        include_once PATH . '/includes/graphic.inc.php';
    }

    /**
     * Выводит визуальный редактор
     *
     * @param string $name Название поля
     * @param string $text Текст в редакторе
     * @param int $height Высота
     * @param int $width Ширина
     * @param string $toolbar Название тулбара
     *
     * @return bool
     */
    public static function insertEditor($name, $text = '', $height = '350', $width = '500', $toolbar = 'full')
    {
        $editor = \cms\events::call('wysiwyg', [ 'name' => $name, 'text' => $text, 'toolbar' => $toolbar, 'height' => $height, 'width' => $width ], 'single');

        if ( !is_array($editor) ) {
            echo $editor;
            return true;
        }

        echo \cms\lang::getInstance()->insert_wysiwyg_error;

        return false;
    }

    /**
     * Добавляет сообщение в сессию
     *
     * @param string $message
     * @param string $class
     */
    public static function addSessionMessage($message, $class = 'info')
    {
        $_SESSION['core_message'][] = '<div class="message_' . $class . '">' . $message . '</div>';
    }

    /*
     * Возвращает массив сообщений сохраненных в сессии
     */

    public static function getSessionMessages()
    {
        if ( isset($_SESSION['core_message']) ) {
            $messages = $_SESSION['core_message'];
        }
        else {
            $messages = false;
        }

        self::clearSessionMessages();

        return $messages;
    }

    /*
     * Очищает очередь сообщений сессии
     */

    public static function clearSessionMessages()
    {
        unset($_SESSION['core_message']);
    }

    /**
     * Возвращает текущий URI
     * Нужна для того, чтобы иметь возможность переопределить URI.
     * По сути является эмулятором внутреннего mod_rewrite
     *
     * @return string
     */
    private function detectURI()
    {
        $uri = trim(urldecode($_SERVER['REQUEST_URI']));

        $uri = ltrim($uri, '/');

        if ( !$uri ) {
            return '';
        }

        // игнорируемые для детекта url
        if ( preg_match('/^(admin|install|index)(\/|\?|\.)(.*)/ui', $uri) ) {
            return '';
        }

        // если в URL присутствует знак вопроса, значит в нем есть GET-параметры
        // которые нужно распарсить и добавить в массив $_REQUEST
        $pos_que = mb_strpos($uri, '?');

        if ( $pos_que !== false && (mb_strpos($uri, '/url=') === false) ) {
            // получаем строку запроса
            $query_data = [];
            $query_str  = mb_substr($uri, $pos_que + 1);

            // удаляем строку запроса из URL
            $uri = rtrim(mb_substr($uri, 0, $pos_que), '/');

            // парсим строку запроса
            parse_str($query_str, $query_data);

            $this->uri_query = $query_data;

            // добавляем к полученным данным $_REQUEST
            // именно в таком порядке, чтобы POST имел преимущество над GET
            $_REQUEST = array_merge($query_data, $_REQUEST);
        }

        $rules = [];

        if ( self::includeFile('url_rewrite.php') ) {
            // подключаем список rewrite-правил
            if ( function_exists('rewrite_rules') ) {
                //получаем правила
                $rules = rewrite_rules();
            }
        }

        if ( self::includeFile('custom_rewrite.php') ) {
            // подключаем список пользовательских rewrite-правил
            if ( function_exists('custom_rewrite_rules') ) {
                //добавляем к полученным ранее правилам пользовательские
                $rules = array_merge($rules, custom_rewrite_rules());
            }
        }

        $found = false;

        // Запоминаем реальный uri
        $this->real_uri = $uri;

        if ( $rules ) {
            // перебираем правила
            foreach ( $rules as $rule ) {
                // небольшая валидация правила
                if ( !$rule['source'] || !$rule['target'] || !$rule['action'] ) {
                    continue;
                }

                // проверяем совпадение выражения source с текущим uri
                if ( preg_match($rule['source'], $uri, $matches) ) {
                    // перебираем совпавшие сегменты и добавляем их в target
                    // чтобы сохранить параметры из $uri в новом адресе
                    foreach ( $matches as $key => $value ) {
                        if ( !$key ) {
                            continue;
                        }

                        if ( mb_strstr($rule['target'], '{' . $key . '}') ) {
                            $rule['target'] = str_replace('{' . $key . '}', $value, $rule['target']);
                        }
                    }

                    // выполняем действие
                    switch ( $rule['action'] ) {
                        case 'rewrite':
                            $uri   = $rule['target'];
                            $found = true;
                            break;
                        case 'redirect':
                            self::redirect($rule['target']);
                            break;
                        case 'redirect-301':
                            self::redirect($rule['target'], '301');
                            break;
                        case 'alias':
                            // Разбираем $rule['target'] на путь к файлу и его параметры
                            $t     = parse_url($rule['target']);

                            // Для удобства формируем массив $include_query
                            // переменные будут сохранены в элементах массива
                            if ( !empty($t['query']) ) {
                                mb_parse_str($t['query'], $include_query);
                            }

                            if ( file_exists(PATH . '/' . $t['path']) ) {
                                include_once PATH . '/' . $t['path'];
                            }

                            self::halt();
                            break;
                    }
                }

                if ( $found ) {
                    break;
                }
            }
        }

        return $uri;
    }

    /**
     * Определяет текущий компонент
     * Считается, что компонент указан в первом сегменте URI,
     * иначе подключается компонент для главной страницы
     *
     * @return string $component
     */
    private function detectComponent()
    {
        // главная страница
        if ( empty($this->uri) ) {
            return cmsConfig::getConfig('homecom');
        }

        // разбиваем URL на сегменты
        $segments = explode('/', $this->uri);

        $component = $segments[0];

        // в названии только буквы и цифры
        $component = preg_replace('/[^a-z0-9]/iu', '', $component);

        if ( $this->isComponentInstalled($component) ) {
            // если компонент определен и существует
            return $component;
        }
        else {
            // если компонент не существует, считаем что это content
            $this->uri = cmsConfig::getConfig('com_without_name_in_url') . '/' . $this->uri;

            $this->url_without_com_name = true;

            return cmsConfig::getConfig('com_without_name_in_url');
        }
    }

    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Функция подключает файл router.php из папки с текущим компонентом
     * и вызывает метод route_component(), которые возвращает массив правил
     * для анализа URI. Если в массиве найдено совпадение с текущим URI,
     * то URI парсится и переменные, содержащиеся в нем, забиваются в массив $_REQUEST.
     *
     * @return boolean
     */
    private function parseComponentRoute()
    {
        // если uri нет, все равно возвращаем истину - для опции "компонент на главной"
        if ( !$this->uri ) {
            return true;
        }

        // если uri совпадает с названием компонента, возвращаем истину
        if ( $this->uri == $this->component ) {
            return true;
        }

        $routes = [];

        // подключаем список маршрутов компонента
        if ( self::includeFile('components/' . $this->component . '/router.php') ) {
            $fn_name = 'routes_' . $this->component;

            if ( function_exists($fn_name) ) {
                $routes = $fn_name();
            }
        }

        $routes = \cms\events::call('core.get_route_' . $this->component, $routes);

        if ( empty($routes) ) {
            return false;
        }

        // Поддерживаются routes в формате icms2 но в отличии от него в pattern
        // не должно быть названия компонента например запись
        // 'pattern' => '/^photos\/([a-z0-9\-\/]+).html$/i',
        // неправильная должно быть так
        // 'pattern' => '/^([a-z0-9\-\/]+).html$/i',
        // для routes в формате icms1 где паттерн указан в ключе _uri все по старому
        //
        //
        // Ссылка без названия компонента
        $uri = substr($this->uri, strpos($this->uri, '/') + 1);

        // Флаг удачного перебора
        $is_found = false;

        // перебираем все маршруты
        if ( $routes ) {
            foreach ( $routes as $route ) {
                // сравниваем шаблон маршрута с текущим URI
                if ( isset($route['pattern']) ) {
                    preg_match($route['pattern'], $uri, $matches);
                }
                else {
                    preg_match($route['_uri'], $this->uri, $matches);
                }

                // Если найдено совпадение
                if ( $matches ) {
                    // удаляем шаблон из параметров маршрута, чтобы не мешал при переборе
                    unset($route['_uri']);
                    unset($route['pattern']);

                    if ( isset($route['action']) && !isset($route['do']) ) {
                        $route['do'] = $route['action'];
                        unset($route['action']);
                    }

                    // перебираем параметры маршрута в виде ключ=>значение
                    foreach ( $route as $key => $value ) {
                        if ( is_integer($key) ) {
                            // Если ключ - целое число, то значением является сегмент URI
                            $_REQUEST[$value] = isset($matches[$key]) ? $matches[$key] : null;
                        }
                        else {
                            // иначе, значение берется из маршрута
                            $_REQUEST[$key] = $value;
                        }
                    }

                    // совпадение есть
                    $is_found = true;

                    // раз найдено совпадение, прерываем цикл
                    break;
                }
            }
        }

        return $is_found;
    }

    /**
     * Узнаем действие компонента
     */
    private function detectAction()
    {
        $do = preg_replace('/[^a-z0-9_\-]/i', '', self::request('do', 'str', 'view'));

        $this->do = $do ? $do : 'view';

        return true;
    }

    /**
     * Генерирует тело страницы, вызывая нужный компонент
     */
    public function proceedBody()
    {
        ob_start();

        // проверяем что компонент указан
        if ( !$this->component ) {
            return false;
        }

        $components = array( $this->component );

        if ( $this->url_without_com_name ) {
            $components = \cms\events::call('core.get_main_components', $components);
        }

        foreach ( $components as $component ) {
            $this->component = $component;

            // компонент включен?
            if ( !$this->isComponentEnable($this->component) ) {
                continue;
            }

            if ( $this->url_without_com_name ) {
                $this->uri = $this->component . strstr($this->uri, '/');
            }

            $class_name = '\\components\\' . $this->component . '\\frontend';

            if ( class_exists($class_name) ) {
                $com = new $class_name();

                $segments = explode('/', $this->uri);

                // Определяем действие из второго сегмента
                if ( isset($segments[1]) ) {
                    $action_name = $segments[1];
                }
                else {
                    $action_name = 'index';
                }

                $params = [];

                // Определяем параметры действия из всех остальных сегментов
                if ( sizeof($segments) > 2 ) {
                    $params = array_slice($segments, 2);
                }

                $this->do = $action_name;

                $result = $com->runAction($action_name, $params);

                if ( $result === false ) {
                    continue;
                }

                if ( is_string($result) ) {
                    echo $result;
                }

                unset($result);
            }
            else {
                // парсим адрес и заполняем массив $_REQUEST
                if ( !$this->parseComponentRoute() ) {
                    continue;
                }

                // узнаем действие в компоненте
                $this->detectAction();

                self::loadLanguage('components/' . $this->component);

                // Вызываем сначала плагин (если он есть) на действие
                // Успешность выполнения должна определяться в методе execute плагина
                // Он должен вернуть true
                if ( !\cms\events::call('core.get_' . $this->component . '_action_' . $this->do, false) ) {
                    self::includeFile('components/' . $this->component . '/frontend.php');

                    if ( function_exists($component) ) {
                        $result = $component();

                        // в компонетах вместо error404() лучше использовать return false
                        if ( $result === false ) {
                            continue;
                        }
                    }
                    else {
                        continue;
                    }
                }
            }

            if ( self::isAjax() ) {
                cmsCore::halt(\cms\events::call('core.after_component_' . $this->component, ob_get_clean()));
            }

            cmsPage::getInstance()->page_body = \cms\events::call('core.after_component_' . $this->component, ob_get_clean());

            return true;
        }

        self::error404();
    }

    /**
     * Возвращает заголовок текущего компонента
     *
     * @return str
     */
    public function getComponentTitle()
    {
        $component_title = '';

        if ( isset($this->components[$this->component]) ) {
            $component_title = $this->components[$this->component]['title'];
        }

        return $component_title;
    }

    /**
     * Выводит 404 ошибку и завершает работу
     */
    public static function error404()
    {
        if ( ob_get_length() ) {
            ob_end_clean();
        }

        header("HTTP/1.0 404 Not Found");
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");

        if ( !cmsPage::includeTemplateFile('special/error404.php') ) {
            echo '<h1>404</h1>';
        }

        self::halt();
    }

    /**
     * Инициализирует вложенные множества и возвращает объект CCelkoNastedSet
     *
     * @return \cms\nestedsets
     */
    public static function nestedSetsInit($table)
    {
        $ns = new \cms\nestedsets(\cms\db::getInstance());

        if ( substr($table, 0, 4) == 'cms_' ) {
            $table = substr($table, 4);
        }

        $ns->setTable($table);

        $ns->setOption('parent_id', 'ordering', 'NSLeft', 'NSRight', 'NSDiffer', 'NSLevel', 'NSIgnore');

        return $ns;
    }

    /**
     * Возвращает ключевые слова для заданного текста
     *
     * @param string $text
     *
     * @return string
     */
    public static function getKeywords($text)
    {
        self::includeFile('includes/keywords.inc.php');

        $params['content']         = $text; //page content
        $params['min_word_length'] = 5;  //minimum length of single words
        $params['min_word_occur']  = 2;  //minimum occur of single words

        $params['min_2words_length']        = 5;  //minimum length of words for 2 word phrases
        $params['min_2words_phrase_length'] = 10; //minimum length of 2 word phrases
        $params['min_2words_phrase_occur']  = 2; //minimum occur of 2 words phrase

        $params['min_3words_length']        = 5;  //minimum length of words for 3 word phrases
        $params['min_3words_phrase_length'] = 10; //minimum length of 3 word phrases
        $params['min_3words_phrase_occur']  = 2; //minimum occur of 3 words phrase

        $keyword = new autokeyword($params, "UTF-8");

        return $keyword->get_keywords();
    }

    /**
     * Получет из request переменную $search и кладет в сессию
     * при отсутствии в request переменной $search берет из сессии
     * или возвращает $default
     *
     * @return str
     */
    public static function getSearchVar($search = '', $default = '')
    {
        $value = self::strClear(mb_strtolower(urldecode(self::request($search, 'html'))));

        $com = self::getInstance()->component;

        if ( $value ) {
            if ( $value == 'all' ) {
                cmsUser::sessionDel($com . '_' . $search);
                $value = '';
            }
            else {
                cmsUser::sessionPut($com . '_' . $search, $value);
            }
        }
        else if ( cmsUser::sessionGet($com . '_' . $search) ) {
            $value = cmsUser::sessionGet($com . '_' . $search);
        }
        else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Редирект на компонент
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     * @param array $query
     */
    public static function redirectTo($controller, $action = '', $params = [], $query = [], $code = 303)
    {
        $location = '/' . $controller . (($action && $action != 'index') ? '/' . $action : '');

        if ( $params ) {
            $location .= '/' . implode('/', $params);
        }

        if ( $query ) {
            $location .= '?' . http_build_query($query, '', '&');
        }

        self::redirect($location, $code);
    }

    public static function redirectBack()
    {
        self::redirect(self::getBackURL(false));
    }

    public static function redirect($url, $code = 303)
    {
        if ( $code == '301' ) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        else {
            header('HTTP/1.1 303 See Other');
        }

        header('Location:' . $url);

        self::halt();
    }

    /**
     * Возвращает предыдущий URL для редиректа назад.
     * Если находит переменную $_REQUEST['back'], то возвращает ее
     *
     * @param bool $is_request Учитывать $_REQUEST['back']
     *
     * @return string
     */
    public static function getBackURL($is_request = true)
    {
        $back = '/';

        if ( self::inRequest('back') && $is_request ) {
            $back = self::request('back', 'str', '/');
        }
        elseif ( !empty($_SERVER['HTTP_REFERER']) ) {
            $refer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

            if ( $refer_host == $_SERVER['HTTP_HOST'] ) {
                $back = strip_tags($_SERVER['HTTP_REFERER']);
            }
        }

        return $back;
    }

    /**
     * Закачивает файл на сервер и отслеживает ошибки
     *
     * @param string $source
     * @param string $destination
     * @param int $errorCode
     *
     * @return bool
     */
    public static function moveUploadedFile($source, $destination, $errorCode)
    {
        global $_LANG;

        $max_size = ini_get('upload_max_filesize');
        $max_size = str_ireplace(array( 'M', 'K' ), array( 'Mb', 'Kb' ), $max_size);

        $uploadErrors = array(
            UPLOAD_ERR_OK         => $_LANG['UPLOAD_ERR_OK'],
            UPLOAD_ERR_INI_SIZE   => $_LANG['UPLOAD_ERR_INI_SIZE'] . ' &mdash; ' . $max_size,
            UPLOAD_ERR_FORM_SIZE  => $_LANG['UPLOAD_ERR_INI_SIZE'],
            UPLOAD_ERR_PARTIAL    => $_LANG['UPLOAD_ERR_PARTIAL'],
            UPLOAD_ERR_NO_FILE    => $_LANG['UPLOAD_ERR_NO_FILE'],
            UPLOAD_ERR_NO_TMP_DIR => $_LANG['UPLOAD_ERR_NO_TMP_DIR'],
            UPLOAD_ERR_CANT_WRITE => $_LANG['UPLOAD_ERR_CANT_WRITE'],
            UPLOAD_ERR_EXTENSION  => $_LANG['UPLOAD_ERR_EXTENSION']
        );

        if ( $errorCode !== UPLOAD_ERR_OK && isset($uploadErrors[$errorCode]) ) {
            $_SESSION['file_upload_error'] = $uploadErrors[$errorCode];

            return false;
        }
        else {
            $_SESSION['file_upload_error'] = '';

            $upload_dir = dirname($destination);

            if ( !is_writable($upload_dir) ) {
                @chmod($upload_dir, 0777);
            }

            $pi = pathinfo($destination);

            while ( mb_stripos($pi['basename'], '.htm') ||
            mb_stripos($pi['basename'], '.ph') ||
            mb_stripos($pi['basename'], '.ht') ) {
                $pi['basename'] = str_ireplace(array( 'htm', 'ph', 'ht' ), '', $pi['basename']);
            }

            $destination = $pi['dirname'] . DIRECTORY_SEPARATOR . $pi['basename'];

            return @move_uploaded_file($source, $destination);
        }
    }

    public static function uploadError()
    {
        if ( $_SESSION['file_upload_error'] ) {
            return $_SESSION['file_upload_error'];
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает массив с настройками модуля
     *
     * @param int $module_id
     *
     * @return array
     */
    public function loadModuleConfig($module_id)
    {
        if ( isset($this->module_configs[$module_id]) ) {
            return $this->module_configs[$module_id];
        }

        $mod = cmsDatabase::getInstance()->get_fields('cms_modules', "id='" . $module_id . "'", 'content, config');
        if ( !$mod ) {
            return array();
        }

        $config = self::yamlToArray($mod['config']);

        // переходный костыль для указания шаблона
        if ( !isset($config['tpl']) ) {
            $config['tpl'] = $mod['content'] . '.tpl';
        }

        $this->cacheModuleConfig($module_id, $config);

        return $config;
    }

    /**
     * Сохраняет настройки модуля в базу
     *
     * @param int $module_id
     * @param array $config
     *
     * @return bool
     */
    public function saveModuleConfig($module_id, $config)
    {
        $inDB = cmsDatabase::getInstance();

        //конвертируем массив настроек в YAML
        $config_yaml = $inDB->escape_string(self::arrayToYaml($config));

        //обновляем модуль в базе
        $update_query = "UPDATE cms_modules SET config='" . $config_yaml . "' WHERE id = '" . $module_id . "'";

        $inDB->query($update_query);

        return true;
    }

    /**
     * Кэширует конфигурацию модуля на время выполнения скрипта
     *
     * @param int $module_id
     * @param array $config
     *
     * @return boolean
     */
    public function cacheModuleConfig($module_id, $config)
    {
        $this->module_configs[$module_id] = $config;
        return true;
    }

    public function isModuleInstalled($module)
    {
        return (bool) cmsDatabase::getInstance()->rows_count('cms_modules', "content='" . $module . "' AND user=0", 1);
    }

    /**
     * Применяет фильтры к тексту
     *
     * @param str $content
     *
     * @return str
     */
    public static function processFilters($content)
    {
        return \cms\events::call('run_filter', $content);
    }

    /**
     * Возвращает количество загрузок файла
     *
     * @param string $fileurl
     *
     * @return int
     */
    public static function fileDownloadCount($fileurl)
    {
        $inDB = cmsDatabase::getInstance();

        $fileurl = $inDB->escape_string($fileurl);

        $hits = $inDB->get_field('cms_downloads', "fileurl = '" . $fileurl . "'", 'hits');

        return $hits ? $hits : 0;
    }

    /**
     * Возвращает тег <img> с иконкой, соответствующей типу файла
     *
     * @param string $filename
     *
     * @return int
     */
    public static function fileIcon($filename)
    {
        $standart_icon = 'file.gif';

        $ftypes = [
            [
                'ext'  => 'avi mpeg mpg mp4 flv divx xvid vob',
                'icon' => 'video.gif'
            ],
            [
                'ext'  => 'mp3 ogg wav',
                'icon' => 'audio.gif'
            ],
            [
                'ext'  => 'zip rar gz arj 7zip',
                'icon' => 'archive.gif'
            ],
            [
                'ext'  => 'gif jpg jpeg png bmp pcx wmf cdr ai',
                'icon' => 'image.gif'
            ],
            [
                'ext'  => 'pdf djvu',
                'icon' => 'pdf.gif'
            ],
            [
                'ext'  => 'doc',
                'icon' => 'word.gif'
            ],
            [
                'ext'  => 'iso mds mdf 000',
                'icon' => 'cd.gif'
            ],
        ];

        $path_parts = pathinfo($filename);
        $ext        = $path_parts['extension'];
        $icon       = '';

        foreach ( $ftypes as $key => $value ) {
            if ( mb_strstr($value['ext'], $ext) ) {
                $icon = $value['icon'];
                break;
            }
        }

        if ( $icon == '' ) {
            $icon = $standart_icon;
        }

        $html = '<img src="/images/icons/filetypes/' . $icon . '" border="0" />';

        return $html;
    }

    /**
     * Перетирает содержание страницы
     * в случае остутствия у группы доступа к текущему пункту меню
     */
    public function checkMenuAccess()
    {
        $inPage = cmsPage::getInstance();

        if ( !$this->menu_item ) {
            return true;
        }

        $access_list = $this->menu_item['access_list'];

        // если полное совпадение, то ищем опцию "Только для родительских ссылок"
        // если она включена, то полностью совпадаемый урл показываем
        if ( $this->isMenuIdStrict() && $this->menu_item['is_lax'] ) {
            return true;
        }

        if ( !self::checkContentAccess($access_list) ) {
            ob_start();

            cmsPage::includeTemplateFile('special/accessdenied.php');

            $inPage->page_body = ob_get_clean();

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Проверяет наличие ссылки в пункте меню
     * в случае обнаружения, возвращает его заголовок
     *
     * @param str $link
     *
     * @return string
     */
    public function getLinkInMenu($link)
    {
        if ( !$this->menu_item ) {
            return '';
        }

        foreach ( $this->menu_struct as $menu ) {
            if ( $menu['link'] == $link ) {
                return $menu['title'];
            }
        }

        return '';
    }

    /**
     * Возвращает заголовок текущего пункта меню
     *
     * @return string
     */
    public function menuTitle()
    {
        if ( !$this->menu_item ) {
            return '';
        }

        return $this->menu_item['title'];
    }

    /**
     * Возвращает название шаблона, назначенного на пункт меню
     * Если используется шаблон по-умолчанию, то возвращает false
     *
     * @param int $menuid
     *
     * @return string|false
     */
    public function menuTemplate()
    {
        if ( !$this->menu_item ) {
            return '';
        }

        return $this->menu_item['template'];
    }

    /**
     * Возвращает true если URI страницы и ссылка активного пункта меню совпали полностью
     *
     * @return boolean
     */
    public function isMenuIdStrict()
    {
        return $this->is_menu_id_strict;
    }

    public function isMainPage()
    {
        return $this->menuId() == 1;
    }

    /**
     * Возвращает ID текущего пункта меню
     *
     * @return int
     */
    public function menuId()
    {
        //если menu_id был определен ранее, то вернем и выйдем
        if ( isset($this->menu_id) ) {
            return $this->menu_id;
        }

        if ( $this->url_without_com_name ) {
            $uri = mb_substr($this->uri, mb_strlen(cmsConfig::getConfig('com_without_name_in_url') . '/'));
        }
        else {
            $uri = $this->uri;
        }

        $uri      = '/' . $uri;
        $real_uri = '/' . $this->real_uri;
        $full_uri = HOST . $uri;

        //флаг, показывающий было совпадение URI и ссылки пунта меню
        //полным или частичным
        $is_strict = false;

        //главная страница?
        $menuid = ($uri == '/' ? 1 : 0);

        if ( $menuid == 1 ) {
            $this->is_menu_id_strict = 1;
            $this->menu_id           = 1;
            return 1;
        }

        //перевернем массив меню чтобы перебирать от последнего пункта к первому
        $menu = array_reverse($this->menu_struct);

        //перебираем меню в поисках текущего пункта
        foreach ( $menu as $item ) {
            if ( !$item['link'] ) {
                continue;
            }

            //полное совпадение ссылки и адреса?
            if ( in_array($item['link'], array( urldecode($uri), urldecode($full_uri), urldecode($real_uri) )) ) {
                $menuid    = $item['id'];
                $is_strict = true; //полное совпадение
                break;
            }

            //частичное совпадение ссылки и адреса (по началу строки)?
            $uri_first_part      = mb_substr(urldecode($uri), 0, mb_strlen($item['link']));
            $real_uri_first_part = mb_substr(urldecode($real_uri), 0, mb_strlen($item['link']));

            if ( in_array($item['link'], array( $uri_first_part, $real_uri_first_part )) ) {
                $menuid = $item['id'];
            }
        }

        $this->menu_id           = $menuid;
        $this->is_menu_id_strict = $is_strict;

        return $menuid;
    }

    /**
     * Возвращает данные о текущем пункте меню
     *
     * @return array
     */
    public function getMenuItem($menuid)
    {
        if ( in_array($menuid, array( 0, 1 )) ) {
            return false;
        }

        return isset($this->menu_struct[$menuid]) ? $this->menu_struct[$menuid] : false;
    }

    /**
     * Загружает всю структуру меню
     */
    private function loadMenuStruct()
    {
        if ( is_array($this->menu_struct) ) {
            return;
        }

        $model = new \cms\model();

        $this->menu_struct = $model->get('menu', function($item, $model) {
            $item['menu']   = \cms\model::yamlToArray($item['menu']);
            $item['titles'] = \cms\model::yamlToArray($item['titles']);

            // переопределяем название пункта меню в зависимости от языка
            if ( !empty($item['titles'][\cmsConfig::getConfig('lang')]) ) {
                $item['title'] = $item['titles'][\cmsConfig::getConfig('lang')];
            }

            return $item;
        });

        return;
    }

    /**
     * Возвращает всю структуру меню
     */
    public function getMenuStruct()
    {
        return $this->menu_struct;
    }

    /**
     * Возвращает элементы <option> для списка записей из указанной таблицы БД
     *
     * @param string $table
     * @param int $selected
     * @param string $order_by
     * @param string $order_to
     * @param string $where
     *
     * @return html
     */
    public static function getListItems($table, $selected = 0, $order_by = 'id', $order_to = 'ASC', $where = '', $id_field = 'id', $title_field = 'title')
    {
        $inDB = cmsDatabase::getInstance();

        $html = '';

        $sql = "SELECT " . $id_field . ", " . $title_field . " FROM " . $table . " \n";

        if ( $where ) {
            $sql .= "WHERE " . $where . " \n";
        }

        $sql .= "ORDER BY " . $order_by . " " . $order_to;

        $result = $inDB->query($sql);

        while ( $item = $inDB->fetch_assoc($result) ) {
            if ( @$selected == $item[$id_field] ) {
                $s = 'selected="selected"';
            }
            else {
                $s = '';
            }

            $html .= '<option value="' . htmlspecialchars($item[$id_field]) . '" ' . $s . '>' . $item[$title_field] . '</option>';
        }

        return $html;
    }

    /**
     * Возвращает элементы <option> для списка записей из указанной таблицы БД c вложенными множествами
     *
     * @param string $table таблица
     * @param int $selected id выделенного элемента
     * @param string $differ идентификатор множества (NSDiffer)
     * @param string $need_field выводить только элементы содержащие указанное поле
     * @param int $rootid корневой элемент
     *
     * @return html
     */
    public function getListItemsNS($table, $selected = 0, $differ = '', $need_field = '', $rootid = 0, $no_padding = false)
    {
        $inDB = cmsDatabase::getInstance();

        $html = '';

        $nested_sets = $this->nestedSetsInit($table);

        $lookup = "parent_id=0 AND NSDiffer='" . $differ . "'";

        if ( !$rootid ) {
            $rootid = $inDB->get_field($table, $lookup, 'id');
        }

        if ( !$rootid ) {
            return;
        }

        $rs_rows = $nested_sets->SelectSubNodes($rootid);

        if ( $rs_rows ) {
            while ( $node = $inDB->fetch_assoc($rs_rows) ) {
                if ( !$need_field || $node[$need_field] ) {
                    if ( @$selected == $node['id'] ) {
                        $s = 'selected="selected"';
                    }
                    else {
                        $s = '';
                    }

                    if ( !$no_padding ) {
                        $padding = str_repeat('--', $node['NSLevel']) . ' ';
                    }
                    else {
                        $padding = '';
                    }

                    $html .= '<option data-nsleft="' . $node['NSLeft'] . '" data-nsright="' . $node['NSRight'] . '" value="' . htmlspecialchars($node['id']) . '" ' . $s . '>' . $padding . $node['title'] . '</option>';
                }
            }
        }

        return $html;
    }

    /**
     * Возвращает список директорий внутри указанной, начиная от корня
     *
     * @param string $root_dir Например /languages
     *
     * @return array
     */
    public static function getDirsList($root_dir)
    {
        return \cms\helper\files::getDirsList(PATH . $root_dir);
    }

    /**
     * Регистрирует тип цели для рейтингов в базе
     *
     * @param string $target
     * @param string $component
     * @param boolean $is_user_affect
     * @param int $user_weight
     *
     * @return boolean
     */
    public static function registerRatingsTarget($target, $component, $target_title, $is_user_affect = true, $user_weight = 1, $target_table = '')
    {
        $is_user_affect = (int) $is_user_affect;

        $sql = "INSERT IGNORE INTO cms_rating_targets (target, component, is_user_affect, user_weight, target_table, target_title)
                VALUES ('" . $target . "', '" . $component . "', '" . $is_user_affect . "', '" . $user_weight . "', '" . $target_table . "', '" . $target_title . "')";

        cmsDatabase::getInstance()->query($sql);

        return true;
    }

    /**
     * Удаляет все рейтинги для указанной цели
     *
     * @param string $target
     * @param int $item_id
     *
     * @return boolean
     */
    public static function deleteRatings($target, $item_id)
    {
        $inDB = cmsDatabase::getInstance();

        $sql = "DELETE FROM cms_ratings WHERE target='" . $target . "' AND item_id='" . $item_id . "'";

        $inDB->query($sql);

        $sql = "DELETE FROM cms_ratings_total WHERE target='" . $target . "' AND item_id='" . $item_id . "'";

        $inDB->query($sql);

        return true;
    }

    /**
     * Подключает комментарии
     */
    public static function includeComments()
    {
        include_once PATH . "/components/comments/frontend.php";
    }

    /**
     * Регистрирует тип цели для комментариев в базе
     *
     * @param string $target - Цель
     * @param string $component - Компонент
     * @param string $title - Название цели во множ.числе (например "Статьи")
     * @param string $target_table - таблица, где хранятся комментируемые записи
     * @param string $subj - название цели в родительном падеже (например "вашей статьи")
     *
     * return true
     */
    public static function registerCommentsTarget($target, $component, $title, $target_table, $subj)
    {
        $sql = "INSERT IGNORE INTO cms_comment_targets (target, component, title, target_table, subj)
                 VALUES ('" . $target . "', '" . $component . "', '" . $title . "', '" . $target_table . "', '" . $subj . "')";

        cmsDatabase::getInstance()->query($sql);

        return true;
    }

    public static function getCommentsTargets()
    {
        return cmsDatabase::getInstance()->get_table('cms_comment_targets', 'id>0', '*');
    }

    /**
     * Удаляет все комментарии для указанной цели
     *
     * @param string $target
     * @param int $target_id
     *
     * @return boolean
     */
    public static function deleteComments($target, $target_id)
    {
        $inDB = cmsDatabase::getInstance();

        $comments = $inDB->get_table('cms_comments', "target='" . $target . "' AND target_id='" . $target_id . "'", 'id');

        if ( !$comments ) {
            return false;
        }

        foreach ( $comments as $comment ) {
            cmsActions::removeObjectLog('add_comment', $comment['id']);
            self::deleteUploadImages($comment['id'], 'comment');
            self::deleteRatings('comment', $comment['id']);
        }

        $inDB->delete('cms_comments', "target='" . $target . "' AND target_id='" . $target_id . "'");

        return true;
    }

    /**
     * Возвращает количество комментариев для указанной цели
     *
     * @param string $target
     * @param int $target_id
     *
     * @return int
     */
    public static function getCommentsCount($target, $target_id)
    {
        if ( self::getInstance()->isComponentInstalled('comments') ) {
            return cmsDatabase::getInstance()->rows_count('cms_comments', "target = '" . $target . "' AND target_id = '" . $target_id . "' AND published = 1");
        }
        else {
            return 0;
        }
    }

    /**
     * Переводит номер месяца в название
     *
     * @param int $num
     *
     * @return string
     */
    public static function intMonthToStr($num)
    {
        global $_LANG;
        return @$_LANG['MONTH_' . $num . '_ONE'];
    }

    /**
     * Форматирует дату из формата Y-m-d H:i:s
     *
     * @global array $_LANG
     *
     * @param str $date Исходная дата
     * @param bool $is_full_m Выводить полное название месяца
     * @param bool $is_time Дополнять часом и минутами
     * @param bool $is_now_time Дополнять даты "сегодня" и "вчера" часом и минутами
     *
     * @return string
     */
    public static function dateFormat($date, $is_full_m = true, $is_time = false, $is_now_time = true)
    {
        if ( (int) $date == 0 ) {
            return '';
        }

        global $_LANG;

        $dt     = new DateTime($date);
        $dt_now = new DateTime();

        $with_time = $is_now_time;

        // Изменяем дату в соответствии с временной зоной пользователя
        if ( !empty($_SESSION['timezone']) && $_SESSION['timezone'] != cmsConfig::getConfig('timezone') ) {
            $dt->setTimezone(new DateTimeZone($_SESSION['timezone']));
            $dt_now->setTimezone(new DateTimeZone($_SESSION['timezone']));
        }

        // сегодняшняя дата
        $today = $dt_now->format('Y-m-d');

        // вчерашняя дата
        $yesterday = $dt_now->modify('-1 day')->format('Y-m-d');

        // завтрашняя дата
        $tomorrow = $dt_now->modify('+2 day')->format('Y-m-d');

        $day  = $dt->format('Y-m-d');
        $time = $dt->format('H:i:s');

        switch ( $day ) {
            // Если дата совпадает с сегодняшней
            case $today:
                $result = $_LANG['TODAY'];
                break;
            // Если дата совпадает со вчерашней
            case $yesterday:
                $result = $_LANG['YESTERDAY'];
                break;
            // Если дата совпадает с завтрашней
            case $tomorrow:
                $result = $_LANG['TOMORROW'];
                break;
            default: {
                    // Замена числового обозначения месяца на словесное (склоненное в падеже)
                    if ( $is_full_m ) {
                        $m = $_LANG['MONTH_' . $dt->format('m')];
                    }
                    else {
                        $m = $_LANG['MONTH_' . $dt->format('m') . '_SHORT'];
                    }

                    $result = $dt->format('j') . ' ' . $m . ' ' . $dt->format('Y');

                    $with_time = $is_time;
                }
        }

        if ( $with_time && $time != '00:00:00' ) {
            $result .= ' ' . $_LANG['IN'] . ' ' . $dt->format('H') . ':' . $dt->format('i');
        }

        return $result;
    }

    /**
     * Возвращает день недели по дате
     *
     * @param string $date
     *
     * @return string
     */
    public static function dateToWday($date)
    {
        global $_LANG;

        $d = date('w', strtotime($date) + (cmsConfig::getConfig('timediff') * 3600));

        $days_week = array( $_LANG['SUNDAY'], $_LANG['MONDAY'], $_LANG['TUESDAY'], $_LANG['WEDNESDAY'], $_LANG['THURSDAY'], $_LANG['FRIDAY'], $_LANG['SATURDAY'] );

        return $days_week[$d];
    }

    /**
     * Выводит словами разницу между текущей и указанной датой
     *
     * @param string $date
     *
     * @return string
     */
    public static function dateDiffNow($date)
    {
        global $_LANG;

        $now  = time();
        $date = strtotime($date);

        if ( $date == 0 ) {
            return $_LANG['MANY_YARS'];
        }

        $diff_sec = $now - $date;

        $diff_day  = round($diff_sec / 60 / 60 / 24);
        $diff_hour = round(($diff_sec / 60 / 60) - ($diff_day * 24));
        $diff_min  = round(($diff_sec / 60) - ($diff_hour * 60));

        //Выводим разницу в днях
        if ( $diff_day > 0 ) {
            return self::spellCount($diff_day, $_LANG['DAY1'], $_LANG['DAY2'], $_LANG['DAY10']);
        }

        //Выводим разницу в часах
        if ( $diff_hour > 0 ) {
            return self::spellCount($diff_hour, $_LANG['HOUR1'], $_LANG['HOUR2'], $_LANG['HOUR10']);
        }

        //Выводим разницу в минутах
        if ( $diff_min > 0 ) {
            return self::spellCount($diff_min, $_LANG['MINUTU1'], $_LANG['MINUTE2'], $_LANG['MINUTE10']);
        }

        return $_LANG['LESS_MINUTE'];
    }

    public static function getTimeZones($offsets = false)
    {
        $results = DateTimeZone::listIdentifiers();

        if ( $offsets === false ) {
            return $results;
        }

        $timezones = array();

        foreach ( $results as $result ) {
            $now    = new DateTime(null, new DateTimeZone($result));
            $offset = $now->getOffset();

            $offsetHours   = floor(abs($offset) / 3600);
            $offsetMinutes = floor((abs($offset) - $offsetHours * 3600) / 60);
            $offsetString  = ($offset < 0 ? '-' : '+') . ($offsetHours < 10 ? '0' : '') . $offsetHours . ':' . ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;

            if ( !isset($timezones[$offsetString]) ) {
                $timezones[$offsetString] = array();
            }

            $timezones[$offsetString][] = $result;
        }

        ksort($timezones);

        return $timezones;
    }

    public static function getTimeZonesOptions($sel = '')
    {
        $lang = \cms\lang::getInstance()->load('timezone');

        $timezones = self::getTimeZones(true);

        $options = '';

        foreach ( $timezones as $offset => $tzones ) {
            foreach ( $tzones as $timezone ) {
                $options .= '<option value="' . $timezone . '"' . ($timezone == $sel ? ' selected="selected"' : '') . '>' . $offset . ' ' . $lang->e($timezone) . '</option>' . "\n";
            }
        }

        return $options;
    }

    public static function initAutoGrowText($element_id)
    {
        $inPage = cmsPage::getInstance();

        $inPage->addHeadJS('includes/jquery/autogrow/jquery.autogrow.js');

        $inPage->addHead('<script type="text/javascript">$(document).ready (function() {$(\'' . $element_id . '\').autogrow(); });</script>');

        return true;
    }

    /**
     * Проверяет права доступа к чему-либо
     * @return bool
     */
    public static function checkUserAccess($content_type, $content_id)
    {
        $inUser = cmsUser::getInstance();

        if ( $inUser->is_admin ) {
            return true;
        }

        $access = cmsDatabase::getInstance()->get_table('cms_content_access', "content_type = '" . $content_type . "' AND content_id = '" . $content_id . "'", 'group_id');

        if ( !$access || !is_array($access) ) {
            return true;
        }

        return in_array(array( 'group_id' => $inUser->group_id ), $access);
    }

    /**
     * Устанавливает права доступа
     * @return bool
     */
    public static function setAccess($id, $showfor_list, $content_type)
    {
        if ( !sizeof($showfor_list) ) {
            return true;
        }

        self::clearAccess($id, $content_type);

        foreach ( $showfor_list as $key => $value ) {
            cmsDatabase::getInstance()->insert('cms_content_access', array( 'content_id' => $id, 'content_type' => $content_type, 'group_id' => $value ));
        }

        return true;
    }

    /**
     * Очищает права доступа
     * @return bool
     */
    public static function clearAccess($id, $content_type)
    {
        return cmsDatabase::getInstance()->delete('cms_content_access', "content_id = '" . $id . "' AND content_type = '" . $content_type . "'");
    }

    public static function checkAccessByIp($allow_ips = '')
    {
        $inUser = cmsUser::getInstance();

        if ( !$inUser->ip ) {
            return false;
        }

        $allow_ips = str_replace(' ', '', $allow_ips);

        if ( !$allow_ips ) {
            return true;
        }

        $allow_ips = explode(',', $allow_ips);

        return in_array($inUser->ip, $allow_ips);
    }

    /**
     * Проверяет доступ (модуля, меню) к группе пользователя
     * @param str $access_list yaml или массив
     * @param bool $admin_always_show
     * @return bool
     */
    public static function checkContentAccess($access_list, $admin_always_show = true)
    {
        $inUser = cmsUser::getInstance();

        // если $access_list пуста, то считаем что доступ для всех
        if ( !$access_list ) {
            return true;
        }

        // администраторам показываем всегда
        if ( $inUser->is_admin && $admin_always_show ) {
            return true;
        }

        // можем передавать как YAML так и сформированный массив
        $access_list = is_array($access_list) ? $access_list : self::yamlToArray($access_list);

        return in_array($inUser->group_id, $access_list);
    }

    /**
     * Удаляет теги script iframe style meta
     * @param string $string
     * @return str
     */
    public static function badTagClear($string)
    {
        $bad_tags = array(
            "'<script[^>]*?>.*?</script>'siu",
            "'<style[^>]*?>.*?</style>'siu",
            "'<meta[^>]*?>'siu"
        );

        return self::htmlCleanUp(preg_replace($bad_tags, '', $string));
    }

    /**
     * Очищает html текст
     * @param string $text
     * @return string
     */
    public static function htmlCleanUp($text)
    {
        if ( !isset(self::$jevix) ) {
            self::$jevix = new Jevix();

            // Устанавливаем разрешённые теги. (Все не разрешенные теги считаются запрещенными.)
            self::$jevix->cfgAllowTags(array( 'p', 'a', 'img', 'i', 'b', 'u', 's', 'strike', 'video', 'em', 'strong', 'nobr', 'li', 'ol', 'ul', 'div', 'abbr', 'sup', 'sub', 'acronym', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'hr', 'pre', 'code', 'object', 'param', 'embed', 'blockquote', 'iframe', 'span', 'input', 'table', 'caption', 'th', 'tr', 'td', 'article', 'nav', 'audio', 'menu', 'section', 'time' ));
            // Устанавливаем коротие теги. (не имеющие закрывающего тега)
            self::$jevix->cfgSetTagShort(array( 'br', 'img', 'hr', 'input', 'embed' ));
            // Устанавливаем преформатированные теги. (в них все будет заменятся на HTML сущности)
            self::$jevix->cfgSetTagPreformatted(array( 'code', 'video' ));
            // Устанавливаем теги, которые необходимо вырезать из текста вместе с контентом.
            self::$jevix->cfgSetTagCutWithContent(array( 'script', 'style', 'meta' ));
            // Устанавливаем разрешённые параметры тегов. Также можно устанавливать допустимые значения этих параметров.
            self::$jevix->cfgAllowTagParams('input', array( 'type' => '#text', 'style', 'onclick' => '#text', 'value' => '#text' ));
            self::$jevix->cfgAllowTagParams('a', array( 'class' => '#text', 'title', 'href', 'style', 'rel' => '#text', 'name' => '#text' ));
            self::$jevix->cfgAllowTagParams('img', array( 'src' => '#text', 'style', 'alt' => '#text', 'title', 'align' => array( 'right', 'left', 'center' ), 'width' => '#int', 'height' => '#int', 'hspace' => '#int', 'vspace' => '#int' ));
            self::$jevix->cfgAllowTagParams('div', array(
                'class'                => '#text', 'style',
                'data-align'           => '#text', 'data-oembed'          => '#link', 'data-oembed_provider' => '#text', 'data-resizetype'      => '#text',
                'align'                => array( 'right', 'left', 'center' ) ));
            self::$jevix->cfgAllowTagParams('object', array( 'width' => '#int', 'height' => '#int', 'data' => '#text' ));
            self::$jevix->cfgAllowTagParams('param', array( 'name' => '#text', 'value' => '#text' ));
            self::$jevix->cfgAllowTagParams('embed', array( 'src' => '#image', 'type' => '#text', 'allowscriptaccess' => '#text', 'allowFullScreen' => '#text', 'width' => '#int', 'height' => '#int', 'flashvars' => '#text', 'wmode' => '#text' ));
            self::$jevix->cfgAllowTagParams('acronym', array( 'title' ));
            self::$jevix->cfgAllowTagParams('abbr', array( 'title' ));
            self::$jevix->cfgAllowTagParams('span', array( 'style' ));
            self::$jevix->cfgAllowTagParams('li', array( 'style' ));
            self::$jevix->cfgAllowTagParams('p', array( 'style' ));
            self::$jevix->cfgAllowTagParams('table', array( 'width' => '#text', 'class' => '#text', 'cellpadding' => '#int', 'cellspacing' => '#int', 'align', 'border' => '#int' ));
            self::$jevix->cfgAllowTagParams('caption', array( 'class' => '#text', 'style' ));
            self::$jevix->cfgAllowTagParams('th', array( 'class' => '#text', 'style', 'width' => '#int', 'height' => '#int', 'align', 'valign', 'colspan' => '#int', 'rowspan' => '#int' ));
            self::$jevix->cfgAllowTagParams('tr', array( 'class' => '#text', 'style' ));
            self::$jevix->cfgAllowTagParams('td', array( 'class' => '#text', 'style', 'width' => '#int', 'height' => '#int', 'align', 'valign', 'colspan' => '#int', 'rowspan' => '#int' ));
            self::$jevix->cfgAllowTagParams('iframe', array( 'width' => '#int', 'frameborder' => '#int', 'allowfullscreen' => '#text', 'height' => '#int', 'src' => array( '#domain' => array( 'youtube.com', 'vimeo.com', 'vk.com', 'rutube.ru', 'w.soundcloud.com', 'dailymotion.com', self::getHost() ) ) ));
            // Устанавливаем параметры тегов являющиеся обязательными. Без них вырезает тег оставляя содержимое.
            self::$jevix->cfgSetTagParamsRequired('img', 'src');
            // Устанавливаем теги которые может содержать тег контейнер
            self::$jevix->cfgSetTagChilds('ul', array( 'li' ), false, true);
            self::$jevix->cfgSetTagChilds('ol', array( 'li' ), false, true);
            self::$jevix->cfgSetTagChilds('object', 'param', false, true);
            self::$jevix->cfgSetTagChilds('object', 'embed', false, false);
            // Если нужно оставлять пустые не короткие теги
            self::$jevix->cfgSetTagIsEmpty(array( 'param', 'embed', 'a', 'iframe', 'div' ));
            self::$jevix->cfgSetTagParamDefault('embed', 'wmode', 'opaque', true);
            // Устанавливаем автозамену
            self::$jevix->cfgSetAutoReplace(array( '+/-', '(c)', '(с)', '(r)', '(C)', '(С)', '(R)' ), array( '±', '©', '©', '®', '©', '©', '®' ));
            // выключаем режим замены переноса строк на тег <br/>
            self::$jevix->cfgSetAutoBrMode(false);
            // выключаем режим автоматического определения ссылок
            self::$jevix->cfgSetAutoLinkMode(false);
            // Отключаем типографирование в определенном теге
            self::$jevix->cfgSetTagNoTypography('code', 'video', 'object', 'iframe');
        }

        return self::$jevix->parse($text, $errors);
    }

    /**
     * Создает и отправляет письмо электронной почтой
     * @param mixed $email
     * @param string $subject
     * @param string $message
     * @param mixed $attachment
     * @return bool
     */
    public static function mailText($email, $subject = '', $message = '', $attachment = '')
    {
        $mailer = self::initMailSystem();

        // если пришел массив адресов
        if ( is_array($email) ) {
            foreach ( $email as $address ) {
                $mailer->AddAddress($address);
            }
        }
        else {
            $mailer->AddAddress($email);
        }

        // Тема письма
        // Если тема задана, устанавливаем
        // иначе ищем в тексте письма выражение [subject:Тема письма]
        $matches = array();

        if ( $subject ) {
            $mailer->Subject = $subject;
        }
        else if ( preg_match('/\[subject:(.+)\]/iu', $message, $matches) ) {
            list($subj_tag, $subj) = $matches;

            $message = trim(str_replace($subj_tag, '', $message));

            $mailer->Subject = $subj;
        }

        // если пришел файл для вложения, вкладываем
        // иначе пытаемся в теле письма найти
        // все выражения [attachment:/path/to/file.ext]
        $matches = array();

        if ( $attachment ) {
            if ( is_array($attachment) ) {
                foreach ( $attachment as $attach ) {
                    $mailer->AddAttachment($attach);
                }
            }
            else {
                $mailer->AddAttachment($attachment);
            }
        }
        else if ( preg_match_all('/\[attachment:(.+)\]/iu', $message, $matches) ) {
            list($tags, $files) = $matches;

            foreach ( $tags as $idx => $att_tag ) {
                $message = trim(str_replace($att_tag, '', $message));

                $mailer->AddAttachment(PATH . $files[$idx]);
            }
        }

        // Тело сообщения в html
        $mailer->MsgHTML(nl2br($message));

        // Тело собщения в текстовом формате
        $mailer->AltBody = strip_tags($message);

        return $mailer->Send();
    }

    /**
     * Инициализирует объект класса PHPMailer
     * и формирует предустановки
     */
    private static function initMailSystem()
    {
        $inConf = cmsConfig::getInstance();

        self::includeFile('includes/phpmailer/class.phpmailer.php');

        $mailer          = new PHPMailer();
        $mailer->CharSet = 'UTF-8';
        $mailer->SetFrom($inConf->sitemail, ($inConf->sitemail_name ? $inConf->sitemail_name : $inConf->sitename));

        if ( $inConf->mailer == 'smtp' ) {
            $mailer->IsSMTP();
            $mailer->Host          = $inConf->smtphost;
            $mailer->Port          = $inConf->smtpport;
            $mailer->SMTPAuth      = (bool) $inConf->smtpauth;
            $mailer->SMTPKeepAlive = true;
            $mailer->Username      = $inConf->smtpuser;
            $mailer->Password      = $inConf->smtppass;
            $mailer->SMTPSecure    = $inConf->smtpsecure;
        }

        if ( $inConf->mailer == 'sendmail' ) {
            $mailer->IsSendmail();
        }

        return $mailer;
    }

    /**
     * Добавляет запись о загружаемом изображении
     * @return bool
     */
    public static function registerUploadImages($target_id, $target, $fileurl, $component)
    {
        return cmsDatabase::getInstance()->insert('cms_upload_images', array(
                    'target_id'  => $target_id,
                    'session_id' => session_id(),
                    'fileurl'    => $fileurl,
                    'component'  => $component,
                    'target'     => $target
        ));
    }

    /**
     * Устанавливает ID места назначения к загруженному изображению
     * @return bool
     */
    public static function setIdUploadImage($target, $target_id)
    {
        $inDB = cmsDatabase::getInstance();
        $sid  = session_id();

        return $inDB->query("UPDATE cms_upload_images SET target_id = '" . $target_id . "' WHERE session_id = '" . $sid . "' AND target = '" . $target . "' AND target_id = 0");
    }

    /**
     * Возвращает количество загруженных изображений для текущей сессии данного места назначения
     * @return int
     */
    public static function getTargetCount($target_id = 0)
    {
        $sid = session_id();

        $target_id = (int) $target_id;

        return cmsDatabase::getInstance()->rows_count('cms_upload_images', "target_id = '" . $target_id . "' AND session_id = '" . $sid . "'");
    }

    /**
     * Удаляет все изображения места их назначения
     * @return bool
     */
    public static function deleteUploadImages($target_id, $target)
    {
        $inDB = cmsDatabase::getInstance();

        $rs = $inDB->query("SELECT * FROM cms_upload_images WHERE target_id = '" . $target_id . "' AND target='" . $target . "'");

        if ( $inDB->num_rows($rs) ) {
            while ( $file = $inDB->fetch_assoc($rs) ) {
                $filename = PATH . $file['fileurl'];

                if ( file_exists($filename) ) {
                    @unlink($filename);
                }

                $inDB->query("DELETE FROM cms_upload_images WHERE id = '" . $file['id'] . "'");
            }
        }

        return true;
    }

    public static function parseSmiles($text, $parse_bbcode = false)
    {
        $_parse_text = self::callEvent('GET_PARSER', array(
                    'return'       => '',
                    'text'         => $text,
                    'parse_bbcode' => $parse_bbcode
        ));

        if ( $_parse_text['return'] ) {
            return $_parse_text['return'];
        }

        self::includeFile('includes/bbcode/bbcode.lib.php');

        if ( !$parse_bbcode ) {
            $text = bbcode::autoLink($text);
        }
        else {
            //parse bbcode
            $bb   = new bbcode($text);
            $text = $bb->get_html();

            // конвертируем в смайлы в изображения
            $text = $bb->replaceEmotionToSmile($text);
        }

        return $text;
    }

    /**
     * Проверяет наличие кэша для указанного контента
     *
     * @param string $target
     * @param int $target_id
     * @param int $cachetime
     * @param string $cacheint
     *
     * @return bool
     */
    public static function isCached($target, $target_id, $cachetime = 1, $cacheint = 'MINUTE')
    {
        $where = "target='" . $target . "' AND target_id='" . $target_id . "' AND cachedate >= DATE_SUB(NOW(), INTERVAL " . $cachetime . " " . (empty($cacheint) ? 'MINUTE' : $cacheint) . ")";

        $cachefile = cmsDatabase::getInstance()->get_field('cms_cache', $where, 'cachefile');

        if ( $cachefile ) {
            $cachefile = PATH . '/cache/' . $cachefile;

            if ( file_exists($cachefile) ) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            self::deleteCache($target, $target_id);
            return false;
        }
    }

    /**
     * Возвращает кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @return html
     */
    public static function getCache($target, $target_id)
    {
        $cachefile = cmsDatabase::getInstance()->get_field('cms_cache', "target='" . $target . "' AND target_id='" . $target_id . "'", 'cachefile');

        if ( $cachefile ) {
            $cachefile = PATH . '/cache/' . $cachefile;

            if ( file_exists($cachefile) ) {
                $cache = file_get_contents($cachefile);
                return $cache;
            }
        }

        return false;
    }

    /**
     * Сохраняет переданный кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @param string $html
     * @return bool
     */
    public static function saveCache($target, $target_id, $html)
    {
        $filename = md5($target . $target_id) . '.html';

        $sql = "INSERT DELAYED INTO cms_cache (target, target_id, cachedate, cachefile)
                VALUES ('" . $target . "', " . $target_id . ", NOW(), '" . $filename . "')";

        cmsDatabase::getInstance()->query($sql);

        $filename = PATH . '/cache/' . $filename;

        file_put_contents($filename, $html);

        return true;
    }

    /**
     * Удаляет кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @return bool
     */
    public static function deleteCache($target, $target_id)
    {
        cmsDatabase::getInstance()->query("DELETE FROM cms_cache WHERE target='" . $target . "' AND target_id='" . $target_id . "'");

        $oldcache = PATH . '/cache/' . md5($target . $target_id) . '.html';

        if ( file_exists($oldcache) ) {
            @unlink($oldcache);
        }

        return true;
    }

    /**
     * Очищает системный кеш
     */
    public static function clearCache()
    {
        \cms\events::call('core.clear_cache', '');

        $directory = PATH . '/cache';

        $handle = opendir($directory);

        while ( false !== ($node = readdir($handle)) ) {
            if ( $node != '.' && $node != '..' && $node != '.htaccess' ) {
                $path = $directory . '/' . $node;

                if ( is_file($path) ) {
                    if ( !@unlink($path) ) {
                        return false;
                    }
                }
            }
        }

        closedir($handle);

        return true;
    }

    /**
     * Возвращает seolink для ns категории
     * подразумевается, что категория существующая (созданная)
     * @param array $category
     * @param str $table
     * @param bool $is_cyr
     * @return str
     */
    public static function generateCatSeoLink($category, $table, $is_cyr = false, $differ = '')
    {
        $inDB = cmsDatabase::getInstance();

        $seolink = '';

        $cat = $inDB->getNsCategory($table, $category['id']);
        if ( !$cat ) {
            return $seolink;
        }

        $path_list = $inDB->getNsCategoryPath($table, $cat['NSLeft'], $cat['NSRight'], 'id, title, NSLevel, seolink, url', $differ);

        if ( !$path_list ) {
            return $seolink;
        }

        $path_list[count($path_list) - 1] = array_merge($path_list[count($path_list) - 1], $category);

        foreach ( $path_list as $pcat ) {
            $seolink .= self::strToURL((@$pcat['url'] ? $pcat['url'] : $pcat['title']), $is_cyr) . '/';
        }

        $seolink = rtrim($seolink, '/');

        $is_exists = $inDB->rows_count($table, "seolink='" . $seolink . "' AND id <> " . $category['id']);

        if ( $is_exists ) {
            $seolink .= '-' . $cat['id'];
        }

        return $seolink;
    }

    public static function halt($message = '')
    {
        die((string) $message);
    }

    public static function spellCount($num, $one, $two, $many, $is_full = true)
    {
        if ( $num % 10 == 1 && $num % 100 != 11 ) {
            $str = $one;
        }
        else if ( $num % 10 >= 2 && $num % 10 <= 4 && ($num % 100 < 10 || $num % 100 >= 20) ) {
            $str = $two;
        }
        else {
            $str = $many;
        }

        return ($is_full ? $num : '') . ' ' . $str;
    }

    public static function jsonOutput($data = array(), $is_header = true)
    {
        // очищаем буфер
        if ( ob_get_length() ) {
            ob_end_clean();
        }

        if ( $is_header ) {
            header('Content-type: application/json; charset=utf-8');
        }

        self::halt(json_encode($data));
    }

    //========================================================================//

    public static function getComponentDefaultConfig($component_name)
    {
        $class_name = \cms\controller::getModelClassName($component_name);

        if ( class_exists($class_name) ) {
            if ( method_exists($class_name, 'getDefaultConfig') ) {
                return $class_name::getDefaultConfig();
            }
        }

        return [];
    }

    public static function showDebugInfo()
    {
        if ( !defined('VALID_CMS_ADMIN') ) {
            return \cmsPage::includeTemplateFile('special/debug.php');
        }
        else {
            return \cms\backend::showDebugInfo();
        }
    }

    // ============================= DEPRECATED ==============================//
    // Методы не желательные к использованию в новых компонентах, плагинах и модулях
    // но оставленные для совместимости со старыми версиями

    public function isComponentEnable($component)
    {
        return \cms\controller::enabled($component);
    }

    public function loadComponentConfig($component)
    {
        return \cms\controller::loadOptions($component);
    }

    public function saveComponentConfig($component, $config)
    {
        return \cms\controller::saveOptions($component, $config);
    }

    public function isComponentInstalled($component)
    {
        return \cms\controller::installed($component);
    }

    public function cacheComponentConfig($component, $config)
    {
        return true;
    }

    public static function callEvent($event, $item, $is_all = false)
    {
        return \cms\events::call($event, $item, ($is_all === true ? 'multi' : 'single'));
    }

    public static function callAllEvent($event, $item)
    {
        return \cms\events::call($event, $item, 'multi');
    }

    public function loadPluginConfig($plugin_name)
    {
        return \cms\plugin::loadConfig($plugin_name);
    }

    public function getEventPlugins($event)
    {
        return \cms\events::getEventPlugins($event);
    }

    public static function loadPlugin($plugin)
    {
        return \cms\plugin::load($plugin);
    }

    public static function strToURL($str, $dont_translit = false)
    {
        return \cms\lang::slug($str, !$dont_translit);
    }

    public static function isAjax()
    {
        return \cms\request::getInstance()->isAjax();
    }

    public static function getHost()
    {
        return \cms\request::getHost();
    }

    public static function setCookie($name, $value, $time)
    {
        return \cms\cookie::set($name, $value, $time);
    }

    public static function unsetCookie($name)
    {
        return \cms\cookie::delete($name);
    }

    public static function getCookie($name)
    {
        return \cms\cookie::get($name);
    }

    public static function inRequest($name)
    {
        return \cms\request::getInstance()->has($name);
    }

    public static function request($name, $type = 'str', $default = false, $r = 'request')
    {
        return \cms\request::getInstance()->get($name, $type, $default, $r);
    }

    public static function getArrayFromRequest($types)
    {
        return \cms\request::getInstance()->getArrayFromRequest($types);
    }

    public static function cleanVar($var, $type = 'str', $default = false)
    {
        return \cms\request::cleanVar($var, $type, $default);
    }

    public static function strClear($input, $strip_tags = true)
    {
        return \cms\request::strClear($input);
        /*
          if ( is_array($input) ) {
          foreach ( $input as $key => $string ) {
          $value[self::strClear((string) $key)] = self::strClear($string, $strip_tags);
          }

          return $value;
          }

          $string = trim((string) $input);

          //Если magic_quotes_gpc = On, сначала убираем экранирование
          $string = (@get_magic_quotes_gpc()) ? stripslashes($string) : $string;

          $string = rtrim($string, ' \\');

          if ( $strip_tags ) {
          $string = cmsDatabase::getInstance()->escape_string(strip_tags($string));
          }

          return $string;
         */
    }

    public static function loadLanguage($file)
    {
        return \cms\lang::getInstance()->load($file);
    }

    public static function getLanguageTextFile($file)
    {
        return \cms\lang::getInstance()->getLetter($file);
    }

    public static function arrayToYaml($input_array, $indent = 2, $word_wrap = 40)
    {
        return \cms\model::arrayToYaml($input_array, $indent, $word_wrap);
    }

    public static function yamlToArray($yaml)
    {
        return \cms\model::yamlToArray($yaml);
    }

    public static function getGenTime()
    {
        return \cms\debug::getTime('cms');
    }

    public static function loadModel($component_name)
    {
        return \cms\controller::getModelClassName($component_name) === false ? false : true;
    }

    public static function loadClass($class)
    {
        return self::includeFile('core/classes/' . $class . '.class.php');
    }

    public function initSmarty($tpl_folder, $tpl_file)
    {
        trigger_error('initSmarty is DEPRECATED, use cmsPage::initTemplate', E_USER_NOTICE);
        return cmsPage::initTemplate($tpl_folder, $tpl_file);
    }

    public static function validateForm()
    {
        return cmsUser::checkCsrfToken();
    }

    public static function getFilters()
    {
        return false;
    }

}

//cmsCore

function icms_ucfirst($str)
{
    return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
}

function icms_substr_replace($str, $replacement, $offset, $length = NULL)
{
    $length = ($length === NULL) ? mb_strlen($str) : (int) $length;

    preg_match_all('/./us', $str, $str_array);
    preg_match_all('/./us', $replacement, $replacement_array);

    array_splice($str_array[0], $offset, $length, $replacement_array[0]);

    return implode('', $str_array[0]);
}

/**
 * Обрезает строку по заданному кол-ву символов
 *
 * @return string
 */
function crop($text, $length = 250, $etc = '')
{
    return \cms\helper\str::crop($text, $length, $etc);
}

/**
 * Выводит информацию об переменной, и при необходимости завершает работу скрипта
 *
 * @param mixed $var
 */
function dump($var, $halt = true)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';

    if ( $halt ) {
        die();
    }
}

require_once __DIR__ . '/classes/autoload.php';
require_once __DIR__ . '/legacy_classes.php';
