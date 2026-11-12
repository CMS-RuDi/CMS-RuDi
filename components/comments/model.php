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

class cms_model_comments
{

    public $is_can_delete    = false;
    public $is_can_moderate  = false;
    public $is_can_bbcode    = false;
    public $is_can_add       = false;
    public $is_add_published = false;

    /**
     * @var \cms\db
     */
    public $inDB;

    public function __construct($labels = array())
    {
        $this->inDB   = \cms\db::getInstance();
        $this->config = cmsCore::getInstance()->loadComponentConfig('comments');
        cmsCore::loadLanguage('components/comments');
        $this->labels = array_merge(self::getDefaultLabels(), $labels);
    }

    public static function getDefaultConfig()
    {
        return array(
            'email'          => '',
            'regcap'         => 0,
            'subscribe'      => 1,
            'min_karma'      => 0,
            'min_karma_show' => 0,
            'min_karma_add'  => 0,
            'perpage'        => 20,
            'cmm_ajax'       => 0,
            'cmm_ip'         => 1,
            'max_level'      => 5,
            'edit_minutes'   => 0,
            'watermark'      => 0,
            'meta_keys'      => '',
            'meta_desc'      => ''
        );
    }

    public static function getDefaultLabels()
    {
        global $_LANG;

        return array( 'comments' => $_LANG['COMMENTS'], 'add' => $_LANG['ADD_COMM'], 'rss' => $_LANG['RSS_COMM'], 'not_comments' => $_LANG['NOT_COMMENT_TEXT'] );
    }

    public function initAccess()
    {
        $this->is_can_delete        = cmsUser::isUserCan('comments/delete');
        $this->is_can_moderate      = cmsUser::isUserCan('comments/moderate');
        $this->is_can_bbcode        = cmsUser::isUserCan('comments/bbcode');
        $this->is_can_add           = cmsUser::isUserCan('comments/add');
        $this->is_add_published     = cmsUser::isUserCan('comments/add_published');
        $this->target_author_delete = cmsUser::isUserCan('comments/target_author_delete');
    }

    public function addComment($item)
    {
        $item = \cms\events::call('comments.add_item', $item);

        // Устаревшее поле cms_comments.pid: в актуальном коде не используется,
        // но в старых схемах БД оно объявлено как NOT NULL без значения по умолчанию.
        if ( !isset($item['pid']) ) {
            $item['pid'] = 0;
        }

        $comment_id = $this->inDB->insert('comments', $item);

        cmsCore::setIdUploadImage('comment', $comment_id);

        return $comment_id;
    }

    public function updateComment($id, $comment)
    {
        if ( !$id ) {
            return false;
        }

        unset($comment['id']);

        $this->inDB->update('comments', (int) $id, $comment);

        return true;
    }

    public function getTargetAuthor($table, $target_id)
    {
        $db = clone $this->inDB;

        // в cms_comment_targets имя таблицы хранится с физическим префиксом,
        // а построитель сам добавляет префикс к именам таблиц
        if ( $this->inDB->prefix && strpos($table, $this->inDB->prefix) === 0 ) {
            $table = substr($table, strlen($this->inDB->prefix));
        }

        $user = $db->selectOnly('i.id')->
                select('i.email')->
                joinInner($table, 'p', 'p.user_id = i.id')->
                filterEqual('p.id', (int) $target_id)->
                filterEqual('i.is_locked', 0)->
                filterEqual('i.is_deleted', 0)->
                getItem('users');

        if ( !$user ) {
            return false;
        }

        return $user;
    }

    public function getCommentAuthorId($comment_id)
    {
        $db = clone $this->inDB;

        return $db->filterEqual('id', (int) $comment_id)->
                        getFieldFiltered('comments', 'user_id');
    }

    public function isAuthorNeedMail($author_id)
    {
        $db = clone $this->inDB;

        return $db->filterEqual('user_id', (int) $author_id)->
                        getFieldFiltered('user_profiles', 'email_newmsg');
    }

    private function getCommentChilds($comment_id)
    {
        $db = clone $this->inDB;

        $childs = $db->selectOnly('i.id')->
                filterEqual('i.parent_id', (int) $comment_id)->
                get('comments', false, false);

        if ( !$childs ) {
            return false;
        }

        return $childs;
    }

    public function deleteComment($comment_id)
    {
        \cms\events::call('comments.delete_item', $comment_id);

        $childs = $this->getCommentChilds($comment_id);

        if ( $childs ) {
            foreach ( $childs as $child ) {
                $this->deleteComment($child['id']);
            }
        }

        $db = clone $this->inDB;

        $db->delete('comments', (int) $comment_id);

        cmsCore::deleteRatings('comment', $comment_id);
        cmsActions::removeObjectLog('add_comment', $comment_id);
        cmsCore::deleteUploadImages($comment_id, 'comment');

        return true;
    }

    public function isEditable($pubdate)
    {
        if ( cmsUser::getInstance()->is_admin ) {
            return true;
        }

        if ( !$this->config['edit_minutes'] ) {
            return false;
        }

        $now      = time();
        $date     = strtotime($pubdate);
        $diff_sec = $now - $date;
        $end_min  = $this->config['edit_minutes'] - round($diff_sec / 60);

        return $end_min > 0 ? $end_min : false;
    }

    /**
     * Условия выборки
     */
    public function whereUserIs($user_id)
    {
        $this->inDB->filterEqual('i.user_id', (int) $user_id);
    }

    public function whereOnlyUsers()
    {
        $this->inDB->filterGt('i.user_id', 0);
    }

    public function whereRatingOver($rating)
    {
        $this->inDB->filterGtEqual('i.rating', (int) $rating);
    }

    public function whereTargetIs($target, $target_id)
    {
        $this->inDB->filterEqual('i.target', $target)->
                filterEqual('i.target_id', (int) $target_id);
    }

    public function whereTargetIn($targets)
    {
        $this->inDB->filterIn('i.target', (array) $targets);
    }

    public function whereIsShow()
    {
        $this->inDB->filterEqual('i.is_hidden', 0);
    }

    /**
     * Получаем комментарии по заданным параметрам
     * @return array
     */
    public function getComments($only_published = true, $is_tree = false, $from_module = false)
    {
        $inUser = cmsUser::getInstance();

        $comments = array();

        global $_LANG;

        $db = $this->inDB;

        // legacy-условия сортировки/группировки используют алиас c,
        // а построитель — алиас i
        if ( $db->order_by ) {
            $db->order_by = str_replace('c.', 'i.', $db->order_by);
        }

        if ( $db->group_by ) {
            $db->group_by = str_replace('c.', 'i.', $db->group_by);
        }

        $db->selectOnly('i.*')->
                select('IFNULL(u.nickname, 0)', 'nickname')->
                select('IFNULL(u.login, 0)', 'login')->
                select('IFNULL(u.is_deleted, 0)', 'is_deleted')->
                select('IFNULL(p.imageurl, 0)', 'imageurl')->
                select('IFNULL(p.gender, 0)', 'gender')->
                joinLeft('users', 'u', 'u.id = i.user_id')->
                joinLeft('user_profiles', 'p', 'p.user_id = u.id');

        if ( $only_published ) {
            $db->filterEqual('i.published', 1);
        }

        $comments = $db->get('comments', false, false);

        if ( !$comments ) {
            return array();
        }

        foreach ( $comments as $index => $comment ) {
            $comment['level']       = 0;
            $comment['is_editable'] = $this->isEditable($comment['pubdate']);
            $comment['fpubdate']    = cmsCore::dateFormat($comment['pubdate'], true, true);

            if ( $comment['guestname'] ) {
                $comment['author']     = $comment['guestname'];
                $comment['is_profile'] = false;
                $comment['ip']         = in_array($this->config['cmm_ip'], array( 1, 2 )) ? $comment['ip'] : '';
            }
            else {
                $comment['author']['nickname'] = $comment['nickname'];
                $comment['author']['login']    = $comment['login'];
                $comment['is_profile']         = true;
                $comment['user_image']         = cmsUser::getUserAvatarUrl($comment['user_id'], 'small', $comment['imageurl'], $comment['is_deleted']);
                $comment['ip']                 = ($this->config['cmm_ip'] == 2 && $comment['ip']) ? $comment['ip'] : '';
            }

            switch ( $comment['gender'] ) {
                case 'm': $comment['gender'] = $_LANG['COMMENTS_MALE'];
                    break;
                case 'f': $comment['gender'] = $_LANG['COMMENTS_FEMALE'];
                    break;
                default: $comment['gender'] = $_LANG['COMMENTS_GENDER'];
            }

            $comment['show']  = (!$this->config['min_karma'] || $comment['rating'] >= $this->config['min_karma_show']) || cmsUser::userIsAdmin($comment['user_id']);
            $comment['is_my'] = ($inUser->id == $comment['user_id']);

            if ( $inUser->id ) {
                $comment['is_voted'] = $comment['is_my'] ? true : cmsUser::isRateUser('comment', $inUser->id, $comment['id']);
            }
            else {
                $comment['is_voted'] = true;
            }

            $comments[$index] = $comment;
        }

        if ( $is_tree ) {
            $comments = $this->buildTree(0, 0, $comments);
        }

        return \cms\events::call($from_module ? 'comments.get_items_from_module' : 'comments.get_items', $comments);
    }

    /**
     * Возвращает количество комментариев по заданным параметрам
     * @return int
     */
    public function getCommentsCount($only_published = true)
    {
        if ( $only_published ) {
            $this->inDB->filterEqual('i.published', 1);
        }

        return $this->inDB->getCount('comments');
    }

    public function getComment($id)
    {
        $db = clone $this->inDB;

        $comment = $db->selectOnly('i.*')->
                select('IFNULL(u.nickname, 0)', 'nickname')->
                select('IFNULL(u.login, 0)', 'login')->
                select('IFNULL(u.is_deleted, 0)', 'is_deleted')->
                select('IFNULL(p.imageurl, 0)', 'imageurl')->
                joinLeft('users', 'u', 'u.id = i.user_id')->
                joinLeft('user_profiles', 'p', 'p.user_id = u.id')->
                filterEqual('i.id', (int) $id)->
                getItem('comments');

        if ( !$comment ) {
            return false;
        }

        $comment['is_editable'] = $this->isEditable($comment['pubdate']);

        return \cms\events::call('comments.get_item', $comment);
    }

    private function buildTree($parent_id, $level, $comments, $tree = array())
    {
        $level++;

        foreach ( $comments as $comment ) {
            if ( $comment['parent_id'] == $parent_id ) {
                $comment['level'] = $level - 1;
                $tree[]           = $comment;
                $tree             = $this->buildTree($comment['id'], $level, $comments, $tree);
            }
        }

        return $tree;
    }

}
