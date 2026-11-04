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

/**
 * Оставлен для совместимости со старыми плагинами, для новых плагинов в качестве
 * родителя используйте \cms\plugin и ознакомьтесь с новой документацией по созданию
 * плагинов
 *
 * @package Classes
 * @subpackage Old
 */
class cmsPlugin extends \cms\plugin {
    protected $inDB;
    public $info;

    public function __construct() {
        parent::__construct();

        $this->inDB = cmsDatabase::getInstance();

        $this->config = array_merge($this->config, self::loadConfig($this->name));

        // Выставляем информацию о плагине

        $title = $this->getTitle();

        if (empty($title) && !empty($this->info['title'])) {
            $this->lang->set($this->name . '_title', $this->info['title']);
        }

        $description = $this->getDescription();

        if (empty($description) && !empty($this->info['description'])) {
            $this->lang->set($this->name . '_description', $this->info['description']);
        }

        if (!empty($this->info['version'])) {
            $this->version = $this->info['version'];
        }

        if (!empty($this->info['author'])) {
            $this->author = $this->info['author'];
        }

        if (!empty($this->info['url'])) {
            $this->url = $this->info['url'];
        }

        if (!empty($this->info['author_url'])) {
            $this->author_url = $this->info['author_url'];
        }

        if (!empty($this->info['author_email'])) {
            $this->author_email = $this->info['author_email'];
        }
    }

    /**
     * Для старых плагинов, которые определяют getConfigFields().
     *
     * @return array|bool
     */
    public function getConfigFormFields() {
        if (method_exists($this, 'getConfigFields')) {
            return $this->getConfigFields();
        }

        return parent::getConfigFormFields();
    }

    /**
     * Старый контракт install() возвращал boolean, а не ID.
     *
     * @return bool
     */
    public function install() {
        return (bool) parent::install();
    }
}