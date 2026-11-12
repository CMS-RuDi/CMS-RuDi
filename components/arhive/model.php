<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cms_model_arhive
{

    public function __construct()
    {
        $this->config = cmsCore::getInstance()->loadComponentConfig('arhive');
        $this->inDB   = \cms\db::getInstance();
        cmsCore::loadLanguage('components/arhive');
        $this->year   = cmsCore::request('y', 'int', 'all');
        $this->month  = sprintf("%02d", cmsCore::request('m', 'int', 'all'));
        $this->day    = sprintf("%02d", cmsCore::request('d', 'int', 'all'));
        $this->setSqlParams();
    }

    public static function getDefaultConfig()
    {
        return array(
            'source' => 'arhive'
        );
    }

    private function setSqlParams()
    {
        if ( $this->config['source'] != 'both' ) {
            $this->inDB->filterEqual('i.is_arhive', $this->config['source'] == 'arhive' ? 1 : 0);
        }

        $now = date('Y-m-d H:i:s');

        $this->inDB->filter("i.published = 1 AND i.pubdate <= '" . $this->inDB->escape($now) . "'");
        $this->inDB->groupBy("DATE_FORMAT(i.pubdate, '%M, %Y')");
        $this->inDB->orderBy('MIN(i.pubdate)', 'DESC');
        $this->inDB->select("DATE_FORMAT(MIN(i.pubdate), '%Y') as year, DATE_FORMAT(MIN(i.pubdate), '%m') as month, COUNT(i.id) as num");
    }

    public function whereYearIs()
    {
        if ( is_numeric($this->year) ) {
            $this->inDB->filter("DATE_FORMAT(i.pubdate, '%Y') LIKE '" . (int) $this->year . "'");
        }
    }

    public function whereMonthIs()
    {
        if ( is_numeric($this->year) && is_numeric($this->month) ) {
            $date_str = $this->year . '-' . $this->month;
            $this->inDB->filter("DATE_FORMAT(i.pubdate, '%Y-%m') LIKE '" . $this->inDB->escape($date_str) . "'");
        }
    }

    public function whereDayIs()
    {
        if ( is_numeric($this->day) && is_numeric($this->year) && is_numeric($this->month) ) {
            $date_str = $this->year . '-' . $this->month . '-' . $this->day;
            $this->inDB->filter("DATE_FORMAT(i.pubdate, '%Y-%m-%d') LIKE '" . $this->inDB->escape($date_str) . "'");
        }
    }

    public function whereThisAndNestedCats($cat_id)
    {
        if ( !$cat_id ) {
            return false;
        }

        $rootcat = $this->inDB->getItemById('category', (int) $cat_id);

        if ( !$rootcat ) {
            return false;
        }

        $this->inDB->joinInner('category', 'cat', 'cat.id = i.category_id');
        $this->inDB->filter(
            'cat.NSLeft >= ' . (int) $rootcat['NSLeft'] .
            ' AND cat.NSRight <= ' . (int) $rootcat['NSRight'] .
            ' AND cat.parent_id > 0'
        );

        return true;
    }

    public function setArtticleSql()
    {
        $this->inDB->selectOnly('i.*');
        $this->inDB->select("DATE_FORMAT(i.pubdate, '%Y') as year");
        $this->inDB->select("DATE_FORMAT(i.pubdate, '%m') as month");
        $this->inDB->select("DATE_FORMAT(i.pubdate, '%d') as day");
        $this->inDB->select('cat.title', 'cat_title');
        $this->inDB->select('cat.showdesc');
        $this->inDB->select('cat.seolink', 'cat_seolink');
        $this->inDB->group_by = '';
        $this->inDB->joinInner('category', 'cat', 'cat.id = i.category_id');
    }

    public function getArhiveContent()
    {
        $content_model = new cms_model_content();

        $content = $this->inDB->get('content', function ($item) use ($content_model) {
            if ( !isset($item['seolink']) ) {
                $item['fmonth'] = cmsCore::intMonthToStr($item['month']);
            } else {
                $item['url']          = $content_model->getArticleURL(0, $item['seolink']);
                $item['category_url'] = $content_model->getCategoryURL(0, $item['cat_seolink']);
                $item['fpubdate']     = cmsCore::dateFormat($item['pubdate']);
            }

            $item['image'] = file_exists(PATH . '/images/photos/small/article' . $item['id'] . '.jpg')
                ? 'article' . $item['id'] . '.jpg'
                : '';

            return $item;
        });

        if ( !$content ) {
            return array();
        }

        return \cms\events::call(
            'arhive.get_items',
            translations::process(cmsConfig::getConfig('lang'), 'content_content', $content)
        );
    }

}
