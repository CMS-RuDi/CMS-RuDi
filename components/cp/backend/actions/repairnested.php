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

namespace components\cp\backend\actions;

class repairnested extends \cms\com_action {

    public function actView() {
        if (!\cmsUser::isAdminCan('admin/config', $this->admin_access)) {
            self::accessDenied();
        }

        $tables = $this->getTables();

        $this->processErrorTables($tables);

        foreach ($tables as $key => $table) {
            $tables[$key]['error'] = $this->checkNestedSet($table);
        }

        $this->page()->setTitle($this->lang->ad_checking_trees);
        $this->page()->addPathway($this->lang->ad_site_setting, $this->genActionUrl('config'));
        $this->page()->addPathway($this->lang->ad_checking_trees);

        $this->page()->addHeadJS('admin/js/repair.js');
        $this->page()->displayLangJS(['AD_REPAIR_CONFIRM', 'AD_REPAIR_TOTREE_CONFIRM']);

        $this->page()->initTemplate('cp/applets', 'check_repeir_ns_tables')->
                assign('tables', $tables)->
                display();
    }

    //========================================================================//

    protected function processErrorTables($tables) {
        if ($this->request()->has('tables', 'post')) {
            $post_tables = $this->request()->get('tables', 'array_str', []);

            $go_repair = $this->request()->get('go_repair', 'int');
            $go_repair_tree = $this->request()->get('go_repair_tree', 'int');

            if (!empty($post_tables)) {
                foreach ($post_tables as $key) {
                    if (isset($tables[$key])) {
                        if ($go_repair) {
                            $this->repairNestedSet($tables[$key]);
                        } else if ($go_repair_tree) {
                            if ($this->treeAllNS($tables[$key]['name']) !== false) {
                                \cmsCore::addSessionMessage($tables[$key]['title'] . ' ' . $this->lang->ad_restored, 'success');
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getTables() {
        $tables = [
            'cms_category' => ['name' => 'cms_category', 'title' => $this->lang->ad_articles_tree, 'differ' => ''],
            'cms_photo_albums' => ['name' => 'cms_photo_albums', 'title' => $this->lang->ad_albums_tree, 'differ' => ''],
            'cms_menu' => ['name' => 'cms_menu', 'title' => $this->lang->ad_menu_tree, 'differ' => '']
        ];

        if (\cms\controller::installed('board')) {
            $tables['cms_board_cats'] = [
                'name' => 'cms_board_cats',
                'title' => $this->lang->ad_desk_tree,
                'differ' => ''
            ];
        }

        if (\cms\controller::installed('forum')) {
            $tables['cms_forums'] = [
                'name' => 'cms_forums',
                'title' => $this->lang->ad_forums_tree,
                'differ' => ''
            ];
        }

        if (\cms\controller::installed('catalog')) {
            $tables['cms_uc_cats'] = [
                'name' => 'cms_uc_cats',
                'title' => $this->lang->ad_catalog_tree,
                'differ' => ''
            ];
        }

        if (\cms\controller::installed('maps')) {
            $tables['cms_map_cats'] = [
                'name' => 'cms_map_cats',
                'title' => $this->lang->ad_maps_tree,
                'differ' => ''
            ];
        }

        if (\cms\controller::installed('video')) {
            $tables['cms_video_category'] = [
                'name' => 'cms_video_category',
                'title' => $this->lang->ad_video_tree,
                'differ' => ''
            ];
        }

        if (\cms\controller::installed('shop')) {
            $tables['cms_shop_cats'] = [
                'name' => 'cms_shop_cats',
                'title' => $this->lang->ad_shop_tree,
                'differ' => ''
            ];
        }

        return $tables;
    }

    //========================================================================//

    public function repairNestedSet($table) {
        $inDB = \cms\db::getInstance();

        $differ = $table['differ'];
        $title = $table['title'];
        $table = $table['name'];

        $root_id = $inDB->getNsRootCatId($table, $differ);

        $items = $inDB->getRows($table, "NSDiffer = '" . $inDB->escape($differ) . "' AND NSLevel > 0", 'id', 'NSLeft');

        if (!$inDB->errno()) {
            $items_count = count($items);
            $max_right = ($items_count + 1) * 2;

            //fix root node
            if ($root_id) {
                $inDB->update($table, (int) $root_id, [
                    'NSLeft' => 1,
                    'NSRight' => $max_right,
                    'parent_id' => 0,
                    'NSLevel' => 0,
                    'ordering' => 1
                ]);
            }

            //fix child nodes
            $pos = 0;
            $ord = 1;

            foreach ($items as $item) {
                $level = 1;
                $left = $pos + 2;
                $right = $pos + 3;

                $inDB->update($table, (int) $item['id'], [
                    'NSLeft' => $left,
                    'NSRight' => $right,
                    'parent_id' => (int) $root_id,
                    'NSLevel' => $level,
                    'ordering' => $ord
                ]);

                $pos += 2;
                $ord++;
            }

            \cmsCore::addSessionMessage($title . ' ' . $this->lang->ad_restored, 'success');
        }
    }

    public function treeAllNS($s_table, $i_value = 1, $k_parent = 0, $lvl = 0) {
        $inDB = \cms\db::getInstance();

        if (!is_numeric($k_parent) || !is_numeric($i_value)) {
            return false;
        }

        $rows = $inDB->getRows($s_table, 'parent_id = ' . (int) $k_parent, 'id', 'NSLeft ASC, ordering ASC');

        $o = 1;

        foreach ($rows as $f) {
            $k_item = $f['id'];
            $i_right = tree_all_ns($s_table, $i_value + 1, $k_item, $lvl + 1);

            if ($i_right === false) {
                return false;
            }

            $updated = $inDB->update($s_table, (int) $k_item, [
                'NSLeft' => $i_value,
                'NSRight' => $i_right,
                'ordering' => $o++,
                'NSLevel' => $lvl
            ]);

            if (!$updated) {
                return false;
            }

            $i_value = $i_right + 1;
        }

        return $i_value;
    }

    public function checkNestedSet($table) {
        $inDB = \cms\db::getInstance();
        $differ = $table['differ'];
        $table = $table['name'];
        $errors = [];

        // имя таблицы для JOIN: builder сам добавляет префикс
        $join_table = $table;

        if ($inDB->prefix && strpos($join_table, $inDB->prefix) === 0) {
            $join_table = substr($join_table, strlen($inDB->prefix));
        }

        // step 1
        $res = $inDB->getRows($table, "NSLeft >= NSRight AND NSDiffer = '" . $inDB->escape($differ) . "'", 'id', '', true);

        if (!$inDB->errno()) {
            $errors[] = (count($res) > 0);
        } else {
            $errors[] = true;
        }

        // step 2 and 3
        $res = $inDB->getRows(
                $table,
                "NSDiffer = '" . $inDB->escape($differ) . "'",
                'COUNT(id) as rows, MIN(NSLeft) as min_left, MAX(NSRight) as max_right',
                '',
                true
        );

        if (!$inDB->errno()) {
            $data = $res ? $res[0] : ['rows' => 0, 'min_left' => null, 'max_right' => null];
            $errors[] = ($data['min_left'] != 1);
            $errors[] = ($data['max_right'] != 2 * $data['rows']);
        } else {
            $errors[] = true;
        }

        // step 4
        $res = $inDB->getRows($table, "MOD((NSRight-NSLeft), 2) = 0 AND NSDiffer = '" . $inDB->escape($differ) . "'", 'id, NSRight, NSLeft', '', true);

        if (!$inDB->errno()) {
            $errors[] = (count($res) > 0);
        } else {
            $errors[] = true;
        }

        // step 5
        $res = $inDB->getRows($table, "MOD((NSLeft-NSLevel+2), 2) = 0 AND NSDiffer = '" . $inDB->escape($differ) . "'", 'id', '', true);

        if (!$inDB->errno()) {
            $errors[] = (count($res) > 0);
        } else {
            $errors[] = true;
        }

        // step 6: для каждого узла считаем количество пересечений и сравниваем
        // с максимальным NSRight дерева (прежний MAX(t3.NSRight) был константой)
        $max_right_row = $inDB->getRows(
                $table,
                "NSDiffer = '" . $inDB->escape($differ) . "'",
                'MAX(NSRight) as max_right',
                '',
                true
        );

        if (!$inDB->errno()) {
            $max_right = $max_right_row ? $max_right_row[0]['max_right'] : null;

            $db = clone $inDB;
            $db->select('i.id');
            $db->select('COUNT(i.id)', 'rep');
            $db->joinInner(
                    $join_table,
                    't2',
                    'i.NSLeft <> t2.NSLeft AND i.NSLeft <> t2.NSRight AND i.NSRight <> t2.NSLeft AND i.NSRight <> t2.NSRight'
            );
            $db->filterEqual('i.NSDiffer', $differ);
            $db->filterEqual('t2.NSDiffer', $differ);
            $db->groupBy('i.id');

            $reps = $db->get($table, false, false);

            $has_rep_error = false;

            foreach ($reps as $rep) {
                if ($max_right != sqrt(4 * $rep['rep'] + 1) + 1) {
                    $has_rep_error = true;
                    break;
                }
            }

            $errors[] = $has_rep_error;
        } else {
            $errors[] = true;
        }

        return (in_array(true, $errors));
    }
}