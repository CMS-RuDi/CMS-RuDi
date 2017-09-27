<?php

/*
 *                           CMS RuDi v1.0.0
 *                        https://ds-soft.ru/
 *
 *                    written by DS Soft, 2015-2017
 *
 *                        LICENSED BY GNU/GPL v2
 */

/**
 * Класс инициализации шаблонизатора Fenom
 */
class fenomTpl extends tplMainClass
{

    protected static $provider;

    protected function initTemplateEngine()
    {
        if ( !isset(self::$tpl) ) {
            self::$provider = new \Fenom\MultiPathProvider(TEMPLATE_DIR);

            self::$provider->addPath(DEFAULT_TEMPLATE_DIR);

            self::$provider->addPath(PATH . '/templates');

            self::$provider->setClearCachedStats(true);

            self::$tpl = \Fenom::factory(self::$provider, PATH . '/cache');

            self::$tpl->addFunction('csrf_token', function() {
                return \cmsUser::getCsrfToken();
            });

            self::$tpl->addFunction('add_js', function($params) {
                \cmsPage::getInstance()->addHeadJS($params['file']);
            });

            self::$tpl->addFunction('add_css', function($params) {
                \cmsPage::getInstance()->addHeadCSS($params['file']);
            });

            self::$tpl->addModifier('str_to_url', function($string, $is_cyr = false) {
                return \cmsCore::strToUrl($string, $is_cyr);
            });

            self::$tpl->addModifier('rating', function($rating, $with_icon = false) {
                if ( $rating == 0 ) {
                    $html = '<span class="color_gray">0</span>';
                }
                else if ( $rating > 0 ) {
                    $html = '<span class="color_green">' . ($with_icon ? '<i class="fa fa-thumbs-up fa-lg"></i> ' : '') . '+' . $rating . '</span>';
                }
                else {
                    $html = '<span class="color_red">' . ($with_icon ? '<i class="fa fa-thumbs-down fa-lg"></i> ' : '') . $rating . '</span>';
                }

                return $html;
            });

            self::$tpl->addModifier('spellcount', function($string, $one, $two, $many, $is_full = true) {
                return \cmsCore::strToUrl($string, $one, $two, $many, $is_full);
            });

            self::$tpl->addModifier('NoSpam', function($email, $filterLevel = 'normal') {
                $email = strrev($email);

                $email = preg_replace('[\.]', '/', $email, 1);

                $email = preg_replace('[@]', '/', $email, 1);

                if ( $filterLevel == 'low' ) {
                    $email = strrev($email);
                }

                return $email;
            });

            self::$tpl->addModifier('translit', function($string, $separator = false) {
                $string = preg_replace_callback('#(а|и|о|у|ы|э|ю|я|ъ|ь|\s)(е|ё)#isu', function ($matches) {
                    if ( $matches[2] == 'е' || $matches[2] == 'ё' ) {
                        return $matches[1] . 'ye';
                    }
                    else {
                        return $matches[1] . 'Ye';
                    }
                }, ' ' . $string);

                $string = str_replace(
                        array( 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', ), array( 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'ch', 'sh', 'shch', '"', 'y', "'", 'e', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'Kh', 'Ts', 'Ch', 'Sh', 'Shch', '"', 'Y', "'", 'E', 'Yu', 'Ya' ), $string
                );

                if ( $separator !== false ) {
                    $string = strtolower($string);
                    $string = preg_replace('#[^a-z0-9]+#is', $separator, $string);
                    $string = preg_replace('#[' . $separator . ']{2,}#is', $separator, $string);
                    $string = trim($string, $separator);
                }
                else {
                    $string = trim(preg_replace('#\s+#is', ' ', $string));
                }

                return $string;
            });
        }
    }

    public function display()
    {
        $this->preInit();

        return self::$tpl->display($this->tpl_file, $this->tpl_vars);
    }

    public function fetch()
    {
        $this->preInit();

        return self::$tpl->fetch($this->tpl_file, $this->tpl_vars);
    }

    protected function preInit()
    {
        global $_LANG;

        $this->tpl_vars['LANG']     = $_LANG;
        $this->tpl_vars['template'] = $this->template;
        $this->tpl_vars['is_auth']  = cmsUser::getInstance()->id;
        $this->tpl_vars['user_id']  = cmsUser::getInstance()->id;
        $this->tpl_vars['is_admin'] = cmsUser::getInstance()->is_admin;
        $this->tpl_vars['is_ajax']  = cmsCore::isAjax();

        $folders = explode('/', $this->tpl_file);

        self::$provider->addPath(PATH . '/templates/' . $this->template . '/' . $folders[0]);

        if ( !file_exists(PATH . '/cache/tpl_' . $this->template) ) {
            mkdir(PATH . '/cache/tpl_' . $this->template, 0777);
        }

        self::$tpl->setCompileDir(PATH . '/cache/tpl_' . $this->template);
    }

}
