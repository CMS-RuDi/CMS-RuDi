<?php

/*
 *                           InstantCMS v1.10.7
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2017
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

class cmsPhoto {
    private static $instance;
    private $targets = array(
        'tags' => 'photo',
        'rating' => 'photo',
        'comments_photo' => 'photo',
        'comments_album' => 'palbum',
        'actions_photo' => 'add_photo'
    );

    private function __construct() {
        $this->inDB = \cms\db::getInstance();
        cmsCore::loadLib('tags');
        cmsCore::loadLanguage('components/photos');
    }

    private function __clone() {

    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Устанавливает таргеты
     * @param array $targets
     * @return bool
     */
    public function setTargets($targets = array()) {
        $this->targets = $targets;
        return true;
    }

    public function getTarget($target = '') {
        return isset($this->targets[$target]) ? $this->targets[$target] : '';
    }

    public function whereAlbumIs($album_id) {
        $this->inDB->filterEqual('i.album_id', $album_id);
        return;
    }

    public function whereThisAndNestedCats($left_key, $right_key) {
        $this->inDB->filterGtEqual('a.NSLeft', $left_key)
                ->filterLtEqual('a.NSRight', $right_key)
                ->filterGt('a.parent_id', 0);
    }

    public function wherePeriodIs($period) {
        $today = date("d-m-Y");

        switch ($period) {
            case 'today': $this->inDB->filter("DATE_FORMAT(i.pubdate, '%d-%m-%Y') = '" . $this->inDB->escape($today) . "'");
                break;
            case 'week': $this->inDB->filter("DATEDIFF(NOW(), i.pubdate) <= 7");
                break;
            case 'month': $this->inDB->filter("DATE_SUB(NOW(), INTERVAL 1 MONTH) < i.pubdate");
                break;
            default: return;
        }

        return;
    }

    /**
     * Добавляет фото
     * @param array $photo
     * @return int
     */
    public function addPhoto($photo) {
        $photo_id = $this->inDB->insert('photo_files', \cms\events::call('photos.add_photo', $photo));

        if ($photo['tags']) {
            cmsInsertTags($photo['tags'], $this->getTarget('tags'), $photo_id);
        }

        cmsUser::checkAwards($inUser->id);

        return $photo_id;
    }

    /**
     * Обновляет данные фото
     * @return bool
     */
    public function updatePhoto($photo, $id) {
        $photo = \cms\events::call('photos.update_photo', $photo);

        unset($photo['id']);

        $this->inDB->update('photo_files', (int) $id, $photo);

        if ( isset($photo['tags']) && $photo['tags'] ) {
            cmsInsertTags($photo['tags'], $this->getTarget('tags'), $id);
        }

        return true;
    }

    /**
     * Получает фото
     * @param int $photo_id
     * @param bool $is_full
     * @return array $photo
     */
    public function getPhoto($photo_id) {
        $db = $this->inDB;

        $db->resetFilters();

        $photo = $db->select('i.*')
                ->select('a.user_id', 'auser_id')
                ->select('a.title', 'cat_title')
                ->select('a.NSLeft')
                ->select('a.NSRight')
                ->select('a.NSDiffer')
                ->select('a.nav', 'album_nav')
                ->select('a.public')
                ->select('a.showtags', 'a_tags')
                ->select('a.bbcode', 'a_bbcode')
                ->select('a.orderby')
                ->select('a.orderto')
                ->select('u.nickname')
                ->select('u.login')
                ->select('p.gender')
                ->select('p.imageurl')
                ->joinInner('photo_albums', 'a', 'a.id = i.album_id')
                ->joinLeft('users', 'u', 'u.id = i.user_id')
                ->joinLeft('user_profiles', 'p', 'p.user_id = u.id')
                ->filterEqual('i.id', (int) $photo_id)
                ->getItem('photo_files');

        $db->resetFilters();

        if (!$photo) {
            return false;
        }

        $photo['pubdate'] = cmsCore::dateFormat($photo['pubdate']);

        return $photo;
    }

    /**
     * Удаляет фото
     * @param array $photo
     * @param obj $inUploadPhoto
     * @return bool
     */
    public function deletePhoto($photo, $inUploadPhoto) {
        $photo = \cms\events::call('photos.delete_photo', $photo);

        if (!$this->deletePhotoFile($photo['file'], $inUploadPhoto)) {
            return false;
        }

        cmsCore::deleteComments($this->getTarget('comments_photo'), $photo['id']);
        cmsCore::deleteRatings($this->getTarget('rating'), $photo['id']);
        cmsClearTags($this->getTarget('tags'), $photo['id']);

        cmsActions::removeObjectLog($this->getTarget('actions_photo'), $photo['id']);

        $this->inDB->delete('photo_files', (int) $photo['id']);

        return true;
    }

    /**
     * Публикует фото
     * @param int $photo_id
     * @return bool
     */
    public function publishPhoto($photo_id) {
        \cms\events::call('photos.publish_photo', $photo_id);

        return $this->inDB->update('photo_files', (int) $photo_id, array('published' => 1));
    }

    /**
     * Удаляет фотографии
     * @param array $photo
     * @param obj $inUploadPhoto
     * @return bool
     */
    public function deletePhotos($id_list, $inUploadPhoto) {
        foreach ($id_list as $key => $id) {
            $this->deletePhoto($id, $inUploadPhoto);
        }

        return true;
    }

    /**
     * Удаляет файл фото с папок загрузки
     * @return bool
     */
    public function deletePhotoFile($file = '', $inUploadPhoto = null) {
        if (!($file && is_object($inUploadPhoto))) {
            return false;
        }

        return $inUploadPhoto->deletePhotoFile($file);
    }

    /**
     * Возвращает количество альбомов для заданного $differ
     * @param str $differ
     * @return int
     */
    public function getAlbumsCount($differ) {
        $db = $this->inDB;

        $db->resetFilters();

        $count = $db->filterEqual('i.NSDiffer', $differ)
                ->filterGt('i.parent_id', 0)
                ->filterEqual('i.published', 1)
                ->getCount('photo_albums');

        $db->resetFilters();

        return (int) $count;
    }

    /**
     * Возвращает альбомы
     * @return array $albums
     */
    public function getAlbums($parent_id = 0, $differ = '', $recurse = false) {
        $db = $this->inDB;

        if (!$parent_id) {
            $db->filterGt('i.parent_id', 0);
        }

        if ($parent_id) {
            if ($recurse) {
                $parent = $db->getNsCategory('photo_albums', $parent_id, $differ);
                $db->filterGt('i.NSLeft', $parent['NSLeft'])->filterLt('i.NSRight', $parent['NSRight']);
            } else {
                $db->filterEqual('i.parent_id', (int) $parent_id);
            }
        }

        $db->filterEqual('i.NSDiffer', $differ)
                ->filterEqual('i.published', 1)
                ->select('i.id')
                ->select('i.title')
                ->select('i.description')
                ->select('i.pubdate')
                ->select('i.iconurl')
                ->select('i.thumb1')
                ->select('MIN(f.file)', 'file')
                ->select('IFNULL(COUNT(f.id), 0)', 'content_count')
                ->joinLeft('photo_files', 'f', 'f.album_id = i.id AND f.published = 1')
                ->groupBy('i.id');

        $albums = $db->get('photo_albums', false, '');

        if (!$albums) {
            return false;
        }

        foreach ($albums as $key => $album) {
            $albums[$key]['file'] = ($album['iconurl'] && file_exists(PATH . '/images/photos/small/' . $album['iconurl'])) ?
                    $album['iconurl'] : ($album['file'] ? $album['file'] : 'no_image.png');
            $albums[$key]['pubdate'] = cmsCore::dateFormat($album['pubdate']);
        }

        return \cms\events::call('photos.get_albums', $albums);
    }

    /**
     * Удаляет альбом
     * @param int $album_id
     * @param str $differ
     * @param obj $inUploadPhoto
     * @return bool
     */
    public function deleteAlbum($album_id, $differ = '', $inUploadPhoto = null) {
        $album = $this->inDB->getNsCategory('photo_albums', $album_id, $differ);

        if (!$album) {
            return false;
        }

        \cms\events::call('photos.delete_album', $album_id);

        //устанавливаем нужный альбом и все вложенные
        $this->whereThisAndNestedCats($album['NSLeft'], $album['NSRight']);

        $this->inDB->joinInner('photo_albums', 'a', "a.id = i.album_id AND a.NSDiffer = '" . $this->inDB->escape($differ) . "'");

        $photos = $this->getPhotos(true);

        if ($photos) {
            foreach ($photos as $photo) {
                $this->deletePhoto($photo, $inUploadPhoto);
            }
        }

        cmsCore::deleteComments($this->getTarget('comments_album'), $album_id);

        cmsActions::removeTargetLog($this->getTarget('actions_photo'), $album_id);

        return $this->inDB->deleteNS('photo_albums', $album_id, $differ);
    }

    /**
     * Возвращает список options альбомов для заданного $differ
     * @param str $differ
     * @param str $selected
     * @return str
     */
    public function getAlbumsOption($differ, $selected = '') {
        return cmsCore::getInstance()->getListItems('photo_albums', $selected, 'id', 'ASC', "parent_id > 0 AND NSDiffer='" . $differ . "' AND published = 1");
    }

    /**
     * Возвращает массив фотографий по заданным условиям
     * @param bool $show_all
     * @param bool $is_rating
     * @return array $photos
     */
    public function getPhotos($show_all = false, $is_comments_count = false) {
        $db = $this->inDB;

        if (!$show_all) {
            $db->filterEqual('i.published', 1);
        }

        $db->select('i.*');

        $photos = $db->get('photo_files', false, '');

        if (!$photos) {
            return false;
        }

        foreach ($photos as $key => $photo) {
            if ($is_comments_count) {
                $photos[$key]['comments'] = cmsCore::getCommentsCount(($photo['owner'] == 'photos' ? 'photo' : 'club_photo'), $photo['id']);
            }

            $photos[$key]['pubdate'] = cmsCore::dateFormat($photo['pubdate'], false, false, false);
        }

        return \cms\events::call('photos.get_photos', $photos);
    }

    /**
     * Возвращает количество фотографий по заданным условиям
     * @param bool $show_all
     * @return int
     */
    public function getPhotosCount($show_all = false) {
        $db = $this->inDB;

        //подготовим условия
        if (!$show_all) {
            $db->filterEqual('i.published', 1);
        }

        return $db->getCount('photo_files');
    }
}
