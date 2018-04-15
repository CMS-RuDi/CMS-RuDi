<?php

namespace components\cp\actions;

class repairnested extends \cms\com_action
{

    public function actView()
    {
        if ( !\cmsUser::isAdminCan('admin/config', $this->admin_access) ) {
            self::accessDenied();
        }

        $tables = $this->getTables();

        $this->processErrorTables($tables);

        foreach ( $tables as $key => $table ) {
            $tables[$key]['error'] = $this->checkNestedSet($table);
        }

        $this->page()->setTitle($this->lang->ad_checking_trees);
        $this->page()->addPathway($this->lang->ad_site_setting, $this->genActionUrl('config'));
        $this->page()->addPathway($this->lang->ad_checking_trees);

        $this->page()->addHeadJS('admin/js/repair.js');
        $this->page()->displayLangJS([ 'AD_REPAIR_CONFIRM', 'AD_REPAIR_TOTREE_CONFIRM' ]);

        $this->page()->initTemplate('cp/applets', 'check_repeir_ns_tables')->
                assign('tables', $tables)->
                display();
    }

    //========================================================================//

    protected function processErrorTables($tables)
    {
        if ( $this->request()->has('tables', 'post') ) {
            $post_tables = $this->request()->get('tables', 'array_str', []);

            $go_repair      = $this->request()->get('go_repair', 'int');
            $go_repair_tree = $this->request()->get('go_repair_tree', 'int');

            if ( !empty($post_tables) ) {
                foreach ( $post_tables as $key ) {
                    if ( isset($tables[$key]) ) {
                        if ( $go_repair ) {
                            $this->repairNestedSet($tables[$key]);
                        }
                        else if ( $go_repair_tree ) {
                            if ( $this->treeAllNS($tables[$key]['name']) !== false ) {
                                \cmsCore::addSessionMessage($tables[$key]['title'] . ' ' . $this->lang->ad_restored, 'success');
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getTables()
    {
        $tables = [
            'cms_category'     => [ 'name' => 'cms_category', 'title' => $this->lang->ad_articles_tree, 'differ' => '' ],
            'cms_photo_albums' => [ 'name' => 'cms_photo_albums', 'title' => $this->lang->ad_albums_tree, 'differ' => '' ],
            'cms_board_cats'   => [ 'name' => 'cms_board_cats', 'title' => $this->lang->ad_desk_tree, 'differ' => '' ],
            'cms_uc_cats'      => [ 'name' => 'cms_uc_cats', 'title' => $this->lang->ad_catalog_tree, 'differ' => '' ],
            'cms_menu'         => [ 'name' => 'cms_menu', 'title' => $this->lang->ad_menu_tree, 'differ' => '' ],
            'cms_forums'       => [ 'name' => 'cms_forums', 'title' => $this->lang->ad_forums_tree, 'differ' => '' ],
        ];

        if ( \cms\controller::installed('maps') ) {
            $tables['cms_map_cats'] = [
                'name'   => 'cms_map_cats',
                'title'  => $this->lang->ad_maps_tree,
                'differ' => ''
            ];
        }

        if ( \cms\controller::installed('video') ) {
            $tables['cms_video_category'] = [
                'name'   => 'cms_video_category',
                'title'  => $this->lang->ad_video_tree,
                'differ' => ''
            ];
        }

        if ( \cms\controller::installed('shop') ) {
            $tables['cms_shop_cats'] = [
                'name'   => 'cms_shop_cats',
                'title'  => $this->lang->ad_shop_tree,
                'differ' => ''
            ];
        }

        return $tables;
    }

    //========================================================================//

    public function repairNestedSet($table)
    {
        $inDB = \cmsDatabase::getInstance();

        $differ = $table['differ'];
        $title  = $table['title'];
        $table  = $table['name'];

        $root_id = $inDB->getNsRootCatId($table, $differ);

        $res = $inDB->query("SELECT id FROM " . $table . " WHERE NSDiffer = '" . $differ . "' AND NSLevel > 0 ORDER BY NSLeft");

        if ( !$inDB->errno() ) {
            $items_count = $inDB->num_rows($res);
            $max_right   = ($items_count + 1) * 2;

            //fix root node
            $sql = "UPDATE " . $table . "
				SET NSLeft = 1,
					NSRight = " . $max_right . ",
					parent_id = 0,
					NSLevel = 0,
					ordering = 1
				WHERE id = " . $root_id;
            $inDB->query($sql);

            //fix child nodes
            $pos = 0;
            $ord = 1;

            while ( $item = $inDB->fetch_assoc($res) ) {
                $level = 1;
                $left  = $pos + 2;
                $right = $pos + 3;

                $sql = "UPDATE " . $table . "
                            SET NSLeft=" . $left . ",
                                    NSRight=" . $right . ",
                                    parent_id = " . $root_id . ",
                                    NSLevel = " . $level . ",
                                    ordering = " . $ord . "
                            WHERE id=" . $item['id'];

                $inDB->query($sql);

                $pos += 2;
                $ord++;
            }

            \cmsCore::addSessionMessage($title . ' ' . $this->lang->ad_restored, 'success');
        }
    }

    public function treeAllNS($s_table, $i_value = 1, $k_parent = 0, $lvl = 0)
    {
        $inDB = \cmsDatabase::getInstance();

        if ( !is_numeric($k_parent) || !is_numeric($i_value) ) {
            return false;
        }

        $r = $inDB->query("SELECT id FROM " . $s_table . " WHERE parent_id='" . $k_parent . "' ORDER BY NSLeft ASC, ordering ASC");

        if ( !$r ) {
            return false;
        }

        $o = 1;

        while ( $f = $inDB->fetch_assoc($r) ) {
            $k_item  = $f['id'];
            $i_right = tree_all_ns($s_table, $i_value + 1, $k_item, $lvl + 1);

            if ( $i_right === false ) {
                return false;
            }

            if ( !$inDB->query("UPDATE " . $s_table . " SET NSLeft='" . $i_value . "', NSRight='" . $i_right . "', ordering = '" . $o++ . "', NSLevel = '" . $lvl . "' where id='" . $k_item . "'") ) {
                return false;
            }

            $i_value = $i_right + 1;
        }

        return $i_value;
    }

    public function checkNestedSet($table)
    {
        $inDB   = \cmsDatabase::getInstance();
        $differ = $table['differ'];
        $table  = $table['name'];
        $errors = [];

        // step 1
        $sql = "SELECT id FROM " . $table . " WHERE NSLeft >= NSRight AND NSDiffer = '" . $differ . "'";
        $res = $inDB->query($sql);

        if ( !$inDB->errno() ) {
            $errors[] = ($inDB->num_rows($res) > 0);
        }
        else {
            $errors[] = true;
        }

        // step 2 and 3
        $sql = "SELECT COUNT(id) as rows, MIN(NSLeft) as min_left, MAX(NSRight) as max_right FROM " . $table . " WHERE NSDiffer = '" . $differ . "'";
        $res = $inDB->query($sql);

        if ( !$inDB->errno() ) {
            $data     = $inDB->fetch_assoc($res);
            $errors[] = ($data['min_left'] != 1);
            $errors[] = ($data['max_right'] != 2 * $data['rows']);
        }
        else {
            $errors[] = true;
        }

        // step 4
        $sql = "SELECT id, NSRight, NSLeft FROM " . $table . " WHERE MOD((NSRight-NSLeft), 2) = 0 AND NSDiffer = '" . $differ . "'";

        $res = $inDB->query($sql);

        if ( !$inDB->errno() ) {
            $errors[] = ($inDB->num_rows($res) > 0);
        }
        else {
            $errors[] = true;
        }

        // step 5
        $sql = "SELECT id FROM " . $table . " WHERE MOD((NSLeft-NSLevel+2), 2) = 0 AND NSDiffer = '" . $differ . "'";

        $res = $inDB->query($sql);

        if ( !$inDB->errno() ) {
            $errors[] = ($inDB->num_rows($res) > 0);
        }
        else {
            $errors[] = true;
        }

        // step 6
        $sql = "SELECT 	t1.id, COUNT(t1.id) AS rep, MAX(t3.NSRight) AS max_right
				FROM " . $table . " AS t1, " . $table . " AS t2, " . $table . " AS t3
				WHERE t1.NSLeft <> t2.NSLeft AND t1.NSLeft <> t2.NSRight AND t1.NSRight <> t2.NSLeft AND t1.NSRight <> t2.NSRight
				AND t1.NSDiffer = '" . $differ . "' AND t2.NSDiffer = '" . $differ . "' AND t3.NSDiffer = '" . $differ . "'
				GROUP BY t1.id
				HAVING max_right <> SQRT(4 * rep + 1) + 1";

        $res = $inDB->query($sql);

        if ( !$inDB->errno() ) {
            $errors[] = ($inDB->num_rows($res) > 0);
        }
        else {
            $errors[] = true;
        }

        return (in_array(true, $errors));
    }

}
