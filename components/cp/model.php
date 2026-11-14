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

namespace components\cp;

class model extends \cms\model {

    /**
     * Возвращает количество материалов добавленных сегодня
     *
     * @param string $table
     * @param string $where
     *
     * @return int
     */
    public function getCountNewContent($table, $where = '') {
        if (substr($table, 0, 4) == 'cms_') {
            $table = substr($table, 5);
        }

        if ($where) {
            $where = ' AND ' . $where;
        }

        $where = "DATE_FORMAT(pubdate, '%d-%m-%Y') = DATE_FORMAT(NOW(), '%d-%m-%Y')" . $where;

        return (int) $this->db->getRowsCount($table, $where);
    }

    /**
     * Возвращает список активных компонентов имеющих админку, для вывода в верхнем
     * меню админки
     *
     * @return array
     */
    public function getMenuComponents() {
        $components = \cms\controller::getAllComponents();

        unset($components['cp']);

        $items = [];

        foreach ($components as $name => $component) {
            if (!\cms\controller::enabled($name)) {
                continue;
            }

            if (!file_exists(PATH . '/admin/components/' . $name . '/backend.php') && !file_exists(PATH . '/components/' . $name . '/backend.php')) {
                continue;
            }

            $items[$name] = $component;
        }

        return $items;
    }

    public function getModuleContentById($id) {
        return $this->db->
                        filterEqual('id', $id)->
                        filterEqual('is_external', 1)->
                        getFieldFiltered('modules', 'content');
    }

    public function getModuleNameById($id) {
        return $this->db->getField('modules', $id, 'name');
    }

    public function getCountComponents() {
        return $this->db->getCount('components');
    }

    public function getComponents() {
        return $this->db->selectOnly('i.id')
                        ->select('i.title')
                        ->select('i.link')
                        ->select('i.version')
                        ->select('i.published')
                        ->select('i.author')
                        ->get('components');
    }
}