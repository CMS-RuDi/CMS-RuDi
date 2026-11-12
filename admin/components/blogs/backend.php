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

function cpBlogOwner($item) {
    $DB = \cms\db::getInstance();

    if ($item['owner'] == 'user') {
        $nickname = $DB->getField('users', $item['user_id'], 'nickname');
        $link = '<a href="/cp/components/users?opt=edit&user_id=' . $item['user_id'] . '" class="user_link" target="_blank">
                 ' . $nickname . '
                 </a>';
    } else {
        $title = $DB->getField('clubs', $item['user_id'], 'title');
        $link = '<a href="/cp/components/clubs?opt=edit&item_id=' . $item['user_id'] . '" class="club_link" target="_blank">' . $title . '</a>';
    }

    return $link;
}

//============================================================================//

$opt = $inPut->get('opt', 'str', 'list_blogs');

$cfg = $inCore->loadComponentConfig('blogs');

$model = new cms_model_blogs();

$inBlog = cmsBlogs::getInstance();

//============================================================================//

if ($opt == 'list_blogs') {
    $toolmenu[] = array('icon' => 'edit.gif', 'title' => $_LANG['AD_EDIT_SELECTED'], 'link' => "javascript:checkSel('/cp/components/blogs?opt=edit_blog&multiple=1');");
    $toolmenu[] = array('icon' => 'delete.gif', 'title' => $_LANG['AD_DELETE_SELECTED'], 'link' => "javascript:checkSel('/cp/components/blogs?opt=delete_blog&multiple=1');");
    $toolmenu[] = array('icon' => 'config.gif', 'title' => $_LANG['AD_SETTINGS'], 'link' => '/cp/components/blogs?opt=config');

    cpToolMenu($toolmenu);

    $fields[] = array('title' => 'id', 'field' => 'id', 'width' => '30');
    $fields[] = array('title' => $_LANG['AD_CREATED'], 'field' => 'pubdate', 'width' => '80', 'filter' => 15, 'fdate' => '%d/%m/%Y');
    $fields[] = array('title' => $_LANG['TITLE'], 'field' => 'title', 'width' => '', 'filter' => 15, 'link' => '/cp/components/blogs?opt=edit_blog&item_id=%id%');
    $fields[] = array('title' => $_LANG['AD_OWNER'], 'field' => array('id', 'owner', 'user_id'), 'width' => '300', 'prc' => 'cpBlogOwner');

    $actions[] = array('title' => $_LANG['AD_RENAME'], 'icon' => 'edit.gif', 'link' => '/cp/components/blogs?opt=edit_blog&item_id=%id%');
    $actions[] = array('title' => $_LANG['DELETE'], 'icon' => 'delete.gif', 'confirm' => $_LANG['AD_IF_BLOG_DELETE'], 'link' => '/cp/components/blogs?opt=delete_blog&item_id=%id%');

    cpListTable('cms_blogs', $fields, $actions, '', 'pubdate DESC');
}

if ($opt == 'saveconfig') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $cfg['perpage'] = $inPut->get('perpage', 'int', 0);
    $cfg['perpage_blog'] = $inPut->get('perpage_blog', 'int', 0);
    $cfg['update_date'] = $inPut->get('update_date', 'int', 0);
    $cfg['update_seo_link'] = $inPut->get('update_seo_link', 'int', 0);
    $cfg['min_karma_private'] = $inPut->get('min_karma_private', 'int', 0);
    $cfg['min_karma_public'] = $inPut->get('min_karma_public', 'int', 0);
    $cfg['min_karma'] = $inPut->get('min_karma', 'int', 0);
    $cfg['list_min_rating'] = $inPut->get('list_min_rating', 'int', 0);
    $cfg['watermark'] = $inPut->get('watermark', 'int', 0);
    $cfg['img_on'] = $inPut->get('img_on', 'int', 0);
    $cfg['update_seo_link_blog'] = $inPut->get('update_seo_link_blog', 'int', 0);
    $cfg['meta_keys'] = $inPut->get('meta_keys', 'str', '');
    $cfg['meta_desc'] = $inPut->get('meta_desc', 'str', '');
    $cfg['seo_user_access'] = $inPut->get('seo_user_access', 'int', 0);

    $inCore->saveComponentConfig('blogs', $cfg);

    cmsCore::addSessionMessage($_LANG['AD_CONFIG_SAVE_SUCCESS'], 'success');

    cmsCore::redirectBack();
}

if ($opt == 'delete_blog') {
    if (!$inPut->has('item')) {
        $model->deleteBlog($inPut->get('item_id', 'int', 0));
    } else {
        $model->deleteBlog($inPut->get('item', 'array_int', array()));
    }
    cmsCore::addSessionMessage($_LANG['AD_DO_SUCCESS'], 'success');
    cmsCore::redirectBack();
}

if ($opt == 'update_blog') {
    if (!cmsUser::checkCsrfToken()) {
        cmsCore::error404();
    }

    $blog = $inBlog->getBlog($inPut->get('item_id', 'int', 0));
    if (!$blog) {
        cmsCore::error404();
    }

    $title = $inPut->get('title', 'str', $blog['title']);

    $seolink_new = $inBlog->updateBlog($blog['id'], \cms\events::call('blogs.update', array('title' => $title)), true);

    $blog['seolink'] = $seolink_new ? $seolink_new : $blog['seolink'];

    if (stripslashes($title) != $blog['title']) {
        cmsActions::updateLog('add_post', array('target' => $title, 'target_url' => $model->getBlogURL($blog['seolink'])), 0, $blog['id']);
        cmsActions::updateLog('add_blog', array('object' => $title, 'object_url' => $model->getBlogURL($blog['seolink'])), $blog['id']);
    }

    cmsCore::addSessionMessage($_LANG['AD_DO_SUCCESS'] . '.  ' . $_LANG['AD_SAVE_SUCCESS'], 'success');

    if (empty($_SESSION['editlist'])) {
        cmsCore::redirect('/cp/components/blogs?opt=list_blogs');
    } else {
        cmsCore::redirect('/cp/components/blogs?opt=edit_blog');
    }
}

if ($opt == 'config') {
    require('../includes/jwtabs.php');
    $GLOBALS['cp_page_head'][] = jwHeader();

    cpAddPathway($_LANG['AD_SETTINGS']);

    $toolmenu[] = array('icon' => 'save.gif', 'title' => $_LANG['SAVE'], 'link' => 'javascript:document.optform.submit();');
    $toolmenu[] = array('icon' => 'listblogs.gif', 'title' => $_LANG['AD_BLOGS'], 'link' => '/cp/components/blogs?opt=list_blogs');
    $toolmenu[] = array('icon' => 'config.gif', 'title' => $_LANG['AD_SETTINGS'], 'link' => '/cp/components/blogs?opt=config');

    cpToolMenu($toolmenu);
    ?>
    <form action="/cp/components/blogs" method="post" name="optform" target="_self" style="margin-top:10px" id="form1">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <?php ob_start(); ?>
        {tab=<?php echo $_LANG['AD_BLOG_VIEW']; ?>}
        <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4><?php echo $_LANG['AD_BLOG_VIEW']; ?></h4></td>
            </tr>
            <tr>
                <td valign="top"><strong><?php echo $_LANG['AD_BLOG_POSTS_QUANTITY']; ?> </strong></td>
                <td width="100" valign="top">
                    <input name="perpage" type="text" id="perpage" value="<?php echo @$cfg['perpage']; ?>" size="5" /> <?php echo $_LANG['AD_PIECES']; ?>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong><?php echo $_LANG['AD_BLOGS_QUANTITY']; ?> </strong></td>
                <td width="100" valign="top">
                    <input name="perpage_blog" type="text" id="perpage_blog" value="<?php echo @$cfg['perpage_blog']; ?>" size="5" /> <?php echo $_LANG['AD_PIECES']; ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4><?php echo $_LANG['AD_PHOTO_OPTIONS']; ?></h4></td>
            </tr>
            <tr>
                <td valign="top"><strong><?php echo $_LANG['AD_ENABLE_PHOTO_LOAD']; ?></strong></td>
                <td width="100" valign="top">
                    <label><input name="img_on" type="radio" value="1" <?php
                        if (@$cfg['img_on']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="img_on" type="radio" value="0" <?php
                        if (@!$cfg['img_on']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_ENABLE_WATERMARK']; ?></strong><br />
                    <?php echo $_LANG['AD_IF_ENABLE_WATERMARK']; ?>"<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"
                </td>
                <td width="100" valign="top">
                    <label><input name="watermark" type="radio" value="1" <?php
                        if (@$cfg['watermark']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="watermark" type="radio" value="0" <?php
                        if (@!$cfg['watermark']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>

            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4><?php echo $_LANG['AD_EDIT_SETUP']; ?></h4></td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_UPDATE_CALENDAR_DATA']; ?></strong><br />
                    <span class="hinttext">
                        <?php echo $_LANG['AD_IF_ENABLE_TODAY']; ?>
                    </span>
                </td>
                <td valign="top">
                    <label><input name="update_date" type="radio" value="1" <?php
                        if (@$cfg['update_date']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="update_date" type="radio" value="0" <?php
                        if (@!$cfg['update_date']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_UPDATE_BLOG_LINK']; ?></strong><br />
                    <span class="hinttext">
                        <?php echo $_LANG['AD_IF_ENABLE_LINK']; ?>
                    </span>
                </td>
                <td valign="top">
                    <label><input name="update_seo_link_blog" type="radio" value="1" <?php
                        if (@$cfg['update_seo_link_blog']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="update_seo_link_blog" type="radio" value="0" <?php
                        if (@!$cfg['update_seo_link_blog']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_UPDATE_TITLE_LINK']; ?></strong><br />
                    <span class="hinttext">
                        <?php echo $_LANG['AD_IF_ENABLE_TITLE']; ?>
                    </span>
                </td>
                <td valign="top">
                    <label><input name="update_seo_link" type="radio" value="1" <?php
                        if (@$cfg['update_seo_link']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="update_seo_link" type="radio" value="0" <?php
                        if (@!$cfg['update_seo_link']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>
        </table>
        {tab=<?php echo $_LANG['AD_LIMIT']; ?>}
        <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4><?php echo $_LANG['AD_KARMA_LIMIT']; ?></h4></td>
            </tr>

            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_USE_LIMIT']; ?></strong><br />
                    <span class="hinttext"><?php echo $_LANG['AD_IF_DISABLE_KARMA_LIMIT']; ?></span>
                </td>
                <td valign="top">
                    <label><input name="min_karma" type="radio" value="1" <?php
                        if (@$cfg['min_karma']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?></label>
                    <label><input name="min_karma" type="radio" value="0" <?php
                        if (@!$cfg['min_karma']) {
                            echo 'checked="checked"';
                        }
                        ?>/> <?php echo $_LANG['NO']; ?></label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_CREATE_PERSONAL_BLOG']; ?></strong><br />
                    <span class="hinttext"><?php echo $_LANG['AD_HOW_MANY_KARMA_P']; ?> </span>
                </td>
                <td valign="top">
                    <input name="min_karma_private" type="text" id="min_karma_private" value="<?php echo @$cfg['min_karma_private']; ?>" size="5" />
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_CREATE_COLLECTIVE_BLOG']; ?></strong><br />
                    <span class="hinttext"><?php echo $_LANG['AD_HOW_MANY_KARMA_C']; ?></span>
                </td>
                <td valign="top">
                    <input name="min_karma_public" type="text" id="min_karma_public" value="<?php echo @$cfg['min_karma_public']; ?>" size="5" />
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong><?php echo $_LANG['AD_RATING_MIN']; ?></strong><br />
                    <span class="hinttext"><?php echo $_LANG['AD_POST_LIST']; ?></span>
                </td>
                <td valign="top">
                    <input name="list_min_rating" type="text" value="<?php echo @$cfg['list_min_rating']; ?>" size="5" />
                </td>
            </tr>
        </table>
        {tab=SEO}
        <table width="609" border="0" cellspacing="5" class="proptable">
            <tr>
                <td colspan="2">
                    <strong style="margin:5px 0px 5px 0px"><?php echo $_LANG['AD_ROOT_METAKEYS']; ?></strong><br />
                    <div class="hinttext"><?php echo $_LANG['AD_FROM_COMMA'] ?><br /></div>
                    <textarea name="meta_keys" rows="2" style="width:580px"><?php echo $cfg['meta_keys'] ?></textarea>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong style="margin:5px 0px 5px 0px"><?php echo $_LANG['AD_ROOT_METADESC']; ?></strong><br />
                    <div class="hinttext"><?php echo $_LANG['SEO_METADESCR_HINT'] ?></div>
                    <textarea name="meta_desc" rows="4" style="width:580px"><?php echo $cfg['meta_desc'] ?></textarea>
                </td>
            </tr>
            <tr>
                <td><strong><?php echo $_LANG['AD_USER_SEO_ACCESS']; ?> </strong></td>
                <td>
                    <label><input name="seo_user_access" type="radio" value="1" <?php
                        if ($cfg['seo_user_access']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['YES']; ?> </label>
                    <label><input name="seo_user_access" type="radio" value="0"  <?php
                        if (!$cfg['seo_user_access']) {
                            echo 'checked="checked"';
                        }
                        ?> /> <?php echo $_LANG['NO']; ?> </label>
                </td>
            </tr>
        </table>
        {/tabs}
        <?php echo jwTabs(ob_get_clean()); ?>
        <p>
            <input name="opt" type="hidden" value="saveconfig" />
            <input name="save" type="submit" id="save" value="<?php echo $_LANG['SAVE']; ?>" />
            <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components';"/>
        </p>
    </form>
<?php } ?>

<?php
if ($opt == 'edit_blog') {
    if ($inPut->has('multiple')) {
        if ($inPut->has('item')) {
            $_SESSION['editlist'] = $inPut->get('item', 'array_int', array());
        } else {
            cmsCore::addSessionMessage($_LANG['AD_NO_SELECT_OBJECTS'], 'error');
            cmsCore::redirectBack();
        }
    }

    $ostatok = '';

    if (isset($_SESSION['editlist'])) {
        $item_id = array_shift($_SESSION['editlist']);
        if (sizeof($_SESSION['editlist']) == 0) {
            unset($_SESSION['editlist']);
        } else {
            $ostatok = '(' . $_LANG['AD_NEXT_IN'] . sizeof($_SESSION['editlist']) . ')';
        }
    } else {
        $item_id = $inPut->get('item_id', 'int', 0);
    }

    $mod = $DB->getItemById('blogs', $item_id);
    if (!$mod) {
        cmsCore::error404();
    }

    echo '<h3>' . $_LANG['AD_EDIT_BLOG'] . ' ' . $ostatok . '</h3>';
    cpAddPathway($mod['title']);
    ?>
    <form action="/cp/components/blogs?opt=update_blog&item_id=<?php echo $mod['id']; ?>" method="post" name="optform" target="_self" id="form1">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td width="120"><strong><?php echo $_LANG['AD_BLOG_NAME']; ?>: </strong></td>
                <td>
                    <input name="title" type="text" id="title" value="<?php echo htmlspecialchars($mod['title']); ?>" style="width:99%" /><br />
                    <span class="hinttext"><?php echo $_LANG['AD_CHANGE_URL']; ?></span>
                </td>
            </tr>
        </table>
        <p>
            <input name="opt" type="hidden" value="update_blog" />
            <input name="save" type="submit" id="save" value="<?php echo $_LANG['SAVE']; ?>" />
            <input name="back" type="button" id="back" value="<?php echo $_LANG['CANCEL']; ?>" onclick="window.location.href = '/cp/components/blogs?opt=list_blogs';"/>
        </p>
    </form>
    <?php
}