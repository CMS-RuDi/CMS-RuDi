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

    public function __construct($controller, $params = []) {
        $this->controller = $controller;
        $this->params = $params;

        if (method_exists($controller, 'core')) {
            $this->core = $controller->core();
        } else {
            $this->core = \cmsCore::getInstance();
        }

        $this->lang = \cms\lang::getInstance();
    }

    public function run(...$params) {
        if (empty($params)) {
            $action_name = isset($this->default_action) ? $this->default_action : 'view';
        } else {
            $action_name = array_shift($params);
        }

        if ($this->doBefore($action_name) === false) {
            return false;
        }

        $method = \cms\helper\str::toCamel('_', 'act_' . $action_name);

        if (!method_exists($this, $method)) {
            \cmsCore::error404();
        }

        $this->{$method}(...$params);

        $this->doAfter($action_name);
    }

    public function doBefore($action_name) {
        return true;
    }

    public function doAfter($action_name) {
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