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

class com_action {
    protected $controller;
    protected $params;

    /**
     * @var \cmsCore
     */
    protected $core;

    /**
     * @var \cms\lang
     */
    protected $lang;

    /**
     * Название главного действия
     * @var string
     */
    protected $main_action;

    /**
     * Название текущего действия
     * @var string
     */
    protected $action;

    public function __construct($controller, $params = []) {
        $this->controller = $controller;
        $this->params = $params;
        $this->main_action = $this->controller->current_action;

        if (method_exists($controller, 'core')) {
            $this->core = $controller->core();
        } else {
            $this->core = \cmsCore::getInstance();
        }

        $this->lang = \cms\lang::getInstance();
    }

    public function run(...$params) {
        if (empty($params) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $params[0])) {
            $action_name = isset($this->default_action) ? $this->default_action : 'view';
        } else {
            $action_name = array_shift($params);
        }

        $method = \cms\helper\str::toCamel('_', 'act_' . $action_name);

        if (!method_exists($this, $method)) {
            \cmsCore::error404();
        }

        $pcount = !empty($params) ? count($params) : 0;
        $reflection = new \ReflectionMethod($this, $method);

        $required = $reflection->getNumberOfRequiredParameters();
        $total = $reflection->getNumberOfParameters();

        if ($pcount > $total || $pcount < $required) {
            \cmsCore::error404();
        }

        $this->action = $action_name;

        if ($this->doBefore() === false) {
            return false;
        }

        $this->{$method}(...$params);

        $this->doAfter();
    }

    public function doBefore() {
        return true;
    }

    public function doAfter() {
        return true;
    }

    public function __get($name) {
        if (isset($this->controller->{$name})) {
            return $this->controller->{$name};
        } else {
            $class_name = get_class($this->controller);

            return $class_name::${$name};
        }
    }

    public function __set($name, $value) {
        $this->controller->{$name} = $value;
    }

    public function __isset($name) {
        return isset($this->controller->{$name});
    }

    public function __unset($name) {
        unset($this->controller->{$name});
    }

    public function __call($name, $arguments) {
        return $this->controller->{$name}(...$arguments);
    }
}