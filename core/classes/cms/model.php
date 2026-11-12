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

class model {
    protected $name;

    /**
     * @var \cms\db;
     */
    public $db;

    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    const INNER_JOIN = 'INNER JOIN';
    const STRAIGHT_JOIN = 'STRAIGHT_JOIN';
    const LEFT_OUTER_JOIN = 'LEFT OUTER JOIN';
    const RIGHT_OUTER_JOIN = 'RIGHT OUTER JOIN';
    const NATURAL_LEFT_JOIN = 'NATURAL LEFT JOIN';
    const NATURAL_LEFT_OUTER_JOIN = 'NATURAL LEFT OUTER JOIN';
    const NATURAL_RIGHT_JOIN = 'NATURAL RIGHT JOIN';
    const NATURAL_RIGHT_OUTER_JOIN = 'NATURAL RIGHT OUTER JOIN';

    //
    protected $privacy_filter_disabled = false;
    protected $privacy_filtered = false;
    protected $approved_filter_disabled = false;
    protected $delete_filter_disabled = false;
    protected $approved_filtered = false;
    protected $available_filtered = false;
    //
    protected static $cached = [];
    private $cache_key = false;

    //
    const CATEGORY_TABLE = '';

    public function __construct() {
        // components\Название_Компонента\model;
        $part = explode('\\', get_called_class());

        if (!empty($part)) {
            if (count($part) == 1 && strpos($part[0], 'cms_model_') !== false) {
                $this->name = str_replace('cms_model_', '', $part[0]);
            } else if ($part[0] == "components") {
                $this->name = $part[1];
            }
        }

        // Отдельное состояние builder'а на модель, общее соединение с БД.
        $this->db = clone db::getInstance();
        $this->db->select = 'i.*';
    }

    public function __get($name) {
        if ($name === 'table_prefix') {
            return $this->db->prefix;
        }

        return $this->db->$name;
    }

    public function __set($name, $value) {
        $this->db->$name = $value;
    }

    public function __isset($name) {
        return isset($this->db->$name);
    }

    /**
     * Колбэки исторически получают модель, а не cms\db.
     */
    protected function modelCallback($callback) {
        if (!is_callable($callback)) {
            return $callback;
        }

        return function ($item, $db) use ($callback) {
            return call_user_func($callback, $item, $this);
        };
    }

    public function get($table_name, $item_callback = false, $key_field = 'id') {
        $result = $this->db->get($table_name, $this->modelCallback($item_callback), $key_field);

        $this->db->select = 'i.*';

        return $result;
    }

    public function getItem($table_name, $item_callback = false) {
        $result = $this->db->getItem($table_name, $this->modelCallback($item_callback));

        $this->db->select = 'i.*';

        return $result;
    }

    public function getItemById($table_name, $id, $item_callback = false) {
        $result = $this->db->getItemById($table_name, $id, $this->modelCallback($item_callback));

        $this->db->select = 'i.*';

        return $result;
    }

    public function getItemByField($table_name, $field_name, $field_value, $item_callback = false) {
        $result = $this->db->getItemByField(
                $table_name, $field_name, $field_value, $this->modelCallback($item_callback)
        );

        $this->db->select = 'i.*';

        return $result;
    }

    public function insert($table_name, $data) {
        return $this->db->insert($table_name, $data);
    }

    public function update($table_name, $id, $data, $skip_check_fields = false) {
        if (is_numeric($id)) {
            return $this->db->update($table_name, (int) $id, $data);
        }

        return $this->db->filter($id)->updateFiltered($table_name, $data);
    }

    public function delete($table_name, $id, $by_field = 'id') {
        return $this->db->delete($table_name, $id, $by_field);
    }

    /**
     * Builder-методы выполняет cms\db; модель возвращает себя для цепочек.
     */
    public function __call($name, $arguments) {
        if (!is_callable([$this->db, $name])) {
            trigger_error('Call to undefined method cms\\model::' . $name . '()', E_USER_ERROR);
        }

        $result = call_user_func_array([$this->db, $name], $arguments);

        return $result === $this->db ? $this : $result;
    }

//============================================================================//

    public function useCache($key) {
        $this->cache_key = $key;
        return $this;
    }

    protected function stopCache() {
        $this->cache_key = false;
        return $this;
    }

//============================================================================//

    public function getContentTableStruct() {
        return [
            'id' => ['type' => 'primary'],
            'title' => ['type' => 'varchar', 'size' => 100, 'fulltext' => true],
            'content' => ['type' => 'text'],
            'photo' => ['type' => 'text'],
            'slug' => ['type' => 'varchar', 'index' => true, 'size' => 100],
            'seo_keys' => ['type' => 'varchar', 'size' => 256],
            'seo_desc' => ['type' => 'varchar', 'size' => 256],
            'seo_title' => ['type' => 'varchar', 'size' => 256],
            'tags' => ['type' => 'varchar', 'size' => 1000],
            'date_pub' => ['type' => 'timestamp', 'index' => ['date_pub', 'parent_id', 'user_id'], 'composite_index' => [3, 2, 1], 'default_current' => true],
            'date_last_modified' => ['type' => 'timestamp'],
            'date_pub_end' => ['type' => 'timestamp', 'index' => true],
            'is_pub' => ['type' => 'bool', 'index' => 'date_pub', 'composite_index' => 0, 'default' => 1],
            'hits_count' => ['type' => 'int', 'default' => 0, 'unsigned' => true],
            'user_id' => ['type' => 'int', 'index' => 'user_id', 'composite_index' => 0, 'unsigned' => true],
            'parent_id' => ['type' => 'int', 'index' => 'parent_id', 'composite_index' => 0, 'unsigned' => true],
            'parent_type' => ['type' => 'varchar', 'size' => 32, 'index' => 'parent_id', 'composite_index' => 1],
            'parent_title' => ['type' => 'varchar', 'size' => 100],
            'parent_url' => ['type' => 'varchar', 'size' => 255],
            'is_parent_hidden' => ['type' => 'bool', 'index' => 'date_pub', 'composite_index' => 1],
            'category_id' => ['type' => 'int', 'index' => true, 'default' => 1, 'unsigned' => true],
            'folder_id' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'is_comments_on' => ['type' => 'bool', 'default' => 1],
            'comments' => ['type' => 'int', 'default' => 0, 'unsigned' => true],
            'rating' => ['type' => 'int', 'default' => 0],
            'is_approved' => ['type' => 'bool', 'index' => 'date_pub', 'composite_index' => 2, 'default' => 1],
            'approved_by' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'date_approved' => ['type' => 'timestamp'],
            'is_private' => ['type' => 'bool', 'default' => 0]
        ];
    }

    public function getFieldsTableStruct() {

        return [
            'id' => ['type' => 'primary'],
            'ctype_id' => ['type' => 'int', 'unsigned' => true],
            'name' => ['type' => 'varchar', 'size' => 40],
            'title' => ['type' => 'varchar', 'size' => 100],
            'hint' => ['type' => 'varchar', 'size' => 200],
            'ordering' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'fieldset' => ['type' => 'varchar', 'size' => 32],
            'type' => ['type' => 'varchar', 'size' => 16],
            'is_in_list' => ['type' => 'bool'],
            'is_in_item' => ['type' => 'bool'],
            'is_in_filter' => ['type' => 'bool'],
            'is_private' => ['type' => 'bool'],
            'is_fixed' => ['type' => 'bool'],
            'is_fixed_type' => ['type' => 'bool'],
            'is_system' => ['type' => 'bool'],
            'values' => ['type' => 'text'],
            'options' => ['type' => 'text'],
            'groups_read' => ['type' => 'text'],
            'groups_edit' => ['type' => 'text'],
            'filter_view' => ['type' => 'text']
        ];
    }

    public function getPropsTableStruct() {

        return [
            'id' => ['type' => 'primary'],
            'ctype_id' => ['type' => 'int', 'unsigned' => true],
            'title' => ['type' => 'varchar', 'size' => 100],
            'fieldset' => ['type' => 'varchar', 'size' => 32],
            'type' => ['type' => 'varchar', 'size' => 16],
            'is_in_filter' => ['type' => 'bool', 'index' => true],
            'values' => ['type' => 'text'],
            'options' => ['type' => 'text']
        ];
    }

    public function getPropsBindTableStruct() {

        return [
            'id' => ['type' => 'primary'],
            'prop_id' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'cat_id' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'ordering' => ['type' => 'int', 'index' => true, 'unsigned' => true],
        ];
    }

    public function getPropsValuesTableStruct() {

        return [
            'prop_id' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'item_id' => ['type' => 'int', 'index' => true, 'unsigned' => true],
            'value' => ['type' => 'varchar', 'size' => 255],
        ];
    }

//============================================================================//
//============================================================================//

    public function getRootCategory($ctype_name) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        return $this->db->getFields($table_name, 'parent_id=0');
    }

    public function getCategory($ctype_name, $id, $by_field = 'id') {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        $category = $this->getItemByField($table_name, $by_field, $id);

        if (!$category) {
            return false;
        }

        $category['path'] = $this->getCategoryPath($ctype_name, $category);

        if (!empty($category['allow_add'])) {
            $category['allow_add'] = self::yamlToArray($category['allow_add']);
        }

        return $category;
    }

    public function getCategoryBySLUG($ctype_name, $slug) {
        return $this->getCategory($ctype_name, $slug, 'slug');
    }

    public function getCategorySLUG($category) {
        $slug = '';

        foreach ($category['path'] as $c) {
            if ($c['id'] == 1) {
                continue;
            }

            if ($slug) {
                $slug .= '/';
            }

            $slug .= lang_slug(empty($c['slug_key']) ? str_replace('/', '', $c['title']) : $c['slug_key']);
        }

        return $slug;
    }

//============================================================================//

    public function getCategoriesTree($ctype_name, $is_show_root = true) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        if (!$is_show_root) {
            $this->filterGt('parent_id', 0);
        }

        $this->orderBy('NSLeft');

        return $this->get($table_name, function ($node, $model) {
                    if ($node['NSLevel'] == 0) {
                        $node['title'] = \cms\lang::getInstance()->root_category;
                    }

                    if (!empty($node['allow_add'])) {
                        $node['allow_add'] = self::yamlToArray($node['allow_add']);
                    }

                    return $node;
                });
    }

//============================================================================//

    public function getSubCategories($ctype_name, $parent_id = 1) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        $this->filterEqual('parent_id', $parent_id);
        $this->orderBy('NSLeft');

        return $this->get($table_name);
    }

    public function getSubCategoriesTree($ctype_name, $parent_id = 1, $level = 1) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        $parent = $this->getCategory($ctype_name, $parent_id);

        $this->filterGt('NSLeft', $parent['NSLeft'])->
                filterLt('NSRight', $parent['NSRight']);

        if ($level) {
            $this->filterLtEqual('NSLevel', $parent['NSLevel'] + $level);
        }

        $this->orderBy('NSLeft');

        return $this->get($table_name);
    }

//============================================================================//

    public function getCategoryPath($ctype_name, $category) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            $this->useCache($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            $this->useCache($this->name . '.' . $table_name);
        }

        if (!isset($category['NSLeft'])) {
            $category = $this->getCategory($ctype_name, $category['id']);
        }

        $this->filterLtEqual('NSLeft', $category['NSLeft'])->
                filterGtEqual('NSRight', $category['NSRight'])->
                filterLtEqual('NSLevel', $category['NSLevel'])->
                filterGt('NSLevel', 0)->
                orderBy('NSLeft');

        return $this->get($table_name);
    }

//============================================================================//

    public function addCategory($ctype_name, $category) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';
        } else {
            $table_name = $this::CATEGORY_TABLE;
        }

        $this->db->nestedSets->setTable($table_name);

        $category['id'] = $this->db->nestedSets->addNode($category['parent_id']);

        if (!$category['id']) {
            return false;
        }

        $category['title'] = $this->db->escape($category['title']);

        $this->update($table_name, $category['id'], $category);

        $category['path'] = $this->getCategoryPath($ctype_name, $category);

        $category['slug'] = $this->getCategorySLUG($category);

        $this->update($table_name, $category['id'], [
            'slug' => $category['slug']
        ]);

        if (empty($this::CATEGORY_TABLE)) {
            cache::getInstance()->clean($this->name . '.' . $ctype_name . '_categories');
        } else {
            cache::getInstance()->clean($this->name . '.' . $table_name);
        }

        return $category;
    }

//============================================================================//

    public function updateCategory($ctype_name, $id, $category) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            cache::getInstance()->clean($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            cache::getInstance()->clean($this->name . '.' . $table_name);
        }

        $category_old = $this->getCategory($ctype_name, $id);

        if ($category_old['parent_id'] != $category['parent_id']) {
            $this->db->nestedSets->setTable($table_name);
            $this->db->nestedSets->moveNode($id, $category['parent_id']);
        }

        $this->update($table_name, $id, $category);

        $category['id'] = $id;
        $category['path'] = $this->getCategoryPath($ctype_name, ['id' => $id]);
        $category['slug'] = $this->getCategorySLUG($category);

        $this->update($table_name, $id, [
            'slug' => $category['slug']
        ]);

        $subcats = $this->getSubCategoriesTree($ctype_name, $id, false);

        if ($subcats) {
            foreach ($subcats as $subcat) {
                $subcat['path'] = $this->getCategoryPath($ctype_name, ['id' => $subcat['id']]);
                $subcat['slug'] = $this->getCategorySLUG($subcat);
                $this->update($table_name, $subcat['id'], ['slug' => $subcat['slug']]);
            }
        }

        return $category;
    }

//============================================================================//

    public function updateCategoryTree($ctype_name, $tree, $categories_count) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            cache::getInstance()->clean($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            cache::getInstance()->clean($this->name . '.' . $table_name);
        }

        $this->updateCategoryTreeNode($ctype_name, $tree);
        $this->updateCategoryTreeNodeSlugs($ctype_name, $tree);

        $root_keys = [
            'NSLeft' => 1,
            'NSRight' => 1 + ($categories_count * 2) + 1
        ];

        $this->update($table_name, 1, $root_keys);

        return true;
    }

    public function updateCategoryTreeNode($ctype_name, $tree) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';
        } else {
            $table_name = $this::CATEGORY_TABLE;
        }

        foreach ($tree as $node) {
            $this->update($table_name, $node['key'], [
                'parent_id' => $node['parent_key'],
                'NSLeft' => $node['left'],
                'NSRight' => $node['right'],
                'NSLevel' => $node['level'],
            ]);

            if (!empty($node['children'])) {
                $this->updateCategoryTreeNode($ctype_name, $node['children']);
            }
        }

        return true;
    }

    public function updateCategoryTreeNodeSlugs($ctype_name, $tree) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';
        } else {
            $table_name = $this::CATEGORY_TABLE;
        }

        foreach ($tree as $node) {
            $path = $this->getCategoryPath($ctype_name, [
                'id' => $node['key'],
                'parent_id' => $node['parent_key'],
                'NSLeft' => $node['left'],
                'NSRight' => $node['right'],
                'NSLevel' => $node['level']
            ]);

            $slug = $this->getCategorySLUG([
                'path' => $path,
                'title' => $node['title']
            ]);

            $this->update($table_name, $node['key'], [
                'slug' => $slug
            ]);

            if (!empty($node['children'])) {
                $this->updateCategoryTreeNodeSlugs($ctype_name, $node['children']);
            }
        }

        return true;
    }

//============================================================================//

    public function deleteCategory($ctype_name, $id) {
        //
        // Эта функция должна быть переопределена и вызываться
        // из дочернего класса чтобы после нее удалять все записи
        // из категории
        //

        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';

            cache::getInstance()->clean($this->name . '.' . $ctype_name . '_categories');
        } else {
            $table_name = $this::CATEGORY_TABLE;

            cache::getInstance()->clean($this->name . '.' . $table_name);
        }

        $this->db->nestedSets->setTable($table_name);

        $this->db->nestedSets->deleteNode($id);

        return true;
    }

//============================================================================//
//============================================================================//
//============================================================================//
//============================================================================//





























    public function filterCategory($ctype_name, $category, $is_recursive = false) {
        if (empty($this::CATEGORY_TABLE)) {
            $table_name = $this->table_prefix . $ctype_name . '_cats';
        } else {
            $table_name = $this::CATEGORY_TABLE;
        }

        $bind_table_name = $table_name . '_bind';

        if (!$is_recursive) {
            $this->joinInner($bind_table_name, 'b FORCE INDEX (item_id)', 'b.item_id = i.id')->filterEqual('b.category_id', $category['id']);
        } else {
            // для корневой категории фильтрация не нужна
            if (!$category['parent_id']) {
                return $this;
            }

            /**
             * Нам нужны только уникальные значения
             * Закомментировано потому что DISTINCT дает нагрузку
             * @todo сделать анализ кол-ва записей, вложенности категорий и динамически включать или отключать
             * В общих случаях, когда дерево категорий имеет первый и второй уровень раскомментировать не нужно
             * Для малых БД, где повсеместно используется принадлежность к нескольким категориям ниже второго уровня
             * имеет смысл раскомментировать строку ниже
             */
            // $this->distinctSelect();

            $this->joinInner($bind_table_name, 'b FORCE INDEX (item_id)', 'b.item_id = i.id');
            $this->joinInner($table_name, 'c', 'c.id = b.category_id');
            $this->filterGtEqual('c.NSLeft', $category['NSLeft']);
            $this->filterLtEqual('c.NSRight', $category['NSRight']);
        }

        return $this;
    }

    public function filterCategoryId($ctype_name, $category_id, $is_recursive = false) {
        if (!$is_recursive) {
            if ($category_id) {
                return $this->filterCategory($ctype_name, ['id' => $category_id]);
            }
        } else {
            $category = $this->getCategory($ctype_name, $category_id);

            if ($category) {
                return $this->filterCategory($ctype_name, $category, true);
            }
        }

        return $this;
    }

    public function disablePrivacyFilter() {
        $this->privacy_filter_disabled = true;
        return $this;
    }

    public function enablePrivacyFilter() {
        $this->privacy_filter_disabled = false;
        return $this;
    }

    public function isEnablePrivacyFilter() {
        return $this->privacy_filter_disabled === false;
    }

    public function filterPrivacy() {
        if ($this->privacy_filtered) {
            return $this;
        }

        // Этот фильтр может применяться при подсчете числа записей
        // и при выборке самих записей
        // используем флаг чтобы фильтр не применился дважды
        $this->privacy_filtered = true;

        return $this->filterEqual('i.is_private', 0);
    }

    public function enableDeleteFilter() {
        $this->delete_filter_disabled = false;
        return $this;
    }

    public function disableDeleteFilter() {
        $this->delete_filter_disabled = true;
        return $this;
    }

    public function enableApprovedFilter() {
        $this->approved_filter_disabled = false;
        return $this;
    }

    public function disableApprovedFilter() {
        $this->approved_filter_disabled = true;
        return $this;
    }

    public function filterAvailableOnly() {
        if ($this->available_filtered) {
            return $this;
        }

        $this->available_filtered = true;

        return $this->filterIsNull('is_deleted');
    }

    public function filterDeleteOnly() {
        return $this->filterEqual('is_deleted', 1);
    }

    public function filterApprovedOnly() {
        if ($this->approved_filtered) {
            return $this;
        }

        // Этот фильтр может применяться при подсчете числа записей
        // и при выборке самих записей
        // используем флаг чтобы фильтр не применился дважды
        $this->approved_filtered = true;

        return $this->filterEqual('is_approved', 1);
    }

    public function filterHiddenParents() {
        return $this->filterIsNull('is_parent_hidden');
    }

    public function filterFriends($user_id) {
        $this->joinInner('{users}_friends', 'fr', 'fr.friend_id = i.user_id');

        $this->filterEqual('fr.user_id', intval($user_id));
        $this->filterEqual('fr.is_mutual', 1);

        return $this;
    }

    public function filterFriendsPrivateOnly($user_id) {
        // фильтр приватности при этом не нужен
        $this->privacy_filtered = true;

        return $this->filterEqual('i.is_private', 1)->filterFriends($user_id);
    }

    public function applyDatasetFilters($dataset, $ignore_sorting = false) {
        if (!empty($dataset['filters'])) {
            foreach ($dataset['filters'] as $filter) {
                if (($filter['value'] === '') && !in_array($filter['condition'], ['nn', 'ni'])) {
                    continue;
                }

                if (empty($filter['condition'])) {
                    continue;
                }

                if ($filter['value'] !== '') {
                    $filter['value'] = string_replace_user_properties($filter['value']);
                }

                switch ($filter['condition']) {
                    // общие условия
                    case 'eq': $this->filterEqual($filter['field'], $filter['value']);
                        break;
                    case 'gt': $this->filterGt($filter['field'], $filter['value']);
                        break;
                    case 'lt': $this->filterLt($filter['field'], $filter['value']);
                        break;
                    case 'ge': $this->filterGtEqual($filter['field'], $filter['value']);
                        break;
                    case 'le': $this->filterLtEqual($filter['field'], $filter['value']);
                        break;
                    case 'nn': $this->filterNotNull($filter['field']);
                        break;
                    case 'ni': $this->filterIsNull($filter['field']);
                        break;

                    // строки
                    case 'lk': $this->filterLike($filter['field'], '%' . $filter['value'] . '%');
                        break;
                    case 'lb': $this->filterLike($filter['field'], $filter['value'] . '%');
                        break;
                    case 'lf': $this->filterLike($filter['field'], '%' . $filter['value']);
                        break;

                    // даты
                    case 'dy': $this->filterDateYounger($filter['field'], $filter['value']);
                        break;
                    case 'do': $this->filterDateOlder($filter['field'], $filter['value']);
                        break;
                }
            }
        }

        if (!empty($dataset['sorting']) && !$ignore_sorting) {
            $this->orderByList($dataset['sorting']);
        }

        if (!empty($dataset['index'])) {
            $this->forceIndex($dataset['index']);
        }

        return true;
    }

    public function selectList($fields, $is_this_only = false) {
        if ($is_this_only) {
            $this->select = [];
        } elseif ($this->select === '' || $this->select === []) {
            // как в старом cms\model: базовый набор полей сохраняется
            $this->select = 'i.*';
        }

        foreach ($fields as $field => $alias) {
            if (is_int($field)) {
                $this->select($alias);
            } else {
                $this->select($field, $alias);
            }
        }

        return $this;
    }

    public function joinUser($on_field = 'user_id', $user_fields = [], $join_direction = false) {
        if (!$user_fields) {
            $user_fields = [
                'u.nickname' => 'user_nickname',
                'u.avatar' => 'user_avatar'
            ];
        }

        foreach ($user_fields as $field => $alias) {
            $this->select($field, $alias);
        }

        switch ($join_direction) {
            case 'left':
                $this->joinLeft('{users}', 'u', 'u.id = i.' . $on_field);
                break;
            case 'right':
                $this->joinRight('{users}', 'u', 'u.id = i.' . $on_field);
                break;
            default:
                $this->join('{users}', 'u', 'u.id = i.' . $on_field);
                break;
        }

        return $this;
    }

    public function joinUserLeft($on_field = 'user_id', $user_fields = []) {
        return $this->joinUser($on_field, $user_fields, 'left');
    }

    public function joinUserRight($on_field = 'user_id', $user_fields = []) {
        return $this->joinUser($on_field, $user_fields, 'right');
    }

    /**
     * Управляет индексами в запросе
     *
     * @param string $index_name Название индекса в БД
     * @param string $action FORCE | IGNORE | USE
     * @param int $for 1 - FOR JOIN, 2 - FOR ORDER BY, 3 - FOR GROUP BY
     *
     * @return self
     */
    public function orderByList($list) {
        $this->order_by = '';

        if (is_array($list)) {
            foreach ($list as $o) {
                if (strpos($o['by'], '(') !== false) {
                    continue;
                }

                $field = $o['by'];
                $direction = strtolower($o['to']) === 'desc' ? 'desc' : 'asc';

                if (strpos($field, '.') === false) {
                    $field = 'i.' . $field;
                }

                if ($this->order_by) {
                    $this->order_by .= ', ';
                }

                $this->order_by .= $field . ' ' . $direction;
            }
        }

        return $this;
    }

//============================================================================//
//============================================================================//
//============================================================================//
//============================================================================//

    /**
     * Возвращает записи из базы, применяя все наложенные ранее фильтры
     * @return array
     */
//============================================================================//
//============================================================================//

    public function getMax($table, $field, $default = 0) {
        $sql = "SELECT i." . $field . " as " . $field . "
                FROM {#}" . $table . " i
                ";

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where . PHP_EOL;
        }

        $sql .= 'ORDER BY i.' . $field . ' DESC
                 LIMIT 1';

        $result = $this->db->query($sql);

        $this->resetFilters();

        if (!$this->db->numRows($result)) {
            return $default;
        }

        $max = $this->db->fetchAssoc($result);

        $this->db->freeResult($result);

        return $max[$field];
    }

    /**
     * Возвращает максимальный порядковый номер в таблице
     * @param string $table
     * @param string $where
     * @return int
     */
    public function getMaxOrdering($table) {
        return $this->getMax($table, 'ordering');
    }

    /**
     * Возращает следующий порядковый номер в таблице для новых записей
     * @param string $table
     * @param string $where
     * @return int
     */
    public function getNextOrdering($table) {
        return $this->getMaxOrdering($table) + 1;
    }

    /**
     * Пересчитывает порядковые номера в таблице
     * @param string $table_name
     * @param string $where
     * @return bool
     */
    public function reorder($table_name) {
        $sql = "SELECT i.id as id, i.ordering as ordering
                FROM {#}" . $table_name . " i
                ";

        if ($this->where) {
            $sql .= 'WHERE ' . $this->where . PHP_EOL;
        }

        $sql .= 'ORDER BY ordering';

        $result = $this->db->query($sql);

        $this->resetFilters();

        if (!$this->db->numRows($result)) {
            $this->db->freeResult($result);
            return false;
        }

        $ordering = 0;

        while ($item = $this->db->fetchAssoc($result)) {
            $ordering += 1;
            $this->db->query('UPDATE {#}' . $table_name . ' SET ordering = ' . $ordering . ' WHERE id = ' . $item['id']);
        }

        $this->db->freeResult($result);

        return true;
    }

    /**
     * Расставляет порядковые номера для списка из ID записей
     * @param string $table_name
     * @param string $list
     * @param array $additional_fields Список дополнительных полей и их значений, которые нужно обновлять вместе с ordering
     * @return bool
     */
    public function reorderByList($table_name, $list, $additional_fields = false) {
        $ordering = 0;

        $additional_set = [];

        if (is_array($additional_fields)) {
            foreach ($additional_fields as $field => $value) {
                $value = $this->db->escape($value);
                $additional_set[] = $field . " = '" . $value . "'";
            }
        }

        if ($additional_set) {
            $additional_set = ', ' . implode(', ', $additional_set);
        } else {
            $additional_set = '';
        }

        foreach ($list as $id) {
            $ordering += 1;

            $id = $this->db->escape($id);

            $query = "UPDATE {#}" . $table_name . "
                      SET ordering = '" . $ordering . "' " . $additional_set . "
                      WHERE id = '" . $id . "'";

            $this->db->query($query);
        }

        return true;
    }

//============================================================================//

    /**
     * Применяет к модели фильтры, переданные из просмотра
     * таблицы со списком записей
     * @param array $grid
     * @param array $filter
     * @return bool
     */
    public function applyGridFilter($grid, $filter) {
        // применяем сортировку
        if (!empty($filter['order_by'])) {
            if (!empty($grid['columns'][$filter['order_by']]['order_by'])) {
                $filter['order_by'] = $grid['columns'][$filter['order_by']]['order_by'];
            }

            $this->orderBy($filter['order_by'], $filter['order_to']);
        }

        // устанавливаем страницу
        if (!empty($filter['page'])) {
            $perpage = !empty($filter['perpage']) ? intval($filter['perpage']) : $this->perpage;
            $this->limitPage(intval($filter['page']), $perpage);
        }

        //
        // проходим по каждой колонке таблицы
        // и проверяем не передан ли фильтр для нее
        //
        foreach ($grid['columns'] as $field => $column) {
            if (!empty($column['filter']) && $column['filter'] != 'none' && isset($filter[$field])) {
                if ($filter[$field] || (string) $filter[$field] === '0') {
                    if (!empty($column['filter_by'])) {
                        $filter_field = $column['filter_by'];
                    } else {
                        $filter_field = $field;
                    }

                    switch ($column['filter']) {
                        case 'exact': $this->filterEqual($filter_field, $filter[$field]);
                            break;
                        case 'like': $this->filterLike($filter_field, "%" . $filter[$field] . "%");
                            break;
                        case 'date':
                            $date = date('Y-m-d', strtotime($filter[$field]));
                            $this->filterLike($filter_field, "%" . $date . "%");
                            break;
                    }
                }
            }
        }

        return $this;
    }

//============================================================================//



    public function deleteController($id) {
        if (is_numeric($id)) {
            $field = 'id';
        } else {
            $field = 'name';
        }

        return $this->filterEqual($field, $id)->deleteFiltered('controllers');
    }

//============================================================================//

    /**
     * Сортирует элементы массива $items в виде плоского дерева
     * на основании связей через parent_id
     * @param array $items
     * @param array $result_tree
     * @param int $parent_id
     * @param int $level
     */
    public static function buildTreeRecursive($items, &$result_tree, $parent_id = 0, $level = 1) {
        $level++;

        foreach ($items as $item) {
            if ($item['parent_id'] == $parent_id) {
                $item['level'] = $level - 1;

                if (!isset($result_tree[$item['id']])) {
                    $result_tree[$item['id']] = $item;
                }
                self::buildTreeRecursive($items, $result_tree, $item['id'], $level);
            }
        }
    }

//============================================================================//

    /**
     * Преобразует массив в YAML
     * @param array $array
     * @return string
     */
    public static function arrayToYaml($input_array, $indent = 2, $word_wrap = 40) {
        if (!empty($input_array)) {
            foreach ($input_array as $key => $value) {
                $_k = str_replace(['[', ']'], '', $key); // был фатальный баг, если в ключах эти символы
                $array[$_k] = $value;
            }
        } else {
            $array = [];
        }

        return \Spyc::YAMLDump($array, $indent, $word_wrap);
    }

    /**
     * Преобразует YAML в массив
     * @param string $yaml
     * @return array
     */
    public static function yamlToArray($yaml) {
        return \Spyc::YAMLLoadString($yaml);
    }

    /**
     * Кеширует данные в пределах запроса
     * @param string $key Ключ
     * @param mixed $data Данные
     */
    public static function cacheResult($key, $data) {
        self::$cached[$key] = $data;
    }

    public static function getCachedResult($key) {
        if (isset(self::$cached[$key])) {
            return self::$cached[$key];
        }

        return null;
    }
}