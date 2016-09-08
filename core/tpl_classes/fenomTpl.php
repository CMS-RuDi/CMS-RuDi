<?php
/**
 * Класс инициализации шаблонизатора Fenom
 */
class fenomTpl
{
    protected static $fenom;
    
    // Оставлены для совместимости
    protected $tpl_folder;
    protected $tpl_file;
    
    protected $tpl_vars;

    public function __construct($tpl_folder = false, $tpl_file = false)
    {
        $this->loadFenom();
        
        if (!empty($tpl_folder)) {
            $this->tpl_folder = $tpl_folder;
        }
        
        if (!empty($tpl_file)) {
            $this->tpl_file = $tpl_file;
        }
    }

    protected function loadFenom()
    {
        if (!isset(self::$fenom)) {
            self::$provider = new \Fenom\MultiPathProvider(TEMPLATE_DIR);
            
            self::$provider->addPath(DEFAULT_TEMPLATE_DIR);
            
            self::$provider->addPath(PATH .'/templates');
            
            self::$fenom = \Fenom::factory(self::$provider, PATH .'/cache');
            
            self::$fenom->addFunction('csrf_token', function() {
                return \cmsUser::getCsrfToken();
            });
            
            self::$fenom->addFunction('add_js', function($params) {
                \cmsPage::getInstance()->addHeadJS($params['file']);
            });

            self::$fenom->addFunction('add_css', function($params) {
                \cmsPage::getInstance()->addHeadCSS($params['file']);
            });

            self::$fenom->addModifier('str_to_url', function($string, $is_cyr = false) {
                return \cmsCore::strToUrl($string, $is_cyr);
            });
            
            self::$fenom->addModifier('rating', function($rating, $with_icon = false) {
                if ($rating == 0)
                {
                    $html = '<span class="color_gray">0</span>';
                }
                else if ($rating > 0)
                {
                    $html = '<span class="color_green">'.($with_icon ? '<i class="fa fa-thumbs-up fa-lg"></i> ' : '') .'+'. $rating .'</span>';
                }
                else
                {
                    $html = '<span class="color_red">'. ($with_icon ? '<i class="fa fa-thumbs-down fa-lg"></i> ' : '') . $rating .'</span>';
                }
                
                return $html;
            });
            
            self::$fenom->addModifier('spellcount', function($string, $one, $two, $many, $is_full = true) {
                return \cmsCore::strToUrl($string, $one, $two, $many, $is_full);
            });
            
            self::$fenom->addModifier('NoSpam', function($email, $filterLevel = 'normal') {
                $email = strrev($email);
                
                $email = preg_replace('[\.]', '/', $email, 1);
                
                $email = preg_replace('[@]', '/', $email, 1);

                if ($filterLevel == 'low') {
                    $email = strrev($email);
                }

                return $email;
            });

            self::$fenom->addModifier('translit', function($string, $separator = false) {
                $string = preg_replace_callback('#(а|и|о|у|ы|э|ю|я|ъ|ь|\s)(е|ё)#isu', function ($matches) {
                    if ($matches[2] == 'е' || $matches[2] == 'ё')
                    {
                        return $matches[1] .'ye';
                    }
                    else
                    {
                        return $matches[1] .'Ye';
                    }
                }, ' '. $string);

                $string = str_replace(
                    array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',),
                    array('a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','"','y',"'",'e','yu','ya','A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','Kh','Ts','Ch','Sh','Shch','"','Y',"'",'E','Yu','Ya'),
                    $string
                );

                if ($separator !== false)
                {
                    $string = strtolower($string);
                    $string = preg_replace('#[^a-z0-9]+#is', $separator, $string);
                    $string = preg_replace('#['. $separator .']{2,}#is', $separator, $string);
                    $string = trim($string, $separator);
                }
                else
                {
                    $string = trim(preg_replace('#\s+#is', ' ', $string));
                }

                return $string;
            });
        }
    }
    
    //==========================================================================
    
    /**
     * Добавляет переменную в набор
     */
    public function assign($tpl_var, $value)
    {
        if (is_array($tpl_var))
        {
            foreach ($tpl_var as $key => $val) {
                if ($key) {
                    $this->tpl_vars[$key] = $val;
                }
            }
        }
        else
        {
            if ($tpl_var) {
                $this->tpl_vars[$tpl_var] = $value;
            }
        }

        return $this;
    }

    public function display($tpl, $vars = false)
    {
        $this->initVars($vars);

        return self::$fenom->display($this->getTplFile($tpl), $this->tpl_vars);
    }
    
    public function fetch($tpl, $vars = false)
    {
        $this->initVars($vars);
        
        return self::$fenom->fetch($this->getTplFile($tpl), $this->tpl_vars);
    }
    
    protected function getTplFile($tpl_file)
    {
        if (!empty($this->tpl_folder))
        {
            $tpl = $this->tpl_folder .'/'. (!empty($tpl_file)) ? $tpl_file : $this->tpl_file;
        }
        else
        {
            $tpl = $tpl_file;
        }

        return $tpl;
    }

    protected function initVars($vars)
    {
        global $_LANG;
        
        if (!empty($vars)) {
            $this->assign($vars);
        }
        
        $this->tpl_vars['LANG'] = $_LANG;
    }
    
    //==========================================================================

    public function __set($name, $value)
    {
        self::$fenom->{$name} = $value;
    }
    
    public function __get($name)
    {
        return self::$fenom->{$name};
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->fenom, $name), $arguments);
    }
}