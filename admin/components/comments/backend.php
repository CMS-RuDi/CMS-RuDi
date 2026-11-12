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

if (!defined('VALID_CMS_ADMIN')) {
    die('ACCESS DENIED');
}

function cpStripComment($text) {
    $text = strip_tags($text);

    if (sizeof($text) < 120) {
        return $text;
    }

    return mb_substr($text, 0, 120) . '...';
}

function cpCommentAuthor($item) {
    if (!$item['user_id']) {
        $author = $item['guestname'];
    } else {
        $u = \cms\db::getInstance()->getItemById('users', (int) $item['user_id']);
        $author = $u['nickname'] . ' (<a href="/cp/components/users?opt=edit&user_id=' . $u['id'] . '" target="_blank">' . $u['login'] . '</a>)';
    }

    return $author;
}

function cpCommentTarget($item) {
    return '<a target="_blank" href="' . $item['target_link'] . '#c' . $item['id'] . '">' . $item['target_title'] . '</a>';
}

//------------------------------------------------------------------//
$opt = $inPut->get('opt', 'str', 'list');

$DB = \cms\db::getInstance();

$toolmenu[] = array('icon' => 'listcomments.gif', 'title' => $_LANG['AD_ALL_COMENTS'], 'link' => '/cp/components/comments?opt=list');
$toolmenu[] = array('icon' => 'config.gif', 'title' => $_LANG['AD_SETTINGS'], 'link' => '/cp/components/comments?opt=config');

cpToolMenu($toolmenu);

$model = new cms_model_comments();

$cfg = $model->config;

if ($opt == 'saveconfig') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $cfg['email'] = $inPut->get('email', 'email', '');
    $cfg['regcap'] = $inPut->get('regcap', 'int');
    $cfg['subscribe'] = $inPut->get('subscribe', 'int');
    $cfg['min_karma'] = $inPut->get('min_karma', 'int');
    $cfg['min_karma_show'] = $inPut->get('min_karma_show', 'int');
    $cfg['min_karma_add'] = $inPut->get('min_karma_add', 'int');
    $cfg['perpage'] = $inPut->get('perpage', 'int');
    $cfg['cmm_ajax'] = $inPut->get('cmm_ajax', 'int');
    $cfg['cmm_ip'] = $inPut->get('cmm_ip', 'int');
    $cfg['max_level'] = $inPut->get('max_level', 'int');
    $cfg['edit_minutes'] = $inPut->get('edit_minutes', 'int');
    $cfg['watermark'] = $inPut->get('watermark', 'int');
    $cfg['meta_keys'] = $inPut->get('meta_keys', 'str', '');
    $cfg['meta_desc'] = $inPut->get('meta_desc', 'str', '');

    $inCore->saveComponentConfig('comments', $cfg);

    cmsCore::addSessionMessage($_LANG['AD_CONFIG_SAVE_SUCCESS'], 'success');

    cmsCore::redirectBack();
}

if ($opt == 'show_comment') {
    $item_id = $inPut->get('item_id', 'int', 0);
    $DB->resetFilters();
    $DB->update('comments', $item_id, array('published' => 1));
    echo '1';
    exit;
}

if ($opt == 'hide_comment') {
    $item_id = $inPut->get('item_id', 'int', 0);
    $DB->resetFilters();
    $DB->update('comments', $item_id, array('published' => 0));
    echo '1';
    exit;
}

if ($opt == 'update') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $item_id = $inPut->get('item_id', 'int', 0);

    $guestname = $inPut->get('guestname', 'str', '');
    $pubdate = $inPut->get('pubdate', 'str');
    $published = $inPut->get('published', 'int');
    $content = $inPut->get('content', 'html');

    $DB->update('comments', $item_id, array(
        'guestname' => $guestname,
        'pubdate' => $pubdate,
        'published' => $published,
        'content' => $content
    ));

    cmsCore::addSessionMessage($_LANG['AD_DO_SUCCESS'], 'success');
    cmsCore::redirect('/cp/components/comments?opt=list');
}

if ($opt == 'delete') {
    $model->deleteComment($inPut->get('item_id', 'int'));
    cmsCore::addSessionMessage($_LANG['AD_DO_SUCCESS'], 'success');
    cmsCore::redirect('/cp/components/comments?opt=list');
}

if ($opt == 'list') {
    if ($inPut->has('show_hidden')) {
        cpAddPathway($_LANG['AD_COMENTS_ON_MODERATE']);
        echo '<h3>' . $_LANG['AD_COMENTS_ON_MODERATE'] . '</h3>';
    } else {
        echo '<h3>' . $_LANG['AD_ALL_COMENTS'] . '</h3>';
    }

    $fields[] = array('title' => 'id', 'field' => 'id', 'width' => '30');
    $fields[] = array('title' => $_LANG['DATE'], 'field' => 'pubdate', 'width' => '100');
    $fields[] = array('title' => $_LANG['AD_TEXT'], 'field' => 'content', 'width' => '', 'prc' => 'cpStripComment');
    $fields[] = array('title' => $_LANG['AD_IP'], 'field' => 'ip', 'width' => '80');
    $fields[] = array('title' => $_LANG['AD_IS_PUBLISHED'], 'field' => 'published', 'width' => '50', 'do' => 'opt', 'do_suffix' => '_comment');
    $fields[] = array('title' => $_LANG['AD_AUTHOR'], 'field' => array('user_id', 'guestname'), 'width' => '180', 'prc' => 'cpCommentAuthor');
    $fields[] = array('title' => $_LANG['AD_AIM'], 'field' => array('target_title', 'target_link', 'id'), 'width' => '220', 'prc' => 'cpCommentTarget');

    $actions[] = array('title' => $_LANG['EDIT'], 'icon' => 'edit.gif', 'link' => '/cp/components/comments?opt=edit&item_id=%id%');
    $actions[] = array('title' => $_LANG['DELETE'], 'icon' => 'delete.gif', 'confirm' => $_LANG['AD_IF_COMENT_DELETE'], 'link' => '/cp/components/comments?opt=delete&item_id=%id%');

    $where = $inPut->has('show_hidden') ? 'published = 0' : '';

    cpListTable('cms_comments', $fields, $actions, $where, 'pubdate DESC');
}

if ($opt == 'edit') {
    $mod = $model->getComment($inPut->get('item_id', 'int'));
    if (!$mod) {
        cmsCore::error404();
    }

    if ($mod['user_id'] == 0) {
        $author = '<input name="guestname" type="text" id="title" size="30" value="' . $mod['guestname'] . '"/>';
    } else {
        $author = $mod['nickname'] . ' (<a target="_blank" href="/cp/components/users?opt=edit&user_id=' . $mod['user_id'] . '">' . $mod['login'] . '</a>)';
    }

    cpAddPathway($_LANG['AD_EDIT_COMENT']);
    echo '<h3>' . $_LANG['AD_EDIT_COMENT'] . '</h3>';
    ?>

    <form id="addform" name="addform" method="post" action="/cp/components/comments">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="662" border="0" cellspacing="5" class="proptable">
            <tr>
                <td width="200"><strong><?php echo $_LANG['AD_COMENT_AUTHOR']; ?> </strong></td>
                <td><?php echo $author ?></td>
            </tr>
            <tr>
                <td><strong><?php echo $_LANG['AD_CALENDAR_DATE']; ?> </strong></td>
                <td><input name="pubdate" type="text" id="title3" size="30" value="<?php echo $mod['pubdate']; ?>"/></td>
            </tr>
            <tr>
                <td><strong><?php echo $_LANG['AD_IF_COMENT_PUBLIC']; ?></strong></td>
                <td><label><input name="published" type="radio" value="1" <?php
                        if ($mod['published']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?> </label>
                    <label> <input name="published" type="radio" value="0"  <?php
                        if (!$mod['published']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['NO']; ?> </label></td>
            </tr>
        </table>
        <?php cmsCore::insertEditor('content', $mod['content'], '250', '100%'); ?>
        <p>
            <input name="add_mod" type="submit" id="add_mod" value="<?php echo $_LANG['SAVE']; ?>"/>
            <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components';"/>
            <input name="opt" type="hidden" id="do" value="update" />
            <input name="item_id" type="hidden" value="<?php echo $mod['id'] ?>" />
        </p>
    </form>
    <?php
}

if ($opt == 'config') {
    cpAddPathway($_LANG['AD_SETTINGS']);
    echo '<h3>' . $_LANG['AD_SETTINGS'] . '</h3>';
    ?>

    <form action="/cp/components/comments" method="post" name="optform" target="_self" id="form1">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <div id="config_tabs" style="margin-top:12px;" class="uitabs">

            <ul id="tabs">
                <li><a href="#basic"><span><?php echo $_LANG['AD_OVERALL']; ?></span></a></li>
                <li><a href="#format"><span><?php echo $_LANG['AD_FORMAT']; ?></span></a></li>
                <li><a href="#access"><span><?php echo $_LANG['AD_TAB_ACCESS']; ?></span></a></li>
                <li><a href="#restrict"><span><?php echo $_LANG['AD_LIMIT']; ?></span></a></li>
                <li><a href="#seo"><span><?php echo $_LANG['AD_SEO']; ?></span></a></li>
            </ul>
            <div id="seo">
                <table width="610" border="0" cellspacing="5" class="proptable">
                    <tr>
                        <td>
                            <strong style="margin:5px 0px 5px 0px"><?php echo $_LANG['AD_ROOT_METAKEYS']; ?></strong><br />
                            <div class="hinttext"><?php echo $_LANG['AD_FROM_COMMA'] ?><br /></div>
                            <textarea name="meta_keys" rows="2" style="width:580px"><?php echo $cfg['meta_keys'] ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong style="margin:5px 0px 5px 0px"><?php echo $_LANG['AD_ROOT_METADESC']; ?></strong><br />
                            <div class="hinttext"><?php echo $_LANG['SEO_METADESCR_HINT'] ?></div>
                            <textarea name="meta_desc" rows="4" style="width:580px"><?php echo $cfg['meta_desc'] ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="basic">
                <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
                    <tr>
                        <td width="316" valign="top">
                            <strong><?php echo $_LANG['AD_COMENT_EMAIL']; ?></strong><br/>
                            <span class="hinttext"><?php echo $_LANG['AD_NO_EMAIL']; ?></span>
                        </td>
                        <td width="313" valign="top">
                            <input name="email" type="text" id="email" size="30" value="<?php echo $cfg['email']; ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <strong><?php echo $_LANG['AD_SUBSCRIPTION']; ?> </strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_GET_MESSAGE']; ?></span>
                        </td>
                        <td valign="top">
                            <label><input name="subscribe" type="radio" value="1" <?php
                                if ($cfg['subscribe']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['YES']; ?></label>
                            <label><input name="subscribe" type="radio" value="0"  <?php
                                if (!$cfg['subscribe']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['NO']; ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="format">
                <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
                    <tr>
                        <td width="316" valign="top">
                            <strong><?php echo $_LANG['AD_IF_AJAX']; ?></strong>
                        </td>
                        <td width="313" valign="top">
                            <label><input name="cmm_ajax" type="radio" value="1" <?php
                                if ($cfg['cmm_ajax']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['YES']; ?></label>
                            <label><input name="cmm_ajax" type="radio" value="0"  <?php
                                if (!$cfg['cmm_ajax']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['NO']; ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><strong><?php echo $_LANG['AD_WATERMARK']; ?></strong></td>
                        <td valign="top">
                            <label><input name="watermark" type="radio" value="1" <?php
                                if ($cfg['watermark']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['YES']; ?></label>
                            <label><input name="watermark" type="radio" value="0"  <?php
                                if (!$cfg['watermark']) {
                                    echo 'checked="checked"';
                                }
                                ?> /> <?php echo $_LANG['NO']; ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><strong><?php echo $_LANG['AD_ABOUT_NEW_COMENT']; ?></strong></td>
                        <td valign="top"><?php echo '/languages/' . cmsConfig::getConfig('lang') . '/letters/newcomment.txt'; ?></td>
                    </tr>
                    <tr>
                        <td valign="top"><strong><?php echo $_LANG['AD_MAX_LEVEL']; ?></strong></td>
                        <td valign="top"><input class="uispin" name="max_level" type="text" id="max_level" value="<?php echo $cfg['max_level']; ?>" size="3" /></td>
                    </tr>
                    <tr>
                        <td valign="top"><strong><?php echo $_LANG['AD_HOW_MANY_COMENTS']; ?></strong></td>
                        <td valign="top"><input class="uispin" name="perpage" type="text" id="perpage" value="<?php echo $cfg['perpage']; ?>" size="3" /></td>
                    </tr>
                    <tr>
                        <td valign="middle"><strong><?php echo $_LANG['AD_SHOW_IP']; ?> </strong></td>
                        <td>
                            <select name="cmm_ip" id="cmm_ip" style="width:220px">
                                <option value="0" <?php
                                if ($cfg['cmm_ip'] == 0) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_HIDE_IP']; ?></option>
                                <option value="1" <?php
                                if ($cfg['cmm_ip'] == 1) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_ONLY_GUEST_IP']; ?></option>
                                <option value="2" <?php
                                if ($cfg['cmm_ip'] == 2) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_ALL_IP']; ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="access">
                <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
                    <tr>
                        <td valign="top">
                            <strong><?php echo $_LANG['AD_NEED_CAPCA']; ?></strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_USERS_CAPCA']; ?> </span>
                        </td>
                        <td valign="top">
                            <select name="regcap" id="regcap" style="width:220px">
                                <option value="0" <?php
                                if ($cfg['regcap'] == 0) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_FOR_GUEST']; ?></option>
                                <option value="1" <?php
                                if ($cfg['regcap'] == 1) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_FOR_ALL']; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><strong><?php echo $_LANG['AD_DISALLOW_EDIT']; ?></strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_DISALLOW_TIMER']; ?></span>
                        </td>
                        <td valign="top">
                            <select name="edit_minutes" id="regcap" style="width:220px">
                                <option value="0" <?php
                                if (!$cfg['edit_minutes']) {
                                    echo 'selected';
                                }
                                ?>><?php echo $_LANG['AD_AT_ONCE']; ?></option>
                                <option value="1" <?php
                                if ($cfg['edit_minutes'] == 1) {
                                    echo 'selected';
                                }
                                ?>>1 <?php echo $_LANG['MINUTU1']; ?></option>
                                <option value="5" <?php
                                if ($cfg['edit_minutes'] == 5) {
                                    echo 'selected';
                                }
                                ?>>5 <?php echo $_LANG['MINUTE10']; ?></option>
                                <option value="10" <?php
                                if ($cfg['edit_minutes'] == 10) {
                                    echo 'selected';
                                }
                                ?>>10 <?php echo $_LANG['MINUTE10']; ?></option>
                                <option value="15" <?php
                                if ($cfg['edit_minutes'] == 15) {
                                    echo 'selected';
                                }
                                ?>>15 <?php echo $_LANG['MINUTE10']; ?></option>
                                <option value="30" <?php
                                if ($cfg['edit_minutes'] == 30) {
                                    echo 'selected';
                                }
                                ?>>30 <?php echo $_LANG['MINUTE10']; ?></option>
                                <option value="60" <?php
                                if ($cfg['edit_minutes'] == 60) {
                                    echo 'selected';
                                }
                                ?>>1 <?php echo $_LANG['HOUR1']; ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="restrict">
                <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
                    <tr>
                        <td width="316" valign="top">
                            <strong><?php echo $_LANG['AD_USE_LIMIT']; ?></strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_ALLOW_ALL']; ?> </span>
                        </td>
                        <td width="313" valign="top">
                            <label><input name="min_karma" type="radio" value="1" <?php
                                if ($cfg['min_karma']) {
                                    echo 'checked="checked"';
                                }
                                ?> />  <?php echo $_LANG['YES']; ?></label>
                            <label><input name="min_karma" type="radio" value="0" <?php
                                if (!$cfg['min_karma']) {
                                    echo 'checked="checked"';
                                }
                                ?>/>  <?php echo $_LANG['NO']; ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <strong><?php echo $_LANG['AD_COMENT_ADD']; ?></strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_HOW_MANY_KARMA']; ?> </span>
                        </td>
                        <td valign="top">
                            <input class="uispin" name="min_karma_add" type="text" id="min_karma_add" value="<?php echo $cfg['min_karma_add']; ?>" size="5" />
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <strong><?php echo $_LANG['AD_HIDE_COMENT']; ?></strong><br />
                            <span class="hinttext"><?php echo $_LANG['AD_MIN_RATING']; ?> </span>
                        </td>
                        <td valign="top">
                            <input class="uispin" name="min_karma_show" type="text" id="min_karma_show" value="<?php echo $cfg['min_karma_show']; ?>" size="5" />
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <p>
            <input name="opt" type="hidden" id="do" value="saveconfig" />
            <input name="save" type="submit" id="save" value="<?php echo $_LANG['SAVE']; ?>" />
            <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components/comments';"/>
        </p>
    </form>
    <?php
}