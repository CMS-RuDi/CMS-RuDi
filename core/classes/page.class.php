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

class cmsPage
{

    use \Singeltone;

    public $title               = '';
    public $page_head           = [];
    public $page_keys           = '';
    public $page_desc           = '';
    public $page_img            = '';
    public $page_body           = '';
    public $lang;
    protected $page_lang        = [];
    protected $pathway          = [];
    protected $is_ajax          = false;
    protected $modules;
    protected $tpl_info;
    protected $default_tpl_info = [ 'author' => 'InstantCMS Team', 'renderer' => 'smartyTpl', 'ext' => 'tpl' ];

    private function __construct()
    {
        $this->lang      = \cms\lang::getInstance();
        $this->site_cfg  = cmsConfig::getInstance();
        $this->title     = $this->homeTitle();
        $this->page_keys = $this->site_cfg->keywords;
        $this->page_desc = $this->site_cfg->metadesc;

        $this->setTplInfo();

        $this->addPathway($this->lang->path_home, '/');
    }

    /**
     * Формирует информацию о текущем шаблоне
     * для этого ищет в корне шаблона файл system.php
     * а в нем определенный массив с параметрами шаблона
     */
    private function setTplInfo()
    {
        $info_file = TEMPLATE_DIR . 'system.php';

        if ( file_exists($info_file) ) {
            include $info_file;

            if ( !empty($info) ) {
                $this->tpl_info = $info;
                return;
            }
        }

        $this->tpl_info = $this->default_tpl_info;
    }

    /**
     * Возвращает информацию о шаблоне
     * @return array
     */
    public function getCurrentTplInfo()
    {
        return $this->tpl_info;
    }

    /**
     * Производит инициализацию класса шаблонизатора и возвращает его объект
     *
     * @param string $tpl_folder подпапка в папке шаблона, где лежит файл
     * @param string $tpl_file название файла шаблона
     * @param array $vars массив переменных для передачи в шаблон, может отсутствовать
     *
     * @return tplMainClass
     */
    public static function initTemplate($tpl_folder, $tpl_file, $vars = false)
    {
        $thisObj = self::getInstance();

        // чтобы не перезаписать
        $tpl_info = $thisObj->tpl_info;

        // имя файла без расширения (для совместимости)
        $file_name = pathinfo($tpl_file, PATHINFO_FILENAME);

        // есть ли файл в текущем шаблоне
        $is_exists_tpl_file = file_exists(TEMPLATE_DIR . $tpl_folder . '/' . $file_name . '.' . $tpl_info['ext']);

        // если нет, считаем что файл лежит в дефолтном, используем оригинальное имя с расширением
        // если есть формируем полное имя файла с учетом параметров шаблона
        if ( !$is_exists_tpl_file ) {
            $tpl_info = $thisObj->default_tpl_info;

            // Если в дефолтовом шаблоне тоже нет файла выдаем ошибку
            if ( !file_exists(DEFAULT_TEMPLATE_DIR . $tpl_folder . '/' . $file_name . '.' . $tpl_info['ext']) ) {
                throw new \RuntimeException('Template file «' . $tpl_folder . '/' . $file_name . '» not found');
            }
        }

        $tpl_file = $tpl_folder . '/' . $file_name . '.' . $tpl_info['ext'];

        // загружаем шаблонизатор текущего шаблона
        if ( !class_exists($tpl_info['renderer']) ) {
            cmsCore::halt($thisObj->lang->vsprintf('template_class_notfound', $tpl_info['renderer']));
        }

        $tpl_class = new $tpl_info['renderer']($tpl_file, $is_exists_tpl_file ? TEMPLATE : '_default_');

        return $tpl_class->assign($vars);
    }

    public function setRequestIsAjax()
    {
        $this->is_ajax = true;
        return $this;
    }

    /**
     * Добавляет указанный тег в <head> страницы
     * @param string $tag
     * @return $this
     */
    public function addHead($tag)
    {
        if ( !in_array($tag, $this->page_head) ) {
            if ( $this->is_ajax ) {
                echo $tag;
            }
            else {
                $this->page_head[] = $tag;
            }
        }

        return $this;
    }

    public function prependHead($tag)
    {
        $key = array_search($tag, $this->page_head);

        if ( $key !== false ) {
            unset($this->page_head[$key]);
        }

        array_unshift($this->page_head, $tag);

        return $this;
    }

    protected function prepareSrc($src)
    {
        if ( substr($src, 0, 1) != '/' && substr($src, 0, 7) != 'http://' && substr($src, 0, 8) != 'https://' ) {
            $src = '/' . $src;
        }

        return $src;
    }

    /**
     * Добавляет тег <script> с указанным путем
     * @param string $src - Первый слеш не требуется
     * @return $this
     */
    public function addHeadJS($src)
    {
        return $this->addHead('<script type="text/javascript" src="' . $this->prepareSrc($src) . '"></script>');
    }

    public function prependHeadJS($src)
    {
        return $this->prependHead('<script type="text/javascript" src="' . $this->prepareSrc($src) . '"></script>');
    }

    /**
     * Добавляет тег <link> с указанным путем к CSS-файлу
     * @param string $src - Первый слеш не требуется
     * @return $this
     */
    public function addHeadCSS($src)
    {
        return $this->addHead('<link href="' . $this->prepareSrc($src) . '" rel="stylesheet" type="text/css" />');
    }

    public function prependHeadCSS($src)
    {
        return $this->prependHead('<link href="' . $this->prepareSrc($src) . '" rel="stylesheet" type="text/css" />');
    }

    /**
     * Возвращает заголовок главной страницы
     * @return string
     */
    public function homeTitle()
    {
        return !empty($this->site_cfg->hometitle) ? $this->site_cfg->hometitle : $this->site_cfg->sitename;
    }

    /**
     * Устанавливает заголовок страницы
     * @param string
     * @return $this
     */
    public function setTitle($title, $forcibly = false)
    {
        if ( (cmsCore::getInstance()->menuId() == 1 || empty($title)) && $forcibly !== true ) {
            return $this;
        }

        $this->title = strip_tags($title);

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Устанавливает ключевые слова страницы
     * @param string
     * @return $this
     */
    public function setKeywords($keywords)
    {
        if ( cmsCore::getInstance()->menuId() == 1 || empty($keywords) ) {
            return $this;
        }

        $this->page_keys = trim(strip_tags($keywords));

        return $this;
    }

    /**
     * Устанавливает описание страницы
     * @param string
     * @return $this
     */
    public function setDescription($text)
    {
        if ( cmsCore::getInstance()->menuId() == 1 || empty($text) ) {
            return $this;
        }

        $this->page_desc = trim(strip_tags($text));

        return $this;
    }

    /**
     * Печатает название сайта из конфига
     * @return true
     */
    public static function printSitename()
    {
        echo cmsConfig::getConfig('sitename');
    }

    /**
     * Печатает головную область страницы
     */
    public function printHead()
    {
        $this->addHeadJsLang(array( 'SEND', 'CONTINUE', 'CLOSE', 'SAVE', 'CANCEL', 'ATTENTION', 'CONFIRM', 'LOADING', 'ERROR', 'ADD', 'SELECT_CITY', 'SELECT' ));

        $this->page_head = \cms\events::call('page.print_head', $this->page_head);

        // Если есть пагинация и страница больше первой, добавляем "страница №"
        if ( $this->site_cfg->title_and_page ) {
            $page = cmsCore::request('page', 'int', 1);

            if ( $page > 1 ) {
                $this->title = $this->title . ' — ' . $this->lang->page . ' №' . $page;
            }
        }

        // Заголовок страницы
        echo '<title>', htmlspecialchars($this->title . (($this->site_cfg->title_and_sitename && !defined('VALID_CMS_ADMIN')) ? ' — ' . $this->site_cfg->sitename : '')), '</title>', "\n";

        // Ключевые слова
        echo '<meta name="keywords" content="', htmlspecialchars($this->page_keys), '" />', "\n";

        // Описание
        echo '<meta name="description" content="', htmlspecialchars($this->page_desc), '" />', "\n";

        // Изображение
        if ( $this->page_img ) {
            echo '<link rel="image_src" href="', htmlspecialchars($this->page_img), '" />', "\n";
        }

        //Оставшиеся теги
        foreach ( $this->page_head as $value ) {
            echo $value, "\n";
        }

        // LANG переменные
        echo '<script type="text/javascript">';

        foreach ( $this->page_lang as $value ) {
            echo $value;
        }

        echo '</script>', "\n";
    }

    /**
     * Выводит тело страницы (результат работы компонента)
     */
    public function printBody()
    {
        if ( cmsConfig::getConfig('slight') ) {
            $searchquery = cmsUser::sessionGet('searchquery');

            if ( $searchquery && cmsCore::getInstance()->component != 'search' ) {
                $this->page_body = preg_replace('/(' . preg_quote($searchquery) . ')/iu', '<strong class="search_match">$1</strong>', $this->page_body);
                cmsUser::sessionDel('searchquery');
            }
        }

        $this->page_body = \cms\events::call('page.print_body', $this->page_body);

        echo $this->page_body;
    }

    /**
     * Печатает глубиномер
     * @param string $separator
     */
    public function printPathway($separator = '&rarr;', $cp = false)
    {
        $inCore = cmsCore::getInstance();

        //Проверяем, на главной мы или нет
        if ( ($inCore->menuId() == 1 && !$this->site_cfg->index_pw) || !$this->site_cfg->show_pw ) {
            return false;
        }

        $count = sizeof($this->pathway);

        if ( !$this->site_cfg->last_item_pw ) {
            unset($this->pathway[$count - 1]);
            $count --;
        }
        elseif ( $this->site_cfg->last_item_pw == 2 ) {
            $this->pathway[$count - 1]['is_last'] = true;
        }

        if ( $this->pathway ) {
            $this->initTemplate(($cp ? 'cp/' : '') . 'special', 'pathway.tpl')->
                    assign('pathway', $this->pathway)->
                    assign('separator', $separator)->
                    display('pathway.tpl');
        }
    }

    /**
     * Добавляет звено к глубиномеру
     *
     * @param string $title
     * @param string $link
     *
     * @return $this
     */
    public function addPathway($title, $link = '')
    {
        // Если ссылка не указана, берем текущий URI
        if ( empty($link) ) {
            $link = htmlspecialchars($_SERVER['REQUEST_URI']);
        }

        // Проверяем, есть ли уже в глубиномере такое звено
        $already = false;

        foreach ( $this->pathway as $pathway ) {
            if ( $pathway['link'] == $link ) {
                $already = true;
            }
        }

        // Если такого звена еще нет, добавляем его
        if ( !$already ) {
            // проверяем нет ли на ссылку пункта меню, если есть, меняем заголовок
            if ( !defined('VALID_CMS_ADMIN') ) {
                $title      = ($menu_title = cmsCore::getInstance()->getLinkInMenu($link)) ? cmsUser::stringReplaceUserProperties($menu_title, true) : $title;
            }

            $this->pathway[] = [ 'title' => $title, 'link' => $link, 'is_last' => false ];
        }

        return $this;
    }

    /**
     * Возвращает массив с данными глубиномера
     *
     * @return array
     */
    public function getPathway()
    {
        return $this->pathway;
    }

    /**
     * Устанавливает глубиномер
     *
     * @param array $pathway
     */
    public function setPathway($pathway)
    {
        if ( is_array($pathway) ) {
            $this->pathway = $pathway;
        }
    }

    /**
     * Выводит на экран шаблон сайта
     * Какой именно шаблон выводить определяют константы TEMPLATE и TEMPLATE_DIR
     * Эти константы задаются в файле /core/cms.php
     */
    public function showTemplate()
    {
        // Инициализируем нужные объекты
        $inCore = cmsCore::getInstance();
        $inUser = cmsUser::getInstance();
        $inPage = $this;
        $inConf = $this->site_cfg;
        $inDB   = cmsDatabase::getInstance();

        // Формируем модули заранее
        $this->loadModulesForMenuItem();

        global $_LANG;

        if ( file_exists(TEMPLATE_DIR . 'template.php') ) {
            require(TEMPLATE_DIR . 'template.php');
            return;
        }

        cmsCore::halt($this->lang->template . ' "' . TEMPLATE . '" ' . $this->lang->not_found);
    }

    /**
     * Подключает файл из папки с шаблоном
     * Если в папке текущего шаблона такой файл не найден, ищет в дефолтном
     * @param string $file например "special/error404.html"
     * @param array $data массив значений, доступных в шаблоне
     * @return <type>
     */
    public static function includeTemplateFile($file, $data = array())
    {
        $inCore = cmsCore::getInstance();
        $inUser = cmsUser::getInstance();
        $inPage = self::getInstance();
        $inDB   = cmsDatabase::getInstance();
        $inConf = cmsConfig::getInstance();

        extract($data);
        global $_LANG;

        if ( file_exists(TEMPLATE_DIR . $file) ) {
            include(TEMPLATE_DIR . $file);
            return true;
        }

        if ( file_exists(DEFAULT_TEMPLATE_DIR . $file) ) {
            include(DEFAULT_TEMPLATE_DIR . $file);
            return true;
        }

        return false;
    }

    public static function showSiteOffPage()
    {
        if ( file_exists(TEMPLATE_DIR . '/special/siteoff.php') ) {
            self::includeTemplateFile('special/siteoff.php');
        }
        else {
            self::initTemplate('special', 'siteoff.tpl')->display();
        }
    }

    /**
     * Показывает Splash страницу
     * @return bool
     */
    public static function showSplash()
    {
        self::initTemplate('splash', 'splash.tpl')->display('splash.tpl');

        \cms\cookie::set('splash', md5('splash'), time() + 60 * 60 * 24 * 30);

        return true;
    }

    /**
     * Проверяет, нужно ли показывать сплеш-страницу (приветствие)
     * @return bool
     */
    public static function isSplash()
    {
        if ( cmsConfig::getConfig('splash') ) {
            return !\cms\cookie::get('splash');
        }
        else {
            return false;
        }
    }

    /**
     * Загружает все модули для данного пункта меню
     * @return bool
     */
    private function loadModulesForMenuItem()
    {
        if ( isset($this->modules) ) {
            return true;
        }

        $modules = array();

        $inCore = cmsCore::getInstance();
        $inDB   = cmsDatabase::getInstance();

        $is_strict = $inCore->isMenuIdStrict();

        if ( !$is_strict ) {
            $strict_sql = "AND (m.is_strict_bind = 0)";
        }
        else {
            $strict_sql = '';
        }

        $menuid = $inCore->menuId();

        $sql = "SELECT m.*, mb.position as mb_position
            FROM cms_modules m
            INNER JOIN cms_modules_bind mb ON mb.module_id = m.id AND mb.menu_id IN (" . $menuid . ", 0)
            WHERE m.published = 1 " . $strict_sql . "
            ORDER BY m.ordering ASC";

        $result = $inDB->query($sql);

        if ( !$inDB->num_rows($result) ) {
            $this->modules = $modules;
            return true;
        }

        while ( $mod = $inDB->fetch_assoc($result) ) {
            if ( !cmsCore::checkContentAccess($mod['access_list'], false) ) {
                continue;
            }

            // не показывать модуль на определенных пунктах меню
            if ( $mod['hidden_menu_ids'] ) {
                $mod['hidden_menu_ids'] = cmsCore::yamlToArray($mod['hidden_menu_ids']);

                if ( in_array($menuid, $mod['hidden_menu_ids']) ) {
                    if ( $is_strict || !$mod['is_strict_bind_hidden'] ) {
                        continue;
                    }
                }
            }

            // список модулей на позицию
            $modules[$mod['mb_position']][] = $mod;
        }

        $this->modules = $modules;

        return true;
    }

    /**
     * Возвращает кол-во модулей на позицию
     * @return int
     */
    public function countModules($position)
    {
        $this->loadModulesForMenuItem();

        if ( !isset($this->modules[$position]) ) {
            return 0;
        }

        return sizeof($this->modules[$position]);
    }

    /**
     * Формирует модуль
     * @param array $mod
     * @return html
     */
    private function renderModule($mod)
    {
        $tkey = \cms\debug::startTimer();

        $inCore = cmsCore::getInstance();

        // флаг показа модуля
        $callback = true;

        // флаг указывающий что данные из кеша
        $cache = false;

        // html код модуля
        $html = '';

        $mod['titles'] = cmsCore::yamlToArray($mod['titles']);

        // переопределяем название в зависимости от языка
        if ( !empty($mod['titles'][cmsConfig::getConfig('lang')]) ) {
            $mod['title'] = $mod['titles'][cmsConfig::getConfig('lang')];
        }

        // для php модулей загружаем файл локализации
        if ( !$mod['user'] ) {
            cmsCore::loadLanguage('modules/' . $mod['content']);
        }

        // Собственный модуль, созданный в админке
        if ( !$mod['is_external'] ) {
            $mod['body'] = \cms\events::call('run_filter', $mod['content']);
        }
        else { // Отдельный модуль
            if ( cmsCore::includeFile('modules/' . $mod['content'] . '/module.php') ) {
                // Если есть кеш, берем тело модуля из него
                if ( $mod['cache'] && cmsCore::isCached('module', $mod['id'], $mod['cachetime'], $mod['cacheint']) ) {
                    $mod['body'] = cmsCore::getCache('module', $mod['id']);
                    $callback    = true;
                    $cache       = true;
                }
                else {
                    $cfg = cmsCore::yamlToArray($mod['config']);

                    // переходный костыль для указания шаблона
                    if ( !isset($cfg['tpl']) ) {
                        $cfg['tpl'] = $mod['content'] . '.tpl';
                    }

                    $inCore->cacheModuleConfig($mod['id'], $cfg);

                    ob_start();

                    $callback = call_user_func($mod['content'], $mod, $cfg);

                    $mod['body'] = ob_get_clean();

                    if ( $mod['cache'] ) {
                        cmsCore::saveCache('module', $mod['id'], $mod['body']);
                    }
                }
            }
        }

        // выводим модуль в шаблоне если модуль вернул true
        if ( $callback ) {
            $module_tpl = file_exists(TEMPLATE_DIR . 'modules/' . $mod['template']) ? $mod['template'] : 'module.tpl';
            $cfglink    = (cmsConfig::getConfig('fastcfg') && cmsUser::getInstance()->is_admin) ? true : false;

            ob_start();

            self::initTemplate('modules', $module_tpl)->
                    assign('cfglink', $cfglink)->
                    assign('mod', $mod)->
                    display($module_tpl);

            $html = ob_get_clean();
        }

        if ( cmsConfig::getConfig('debug') ) {
            \cms\debug::setDebugInfo('modules', ($mod['is_external'] ? $mod['content'] : 'html') . PHP_EOL . $mod['title'] . ($cache ? ' (CACHE)' : '') . ' (' . $mod['mb_position'] . ')' . (empty($html) ? PHP_EOL . $this->lang->debug_module_not_displayed : ''), $tkey);
        }

        return $html;
    }

    /**
     * Выводит модули для указанной позиции и текущего пункта меню
     * @param string $position
     * @return html
     */
    public function printModules($position)
    {
        $this->loadModulesForMenuItem();

        if ( !isset($this->modules[$position]) ) {
            return;
        }

        foreach ( $this->modules[$position] as $key => $mod ) {
            if ( is_array($mod) ) {
                // формируем html модуля
                $html = $this->renderModule($mod);

                if ( !$html ) {
                    unset($this->modules[$position][$key]);
                    continue;
                }

                $this->modules[$position][$key] = $html;
            }
            else {
                $html = $mod;
            }


            echo (string) $html;
        }

        return;
    }

    /**
     * Печатает модуль по id или по названию
     * @param mixed $id
     * @return html
     */
    public function printModule($id)
    {
        if ( is_numeric($id) ) {
            $where = "id = '" . $id . "'";
        }
        else {
            $where = "MATCH(content) AGAINST ('" . $id . "' IN BOOLEAN MODE)";
        }

        $mod = cmsDatabase::getInstance()->get_fields('cms_modules', $where, '*');

        if ( !$mod ) {
            return false;
        }

        if ( !cmsCore::checkContentAccess($mod['access_list']) ) {
            return false;
        }

        // формируем html модуля
        $m = $this->renderModule($mod);

        if ( !$m ) {
            return false;
        }

        echo $m;

        return true;
    }

    /**
     * Возвращает html-код каптчи
     * @return html
     */
    public static function getCaptcha()
    {
        $captcha = \cms\events::call('captcha.get', '', 'single');

        if ( $captcha ) {
            echo $captcha;
            return;
        }

        echo \cms\lang::getInstance()->insert_captcha_error;

        return;
    }

    /**
     * Валидация каптчи
     * @param array $code
     * @return bool
     */
    public static function checkCaptchaCode()
    {
        return \cms\events::call('captcha.check', false, 'single');
    }

    /**
     * Разбивает текст на слова и делает каждое слово ссылкой, добавляя в его начало $link
     * @param string $link
     * @param string $text
     * @return html
     */
    public static function getMetaSearchLink($link, $text)
    {
        if ( !$text ) {
            return '';
        }

        $text = html_entity_decode(trim(trim(strip_tags($text)), '.'));

        foreach ( explode(',', $text) as $value ) {
            $v        = trim(str_replace(array( "\r", "\n" ), '', $value));
            $worlds[] = '<a href="' . $link . urlencode($v) . '">' . $v . '</a>';
        }

        return implode(', ', $worlds);
    }

    /**
     * Возвращает html-код панели для вставки BBCode
     * @param string $field_id
     * @param bool $images
     * @param string $placekind
     * @return html
     */
    public static function getBBCodeToolbar($field_id, $images = 0, $component = 'forum', $target = 'post', $target_id = 0)
    {
        // Поддержка плагинов панели ббкодов (ее замены)
        $p_toolbar = \cms\events::call('bbcode.replace_buttons', [
                    'html'      => '',
                    'field_id'  => $field_id,
                    'images'    => $images,
                    'component' => $component,
                    'target'    => $target,
                    'target_id' => $target_id ]);

        if ( $p_toolbar['html'] ) {
            return \cms\events::call('bbcode.get_button', $p_toolbar['html']);
        }

        $inPage = self::getInstance();

        $inPage->addHeadJS('core/js/smiles.js');

        if ( $images ) {
            $inPage->addHeadJS('includes/jquery/upload/ajaxfileupload.js');
        }

        ob_start();

        self::includeTemplateFile('special/bbcode_panel.php', array( 'field_id'  => $field_id,
            'images'    => $images,
            'component' => $component,
            'target'    => $target,
            'target_id' => $target_id ));

        return \cms\events::call('bbcode.get_button', ob_get_clean());
    }

    /**
     * Возвращает html-код панели со смайлами
     * @param string $for_field_id
     * @return html
     */
    public static function getSmilesPanel($for_field_id)
    {
        $p_html = \cms\events::call('smiles.replace', array( 'html' => '', 'for_field_id' => $for_field_id ));

        if ( $p_html['html'] ) {
            return $p_html['html'];
        }

        $html = '<div class="usr_msg_smilebox" id="smilespanel-' . $for_field_id . '" style="display:none">';

        if ( $handle = opendir(PATH . '/images/smilies') ) {
            while ( false !== ($file = readdir($handle)) ) {
                if ( $file != '.' && $file != '..' && mb_strstr($file, '.gif') ) {
                    $tag = str_replace('.gif', '', $file);
                    $dir = '/images/smilies/';

                    $html .= '<a href="javascript:addSmile(\'' . $tag . '\', \'' . $for_field_id . '\');"><img src="' . $dir . $file . '" border="0" /></a> ';
                }
            }

            closedir($handle);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Подключает JS и CSS для автокомплита с использованием скрипта Select2
     */
    public function initNewAutocomplete()
    {
        $this->addHeadJS('includes/jquery/select2/select2.full.min.js');
        $this->addHeadJS('includes/jquery/select2/i18n/' . $this->lang->getLang() . '.js');
        $this->addHeadCSS('includes/jquery/select2/select2.min.css');
        return $this;
    }

    /**
     * Подключает JS и CSS для автокомплита
     */
    public function initAutocomplete()
    {
        $this->addHeadJS('includes/jquery/autocomplete/jquery.autocomplete.min.js');
        $this->addHeadCSS('includes/jquery/autocomplete/jquery.autocomplete.css');
        return $this;
    }

    /**
     * Возвращает JS-код инициализации автокомплита для указанного поля ввода и скрипта
     *
     * @param string $script
     * @param string $field_id
     *
     * @return js
     */
    public function getAutocompleteJS($script, $field_id = 'tags')
    {
        return '$("#' . $field_id . '").autocomplete({
                    url: "/core/ajax/' . $script . '.php",
                    useDelimiter: true,
                    queryParamName: "q",
                    lineSeparator: "\n",
                    cellSeparator: "|",
                    minChars: 2,
                    maxItemsToShow: 10,
                    delay: 400
                }
            );';
    }

    /**
     * Возвращает код панели для постраничной навигации
     * @param int $total
     * @param int $page
     * @param int $perpage
     * @param string $link
     * @param array $params
     * @return html
     */
    public static function getPagebar($total, $page, $perpage, $link, $params = array())
    {
        $pagebar = \cms\events::call('page.get_pagebar', array( $total, $page, $perpage, $link, $params ));

        if ( !is_array($pagebar) && $pagebar ) {
            return $pagebar;
        }

        $lang = \cms\lang::getInstance();

        $html = '<div class="pagebar">';
        $html .= '<span class="pagebar_title"><strong>' . $lang->pages . ': </strong></span>';

        $total_pages = ceil($total / $perpage);

        if ( $total_pages < 2 ) {
            return;
        }

        //configure for the starting links per page
        $max = 3;

        //used in the loop
        $max_links = $max + 1;
        $h         = 1;

        //if page is above max link
        if ( $page > $max_links ) {
            //start of loop
            $h = (($h + $page) - $max_links);
        }

        //if page is not page one
        if ( $page >= 1 ) {
            //top of the loop extends
            $max_links = $max_links + ($page - 1);
        }

        //if the top page is visible then reset the top of the loop to the $total_pages
        if ( $max_links > $total_pages ) {
            $max_links = $total_pages + 1;
        }

        //next and prev buttons
        if ( $page > 1 ) {
            $href = $link;

            if ( is_array($params) ) {
                foreach ( $params as $param => $value ) {
                    $href = str_replace('%' . $param . '%', $value, $href);
                }
            }

            $html .= ' <a href="' . str_replace('%page%', 1, $href) . '" class="pagebar_page">' . $lang->first . '</a> ';
            $html .= ' <a href="' . str_replace('%page%', ($page - 1), $href) . '" class="pagebar_page">' . $lang->previous . '</a> ';
        }

        //create the page links
        for ( $i = $h; $i < $max_links; $i++ ) {
            if ( $i == $page ) {
                $html .= '<span class="pagebar_current">' . $i . '</span>';
            }
            else {
                $href = $link;

                if ( is_array($params) ) {
                    foreach ( $params as $param => $value ) {
                        $href = str_replace('%' . $param . '%', $value, $href);
                    }
                }

                $href = str_replace('%page%', $i, $href);
                $html .= ' <a href="' . $href . '" class="pagebar_page">' . $i . '</a> ';
            }
        }

        //Next and last buttons
        if ( ($page >= 1) && ($page != $total_pages) ) {
            $href = $link;

            if ( is_array($params) ) {
                foreach ( $params as $param => $value ) {
                    $href = str_replace('%' . $param . '%', $value, $href);
                }
            }

            $html .= ' <a href="' . str_replace('%page%', ($page + 1), $href) . '" class="pagebar_page">' . $lang->next . '</a> ';
            $html .= ' <a href="' . str_replace('%page%', $total_pages, $href) . '" class="pagebar_page">' . $lang->last . '</a> ';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Возвращает строку js с определенной языковой переменной
     * @param string $key ключ нужной ячейки массива $_LANG
     * @return string
     */
    public static function getLangJS($key)
    {
        global $_LANG;

        if ( !isset($_LANG[$key]) ) {
            return;
        }

        $value = htmlspecialchars($_LANG[$key]);

        return "var LANG_" . $key . " = '" . $value . "'; ";
    }

    /**
     * Печатает строки js с языковыми переменными
     * @param array $keys массив ключей нужных ячеек массива $_LANG
     */
    public static function displayLangJS($keys)
    {
        if ( !is_array($keys) ) {
            return;
        }

        echo '<script type="text/javascript">';

        foreach ( $keys as $key ) {
            echo self::getLangJS($key);
        }

        echo '</script>';

        return;
    }

    public function addHeadJsLang($key)
    {
        if ( is_array($key) ) {
            array_map(array( $this, __FUNCTION__ ), $key);
        }
        else {
            $this->page_lang[$key] = self::getLangJS($key);
        }
        return $this;
    }

}
