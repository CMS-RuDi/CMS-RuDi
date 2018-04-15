<?php

namespace components\cp;

class model extends \cms\model
{

    /**
     * Возвращает количество материалов добавленных сегодня
     *
     * @param string $table
     * @param string $where
     *
     * @return int
     */
    public function getCountNewContent($table, $where = '')
    {
        if ( substr($table, 0, 4) == 'cms_' ) {
            $table = substr($table, 5);
        }

        if ( $where ) {
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
    public function getMenuComponents()
    {
        $components = \cms\controller::getAllComponents();

        unset($components['cp']);

        $items = [];

        foreach ( $components as $name => $component ) {
            if ( !\cms\controller::enabled($name) ) {
                continue;
            }

            if ( !file_exists(PATH . '/admin/components/' . $name . '/backend.php') && !file_exists(PATH . '/components/' . $name . '/backend.php') ) {
                continue;
            }

            $items[$name] = $component;
        }

        return $items;
    }

    public function getModuleContentById($id)
    {
        return \cms\db::getInstance()->getField('modules', 'id=' . $id . ' AND is_external = 1', 'content');
    }

    public function getModuleNameById($id)
    {
        return \cms\db::getInstance()->getField('modules', 'id=' . $id, 'name');
    }

}
