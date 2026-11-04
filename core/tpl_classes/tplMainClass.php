<?php

/*
 *                        CMS RuDi v1.10.9
 *                      https://cmsrudi.ru/
 *
 *              Written by CMS RuDi Team, 2026
 *           produced by DagIT, (https://dagit.su)
 *
 *                    LICENSED BY GNU/GPL v2
 */

abstract class tplMainClass {
    protected static $tpl;
    protected $tpl_file;
    protected $template;
    protected $tpl_vars;

    public function __construct($tpl_file, $template) {
        $this->tpl_file = $tpl_file;
        $this->template = $template;

        $this->initTemplateEngine();

        \cms\lang::loadTemplateLang($this->template);
    }

    /**
     * Инициализирует движок шаблонизатора, должен быть переопределен в классе
     */
    protected abstract function initTemplateEngine();

    /**
     * Добавляет переменную в набор
     */
    public function assign($tpl_var, $value = false) {
        if (!empty($tpl_var)) {
            if (is_array($tpl_var)) {
                foreach ($tpl_var as $key => $val) {
                    $this->tpl_vars[$key] = $val;
                }
            } else {
                $this->tpl_vars[$tpl_var] = $value;
            }
        }

        return $this;
    }

    /**
     * Выводит результат выполнения шаблона в браузер
     */
    public function display() {
        echo $this->fetch();
    }

    /**
     * Возвращает результат выполнения шаблона в виде строки
     */
    public abstract function fetch();

    public function __set($name, $value) {
        static::$tpl->{$name} = $value;
    }

    public function __get($name) {
        return static::$tpl->{$name};
    }

    public function __call($name, $arguments) {
        return static::$tpl->{$name}(...$arguments);
    }
}