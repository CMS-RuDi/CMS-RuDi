<?php

/*
 *                           CMS RuDi v1.0.0
 *                        https://ds-soft.ru/
 *
 *                    written by DS Soft, 2015-2017
 *
 *                        LICENSED BY GNU/GPL v2
 */

abstract class tplMainClass
{

    protected static $tpl;
    protected $tpl_file;
    protected $template;
    protected $tpl_vars;

    public function __construct($tpl_file, $template)
    {
        $this->tpl_file = $tpl_file;
        $this->template = $template;

        $this->initTemplateEngine();
    }

    /**
     * Инициализирует движок шаблонизатора, должен быть переопределен в классе
     */
    protected abstract function initTemplateEngine();

    /**
     * Добавляет переменную в набор
     */
    public function assign($tpl_var, $value)
    {
        if ( !empty($tpl_var) ) {
            if ( is_array($tpl_var) ) {
                foreach ( $tpl_var as $key => $val ) {
                    if ( $key ) {
                        $this->tpl_vars[$key] = $val;
                    }
                }
            }
            else {
                if ( $tpl_var ) {
                    $this->tpl_vars[$tpl_var] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Выводит результат выполнения шаблона в браузер
     */
    public function display()
    {
        echo $this->fetch();
    }

    /**
     * Возвращает результат выполнения шаблона в виде строки
     */
    public abstract function fetch();

    public function __set($name, $value)
    {
        self::$tpl->{$name} = $value;
    }

    public function __get($name)
    {
        return self::$tpl->{$name};
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array( self::$tpl, $name ), $arguments);
    }

}
