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

function printLangPanel($target, $target_id, $field)
{
    echo \cms\backend::getLangPanel($target, $target_id, $field);
}

function cpAccessDenied()
{
    \cms\backend::accessDenied();
}

function cpWarning($text)
{
    \cmsCore::aaddSessionMessage($text, 'error');
}

function cpWritable($file)
{ //relative path with starting "/"
    if ( is_writable(PATH . $file) ) {
        return true;
    }
    else {
        return @chmod(PATH . $file, 0777);
    }
}

function cpCheckWritable($file, $type = 'file')
{
    if ( !cpWritable($file) ) {
        if ( $type == 'file' ) {
            \cmsCore::aaddSessionMessage(\cms\lang::getInstance()->file_not_writable, 'error');
        }
        else {
            \cmsCore::aaddSessionMessage(\cms\lang::getInstance()->dir_not_writable, 'error');
        }
    }
}

/////////////////////////// PAGE GENERATION ////////////////////////////////////

function cpHead()
{
    \components\cp\frontend::prepareHead();

    \cmsPage::getInstance()->printHead();
}

function cpMenu()
{
    (new \components\cp\frontend())->generateMenu();
}

function cpToolMenu($toolmenu_list)
{
    \cms\backend::printToolMenu();
}

//////////////////////////////////////////////// PATHWAY ///////////////////////
function cpPathway($separator = '&raquo;')
{
    \cmsPage::getInstance()->printPathway($separator);
}

function cpAddPathway($title, $link = '')
{
    return \cmsPage::getInstance()->addPathway($title, $link);
}

function cpModulePositions($template)
{
    $pos = array();

    $posfile = PATH . '/templates/' . $template . '/positions.txt';

    if ( file_exists($posfile) ) {
        $file = fopen($posfile, 'r');

        while ( !feof($file) ) {
            $str = fgets($file);
            $str = str_replace("\n", '', $str);
            $str = str_replace("\r", '', $str);
            if ( !mb_strstr($str, '#') && mb_strlen($str) > 1 ) {
                $pos[] = $str;
            }
        }
        fclose($file);
        return $pos;
    }
    else {
        return false;
    }
}

function cpAddParam($query, $param, $value)
{
    $new_query = '';

    mb_parse_str($query, $params);

    $l     = 0;
    $added = false;

    foreach ( $params as $key => $val ) {
        $l ++;
        if ( $key != $param && $key != 'nofilter' ) {
            $new_query .= $key . '=' . $val;
        }
        else {
            $new_query .= $key . '=' . $value;
            $added     = true;
        }

        if ( $l < sizeof($params) ) {
            $new_query .= '&';
        }
    }

    if ( !$added ) {
        if ( mb_strlen($new_query) > 1 ) {
            $new_query .= '&' . $param . '=' . $value;
        }
        else {
            $new_query .= $param . '=' . $value;
        }
    }
    return $new_query;
}

function cpListTable($table, $_fields, $_actions, $where = '', $orderby = 'title')
{
    $action    = cmsCore::getInstance()->do;
    $component = cms\backend::getComponent();

    $event = 'admin.listtable_' . strtolower($table) . '_' . strtolower($action) . (!empty($component) ? '_' . strtolower($component) : '');

    list($table, $_fields, $_actions, $where, $orderby) = \cms\events::call($event, array( $table, $_fields, $_actions, $where, $orderby ));

    global $_LANG;
    $inDB = cmsDatabase::getInstance();

    $perpage = 60;

    $sql        = 'SELECT *';
    $is_actions = sizeof($_actions);

    foreach ( $_fields as $key => $value ) {
        if ( isset($_fields[$key]['fdate']) ) {
            $sql .= ", DATE_FORMAT(" . $_fields[$key]['field'] . ", '" . $_fields[$key]['fdate'] . "') as `" . $_fields[$key]['field'] . "`";
        }
    }

    $sql .= ' FROM ' . $table;

    if ( isset($_SESSION['filter_table']) && $_SESSION['filter_table'] != $table ) {
        unset($_SESSION['filter']);
    }

    if ( cmsCore::inRequest('nofilter') ) {
        unset($_SESSION['filter']);
        cmsCore::redirect('/' . cmsCore::getInstance()->getUri() . '?' . str_replace('&nofilter', '', $_SERVER['QUERY_STRING']));
    }

    $filter = false;

    if ( cmsCore::inRequest('filter') ) {
        $filter             = cmsCore::request('filter', 'array_str', '');
        $_SESSION['filter'] = $filter;
    }
    elseif ( isset($_SESSION['filter']) ) {
        $filter = $_SESSION['filter'];
    }

    $f = 0;

    if ( $filter ) {
        $sql .= ' WHERE 1=1';

        foreach ( $filter as $key => $value ) {
            if ( $filter[$key] && $filter[$key] != -100 ) {
                $sql .= ' AND ';
                if ( !is_numeric($filter[$key]) ) {
                    $sql .= $key . " LIKE '%" . $filter[$key] . "%'";
                }
                else {
                    $sql .= $key . " = '" . $filter[$key] . "'";
                }
                $f++;
            }
        }

        if ( !isset($_SESSION['filter']) ) {
            $_SESSION['filter'] = $filter;
        }
    }

    if ( mb_strlen($where) > 3 ) {
        if ( mb_strstr($sql, 'WHERE') ) {
            $sql .= ' AND ' . $where;
        }
        else {
            $sql .= ' WHERE ' . $where;
        }
    }

    $sort = cmsCore::request('sort', 'str', '');

    if ( $sort == false ) {
        if ( $orderby ) {
            $sort = $orderby;
        }
        else {
            foreach ( $_fields as $key => $value ) {
                if ( $_fields[$key]['field'] == 'ordering' && $sort != 'NSLeft' ) {
                    $sort = 'ordering';
                    $so   = 'asc';
                }
            }
        }
    }

    if ( $sort ) {
        $sql .= ' ORDER BY ' . $sort;
        if ( cmsCore::inRequest('so') ) {
            $sql .= ' ' . cmsCore::request('so', 'str', '');
        }
    }

    $page = cmsCore::request('page', 'int', 1);

    $total_rs = $inDB->query($sql);
    $total    = $inDB->num_rows($total_rs);

    $sql .= " LIMIT " . ($page - 1) * $perpage . ", $perpage";

    $result = $inDB->query($sql);

    $_SESSION['filter_table'] = $table;

    if ( $inDB->error() ) {
        unset($_SESSION['filter']);
        cmsCore::redirect('/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING']);
    }

    $filters = 0;
    $f_html  = '';
    //Find and render filters
    foreach ( $_fields as $key => $value ) {
        if ( isset($_fields[$key]['filter']) ) {
            $f_html .= '<td width="">' . $_fields[$key]['title'] . ': </td>';

            if ( !isset($filter[$_fields[$key]['field']]) ) {
                $initval = '';
            }
            else {
                $initval = $filter[$_fields[$key]['field']];
            }

            $f_html    .= '<td width="">';
            $inputname = 'filter[' . $_fields[$key]['field'] . ']';

            if ( !isset($_fields[$key]['filterlist']) ) {
                $f_html .= '<input name="' . $inputname . '" type="text" size="' . $_fields[$key]['filter'] . '" class="filter_input" value="' . $initval . '"/></td>';
            }
            else {
                $f_html .= cpBuildList($inputname, $_fields[$key]['filterlist'], $initval);
            }

            $f_html                  .= '</td>';
            $filters                 += 1;
            $_SERVER['QUERY_STRING'] = str_replace('filter[' . $_fields[$key]['field'] . ']=', '', $_SERVER['QUERY_STRING']);
        }
    }
    //draw filters
    if ( $filters > 0 ) {
        echo '<div class="filter">';
        echo '<form name="filterform" action="/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING'] . '" method="POST">';
        echo '<table width="250"><tr>';
        echo $f_html;
        echo '<td width="80"><input type="submit" class="filter_submit" value="' . $_LANG['AD_FILTER'] . '" /></td>';
        if ( $f > 0 ) {
            echo '<td width="80"><input type="button" onclick="window.location.href=\'/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING'] . '&nofilter\'" class="filter_submit" value="' . $_LANG['AD_ALL'] . '" /></td>';
        }
        echo '</tr></table>';
        echo '</form>';
        echo '</div>';
    }

    if ( $inDB->num_rows($result) ) {
        //DRAW LIST TABLE
        echo '<form name="selform" action="/' . cmsCore::getInstance()->getUri() . '?view=' . $GLOBALS['applet'] . '&do=saveorder" method="post">';
        echo '<table id="listTable" border="0" class="tablesorter" width="100%" cellpadding="0" cellspacing="0">';
        //TABLE HEADING
        echo '<thead>' . "\n";
        echo '<tr>' . "\n";
        echo '<th width="20" class="lt_header" align="center"><a class="lt_header_link" href="javascript:invert();" title="' . $_LANG['AD_INVERT_SELECTION'] . '">#</a></th>' . "\n";
        foreach ( $_fields as $key => $value ) {
            echo '<th width="' . $_fields[$key]['width'] . '" class="lt_header">';
            echo $_fields[$key]['title'];
            echo '</th>' . "\n";
        }
        if ( $is_actions ) {
            echo '<th width="80" class="lt_header" align="center">' . $_LANG['AD_ACTIONS'] . '</th>' . "\n";
        }
        echo '</tr>' . "\n";
        echo '</thead><tbody>' . "\n";
        //TABLE BODY
        $r    = 0;
        while ( $item = $inDB->fetch_assoc($result) ) {
            $r++;
            if ( $r % 2 ) {
                $row_class = 'lt_row1';
            }
            else {
                $row_class = 'lt_row2';
            }
            echo '<tr id="lt_row2">' . "\n";
            echo '<td class="' . $row_class . '" align="center" valign="middle"><input type="checkbox" name="item[]" value="' . $item['id'] . '" /></td>' . "\n";
            foreach ( $_fields as $key => $value ) {
                if ( isset($_fields[$key]['link']) ) {
                    $link = $_fields[$key]['link'];

                    foreach ( $item as $f => $v ) {
                        $link = str_replace('%' . $f . '%', $v, $link);
                    }

                    if ( isset($_fields[$key]['prc']) ) {
                        // функция обработки под названием $_fields[$key]['prc']
                        // какие параметры передать функции - один ключ или произвольный массив ключей
                        if ( is_array($_fields[$key]['field']) ) {
                            foreach ( $_fields[$key]['field'] as $func_field ) {
                                $in_func_array[$func_field] = $item[$func_field];
                            }
                            $data = call_user_func($_fields[$key]['prc'], $in_func_array);
                        }
                        else {
                            $data = call_user_func($_fields[$key]['prc'], $item[$_fields[$key]['field']]);
                        }
                    }
                    else {
                        $data = $item[$_fields[$key]['field']];

                        if ( isset($_fields[$key]['maxlen']) ) {
                            if ( mb_strlen($data) > $_fields[$key]['maxlen'] ) {
                                $data = mb_substr($data, 0, $_fields[$key]['maxlen']) . '...';
                            }
                        }
                    }

                    //nested sets otstup
                    if ( isset($item['NSLevel']) && ($_fields[$key]['field'] == 'title' || (is_array($_fields[$key]['field']) && in_array('title', $_fields[$key]['field']))) ) {
                        $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                        if ( $item['NSLevel'] - 1 > 0 ) {
                            $otstup .= ' &raquo; ';
                        }
                    }
                    else {
                        $otstup = '';
                    }

                    echo '<td class="' . $row_class . '" valign="middle">' . $otstup . '<a class="lt_link" href="' . $link . '">' . $data . '</a></td>' . "\n";
                }
                else {
                    if ( $_fields[$key]['field'] != 'ordering' ) {
                        if ( $_fields[$key]['field'] == 'published' ) {
                            if ( isset($_fields[$key]['do']) ) {
                                $do = $_fields[$key]['do'];
                            }
                            else {
                                $do = 'do';
                            }

                            if ( isset($_fields[$key]['do_suffix']) ) {
                                $dos = $_fields[$key]['do_suffix'];
                                $ids = 'item_id';
                            }
                            else {
                                $dos = '';
                                $ids = 'id';
                            }

                            if ( !empty($_fields[$key]['uri']) ) {
                                $qs  = str_replace([ '%id%', '%do%' ], [ $item_id, 'hide' ], $_fields[$key]['uri']);
                                $qs2 = str_replace([ '%id%', '%do%' ], [ $item_id, 'show' ], $_fields[$key]['uri']);
                            }
                            else {
                                $qs  = cpAddParam($_SERVER['QUERY_STRING'], $do, 'hide' . $dos);
                                $qs  = '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($qs, $ids, $item['id']);
                                $qs2 = cpAddParam($_SERVER['QUERY_STRING'], $do, 'show' . $dos);
                                $qs2 = '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($qs2, $ids, $item['id']);
                            }

                            if ( $item['published'] ) {
                                $qs = "pub(" . $item['id'] . ", '" . $qs . "', '" . $qs2 . "', 'off', 'on');";

                                echo '<td class="' . $row_class . '" valign="middle"><a title="' . $_LANG['HIDE'] . '" class="uittip" id="publink' . $item['id'] . '" href="javascript:' . $qs . '"><img id="pub' . $item['id'] . '" src="/admin/images/actions/on.gif" border="0"/></a></td>' . "\n";
                            }
                            else {
                                $qs = "pub(" . $item['id'] . ", '" . $qs2 . "', '" . $qs . "', 'on', 'off');";

                                echo '<td class="' . $row_class . '" valign="middle"><a title="' . $_LANG['SHOW'] . '" class="uittip" id="publink' . $item['id'] . '" href="javascript:' . $qs . '"><img id="pub' . $item['id'] . '" src="/admin/images/actions/off.gif" border="0"/></a></td>' . "\n";
                            }
                        }
                        else {
                            if ( isset($_fields[$key]['prc']) ) {
                                // функция обработки под названием $_fields[$key]['prc']
                                // какие параметры передать функции - один ключ или произвольный массив ключей
                                if ( is_array($_fields[$key]['field']) ) {
                                    foreach ( $_fields[$key]['field'] as $func_field ) {
                                        $in_func_array[$func_field] = $item[$func_field];
                                    }
                                    $data = call_user_func($_fields[$key]['prc'], $in_func_array);
                                }
                                else {
                                    $data = call_user_func($_fields[$key]['prc'], $item[$_fields[$key]['field']]);
                                }
                            }
                            else {
                                $data = $item[$_fields[$key]['field']];
                                if ( isset($_fields[$key]['maxlen']) ) {
                                    if ( mb_strlen($data) > $_fields[$key]['maxlen'] ) {
                                        $data = mb_substr($data, 0, $_fields[$key]['maxlen']) . '...';
                                    }
                                }
                            }
                            //nested sets otstup
                            if ( isset($item['NSLevel']) && ($_fields[$key]['field'] == 'title' || (is_array($_fields[$key]['field']) && in_array('title', $_fields[$key]['field']))) ) {
                                $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                                if ( $item['NSLevel'] - 1 > 0 ) {
                                    $otstup .= ' &raquo; ';
                                }
                            }
                            else {
                                $otstup = '';
                            }
                            echo '<td class="' . $row_class . '" valign="middle">' . $otstup . $data . '</td>' . "\n";
                        }
                    }
                    else {
                        if ( isset($_fields[$key]['do']) ) {
                            $do = 'do=config&id=' . (int) $_REQUEST['id'] . '&' . $_fields[$key]['do'];
                        }
                        else {
                            $do = 'do';
                        }
                        if ( isset($_fields[$key]['do_suffix']) ) {
                            $dos = $_fields[$key]['do_suffix'];
                            $ids = 'item_id';
                        }
                        else {
                            $dos = '';
                            $ids = 'id';
                        }
                        echo '<td class="' . $row_class . '" valign="middle">
									<a title="' . $_LANG['AD_DOWN'] . '" href="/' . cmsCore::getInstance()->getUri() . '?' . $do . '=move_down&co=' . $item[$_fields[$key]['field']] . '&' . $ids . '=' . $item['id'] . '"><img src="/admin/images/actions/down.gif" border="0"/></a>';
                        if ( $table != 'cms_menu' && $table != 'cms_category' ) {
                            echo '<input class="lt_input" type="text" size="4" name="ordering[]" value="' . $item['ordering'] . '" />';
                            echo '<input name="ids[]" type="hidden" value="' . $item['id'] . '" />';
                        }
                        else {
                            echo '<input class="lt_input" type="text" size="4" name="ordering[]" value="' . $item['ordering'] . '" disabled/>';
                        }
                        echo '<a title="' . $_LANG['AD_UP'] . '" href="/' . cmsCore::getInstance()->getUri() . '?' . $do . '=move_up&co=' . $item[$_fields[$key]['field']] . '&' . $ids . '=' . $item['id'] . '"><img src="/admin/images/actions/top.gif" border="0"/></a>' .
                        '</td>' . "\n";
                    }
                }
            }
            if ( $is_actions ) {
                echo '<td width="110" class="' . $row_class . '" align="right" valign="middle"><div style="padding-right:8px">';
                foreach ( $_actions as $key => $value ) {
                    if ( isset($_actions[$key]['condition']) ) {
                        $print = $_actions[$key]['condition']($item);
                    }
                    else {
                        $print = true;
                    }
                    if ( $print ) {
                        $icon  = $_actions[$key]['icon'];
                        $title = $_actions[$key]['title'];
                        $link  = $_actions[$key]['link'];

                        if ( is_array($link) ) {
                            $link = call_user_func($link, $item);
                        }
                        else {
                            foreach ( $item as $f => $v ) {
                                $link = str_replace('%' . $f . '%', $v, $link);
                            }
                        }

                        if ( !isset($_actions[$key]['confirm']) ) {
                            echo '<a href="' . $link . '" class="uittip" title="' . $title . '"><img hspace="2" src="/admin/images/actions/' . $icon . '" border="0" alt="' . $title . '"/></a>';
                        }
                        else {
                            echo '<a href="#" class="uittip" onclick="jsmsg(\'' . $_actions[$key]['confirm'] . '\', \'' . $link . '\')" title="' . $title . '"><img hspace="2" src="/admin/images/actions/' . $icon . '" border="0" alt="' . $title . '"/></a>';
                        }
                    }
                }
                echo '</div></td>' . "\n";
            }
            echo '</tr>' . "\n";
        }

        echo '</tbody></table></form>';

        echo '<script type="text/javascript">highlightTableRows("listTable","hoverRow","clickedRow");</script>';
        echo '<script type="text/javascript">activateListTable("listTable");</script>';

        $link = '?view=' . $GLOBALS['applet'];

        if ( $sort ) {
            $link .= '&sort=' . $sort;
            if ( cmsCore::inRequest('so') ) {
                $link .= '&so=' . cmsCore::request('so');
            }
        }

        echo cmsPage::getPagebar($total, $page, $perpage, '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($_SERVER['QUERY_STRING'], 'page', '%page%'));
    }
    else {
        echo '<p class="cp_message">' . $_LANG['OBJECTS_NOT_FOUND'] . '</p>';
    }
}

///////////////////////////// LIST TABLE PROCESSORS ////////////////////////////

function cpForumCatById($id)
{
    if ( \cms\controller::installed('forum') ) {
        $inDB = cmsDatabase::getInstance();

        $result = $inDB->query("SELECT title FROM cms_forum_cats WHERE id = $id");

        if ( $inDB->num_rows($result) ) {
            $cat = $inDB->fetch_assoc($result);
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
        }
    }

    return '--';
}

function cpFaqCatById($id)
{
    if ( \cms\controller::installed('faq') ) {
        $inDB = cmsDatabase::getInstance();

        $result = $inDB->query("SELECT title FROM cms_faq_cats WHERE id = $id");

        if ( $inDB->num_rows($result) ) {
            $cat = $inDB->fetch_assoc($result);
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a>';
        }
    }

    return '--';
}

function cpCatalogCatById($id)
{
    $inDB = cmsDatabase::getInstance();

    $result = $inDB->query("SELECT title, parent_id FROM cms_uc_cats WHERE id = $id");

    if ( $inDB->num_rows($result) ) {
        $cat = $inDB->fetch_assoc($result);
        if ( $cat['parent_id'] ) {
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
        }
        else {
            return $cat['title'];
        }
    }
    else {
        return '--';
    }
}

function cpBoardCatById($id)
{
    $inDB = cmsDatabase::getInstance();

    $result = $inDB->query("SELECT title FROM cms_board_cats WHERE id = $id");

    if ( $inDB->num_rows($result) ) {
        $cat = $inDB->fetch_assoc($result);
        return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
    }
    else {
        return '--';
    }
}

function cpGroupById($id)
{
    if ( isset($GLOBALS['groups'][$id]) ) {
        $title = $GLOBALS['groups'][$id];
    }
    else {
        $title                  = cmsUser::getGroupTitle($id);
        $GLOBALS['groups'][$id] = $title;
    }

    return '<a href="index.php?view=usergroups&do=edit&id=' . $id . '">' . $title . '</a>';
}

function cpCatById($id)
{
    $inDB = cmsDatabase::getInstance();

    $result = $inDB->query("SELECT title, parent_id FROM cms_category WHERE id = $id");

    if ( $inDB->num_rows($result) ) {
        $cat = $inDB->fetch_assoc($result);
        if ( $cat['parent_id'] ) {
            return '<a href="' . \cms\backend::getBackend('content')->genActionUrl('edit_category', $id) . '">' . $cat['title'] . '</a> (' . $id . ')';
        }
        else {
            return $cat['title'];
        }
    }
    else {
        return '--';
    }
}

function cpModuleById($id)
{
    return \cms\controller::getModel('cp')->getModuleContentById($id);
}

function cpModuleTitleById($id)
{
    return \cms\controller::getModel('cp')->getModuleNameById($id);
}

function cpTemplateById($template_id)
{
    global $_LANG;

    if ( $template_id ) {
        return $template_id;
    }
    else {
        return '<span style="color:silver">' . $_LANG['AD_AS_SITE'] . '</span>';
    }
}

function cpUserNick($user_id = 0)
{
    global $_LANG;

    $inDB = cmsDatabase::getInstance();

    if ( $user_id ) {
        $sql    = "SELECT nickname FROM cms_users WHERE id = $user_id";
        $result = $inDB->query($sql);

        if ( $inDB->num_rows($result) ) {
            $usr = $inDB->fetch_assoc($result);
            return $usr['nickname'];
        }
        else {
            return false;
        }
    }
    else {
        return '<em style="color:gray">' . $_LANG['AD_NOT_DEFINED'] . '</em>';
    }
}

function cpYesNo($option)
{
    global $_LANG;

    if ( $option ) {
        return '<span style="color:green">' . $_LANG['YES'] . '</span>';
    }
    else {
        return '<span style="color:red">' . $_LANG['NO'] . '</span>';
    }
}

//////////////////////////////////////////////// DATABASE //////////////////////////////////////////////////////////
function dbMoveUp($table, $id, $current_ord)
{
    $inDB        = cmsDatabase::getInstance();
    $id          = (int) $id;
    $current_ord = (int) $current_ord;
    $sql         = "UPDATE $table SET ordering = ordering + 1 WHERE ordering = ($current_ord-1) LIMIT 1";
    $inDB->query($sql);
    $sql         = "UPDATE $table SET ordering = ordering - 1 WHERE id = $id LIMIT 1";
    $inDB->query($sql);
}

function dbMoveDown($table, $id, $current_ord)
{
    $inDB        = cmsDatabase::getInstance();
    $id          = (int) $id;
    $current_ord = (int) $current_ord;
    $sql         = "UPDATE $table SET ordering = ordering - 1 WHERE ordering = ($current_ord+1) LIMIT 1";
    $inDB->query($sql);
    $sql         = "UPDATE $table SET ordering = ordering + 1 WHERE id = $id LIMIT 1";
    $inDB->query($sql);
}

function dbShow($table, $id)
{
    $inDB = cmsDatabase::getInstance();

    $id = (int) $id;

    $sql = "UPDATE $table SET published = 1 WHERE id = $id";

    $inDB->query($sql);
}

function dbShowList($table, $list)
{
    $inDB = cmsDatabase::getInstance();

    if ( is_array($list) ) {
        $sql  = "UPDATE $table SET published = 1 WHERE ";
        $item = 0;

        foreach ( $list as $key => $value ) {
            $item ++;
            $sql .= 'id = ' . (int) $value;
            if ( $item < sizeof($list) ) {
                $sql .= ' OR ';
            }
        }

        $sql .= ' LIMIT ' . sizeof($list);
        $inDB->query($sql);
    }
}

function dbHide($table, $id)
{
    $inDB = cmsDatabase::getInstance();
    $id   = (int) $id;
    $sql  = "UPDATE $table SET published = 0 WHERE id = $id";
    $inDB->query($sql);
}

function dbHideList($table, $list)
{
    $inDB = cmsDatabase::getInstance();

    if ( is_array($list) ) {
        $sql  = "UPDATE $table SET published = 0 WHERE ";
        $item = 0;
        foreach ( $list as $key => $value ) {
            $item ++;
            $sql .= 'id = ' . (int) $value;
            if ( $item < sizeof($list) ) {
                $sql .= ' OR ';
            }
        }
        $sql .= ' LIMIT ' . sizeof($list);
        $inDB->query($sql);
    }
}

function dbDelete($table, $id)
{
    $inCore = cmsCore::getInstance();
    $inDB   = cmsDatabase::getInstance();
    $id     = (int) $id;
    $sql    = "DELETE FROM $table WHERE id = $id LIMIT 1";
    $inDB->query($sql);

    if ( $table == 'cms_content' ) {
        cmsClearTags('content', $id);
        $inCore->deleteRatings('content', $id);
        $inCore->deleteComments('article', $id);
        $inDB->query("DELETE FROM cms_tags WHERE target='content' AND item_id=$id");
    }

    if ( $table == 'cms_modules' ) {
        $inDB->query("DELETE FROM cms_modules_bind WHERE module_id=$id");
    }
}

function dbDeleteList($table, $list)
{
    $inDB = cmsDatabase::getInstance();

    if ( is_array($list) ) {
        $sql  = "DELETE FROM $table WHERE ";
        $item = 0;

        foreach ( $list as $key => $value ) {
            $item ++;
            $value = (int) $value;
            $sql   .= 'id = ' . $value;

            if ( $item < sizeof($list) ) {
                $sql .= ' OR ';
            }

            if ( $table == 'cms_content' ) {
                cmsClearTags('content', $value);
                $inDB->query("DELETE FROM cms_comments WHERE target='article' AND target_id=$value");
                $inDB->query("DELETE FROM cms_ratings WHERE target='content' AND item_id=$value");
                $inDB->query("DELETE FROM cms_tags WHERE target='content' AND item_id=$value");
            }

            if ( $table == 'cms_modules' ) {
                $inDB->query("DELETE FROM cms_modules_bind WHERE module_id=$value");
            }
        }

        $sql .= ' LIMIT ' . sizeof($list);

        $inDB->query($sql);
    }
}

//============================ HTML GENERATORS ===============================//

function insertPanel()
{
    echo \cms\backend::getPanelHtml();
}

function cpBuildList($attr_name, $list, $selected_id = false)
{
    global $_LANG;

    $html = '';

    $html .= '<select name="' . $attr_name . '" id="' . $attr_name . '">' . "\n";

    $html .= '<option value="-100">-- ' . $_LANG['AD_ALL'] . ' --</option>' . "\n";

    foreach ( $list as $key => $value ) {
        if ( $selected_id == $list[$key]['id'] ) {
            $sel = 'selected';
        }
        else {
            $sel = '';
        }
        $html .= '<option value="' . $list[$key]['id'] . '" ' . $sel . '>' . $list[$key]['title'] . '</option>' . "\n";
    }

    $html .= '</select>' . "\n";

    return $html;
}

function cpGetList($listtype, $field_name = 'title')
{
    global $_LANG;
    $list = array();

    // Позиции для модулей
    if ( $listtype == 'positions' ) {
        $pos = cpModulePositions(cmsConfig::getConfig('template'));

        foreach ( $pos as $p ) {
            $list[] = array( 'title' => $p, 'id' => $p );
        }

        return $list;
    }

    // Типы меню
    if ( $listtype == 'menu' ) {
        $list[] = array( 'title' => $_LANG['AD_MAIN_MENU'], 'id' => 'mainmenu' );
        $list[] = array( 'title' => $_LANG['AD_USER_MENU'], 'id' => 'usermenu' );
        $list[] = array( 'title' => $_LANG['AD_AUTH_MENU'], 'id' => 'authmenu' );

        for ( $m = 1; $m <= 20; $m++ ) {
            $list[] = array( 'title' => "{$_LANG['AD_SUBMENU']} {$m}", 'id' => 'menu' . $m );
        }

        return $list;
    }

    //...или записи из таблицы
    $inDB   = cmsDatabase::getInstance();
    $sql    = "SELECT id, {$field_name} FROM $listtype ORDER BY {$field_name} ASC";
    $result = $inDB->query($sql);

    if ( $inDB->num_rows($result) > 0 ) {
        while ( $item = $inDB->fetch_assoc($result) ) {
            $next                 = sizeof($list);
            $list[$next]['title'] = $item[$field_name];
            $list[$next]['id']    = $item['id'];
        }
    }

    return $list;
}

function getFullAwardsList()
{
    $inDB = cmsDatabase::getInstance();

    $awards = array();

    $rs = $inDB->query("SELECT title FROM cms_user_awards GROUP BY title");

    if ( $inDB->num_rows($rs) ) {
        while ( $aw = $inDB->fetch_assoc($rs) ) {
            $awards[] = $aw;
        }
    }

    $rs = $inDB->query("SELECT title FROM cms_user_autoawards GROUP BY title");

    if ( $inDB->num_rows($rs) ) {
        while ( $aw = $inDB->fetch_assoc($rs) ) {
            if ( !in_array(array( 'title' => $aw['title'] ), $awards) ) {
                $awards[] = $aw;
            }
        }
    }

    return $awards;
}

/**
 * Рекурсивно удаляет директорию
 * @param string $directory
 * @param bool $is_clear Если TRUE, то директория будет очищена, но не удалена
 * @return bool
 */
function files_remove_directory($directory, $is_clear = false)
{
    return \cms\helper\files::removeDirectory($directory, $is_clear);
}
