<?php

namespace graphics;

/**
 * Класс обработки графики
 *
 * Предназначен для изменение размеров изображений, получения информации
 * об изображении нанесение водяного знака и других операций с изображениями
 *
 * @author DS Soft <support@ds-soft.ru>
 * @version 0.3.0
 */
class graphics
{

    protected static $support_types = [ 1 => 'gif', 2 => 'jpg', 3 => 'png' ];
    protected $dir                  = false;

    /**
     * filename - название файла
     * prefix - префикс перед названием файла используется только если не указан filename
     * postfix - постфикс после названия файла используется только если не указан filename
     * width - ширина изображения
     * height - высота изображения
     * folder - папка сохранения, либо абсолютный путь либо относительный от $dir
     * resize_type - режим изменения размера (auto, crop, exact, landscape, portrait)
     * watermark - ссылка на водяной знак
     * watermark_pos - положение водяного знака lt(left-top,top-left) - левый верхний угол, tc(top) - сверху в центре, rt(top-right,right-top) - правый верхний угол, lc(left) - в центре слева, cc(center) - в центре, rc(right) - в центре справа, lb(bottom-left,left-bottom) - левый нижний угол, bc(bottom) - снизу в середине, rb(bottom-right,right-bottom) - правый нижний угол
     * watermark_offset - смещение водяного знака от края основного изображения
     * quality - качество изображения
     *
     * @var array
     */
    protected $sizes   = [];
    protected $quality = 90;
    protected static $wmhash;
    protected static $wm;

    /**
     * Возвращает информацию о размере изображения, ориентации, размере и типе файла, его mime и exif нформацию;
     *
     * @param string $file Ссылка на файл изображения
     * @param bool $from_bytes Показывает что передана не ссылка на файл, а строка байт данных
     *
     * @return boolean|array
     */
    public static function getImgInfo($file, $from_bytes = false)
    {
        $info = new info([ 'info', 'exif' ]);

        if ( $from_string ) {
            $info->load($file);
        }
        else {
            $info->read(realpath($file));
        }

        $data = $info->getData('info');

        $data['exif'] = $info->getData('exif');

        if ( empty($data['exif']['size']) ) {
            $data['filesize'] = $from_string === false ? filesize($file) : strlen($file);
        }
        else {
            $data['filesize'] = $data['exif']['size'];
        }

        return $data;
    }

    //========================================================================//

    /**
     * Выставляет массив опций для изменения размеров изображения
     * @param array $sizes
     * @return $this
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
        return $this;
    }

    /**
     * Добавляет опции для обрезки размера изображения
     * @param array $size
     * @return $this
     */
    public function addSize($size)
    {
        $this->sizes[] = $size;
        return $this;
    }

    /**
     * Очищает список опций для изменения размера изображения
     * @return $this
     */
    public function clearSizes()
    {
        $this->sizes = array();
        return $this;
    }

    /**
     * Выставляет директорию сохранения изображений
     * @param string $dir
     * @return $this
     */
    public function setDir($dir)
    {
        $this->dir = rtrim($dir, '/ \\');
        return $this;
    }

    //========================================================================//

    /**
     * Изменяет размер изображения в соответствии с опцияи обработки добавленными методами setSizes и addSize
     *
     * @param string|array $file_path Ссылка на файл изображения или массив таких изображений
     * @param string $driver Класс обработки изображения
     *
     * @return boolean|array
     */
    public function startResize($file_path, $idriver = 'imagick')
    {
        if ( empty($this->sizes) ) {
            return false;
        }

        if ( is_array($file_path) ) {
            if ( !isset($file_path['data']) ) {
                $result = [];

                foreach ( $file_path as $file ) {
                    $r               = $this->startResize($file, $driver);
                    $r['image_file'] = $file;
                    $result[]        = $r;
                }

                return $result;
            }
        }

        $driver = self::loadImage($file_path, $idriver);

        if ( $driver === false ) {
            return false;
        }

        $info = $driver->info();

        $filename = substr(md5(uniqid() . '|' . mt_rand(111111111, 999999999)), mt_rand(1, 16) - 1, 16);

        $sizes = [];

        foreach ( $this->sizes as $k => $size ) {
            $image = clone $driver;

            $format = $info['type'];

            if ( !isset($size['folder']) ) {
                $size['folder'] = $this->dir;
            }
            else if ( !file_exists($size['folder']) ) {
                if ( file_exists($this->dir . '/' . trim($size['folder'], '\\/ ')) ) {
                    $size['folder'] = $this->dir . '/' . trim($size['folder'], '\\/ ');
                }
                else {
                    $size['folder'] = $this->dir;
                }
            }

            if ( isset($size['filename']) ) {
                if ( !mb_strstr($size['filename'], '.') ) {
                    $size['filename'] .= '.' . $format;
                }
                else {
                    $temp = explode('.', $size['filename']);

                    if ( in_array($temp[count($temp) - 1], self::$support_types) ) {
                        $format = $temp[count($temp) - 1];
                    }

                    unset($temp);
                }
            }
            else {
                $size['filename'] = (isset($size['prefix']) ? $size['prefix'] : '') . $filename . (isset($size['postfix']) ? $size['postfix'] : '') . '.' . $format;
            }

            $size['width']       = !empty($size['width']) ? (int) $size['width'] : 0;
            $size['height']      = !empty($size['height']) ? (int) $size['height'] : 0;
            $size['resize_type'] = !empty($size['resize_type']) ? $size['resize_type'] : 'auto';

            // Изменяем размер изображения
            $image->resize($size['width'], $size['height'], $size['resize_type']);

            if ( !empty($size['watermark']) ) {
                // Накладываем водяной знак
                self::addWatermark(
                        $image, $size['watermark'], (isset($size['watermark_pos']) ? $size['watermark_pos'] : 'rb'), (isset($size['watermark_offset']) ? (int) $size['watermark_offset'] : 0), $idriver
                );
            }

            // Сохраняем отредактированное изображение
            $size['result'] = $image->save($size['folder'] . '/' . $size['filename'], $format, (isset($size['quality']) ? $size['quality'] : $this->quality));

            unset($image);

            $sizes[$k] = $size;
        }

        return $sizes;
    }

    /**
     * Добавляет на изображение водяной знак и возвращает объект класса для последующей обработки или сохранения
     *
     * @param mixed $image Путь к изображению, массив с ключом data с строкой байтов изображения или объект драйвера с загруженным изображением
     * @param mixed $wmfile Путь к изображению водяного знага или массив с ключом data с строкой байтов
     * @param string $position Позиция водяного занака
     * @param string $offset Отступ от границ изображения
     * @param string $idriver Название драйвера обработчика изображения
     *
     * @return object Возвращает объект драйвера с загруженным изображением и нанесенным водяным знаком
     */
    public static function addWatermark($image, $wmfile, $position = 'rb', $offset = 0, $idriver = 'imagick')
    {
        $wmhash = md5(isset($wmfile['data']) ? $wmfile['data'] : $wmfile);

        if ( self::$wmhash != $wmhash || self::$wm === null ) {
            self::$wm = self::loadImage($wmfile, $idriver);

            self::$wmhash = $wmhash;
        }

        if ( !is_object($image) ) {
            $image = self::loadImage($image, $idriver);
        }

        $offset = abs($offset);

        $wm = clone self::$wm;

        $ws = ($image->width() - $offset) / $wm->width();
        $hs = ($image->height() - $offset) / $wm->height();

        $min = min($ws, $hs);

        if ( $min <= 1 ) {
            $min = 0.75 * $min;
            $wm->scale($min);
        }

        $pos = self::getWatermarkPos($image->width, $image->height, $wm->width(), $wm->height(), $position, $offset);

        $image->overlay($wm, $pos['x'], $pos['y']);

        return $image;
    }

    /**
     * Возвращает реальный путь к указанному файлу, либо к загруженному файлу, либо возвращает строку байтов загруженного изображения если передана ссылка
     *
     * @param string $src
     *
     * @return mixed
     */
    protected static function getPathOrContent($src)
    {
        if ( empty($src) ) {
            return false;
        }

        if ( isset($src['data']) ) {
            return $src;
        }

        if ( file_exists(realpath($src)) ) {
            return realpath($src);
        }

        if ( !empty($_FILES[$src]['name']) ) {
            if ( $_FILES[$src]['error'] !== UPLOAD_ERR_OK ) {
                trigger_error('upload error ' . $_FILES[$src]['error'], \E_USER_NOTICE);
                return false;
            }
            else {
                return $_FILES[$src]['tmp_name'];
            }
        }

        if ( (mb_substr($src, 0, 7) == 'http://') || (mb_substr($src, 0, 8) == 'https://') ) {
            $curl = \dssoft\curl::init();

            $curl->request('get', $src);

            if ( $curl->code == 200 ) {
                return [ 'data' => $curl->response ];
            }

            trigger_error('download error ' . $src . ' - ' . $curl->error, \E_USER_NOTICE);
        }

        return false;
    }

    /**
     * Возвращает объект указанного класса обработчика изображения
     *
     * @param string $file_path Изображение для загрузки в класс, может быть путем, ссылкой или массивом с ключем data с байтовой строкой
     * @param string $idriver Название класса обработчика
     *
     * @return mixed Возвращает объект или false в случае возникновения ошибки
     */
    public static function loadImage($file_path, $idriver = 'imagick')
    {
        // Получаем содержимое изображения если передана ссылка, или берем изображения из
        // массива $_FILES если передано название поля
        $file = self::getPathOrContent($file_path);

        if ( empty($file) ) {
            return false;
        }

        // Инициализируем класс обработки изображения
        $driver = self::initDriver($idriver);

        if ( $driver === false ) {
            trigger_error('image driver ' . $idriver . ' not ready', \E_USER_NOTICE);

            return false;
        }

        // Загружаем изображение в класс обработки
        if ( isset($file['data']) ) {
            $file_path = 'DATA';
            $result    = $driver->load($file['data']);
        }
        else {
            $result = $driver->read($file);
        }

        if ( $result === false ) {
            trigger_error('file format error - ' . $file_path, \E_USER_NOTICE);
            return false;
        }

        return $driver;
    }

    /**
     * Возвращает объек класса обработчика изображений
     *
     * @param string $driver Название класса
     *
     * @return mixed Объект класса или false при возникновении ошибки
     */
    protected static function initDriver($driver = 'imagick')
    {
        $class_name = '\\graphics\\driver\\' . $driver;

        if ( class_exists($class_name) && $class_name::checkAvailability() ) {
            return new $class_name();
        }
        else if ( $driver != 'gd' ) {
            return self::initDriver('gd');
        }

        return false;
    }

    //========================================================================//

    /**
     * Возвращает массив с шириной и высотой соответствующих указанному режиму изменения размера
     *
     * @param int $new_width Новая ширина
     * @param int $new_height Новая высота
     * @param int $width Ширина
     * @param int $height Высота
     * @param string $mode Режим изменения размера
     *
     * @return array|bool
     */
    public static function getDimensions($new_width, $new_height, $width, $height, $mode = 'auto')
    {
        if ( empty($new_width) && empty($new_height) ) {
            return false;
        }

        $ws = $new_width / $width;
        $hs = $new_height / $height;

        $min = min($ws, $hs);
        $max = max($ws, $hs);

        self::checkS($ws, $hs);

        switch ( $mode ) {
            case 'exact':
                return [ 'width' => $new_width, 'height' => $new_height ];
                break;
            case 'portrait':
                return [ 'width' => round($width * $hs, 0, \PHP_ROUND_HALF_EVEN), 'height' => $new_height ];
                break;
            case 'landscape':
                return [ 'width' => $new_width, 'height' => round($height * $ws, 0, \PHP_ROUND_HALF_EVEN) ];
                break;
            case 'crop':
            case 'auto':
                return [ 'width' => round($width * $max, 0, \PHP_ROUND_HALF_EVEN), 'height' => round($height * $max, 0, \PHP_ROUND_HALF_EVEN) ];
                break;
        }
    }

    protected static function checkS(&$ws, &$hs)
    {
        if ( !$ws xor ! $hs ) {
            if ( $hs ) {
                $ws = $hs;
            }
            else {
                $hs = $ws;
            }
        }
    }

    /**
     * Возвращает координаты положения водяного знака на изображении
     *
     * @param int $image_width Ширина изображения
     * @param int $image_height Высота изображения
     * @param int $wm_width Ширина водяного знака
     * @param int $wm_height Высота водяного знака
     * @param string $position Позиция водяного знака
     * @param int $offset Отступ водяного знака
     *
     * @return array
     */
    protected static function getWatermarkPos($image_width, $image_height, $wm_width, $wm_height, $position = 'rb', $offset = 0)
    {
        switch ( $position ) {
            case 'top-left':
            case 'left-top':
            case 'lt':
                $X = $offset;
                $Y = $offset;
                break;
            case 'bottom-left':
            case 'left-bottom':
            case 'lb':
                $X = $offset;
                $Y = $image_height - $wm_height - $offset;
                break;
            case 'top-right':
            case 'right-top':
            case 'rt':
                $X = $image_width - $wm_width - $offset;
                $Y = $offset;
                break;
            case 'center':
            case 'c':
                $X = ($image_width - $wm_width) / 2;
                $Y = ($image_height - $wm_height) / 2;
                break;
            case 'left':
            case 'lc':
                $X = $offset;
                $Y = ($image_height - $wm_height) / 2;
                break;
            case 'right':
            case 'rc':
                $X = $image_width - $wm_width - $offset;
                $Y = ($image_height - $wm_height) / 2;
                break;
            case 'top':
            case 'tc':
                $X = ($image_width - $wm_width) / 2;
                $Y = $offset;
                break;
            case 'bottom':
            case 'bc':
                $X = ($image_width - $wm_width) / 2;
                $Y = $image_height - $wm_height - $offset;
                break;
            default:
                $X = $image_width - $wm_width - $offset;
                $Y = $image_height - $wm_height - $offset;
                break;
        }

        return [ 'x' => $X, 'y' => $Y ];
    }

}
