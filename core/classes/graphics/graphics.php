<?php

namespace graphics;

/**
 * Класс обработки графики
 *
 * Предназначен для изменение размеров изображений, получения информации
 * об изображении нанесение водяного знака и других операций с изображениями
 *
 * @author DS Soft <support@ds-soft.ru>
 * @version 0.3.1
 */
class graphics
{

    protected static $support_types = [ 1 => 'gif', 2 => 'jpg', 3 => 'png' ];
    protected $dir                  = false;

    /**
     * Список правил изменения размеров изображений, используется для последующего редактирования конкретного правила
     *
     * name        - идентификатор правила
     * filename    - название файла
     * prefix      - префикс перед названием файла используется только если не указан filename
     * postfix     - постфикс после названия файла используется только если не указан filename
     * width       - ширина изображения
     * height      - высота изображения
     * folder      - папка сохранения, либо абсолютный путь либо относительный от $dir
     * resize_type - режим изменения размера (auto, crop, exact, landscape, portrait)
     * quality     - качество изображения
     * watermark   - array(
     *      enable   - включено или отключено в данный момент нанесение водяного знака,
     *      file     - ссылка на водяной знак,
     *      position - положение водяного знака lt(left-top,top-left) - левый верхний угол, tc(top) - сверху в центре, rt(top-right,right-top) - правый верхний угол, lc(left) - в центре слева, cc(center) - в центре, rc(right) - в центре справа, lb(bottom-left,left-bottom) - левый нижний угол, bc(bottom) - снизу в середине, rb(bottom-right,right-bottom) - правый нижний угол
     *      offset   - смещение водяного знака от края основного изображения
     *      width    - ширина
     *      height   - высота
     * )
     *
     * @var array
     */
    protected $rules   = [];
    protected $quality = 90;
    protected static $wmhash;
    protected static $wm;

    /**
     * Инициализирует класс
     *
     * @param array $rules
     */
    public function __construct($rules = [])
    {
        $this->setRules($rules);
    }

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
     * Выставляет массив правил для обработки изображений
     *
     * @param array $rules
     *
     * @return $this
     */
    public function setRules($rules)
    {
        $this->clearRules();

        foreach ( $rules as $rule ) {
            $this->addRule($rule);
        }

        return $this;
    }

    /**
     * @see \graphics\graphics::setRules
     * @deprecated 0.3.9
     */
    public function setSizes($rules)
    {
        return $this->setRules($rules);
    }

    /**
     * Добавляет опции для обрезки размера изображения
     *
     * @param array $rule
     *
     * @return $this
     */
    public function addRule($rule)
    {
        $this->checkRule($rule);

        $this->rules[$rule['name']] = $rule;

        return $this;
    }

    /**
     * @see \graphics\graphics::addSize
     * @deprecated 0.3.9
     */
    public function addSize($rule)
    {
        return $this->addRule($rule);
    }

    /**
     * Очищает список опций для изменения размера изображения
     *
     * @return $this
     */
    public function clearRules()
    {
        $this->rules = [];

        return $this;
    }

    /**
     * Удаляет указанное правило
     *
     * @param string $name
     *
     * @return $this
     */
    public function deleteRule($name)
    {
        unset($this->rules[$name]);

        return $this;
    }

    /**
     * @see \graphics\graphics::clearRules
     * @deprecated 0.3.9
     */
    public function clearSizes()
    {
        return $this->clearRules();
    }

    /**
     * Позволяет отредактировать правило
     *
     * @param string $name
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function editRule($name, $key, $value)
    {
        if ( is_array($value) ) {
            foreach ( $value as $k => $v ) {
                $this->rules[$name][$k] = $v;
            }
        }
        else {
            $this->rules[$name] = $value;
        }

        return $this;
    }

    /**
     * Приводит старый формат опций в новый
     *
     * @param array $rule
     * @param bool $multiple
     *
     * @return array
     */
    protected function checkRules(&$rule)
    {
        if ( isset($rule['watermark']) && !is_array($rule['watermark']) ) {
            $wm = [
                'enable'   => true,
                'file'     => $rule['watermark'],
                'position' => isset($rule['watermark_pos']) ? $rule['watermark_pos'] : 'rb',
                'offset'   => isset($rule['watermark_offset']) ? $rule['watermark_offset'] : 0,
                'width'    => null,
                'height'   => null
            ];

            unset($rule['watermark_pos'], $rule['watermark_offset']);

            $rule['watermark'] = $wm;
        }
        else {
            $rule['watermark'] = array_merge([ 'enable' => true, 'position' => 'rb', 'offset' => 0, 'width' => null, 'height' => null ], $rule['watermark']);
        }

        $rule['width']       = !empty($rule['width']) ? (int) $rule['width'] : 0;
        $rule['height']      = !empty($rule['height']) ? (int) $rule['height'] : 0;
        $rule['resize_type'] = !empty($rule['resize_type']) ? $rule['resize_type'] : 'auto';

        if ( !isset($rule['name']) ) {
            $rule['name'] = $rule['width'] . 'x' . $rule['height'] . ':' . $rule['resize_type'];
        }

        if ( !isset($rule['enable']) ) {
            $rule['enable'] = true;
        }
    }

    /**
     * Выставляет директорию сохранения изображений
     *
     * @param string $dir
     *
     * @return $this
     */
    public function setDir($dir)
    {
        $this->dir = rtrim($dir, '/ \\');

        return $this;
    }

    //========================================================================//

    /**
     * Изменяет размер изображения в соответствии с правилами обработки добавленными методами setRules и addRule
     *
     * @param string|array $file_path Ссылка на файл изображения или массив таких изображений
     * @param string $driver Класс обработки изображения
     *
     * @return boolean|array
     */
    public function startResize($file_path, $idriver = 'imagick')
    {
        if ( empty($this->rules) ) {
            return false;
        }

        if ( is_array($file_path) ) {
            if ( !isset($file_path['data']) ) {
                $result = [];

                foreach ( $file_path as $file ) {
                    $r               = $this->startResize($file, $idriver);
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

        $rules = [];

        foreach ( $this->rules as $k => $rule ) {
            if ( !$rule['enable'] ) {
                continue;
            }

            $image = clone $driver;

            $format = $info['type'];

            if ( !isset($rule['folder']) ) {
                $rule['folder'] = $this->dir;
            }
            else if ( !file_exists($rule['folder']) ) {
                if ( file_exists($this->dir . '/' . trim($rule['folder'], '\\/ ')) ) {
                    $rule['folder'] = $this->dir . '/' . trim($rule['folder'], '\\/ ');
                }
                else {
                    $rule['folder'] = $this->dir;
                }
            }

            if ( isset($rule['filename']) ) {
                if ( !mb_strstr($rule['filename'], '.') ) {
                    $rule['filename'] .= '.' . $format;
                }
                else {
                    $temp = explode('.', $rule['filename']);

                    if ( in_array($temp[count($temp) - 1], self::$support_types) ) {
                        $format = $temp[count($temp) - 1];
                    }

                    unset($temp);
                }
            }
            else {
                $rule['filename'] = (isset($rule['prefix']) ? $rule['prefix'] : '') . $filename . (isset($rule['postfix']) ? $rule['postfix'] : '') . '.' . $format;
            }

            // Изменяем размер изображения
            $image->resize($rule['width'], $rule['height'], $rule['resize_type']);

            if ( $rule['watermark']['enable'] ) {
                // Накладываем водяной знак
                self::addWatermark(
                        $image, $rule['watermark']['file'], $rule['watermark']['position'], $rule['watermark']['offset'], $idriver, $rule['watermark']['width'], $rule['watermark']['height']
                );
            }

            // Сохраняем отредактированное изображение
            $rule['result'] = $image->save($rule['folder'] . '/' . $rule['filename'], $format, (isset($rule['quality']) ? $rule['quality'] : $this->quality));

            unset($image);

            $rules[$k] = $rule;
        }

        return $rules;
    }

    /**
     * Добавляет на изображение водяной знак и возвращает объект класса для последующей обработки или сохранения
     *
     * @param mixed $image Путь к изображению, массив с ключом data с строкой байтов изображения или объект драйвера с загруженным изображением
     * @param mixed $wmfile Путь к изображению водяного знага или массив с ключом data с строкой байтов
     * @param string $position Позиция водяного занака
     * @param string $offset Отступ от границ изображения
     * @param string $idriver Название драйвера обработчика изображения
     * @param int|null $wm_width Ширина водяного знака на изображении
     * @param int|null $wm_height Высота водяного знака на изображении
     *
     * @return object Возвращает объект драйвера с загруженным изображением и нанесенным водяным знаком
     */
    public static function addWatermark($image, $wmfile, $position = 'rb', $offset = 0, $idriver = 'imagick', $wm_width = null, $wm_height = null)
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

        if ( !empty($wm_width) || !empty($wm_height) ) {
            $wm->resize($wm_width, $wm_height);
        }

        $ws = ($image->width() - 2 * $offset) / $wm->width();
        $hs = ($image->height() - 2 * $offset) / $wm->height();

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

        self::checkS($ws, $hs);

        $min = min($ws, $hs);
        $max = max($ws, $hs);

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
