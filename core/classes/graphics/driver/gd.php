<?php

namespace graphics\driver;

class gd extends \graphics\abstracts\driver
{

    public function __construct($image = null, $width = null, $height = null)
    {
        if ( is_resource($image) ) {
            $this->setImage($image, $width, $height);
        }
    }

    public static function checkAvailability()
    {
        return true;
    }

    public function __clone()
    {
        if ( is_resource($this->image) ) {
            $copied = $this->createGd($this->width, $this->height);

            imagecopy($copied, $this->image, 0, 0, 0, 0, $this->width, $this->height);

            $this->image = $copied;
        }
    }

    public function create($width, $height, $color = 0xffffff, $opacity = 0)
    {
        $this->destroy();

        $this->width  = $width;
        $this->height = $height;

        $this->image = $this->createGd($width, $height);

        $this->fillWithColor($color, $opacity);

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

        switch ( $info['type'] ) {
            case 'jpg':
                $this->image = imagecreatefromjpeg($file);
                break;
            case 'gif':
                $this->image = imagecreatefromgif($file);
                break;
            case 'png':
                $this->image = imagecreatefrompng($file);
                break;
            default:
                trigger_error('File is not a valid image', \E_USER_NOTICE);
                return false;
        }

        imagealphablending($this->image, false);

        $this->width  = $info['width'];
        $this->height = $info['height'];
        $this->info   = $info;

        $this->autoFlipRotate();

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

        $this->image = imagecreatefromstring($bytes);

        imagealphablending($this->image, false);

        $this->width  = $info['width'];
        $this->height = $info['height'];
        $this->info   = $info;

        $this->autoFlipRotate();

        return $this;
    }

    public function resize($width, $height, $mode = 'auto')
    {
        if ( $mode == 'crop' ) {
            return $this->fill($width, $height);
        }

        $size = \graphics\graphics::getDimensions($width, $height, $this->width, $this->height, $mode);

        if ( !empty($size) ) {
            $resized = $this->createGd($size['width'], $size['height']);

            imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $size['width'], $size['height'], $this->width, $this->height);

            $this->setImage($resized, $size['width'], $size['height']);
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

        $cropped = $this->createGd($width, $height);

        imagecopy($cropped, $this->image, 0, 0, $x, $y, $width, $height);

        $this->setImage($cropped, $width, $height);

        return $this;
    }

    public function flip($flipX = false, $flipY = false)
    {
        if ( $flipX || $flipY ) {
            $x = $flipX ? $this->width - 1 : 0;

            $width = ($flipX ? -1 : 1) * $this->width;

            $y = $flipY ? $this->height - 1 : 0;

            $height = ($flipY ? -1 : 1) * $this->height;

            $flipped = $this->createGd($this->width, $this->height);

            imagecopyresampled($flipped, $this->image, 0, 0, $x, $y, $this->width, $this->height, $width, $height);

            $this->setImage($flipped, $this->width, $this->height);
        }

        return $this;
    }

    public function rotate($angle, $bgColor = 0xffffff, $bgOpacity = 0)
    {
        $this->normalizeAngle($angle);

        $rotated = imagerotate($this->image, -$angle, $this->getColor($bgColor, $bgOpacity));

        imagealphablending($rotated, false);

        $this->setImage($rotated, imagesx($rotated), imagesy($rotated));

        return $this;
    }

    public function overlay($layer, $x = 0, $y = 0)
    {
        imagealphablending($this->image, true);

        imagecopy($this->image, $layer->image(), $x, $y, 0, 0, $layer->width, $layer->height);

        imagealphablending($this->image, false);

        return $this;
    }

    public function render($format = 'jpg', $quality = 90)
    {
        switch ( $format ) {
            case 'png':
                imagesavealpha($this->image, true);
                ob_start();
                imagepng($this->image, null, (9 - round($quality * 0.09)));
                return ob_get_clean();
            case 'jpeg':
            case 'jpg':
                $bg = $this->jpgBg($this->image);
                ob_start();
                imagejpeg($bg, null, $quality);
                imagedestroy($bg);
                return ob_get_clean();
            case 'gif':
                ob_start();
                imagegif($this->image);
                return ob_get_clean();
            default:
                trigger_error('Type must be either png, jpg or gif', \E_USER_NOTICE);
                return false;
        }
    }

    public function save($file, $format = 'jpg', $quality = 90)
    {
        if ( file_exists($file) ) {
            unlink($file);
        }

        $format = empty($format) ? $this->getExt($file) : $format;

        switch ( $format ) {
            case 'png':
                imagesavealpha($this->image, true);
                imagepng($this->image, $file, (9 - round($quality * 0.09)));
                break;
            case 'jpeg':
            case 'jpg':
                $bg = $this->jpgBg($this->image);
                imagejpeg($bg, $file, $quality);
                imagedestroy($bg);
                break;
            case 'gif':
                imagegif($this->image, $file);
                break;
            default:
                trigger_error('Type must be either png, jpg or gif', \E_USER_NOTICE);
                return false;
        }

        return true;
    }

    public function getPixel($x, $y)
    {
        $pixel = imagecolorat($this->image, $x, $y);

        $rgba = imagecolorsforindex($this->image, $pixel);

        $color = ($rgba['red'] << 16) + ($rgba['green'] << 8) + $rgba['blue'];

        $opacity = 1 - $rgba['alpha'] / 127;

        return new \graphics\pixel($x, $y, $color, $opacity);
    }

    public function fillWithColor($color, $opacity)
    {
        $color = $this->getColor($color, $opacity);

        imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $color);
    }

    protected function drawText($text, $size, $fontFile, $x, $y, $color, $opacity, $angle)
    {
        $this->normalizeAngle($angle);

        $size = floor($size * 72 / 96);

        $color = $this->getColor($color, $opacity);

        imagealphablending($this->image, true);

        imagettftext($this->image, $size, -$angle, $x, $y, $color, $fontFile, $text);

        imagealphablending($this->image, false);

        return $this;
    }

    protected function textMetrics($text, $size, $fontFile)
    {
        $size = floor($size * 72 / 96);

        $box = imagettfbbox($size, 0, $fontFile, $text);

        return array(
            'ascender'  => -$box[7],
            'descender' => $box[3],
            'width'     => $box[2] - $box[6],
            'height'    => $box[3] - $box[7]
        );
    }

    //========================================================================//

    /**
     * Автомаически отрожает и поворачивает изображение в соответствии с установленным
     * значением EXIF:Orientation вызывается в методах load и read
     */
    protected function autoFlipRotate()
    {
        if ( isset($this->info['exif']['orientation']) ) {
            switch ( $this->info['exif']['orientation'] ) {
                case 'TOPLEFT': break;
                case 'TOPRIGHT': $this->flipX();
                    break;
                case 'BOTRIGHT': $this->rotate(-180);
                    break;
                case 'BOTLEFT': $this->flipY();
                    break;
                case 'LEFTTOP': $this->flipY()->rotate(90);
                    break;
                case 'RIGHTTOP': $this->rotate(90);
                    break;
                case 'RIGHTBOT': $this->flipX()->rotate(90);
                    break;
                case 'LEFTBOT': $this->rotate(-90);
                    break;
            }
        }
    }

    /**
     * Replaces the image resource with a new image
     *
     * @param resource $image  Image resource
     * @param int      $width  New image width
     * @param int      $height New image height
     */
    protected function setImage($image, $width, $height)
    {
        if ( $this->image !== null ) {
            imagedestroy($this->image);
        }

        $this->image  = $image;
        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * Creates new GD Image
     *
     * @param int $width  Image width
     * @param int $height Image height
     *
     * @return resource New GD image resource
     */
    protected function createGd($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);

        imagealphablending($image, false);

        return $image;
    }

    /**
     * Создает копию изображения с белым фоном для сохранения в JPEG формате
     * @return resource изображение с белым фоном
     */
    protected function jpgBg()
    {
        $bg = $this->createGd($this->width, $this->height);

        imagefilledrectangle($bg, 0, 0, $this->width, $this->height, $this->getColor(0xffffff, 1));

        imagealphablending($bg, true);

        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->width, $this->height);

        imagealphablending($bg, false);

        return $bg;
    }

    protected function getColor($color, $opacity)
    {
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;
        return imagecolorallocatealpha($this->image, $r, $g, $b, 127 * (1 - $opacity));
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

    protected function destroy()
    {
        if ( is_resource($this->image) ) {
            imagedestroy($this->image);
        }

        parent::destroy();
    }

    public function __destruct()
    {
        $this->destroy();
    }

}
