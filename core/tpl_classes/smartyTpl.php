<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
/**
 * Класс инициализации шаблонизатора Smarty
 */
class smartyTpl extends tplMainClass
{
    protected function initTemplateEngine()
    {
        if (!isset(self::$tpl)) {
            self::$tpl = new \Smarty();

            self::$tpl->addTemplateDir(
                array(
                    'templates' => PATH .'/templates',
                    TEMPLATE => TEMPLATE_DIR,
                    '_default_' => DEFAULT_TEMPLATE_DIR
                )
            );

            self::$tpl->assign('is_ajax', cmsCore::isAjax());
            self::$tpl->assign('is_auth', cmsUser::getInstance()->id);
            self::$tpl->assign('user_id', cmsUser::getInstance()->id);
            self::$tpl->assign('is_admin', cmsUser::getInstance()->is_admin);
        }
    }
    
    //==========================================================================
    
    public function display()
    {
        $this->preInit();
        
        self::$tpl->fetch($this->tpl_file, null, null, null, true);
        
        $this->postInit();
    }
    
    public function fetch()
    {
        $this->preInit();
        
        $html = self::$tpl->fetch($this->tpl_file);
        
        $this->postInit();
        
        return $html;
    }
    
    /**
     * Выставляет необходимые переменные и опции в шаблонизаторе smarty
     * @global array $_LANG
     */
    protected function preInit()
    {
        global $_LANG;
        
        $this->tpl_vars['LANG']     = $_LANG;
        $this->tpl_vars['template'] = $this->template;

        self::$tpl->assign($this->tpl_vars);

        if (!file_exists(PATH .'/cache/tpl_'. $this->template)) {
            mkdir(PATH .'/cache/tpl_'. $this->template, 0777);
        }

        self::$tpl->setCompileDir(PATH .'/cache/tpl_'. $this->template);
        self::$tpl->setCacheDir(PATH .'/cache/tpl_'. $this->template);
        
        $folders = explode('/', $this->tpl_file);
        self::$tpl->compile_id = $folders[0];
        
        self::$tpl->addTemplateDir(
            array(
                $this->template .'_'. $folders[0] => PATH .'/templates/'. $this->template .'/'. $folders[0]
            )
        );
    }
    
    /**
     * Выполняется после генерации html из шаблона smarty и удаляет переменные, чтобы к ним не было доступа из других шаблонов
     */
    protected function postInit()
    {
        self::$tpl->clearAssign(array_keys($this->tpl_vars));
    }
}