<?php

class p_filters extends \cms\plugin
{

    protected $version      = '1.0.0';
    protected $author       = 'DS Soft';
    protected $author_email = 'admin@ds-soft.ru';
    protected $author_url   = 'https://ds-soft.ru';
    protected $events       = [
        'run_filter'
    ];
    private $filters        = [];

    public function runFilter($text)
    {
        $this->loadFilters();

        foreach ( $this->filters as $filter ) {
            $filter($text);
        }

        return $text;
    }

    //==========================================================================

    private function loadFilters()
    {
        if ( !empty($this->filters) ) {
            return;
        }

        $filters = \cms\helper\files::getDirsList(__DIR__ . '/filters');

        foreach ( $filters as $filter ) {
            if ( file_exists(__DIR__ . '/filters/' . $filter . '/filter.php') ) {
                require_once __DIR__ . '/filters/' . $filter . '/filter.php';

                if ( function_exists($filter) ) {
                    $this->filters[] = $filter;
                }
            }
        }
    }

    //==========================================================================

    public function getConfigFormHtml()
    {
        $this->loadFilters();

        return $this->inPage->initTemplate('plugins', 'p_filters_config')->
                        assign('filters', $this->filters)->
                        assign('config', $this->config)->
                        fetch();
    }

}
