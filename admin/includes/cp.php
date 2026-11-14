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

function printLangPanel($target, $target_id, $field) {
    echo \cms\backend::getLangPanel($target, $target_id, $field);
}

function cpAccessDenied() {
    \cms\backend::accessDenied();
}

function cpWarning($text) {
    \cmsCore::addSessionMessage($text, 'error');
}

function cpWritable($file) { //relative path with starting "/"
    if (is_writable(PATH . $file)) {
        return true;
    } else {
        return @chmod(PATH . $file, 0777);
    }
}

function cpCheckWritable($file, $type = 'file') {
    if (!cpWritable($file)) {
        if ($type == 'file') {
            \cmsCore::addSessionMessage(\cms\lang::getInstance()->file_not_writable, 'error');
        } else {
            \cmsCore::addSessionMessage(\cms\lang::getInstance()->dir_not_writable, 'error');
        }
    }
}

/////////////////////////// PAGE GENERATION ////////////////////////////////////

function cpHead() {
    \components\cp\frontend::prepareHead();

    \cmsPage::getInstance()->printHead();
}

function cpMenu() {
    (new \components\cp\frontend())->generateMenu();
}

function cpToolMenu($toolmenu_list) {
    // Совместимость с InstantCMS 1.10.x: старые бэкенды передают свой список
    if (is_array($toolmenu_list)) {
        foreach ($toolmenu_list as $item) {
            if (empty($item) || empty($item['title'])) {
                continue;
            }

            \cms\backend::addToolMenuItem(
                    $item['title'],
                    isset($item['link']) ? $item['link'] : '',
                    isset($item['icon']) ? $item['icon'] : '',
                    isset($item['target']) ? $item['target'] : false
            );
        }
    }

    \cms\backend::printToolMenu();
}

//////////////////////////////////////////////// PATHWAY ///////////////////////
function cpPathway($separator = '&raquo;') {
    \cmsPage::getInstance()->printPathway($separator);
}

function cpAddPathway($title, $link = '') {
    return \cmsPage::getInstance()->addPathway($title, $link);
}

function cpModulePositions($template) {
    $pos = array();

    $posfile = PATH . '/templates/' . $template . '/positions.txt';

    if (file_exists($posfile)) {
        $file = fopen($posfile, 'r');

        while (!feof($file)) {
            $str = fgets($file);
            $str = str_replace("\n", '', $str);
            $str = str_replace("\r", '', $str);
            if (!mb_strstr($str, '#') && mb_strlen($str) > 1) {
                $pos[] = $str;
            }
        }
        fclose($file);
        return $pos;
    } else {
        return false;
    }
}

function cpAddParam($query, $param, $value) {
    $new_query = '';

    mb_parse_str($query, $params);

    $l = 0;
    $added = false;

    foreach ($params as $key => $val) {
        $l++;
        if ($key != $param && $key != 'nofilter') {
            $new_query .= $key . '=' . $val;
        } else {
            $new_query .= $key . '=' . $value;
            $added = true;
        }

        if ($l < sizeof($params)) {
            $new_query .= '&';
        }
    }

    if (!$added) {
        if (mb_strlen($new_query) > 1) {
            $new_query .= '&' . $param . '=' . $value;
        } else {
            $new_query .= $param . '=' . $value;
        }
    }
    return $new_query;
}

function cpListTable($table, $_fields, $_actions, $where = '', $orderby = 'title') {
    $action = cmsCore::getInstance()->do;
    $component = cms\backend::getComponent();

    $event = 'admin.listtable_' . strtolower($table) . '_' . strtolower($action) . (!empty($component) ? '_' . strtolower($component) : '');

    list($table, $_fields, $_actions, $where, $orderby) = \cms\events::call($event, array($table, $_fields, $_actions, $where, $orderby));

    global $_LANG;

    $db = \cms\db::getInstance();

    $perpage = 60;

    $is_actions = sizeof($_actions);

    // Поля выборки: * плюс отформатированные даты
    $db->resetFilters();
    $db->selectOnly('*');

    foreach ($_fields as $key => $value) {
        if (isset($_fields[$key]['fdate'])) {
            $db->select("DATE_FORMAT(" . $_fields[$key]['field'] . ", '" . $_fields[$key]['fdate'] . "')", $_fields[$key]['field']);
        }
    }

    if (isset($_SESSION['filter_table']) && $_SESSION['filter_table'] != $table) {
        unset($_SESSION['filter']);
    }

    if (cmsCore::inRequest('nofilter')) {
        unset($_SESSION['filter']);
        cmsCore::redirect('/' . cmsCore::getInstance()->getUri() . '?' . str_replace('&nofilter', '', $_SERVER['QUERY_STRING']));
    }

    $filter = false;

    if (cmsCore::inRequest('filter')) {
        $filter = cmsCore::request('filter', 'array_str', '');
        $_SESSION['filter'] = $filter;
    } elseif (isset($_SESSION['filter'])) {
        $filter = $_SESSION['filter'];
    }

    $f = 0;

    if ($filter) {
        foreach ($filter as $key => $value) {
            if (!$value || $value == '-100') {
                continue;
            }

            // фильтровать можно только по именам полей таблицы
            if (!preg_match('/^[a-zA-Z0-9_\.]+$/', (string) $key)) {
                continue;
            }

            if (!is_numeric($value)) {
                $db->filterLike($key, '%' . $value . '%');
            } else {
                $db->filterEqual($key, $value);
            }

            $f++;
        }

        if (!isset($_SESSION['filter'])) {
            $_SESSION['filter'] = $filter;
        }
    }

    if (mb_strlen($where) > 3) {
        $db->filter($where);
    }

    $sort = cmsCore::request('sort', 'str', '');

    if ($sort == false) {
        if ($orderby) {
            $sort = $orderby;
        } else {
            foreach ($_fields as $key => $value) {
                if ($_fields[$key]['field'] == 'ordering' && $sort != 'NSLeft') {
                    $sort = 'ordering';
                    $so = 'asc';
                }
            }
        }
    }

    $order = '';

    if ($sort) {
        // допускаем только список полей с необязательным направлением сортировки
        if (preg_match('/^[a-zA-Z0-9_\.]+(?:\s+(?:asc|desc))?(?:\s*,\s*[a-zA-Z0-9_\.]+(?:\s+(?:asc|desc))?)*$/i', trim($sort))) {
            $order = trim($sort);

            if (cmsCore::inRequest('so')) {
                $so = strtolower(trim(cmsCore::request('so', 'str', '')));

                if (($so === 'asc' || $so === 'desc') && !preg_match('/\s+(asc|desc)$/i', $order)) {
                    $order .= ' ' . $so;
                }
            }
        }
    }

    $page = cmsCore::request('page', 'int', 1);

    $db_table = cpDbTableName($table);

    $total = $db->getCount($db_table, 'id');

    $db->limitPage($page, $perpage);

    if ($order) {
        $db->order_by = $order;
    }

    $items = $db->get($db_table);

    $_SESSION['filter_table'] = $table;

    if ($db->error()) {
        unset($_SESSION['filter']);
        cmsCore::redirect('/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING']);
    }

    $filters = 0;
    $f_html = '';
    //Find and render filters
    foreach ($_fields as $key => $value) {
        if (isset($_fields[$key]['filter'])) {
            $f_html .= '<td width="">' . $_fields[$key]['title'] . ': </td>';

            if (!isset($filter[$_fields[$key]['field']])) {
                $initval = '';
            } else {
                $initval = $filter[$_fields[$key]['field']];
            }

            $f_html .= '<td width="">';
            $inputname = 'filter[' . $_fields[$key]['field'] . ']';

            if (!isset($_fields[$key]['filterlist'])) {
                $f_html .= '<input name="' . $inputname . '" type="text" size="' . $_fields[$key]['filter'] . '" class="filter_input" value="' . $initval . '"/></td>';
            } else {
                $f_html .= cpBuildList($inputname, $_fields[$key]['filterlist'], $initval);
            }

            $f_html .= '</td>';
            $filters += 1;
            $_SERVER['QUERY_STRING'] = str_replace('filter[' . $_fields[$key]['field'] . ']=', '', $_SERVER['QUERY_STRING']);
        }
    }
    //draw filters
    if ($filters > 0) {
        echo '<div class="filter">';
        echo '<form name="filterform" action="/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING'] . '" method="POST">';
        echo '<table width="250"><tr>';
        echo $f_html;
        echo '<td width="80"><input type="submit" class="filter_submit" value="' . $_LANG['AD_FILTER'] . '" /></td>';
        if ($f > 0) {
            echo '<td width="80"><input type="button" onclick="window.location.href=\'/' . cmsCore::getInstance()->getUri() . '?' . $_SERVER['QUERY_STRING'] . '&nofilter\'" class="filter_submit" value="' . $_LANG['AD_ALL'] . '" /></td>';
        }
        echo '</tr></table>';
        echo '</form>';
        echo '</div>';
    }

    if ($items) {
        //DRAW LIST TABLE
        echo '<form name="selform" action="/' . cmsCore::getInstance()->getUri() . '?view=' . $GLOBALS['applet'] . '&do=saveorder" method="post">';
        echo '<table id="listTable" border="0" class="tablesorter" width="100%" cellpadding="0" cellspacing="0">';
        //TABLE HEADING
        echo '<thead>' . "\n";
        echo '<tr>' . "\n";
        echo '<th width="20" class="lt_header" align="center"><a class="lt_header_link" href="javascript:invert();" title="' . $_LANG['AD_INVERT_SELECTION'] . '">#</a></th>' . "\n";
        foreach ($_fields as $key => $value) {
            echo '<th width="' . $_fields[$key]['width'] . '" class="lt_header">';
            echo $_fields[$key]['title'];
            echo '</th>' . "\n";
        }
        if ($is_actions) {
            echo '<th width="80" class="lt_header" align="center">' . $_LANG['AD_ACTIONS'] . '</th>' . "\n";
        }
        echo '</tr>' . "\n";
        echo '</thead><tbody>' . "\n";
        //TABLE BODY
        $r = 0;
        foreach ($items as $item) {
            $r++;
            if ($r % 2) {
                $row_class = 'lt_row1';
            } else {
                $row_class = 'lt_row2';
            }
            echo '<tr id="lt_row2">' . "\n";
            echo '<td class="' . $row_class . '" align="center" valign="middle"><input type="checkbox" name="item[]" value="' . $item['id'] . '" /></td>' . "\n";
            foreach ($_fields as $key => $value) {
                if (isset($_fields[$key]['link'])) {
                    $link = $_fields[$key]['link'];

                    foreach ($item as $f => $v) {
                        $link = str_replace('%' . $f . '%', $v, $link);
                    }

                    if (isset($_fields[$key]['prc'])) {
                        // функция обработки под названием $_fields[$key]['prc']
                        // какие параметры передать функции - один ключ или произвольный массив ключей
                        if (is_array($_fields[$key]['field'])) {
                            foreach ($_fields[$key]['field'] as $func_field) {
                                $in_func_array[$func_field] = $item[$func_field];
                            }
                            $data = call_user_func($_fields[$key]['prc'], $in_func_array);
                        } else {
                            $data = call_user_func($_fields[$key]['prc'], $item[$_fields[$key]['field']]);
                        }
                    } else {
                        $data = $item[$_fields[$key]['field']];

                        if (isset($_fields[$key]['maxlen'])) {
                            if (mb_strlen($data) > $_fields[$key]['maxlen']) {
                                $data = mb_substr($data, 0, $_fields[$key]['maxlen']) . '...';
                            }
                        }
                    }

                    //nested sets otstup
                    if (isset($item['NSLevel']) && ($_fields[$key]['field'] == 'title' || (is_array($_fields[$key]['field']) && in_array('title', $_fields[$key]['field'])))) {
                        $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                        if ($item['NSLevel'] - 1 > 0) {
                            $otstup .= ' &raquo; ';
                        }
                    } else {
                        $otstup = '';
                    }

                    echo '<td class="' . $row_class . '" valign="middle">' . $otstup . '<a class="lt_link" href="' . $link . '">' . $data . '</a></td>' . "\n";
                } else {
                    if ($_fields[$key]['field'] != 'ordering') {
                        if ($_fields[$key]['field'] == 'published') {
                            if (isset($_fields[$key]['do'])) {
                                $do = $_fields[$key]['do'];
                            } else {
                                $do = 'do';
                            }

                            if (isset($_fields[$key]['do_suffix'])) {
                                $dos = $_fields[$key]['do_suffix'];
                                $ids = 'item_id';
                            } else {
                                $dos = '';
                                $ids = 'id';
                            }

                            if (!empty($_fields[$key]['uri'])) {
                                $qs = str_replace(['%id%', '%do%'], [$item_id, 'hide'], $_fields[$key]['uri']);
                                $qs2 = str_replace(['%id%', '%do%'], [$item_id, 'show'], $_fields[$key]['uri']);
                            } else {
                                $qs = cpAddParam($_SERVER['QUERY_STRING'], $do, 'hide' . $dos);
                                $qs = '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($qs, $ids, $item['id']);
                                $qs2 = cpAddParam($_SERVER['QUERY_STRING'], $do, 'show' . $dos);
                                $qs2 = '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($qs2, $ids, $item['id']);
                            }

                            if ($item['published']) {
                                $qs = "pub(" . $item['id'] . ", '" . $qs . "', '" . $qs2 . "', 'off', 'on');";

                                echo '<td class="' . $row_class . '" valign="middle"><a title="' . $_LANG['HIDE'] . '" class="uittip" id="publink' . $item['id'] . '" href="javascript:' . $qs . '"><img id="pub' . $item['id'] . '" src="/admin/images/actions/on.gif" border="0"/></a></td>' . "\n";
                            } else {
                                $qs = "pub(" . $item['id'] . ", '" . $qs2 . "', '" . $qs . "', 'on', 'off');";

                                echo '<td class="' . $row_class . '" valign="middle"><a title="' . $_LANG['SHOW'] . '" class="uittip" id="publink' . $item['id'] . '" href="javascript:' . $qs . '"><img id="pub' . $item['id'] . '" src="/admin/images/actions/off.gif" border="0"/></a></td>' . "\n";
                            }
                        } else {
                            if (isset($_fields[$key]['prc'])) {
                                // функция обработки под названием $_fields[$key]['prc']
                                // какие параметры передать функции - один ключ или произвольный массив ключей
                                if (is_array($_fields[$key]['field'])) {
                                    foreach ($_fields[$key]['field'] as $func_field) {
                                        $in_func_array[$func_field] = $item[$func_field];
                                    }
                                    $data = call_user_func($_fields[$key]['prc'], $in_func_array);
                                } else {
                                    $data = call_user_func($_fields[$key]['prc'], $item[$_fields[$key]['field']]);
                                }
                            } else {
                                $data = $item[$_fields[$key]['field']];
                                if (isset($_fields[$key]['maxlen'])) {
                                    if (mb_strlen($data) > $_fields[$key]['maxlen']) {
                                        $data = mb_substr($data, 0, $_fields[$key]['maxlen']) . '...';
                                    }
                                }
                            }
                            //nested sets otstup
                            if (isset($item['NSLevel']) && ($_fields[$key]['field'] == 'title' || (is_array($_fields[$key]['field']) && in_array('title', $_fields[$key]['field'])))) {
                                $otstup = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($item['NSLevel'] - 1));
                                if ($item['NSLevel'] - 1 > 0) {
                                    $otstup .= ' &raquo; ';
                                }
                            } else {
                                $otstup = '';
                            }
                            echo '<td class="' . $row_class . '" valign="middle">' . $otstup . $data . '</td>' . "\n";
                        }
                    } else {
                        if (isset($_fields[$key]['do'])) {
                            $do = 'do=config&id=' . (int) $_REQUEST['id'] . '&' . $_fields[$key]['do'];
                        } else {
                            $do = 'do';
                        }
                        if (isset($_fields[$key]['do_suffix'])) {
                            $dos = $_fields[$key]['do_suffix'];
                            $ids = 'item_id';
                        } else {
                            $dos = '';
                            $ids = 'id';
                        }
                        echo '<td class="' . $row_class . '" valign="middle">
									<a title="' . $_LANG['AD_DOWN'] . '" href="/' . cmsCore::getInstance()->getUri() . '?' . $do . '=move_down&co=' . $item[$_fields[$key]['field']] . '&' . $ids . '=' . $item['id'] . '"><img src="/admin/images/actions/down.gif" border="0"/></a>';
                        if ($table != 'cms_menu' && $table != 'cms_category') {
                            echo '<input class="lt_input" type="text" size="4" name="ordering[]" value="' . $item['ordering'] . '" />';
                            echo '<input name="ids[]" type="hidden" value="' . $item['id'] . '" />';
                        } else {
                            echo '<input class="lt_input" type="text" size="4" name="ordering[]" value="' . $item['ordering'] . '" disabled/>';
                        }
                        echo '<a title="' . $_LANG['AD_UP'] . '" href="/' . cmsCore::getInstance()->getUri() . '?' . $do . '=move_up&co=' . $item[$_fields[$key]['field']] . '&' . $ids . '=' . $item['id'] . '"><img src="/admin/images/actions/top.gif" border="0"/></a>' .
                        '</td>' . "\n";
                    }
                }
            }
            if ($is_actions) {
                echo '<td width="110" class="' . $row_class . '" align="right" valign="middle"><div style="padding-right:8px">';
                foreach ($_actions as $key => $value) {
                    if (isset($_actions[$key]['condition'])) {
                        $print = $_actions[$key]['condition']($item);
                    } else {
                        $print = true;
                    }
                    if ($print) {
                        $icon = $_actions[$key]['icon'];
                        $title = $_actions[$key]['title'];
                        $link = $_actions[$key]['link'];

                        if (is_array($link)) {
                            $link = call_user_func($link, $item);
                        } else {
                            foreach ($item as $f => $v) {
                                $link = str_replace('%' . $f . '%', $v, $link);
                            }
                        }

                        if (!isset($_actions[$key]['confirm'])) {
                            echo '<a href="' . $link . '" class="uittip" title="' . $title . '"><img hspace="2" src="/admin/images/actions/' . $icon . '" border="0" alt="' . $title . '"/></a>';
                        } else {
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

        if ($sort) {
            $link .= '&sort=' . $sort;
            if (cmsCore::inRequest('so')) {
                $link .= '&so=' . cmsCore::request('so');
            }
        }

        echo cmsPage::getPagebar($total, $page, $perpage, '/' . cmsCore::getInstance()->getUri() . '?' . cpAddParam($_SERVER['QUERY_STRING'], 'page', '%page%'));
    } else {
        echo '<p class="cp_message">' . $_LANG['OBJECTS_NOT_FOUND'] . '</p>';
    }
}

///////////////////////////// LIST TABLE PROCESSORS ////////////////////////////

function cpForumCatById($id) {
    if (\cms\controller::installed('forum')) {
        $cat = \cms\db::getInstance()->getItemById('forum_cats', (int) $id);

        if ($cat) {
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
        }
    }

    return '--';
}

function cpFaqCatById($id) {
    if (\cms\controller::installed('faq')) {
        $cat = \cms\db::getInstance()->getItemById('faq_cats', (int) $id);

        if ($cat) {
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a>';
        }
    }

    return '--';
}

function cpCatalogCatById($id) {
    if (\cms\controller::installed('catalog')) {
        $cat = \cms\db::getInstance()->getItemById('uc_cats', (int) $id);

        if ($cat) {
            if ($cat['parent_id']) {
                return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
            } else {
                return $cat['title'];
            }
        }
    }

    return '--';
}

function cpBoardCatById($id) {
    if (\cms\controller::installed('board')) {
        $cat = \cms\db::getInstance()->getItemById('board_cats', (int) $id);

        if ($cat) {
            return '<a href="index.php?view=components&do=config&id=' . (int) $_REQUEST['id'] . '&opt=edit_cat&item_id=' . $id . '">' . $cat['title'] . '</a> (' . $id . ')';
        }
    }

    return '--';
}

function cpGroupById($id) {
    if (isset($GLOBALS['groups'][$id])) {
        $title = $GLOBALS['groups'][$id];
    } else {
        $title = cmsUser::getGroupTitle($id);
        $GLOBALS['groups'][$id] = $title;
    }

    return '<a href="index.php?view=usergroups&do=edit&id=' . $id . '">' . $title . '</a>';
}

function cpCatById($id) {
    $cat = \cms\db::getInstance()->getItemById('category', (int) $id);

    if ($cat) {
        if ($cat['parent_id']) {
            return '<a href="' . \cms\backend::getBackend('content')->genActionUrl('edit_category', $id) . '">' . $cat['title'] . '</a> (' . $id . ')';
        } else {
            return $cat['title'];
        }
    } else {
        return '--';
    }
}

function cpModuleById($id) {
    return \cms\controller::getModel('cp')->getModuleContentById($id);
}

function cpModuleTitleById($id) {
    return \cms\controller::getModel('cp')->getModuleNameById($id);
}

function cpTemplateById($template_id) {
    global $_LANG;

    if ($template_id) {
        return $template_id;
    } else {
        return '<span style="color:silver">' . $_LANG['AD_AS_SITE'] . '</span>';
    }
}

function cpUserNick($user_id = 0) {
    global $_LANG;

    if ($user_id) {
        $usr = \cms\db::getInstance()->getItemById('users', (int) $user_id);

        if ($usr) {
            return $usr['nickname'];
        } else {
            return false;
        }
    } else {
        return '<em style="color:gray">' . $_LANG['AD_NOT_DEFINED'] . '</em>';
    }
}

function cpYesNo($option) {
    global $_LANG;

    if ($option) {
        return '<span style="color:green">' . $_LANG['YES'] . '</span>';
    } else {
        return '<span style="color:red">' . $_LANG['NO'] . '</span>';
    }
}

//////////////////////////////////////////////// DATABASE //////////////////////////////////////////////////////////

/**
 * Убирает префикс таблиц CMS: методы \cms\db ожидают имя таблицы без префикса.
 * Исторический код передаёт как реальный префикс из конфига, так и жёстко
 * зашитый "cms_".
 */
function cpDbTableName($table) {
    $db = \cms\db::getInstance();

    if ($db->prefix && strpos($table, $db->prefix) === 0) {
        return substr($table, strlen($db->prefix));
    }

    if (strpos($table, 'cms_') === 0) {
        return substr($table, 4);
    }

    return $table;
}

function dbMoveUp($table, $id, $current_ord) {
    $db = \cms\db::getInstance();
    $id = (int) $id;
    $current_ord = (int) $current_ord;
    $table = cpDbTableName($table);

    // исторический запрос менял только одну строку (LIMIT 1)
    $neighbor_id = $db->resetFilters()->filterEqual('ordering', $current_ord - 1)->getFieldFiltered($table, 'id');

    if ($neighbor_id !== false) {
        $db->resetFilters()->filterEqual('id', $neighbor_id)->increment($table, 'ordering', 1);
    }

    $db->resetFilters()->filterEqual('id', $id)->increment($table, 'ordering', -1);
}

function dbMoveDown($table, $id, $current_ord) {
    $db = \cms\db::getInstance();
    $id = (int) $id;
    $current_ord = (int) $current_ord;
    $table = cpDbTableName($table);

    // исторический запрос менял только одну строку (LIMIT 1)
    $neighbor_id = $db->resetFilters()->filterEqual('ordering', $current_ord + 1)->getFieldFiltered($table, 'id');

    if ($neighbor_id !== false) {
        $db->resetFilters()->filterEqual('id', $neighbor_id)->increment($table, 'ordering', -1);
    }

    $db->resetFilters()->filterEqual('id', $id)->increment($table, 'ordering', 1);
}

function dbShow($table, $id) {
    $db = \cms\db::getInstance();
    $table = cpDbTableName($table);
    $db->resetFilters()->filterEqual('id', (int) $id)->updateFiltered($table, array('published' => 1));
}

function dbShowList($table, $list) {
    if (!is_array($list) || !$list) {
        return;
    }

    $ids = array();

    foreach ($list as $value) {
        $ids[] = (int) $value;
    }

    $db = \cms\db::getInstance();
    $table = cpDbTableName($table);
    $db->resetFilters()->filterIn('id', $ids)->updateFiltered($table, array('published' => 1));
}

function dbHide($table, $id) {
    $db = \cms\db::getInstance();
    $table = cpDbTableName($table);
    $db->resetFilters()->filterEqual('id', (int) $id)->updateFiltered($table, array('published' => 0));
}

function dbHideList($table, $list) {
    if (!is_array($list) || !$list) {
        return;
    }

    $ids = array();

    foreach ($list as $value) {
        $ids[] = (int) $value;
    }

    $db = \cms\db::getInstance();
    $table = cpDbTableName($table);
    $db->resetFilters()->filterIn('id', $ids)->updateFiltered($table, array('published' => 0));
}

function dbDelete($table, $id) {
    $inCore = cmsCore::getInstance();
    $db = \cms\db::getInstance();
    $id = (int) $id;
    $table = cpDbTableName($table);

    $db->resetFilters()->delete($table, $id);

    if ($table == 'content') {
        cmsClearTags('content', $id);
        $inCore->deleteRatings('content', $id);
        $inCore->deleteComments('article', $id);
        $db->filterEqual('target', 'content')->filterEqual('item_id', $id)->deleteFiltered('tags');
    }

    if ($table == 'modules') {
        $db->filterEqual('module_id', $id)->deleteFiltered('modules_bind');
    }
}

function dbDeleteList($table, $list) {
    if (!is_array($list) || !$list) {
        return;
    }

    $db = \cms\db::getInstance();
    $table = cpDbTableName($table);

    $db->resetFilters();

    $ids = array();

    foreach ($list as $value) {
        $value = (int) $value;
        $ids[] = $value;

        if ($table == 'content') {
            cmsClearTags('content', $value);
            $db->filterEqual('target', 'article')->filterEqual('target_id', $value)->deleteFiltered('comments');
            $db->filterEqual('target', 'content')->filterEqual('item_id', $value)->deleteFiltered('ratings');
            $db->filterEqual('target', 'content')->filterEqual('item_id', $value)->deleteFiltered('tags');
        }

        if ($table == 'modules') {
            $db->filterEqual('module_id', $value)->deleteFiltered('modules_bind');
        }
    }

    if ($ids) {
        $db->filterIn('id', $ids)->deleteFiltered($table);
    }
}

//============================ HTML GENERATORS ===============================//

function insertPanel() {
    echo \cms\backend::getPanelHtml();
}

function cpBuildList($attr_name, $list, $selected_id = false) {
    global $_LANG;

    $html = '';

    $html .= '<select name="' . $attr_name . '" id="' . $attr_name . '">' . "\n";

    $html .= '<option value="-100">-- ' . $_LANG['AD_ALL'] . ' --</option>' . "\n";

    foreach ($list as $key => $value) {
        if ($selected_id == $list[$key]['id']) {
            $sel = 'selected';
        } else {
            $sel = '';
        }
        $html .= '<option value="' . $list[$key]['id'] . '" ' . $sel . '>' . $list[$key]['title'] . '</option>' . "\n";
    }

    $html .= '</select>' . "\n";

    return $html;
}

function cpGetList($listtype, $field_name = 'title') {
    global $_LANG;
    $list = array();

    // Позиции для модулей
    if ($listtype == 'positions') {
        $pos = cpModulePositions(cmsConfig::getConfig('template'));

        foreach ($pos as $p) {
            $list[] = array('title' => $p, 'id' => $p);
        }

        return $list;
    }

    // Типы меню
    if ($listtype == 'menu') {
        $list[] = array('title' => $_LANG['AD_MAIN_MENU'], 'id' => 'mainmenu');
        $list[] = array('title' => $_LANG['AD_USER_MENU'], 'id' => 'usermenu');
        $list[] = array('title' => $_LANG['AD_AUTH_MENU'], 'id' => 'authmenu');

        for ($m = 1; $m <= 20; $m++) {
            $list[] = array('title' => "{$_LANG['AD_SUBMENU']} {$m}", 'id' => 'menu' . $m);
        }

        return $list;
    }

    //...или записи из таблицы
    $db_table = cpDbTableName($listtype);
    $rows = \cms\db::getInstance()->getRows($db_table, '1', 'id, ' . $field_name, $field_name . ' ASC');

    foreach ($rows as $item) {
        $list[] = array('title' => $item[$field_name], 'id' => $item['id']);
    }

    return $list;
}

function getFullAwardsList() {
    $db = \cms\db::getInstance();

    $awards = array();

    $db->resetFilters();
    $db->selectOnly('title');
    $db->groupBy('title');

    $rows = $db->get('user_awards');

    if ($rows) {
        foreach ($rows as $aw) {
            $awards[] = $aw;
        }
    }

    $db->selectOnly('title');
    $db->groupBy('title');

    $rows = $db->get('user_autoawards');

    if ($rows) {
        foreach ($rows as $aw) {
            if (!in_array(array('title' => $aw['title']), $awards)) {
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
function files_remove_directory($directory, $is_clear = false) {
    return \cms\helper\files::removeDirectory($directory, $is_clear);
}
