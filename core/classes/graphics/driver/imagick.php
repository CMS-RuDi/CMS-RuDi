<?php

namespace graphics\driver;

class imagick extends \graphics\abstracts\driver
{

    /**
     * Класс обрабоки изображения
     *
     * @var string
     */
    protected static $imageClass = '\\Imagick';

    /**
     * Класс рисования
     *
     * @var string
     */
    protected $drawClass = '\\ImagickDraw';

    /**
     * Режим наложения
     *
     * @var int
     */
    protected $compositionMode;

    public function __construct($image = null, $width = null, $height = null)
    {
        if ( $image instanceof self::$imageClass ) {
            $this->image  = $image;
            $this->width  = $width ? $width : $this->image->getImageWidth();
            $this->height = $height ? $height : $this->image->getImageHeight();
        }

        $this->compositionMode = \Imagick::COMPOSITE_OVER;
    }

    public static function checkAvailability()
    {
        return class_exists(self::$imageClass);
    }

    public function __clone()
    {
        if ( $this->image instanceof self::$imageClass ) {
            $this->image = clone $this->image;
        }
    }

    /**
     * Обновляет размеры
     *
     * @param int $width  Ширина
     * @param int $height Высота
     */
    protected function updateSize($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;
    }

    public function create($width, $height, $color = 0xffffff, $opacity = 0)
    {
        $this->destroy();

        $this->width  = $width;
        $this->height = $height;

        $this->image = new self::$imageClass();
        $this->image->newImage($width, $height, $this->getColor($color, $opacity));

        return $this;
    }

    public function read($file)
    {
        $this->destroy();

        $info = \graphics\graphics::getImgInfo($file);

        if ( !$info ) {
            trigger_error('File is not a valid image', \E_USER_NOTICE);
            return false;
        }

        $this->image = new self::$imageClass($file);

        $this->width  = $info['width'];
        $this->height = $info['height'];
        $this->info   = $info;

        return $this;
    }

    public function load($bytes)
    {
        $this->destroy();

        $info = \graphics\graphics::getImgInfo($bytes, true);

        if ( !$info ) {
            trigger_error('File is not a valid image', \E_USER_NOTICE);
            return false;
        }

        $this->image = new self::$imageClass();

        $this->image->readImageBlob($bytes);

        $this->width  = $info['width'];
        $this->height = $info['height'];
        $this->info   = $info;

        return $this;
    }

    public function resize($width, $height, $mode = 'auto')
    {
        if ( $mode == 'crop' ) {
            return $this->fill($width, $height);
        }

        $size = \graphics\graphics::getDimensions($width, $height, $this->width, $this->height, $mode);

        if ( !empty($size) ) {
            $this->image->scaleimage($size['width'], $size['height']);
            $this->updateSize($size['width'], $size['height']);
        }

        return $this;
    }

    public function crop($width, $height, $x = 0, $y = 0)
    {
        if ( $width > ($maxWidth = $this->width - $x) ) {
            $width = $maxWidth;
        }

        if ( $height > ($maxHeight = $this->height - $y) ) {
            $height = $maxHeight;
        }

        $this->image->cropImage($width, $height, $x, $y);
        $this->updateSize($width, $height);

        return $this;
    }

    public function flip($flipX = false, $flipY = false)
    {
        if ( $flipX ) {
            $this->image->flopImage();
        }

        if ( $flipY ) {
            $this->image->flipImage();
        }

        return $this;
    }

    public function rotate($angle, $bgColor = 0xffffff, $bgOpacity = 0)
    {
        $this->normalizeAngle($angle);

        $this->image->rotateImage($this->getColor($bgColor, $bgOpacity), $angle);

        $this->updateSize($this->image->getImageWidth(), $this->image->getImageHeight());

        return $this;
    }

    public function overlay($layer, $x = 0, $y = 0)
    {
        $layerCs = $layer->image->getImageColorspace();

        $layer->image->setImageColorspace($this->image->getImageColorspace());

        $this->image->compositeImage($layer->image(), $this->compositionMode, $x, $y);

        $layer->image->setImageColorspace($layerCs);

        return $this;
    }

    public function render($format = 'jpg', $quality = 90)
    {
        switch ( $format ) {
            case 'png':
            case 'gif':
                $image = clone $this->image;
                $image->setImageFormat($format);
                break;
            case 'jpeg':
            case 'jpg':
                $image = $this->jpgBg();
                break;
            default:
                trigger_error('Type must be either png, jpg or gif', \E_USER_NOTICE);
                return false;
        }

        $image->setImageCompressionQuality($quality);

        return (string) $image;
    }

    public function save($file, $format = 'jpg', $quality = 90)
    {
        if ( file_exists($file) ) {
            unlink($file);
        }

        $format = empty($format) ? $this->getExt($file) : $format;

        switch ( $format ) {
            case 'png':
            case 'gif':
                $image = clone $this->image;
                $image->setImageFormat($format);
                break;
            case 'jpeg':
            case 'jpg':
                $image = $this->jpgBg();
                break;
            default:
                trigger_error('Type must be either png, jpg or gif', \E_USER_NOTICE);
                return false;
        }

        $image->setImageCompressionQuality($quality);

        $image->writeImage($file);

        return true;
    }

    public function getPixel($x, $y)
    {
        $pixel = $this->image->getImagePixelColor($x, $y);

        $color = $pixel->getColor();

        $normalizedColor = $pixel->getColor(true);

        $color = ($color['r'] << 16) + ($color['g'] << 8) + $color['b'];

        $opacity = $normalizedColor['a'];

        return new \graphics\pixel($x, $y, $color, $opacity);
    }

    protected function drawText($text, $size, $fontFile, $x, $y, $color, $opacity, $angle)
    {
        $this->normalizeAngle($angle);

        $draw = new $this->drawClass();

        $draw->setFont($fontFile);

        $draw->setFontSize($size);

        $draw->setFillColor($this->getColor($color, $opacity));

        $this->image->annotateImage($draw, $x, $y, $angle, $text);

        return $this;
    }

    protected function textMetrics($text, $size, $fontFile)
    {
        $draw = new $this->drawClass();

        $draw->setFont($fontFile);
        $draw->setFontSize($size);

        $metrics = $this->image->queryFontMetrics($draw, $text, true);

        return array(
            'ascender'  => floor($metrics['boundingBox']['y2']),
            'descender' => floor(-$metrics['boundingBox']['y1']),
            'width'     => floor($metrics['textWidth']),
            'height'    => floor($metrics['boundingBox']['y2'] - $metrics['boundingBox']['y1']),
        );
    }

    //========================================================================//

    /**
     * Создает копию изображения с белым фоном для сохранения в JPEG формате
     * @return resource изображение с белым фоном
     */
    protected function jpgBg()
    {
        $bg = new self::$imageClass();

        $bg->newImage($this->width, $this->height, $this->getColor(0xffffff, 1));

        $bg->compositeImage($this->image, $this->compositionMode, 0, 0);

        $bg->setImageFormat('jpeg');

        return $bg;
    }

    protected function getColor($color, $opacity)
    {
        $color   = str_pad(dechex($color), 6, '0', \STR_PAD_LEFT);
        $opacity = str_pad(dechex(floor(255 * $opacity)), 2, '0', \STR_PAD_LEFT);

        return '#' . $color . $opacity;
    }

    /**
     * Возвращает расширение файла
     *
     * @param string $file Путь к файлу
     *
     * @return string
     */
    protected function getExt($file)
    {
        $ext = strtolower(pathinfo($file, \PATHINFO_EXTENSION));

        if ( $ext == 'jpeg' ) {
            $ext = 'jpg';
        }

        return $ext;
    }

    public function destroy()
    {
        if ( $this->image instanceof self::$imageClass ) {
            $this->image->destroy();
        }

        parent::destroy();
    }

    public function __destruct()
    {
        $this->destroy();
    }

}
