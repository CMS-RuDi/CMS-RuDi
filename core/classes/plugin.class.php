<?php

/**
 * Оставлен для совместимости со старыми плагинами, для новых плагинов в качестве
 * родителя используйте \cms\plugin и ознакомьтесь с новой документацией по созданию
 * плагинов
 *
 * @package Classes
 * @subpackage Old
 */
class cmsPlugin extends \cms\plugin
{

    protected $inDB;
    public $info;

    public function __construct()
    {
        parent::__construct();

        $this->inDB = cmsDatabase::getInstance();

        $this->config = array_merge($this->config, self::getCfg($this->name));

        // Выставляем информацию о плагине

        $title = $this->getTitle();

        if ( empty($title) ) {
            $this->lang->set($this->lang_prefix . '_title', $this->info['title']);
        }

        $description = $this->getDescription();

        if ( empty($description) ) {
            $this->lang->set($this->lang_prefix . '_description', $this->info['description']);
        }

        $this->version = $this->info['version'];
        $this->author  = $this->info['author'];
    }

}
