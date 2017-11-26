<?php

namespace graphics\abstracts;

abstract class driver
{

    protected $image;
    protected $width;
    protected $height;
    protected $info;

    public abstract function __construct($image = null, $width = null, $height = null);

    /**
     * Проверяет и возвращает статус доступности библиотеки обработки изображения
     * соответствующего текущему драйверу
     *
     * @return bool
     */
    public static function checkAvailability()
    {
        return false;
    }

    /**
     * При клонировании необходимо чтобы все ресурсы передаваемые по ссылке были
     * так же корректно склонированы как новые ресурсы
     */
    public abstract function __clone();

    public function destroy()
    {
        $this->image  = null;
        $this->width  = null;
        $this->height = null;
        $this->info   = [];
    }

    /**
     * Создает изображение с заданной шириной, высотой и цветом
     *
     * @param int $width ширина
     * @param int $height высота
     * @param hec $color цвет заливки
     * @param float $opacity непрозрачность
     *
     * @return $this
     */
    public abstract function create($width, $height, $color = 0xffffff, $opacity = 0);

    /**
     * Загружает изображение по ссылке на файл
     *
     * @param string $file ссылка на файл изображение
     *
     * @return $this
     */
    public abstract function read($file);

    /**
     * Загружает изображение из строки данных
     *
     * @param string $bytes строка с данными изображения
     *
     * @return $this
     */
    public abstract function load($bytes);

    /**
     * Возвращает строку байтов изображения или false при вознокновении ошибки
     *
     * @return string
     */
    public abstract function render($format = 'jpg', $quality = 90);

    /**
     * Сохраняет отредактированное изображение
     *
     * @param string $file ссылка для сохранения
     * @param string $format формат файла изображения для сохранения
     * @param int $quality качество сохраняемого файла в процентах (100 без сжатия)
     *
     * @return bool true если файл успешно сохранен и false если произошла ошибка
     */
    public abstract function save($file, $format = 'jpg', $quality = 90);

    //========================================================================//

    /**
     * Изменяет размеры изображения согласно переданному множителю
     *
     * @param float $scale
     *
     * @return $this
     */
    public function scale($scale)
    {
        $width  = ceil($this->width * $scale);
        $height = ceil($this->height * $scale);

        return $this->resize($width, $height, 'exact');
    }

    /**
     * Изменяет размеры изображения на указанные
     *
     * @param int $width Ширина
     * @param int $height Высота
     * @param int $mode Режим изменения размеров
     *
     * @return $this
     */
    public abstract function resize($width, $height, $mode = 'auto');

    /**
     * Обрезает изображение до указанных размеров, вырезаемый кусок предварительно
     * центрируется
     *
     * @param int $width Ширина обрезки
     * @param int $height Высота обрезки
     *
     * @return $this
     */
    public function fill($width, $height)
    {
        $this->resize($width, $height);

        $x = round(($this->width - $width) / 2);
        $y = round(($this->height - $height) / 2);

        $this->crop($width, $height, $x, $y);

        return $this;
    }

    /**
     * Обрезае изображение до указанных размеров
     *
     * @param int $width ширина
     * @param int $height высота
     * @param int $x координа X начала обрезки
     * @param int $y координата Y начала обрезки
     *
     * @return $this
     */
    public abstract function crop($width, $height, $x = false, $y = false);

    /**
     * Отражает изображение по осям X или Y или по обоим сразу
     *
     * @param  bool $flipX Отражать по X
     * @param bool $flipY Отражать по Y
     *
     * @return $this
     */
    public abstract function flip($flipX = false, $flipY = false);

    /**
     * Поворачивает изображение по часовой стрелке на указанное число градусов
     *
     * @param float $angle Угол поворота в градусах
     * @param int $bgColor Цвет фона
     * @param int $bgOpacity Непрозрачность фона
     *
     * @return $this
     */
    public abstract function rotate($angle, $bgColor = 0xffffff, $bgOpacity = 0);

    /**
     * Накладывает переданное изображение поверх текущего.
     *
     * @param \graphics\driver $layer Объект текущего класса с изображением для наложения
     * @param int $x X координата наложения
     * @param int $y Y координата наложения
     *
     * @return $this
     */
    public abstract function overlay($layer, $x = 0, $y = 0);

    /**
     * Отражает изображение по оси X
     *
     * @return $this
     */
    public function flipX()
    {
        return $this->flip(true);
    }

    /**
     * Отражает изображение по оси Y
     *
     * @return $this
     */
    public function flipY()
    {
        return $this->flip(false, true);
    }

    /**
     * Отражает изображение по осям X и Y
     *
     * @return $this
     */
    public function flipXY()
    {
        return $this->flip(true, true);
    }

    protected function normalizeAngle(&$angle)
    {
        $angle = $angle < -360 ? $angle - ceil($angle / 360) : $angle;
        $angle = $angle > 360 ? $angle - ceil($angle / 360) : $angle;
    }

    //========================================================================//
    /**
     * Вычисляет метрику указанного текста
     *
     * Возвращает массив с ключами 'width', 'height', 'ascender' и 'descender'.
     *
     * @param string $text Текст
     * @param int $size Размер шрифта
     * @param string $fontFile Путь к файлу шрифта
     *
     * @return array
     */
    protected abstract function textMetrics($text, $size, $fontFile);

    /**
     * Рисует текст над изображением.
     *
     * @param string $text Текст
     * @param int $size Размер шрифта
     * @param string $fontFile Путь к файлу шрифта
     * @param int $x X координата базовой линии первой строки текста
     * @param int $y Y координата базовой линии первой строки текста
     * @param int $color Цвет текста (например 0xffffff)
     * @param float $opacity Непрозрачность текста
     * @param float $angle Угол поворота текста по часовой стрелки
     *
     * @return \PHPixie\Image\Resource Returns self
     */
    protected abstract function drawText($text, $size, $fontFile, $x, $y, $color, $opacity, $angle);

    /**
     * Обертывает текст в строки, которые будут соответствовать указанной ширине
     *
     * @param string $text Текст
     * @param int $size Размер шрифта
     * @param string $fontFile Путь к файлу шрифта
     * @param int $width Ширина в пикселях, чтобы соответствовать тексту
     *
     * @return string
     */
    protected function wrapText($text, $size, $fontFile, $width)
    {
        $blocks = explode("\n", $text);
        $lines  = array();

        foreach ( $blocks as $block ) {
            $words     = explode(' ', $block);
            $line      = '';
            $lineWidth = 0;

            foreach ( $words as $key => $word ) {
                $prefix    = $line == '' ? '' : ' ';
                $box       = $this->textMetrics($prefix . $word, $size, $fontFile);
                $wordWidth = $box['width'];

                if ( $line == '' || $lineWidth + $wordWidth < $width ) {
                    $line      .= $prefix . $word;
                    $lineWidth += $wordWidth;
                }
                else {
                    $lines[]   = $line;
                    $line      = $word;
                    $box       = $this->textMetrics($word, $size, $fontFile);
                    $lineWidth = $box['width'];
                }
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Рассчитывает смещение между двумя строками текста на основе размера шрифта
     * и интервала строк.
     *
     * @param int $size Размер шрифта
     * @param int $lineSpacing Межстрочный интервал
     *
     * @return int
     */
    protected function baseLineOffset($size, $lineSpacing)
    {
        return $size * $lineSpacing;
    }

    /**
     * Вычисляет метрику указанного текста
     *
     * Принимает во внимание расстояние между линиями.
     * Получает ширину, высоту, восхождение первой строки текста и нисхождение
     * последней.
     *
     * @param string $text Текст
     * @param int $size Размер шрифта
     * @param string $fontFile Путь к файлу шрифта
     * @param int
     *
     * @return array
     */
    public function textSize($text, $size, $fontFile, $lineSpacing = 1)
    {
        $lines          = explode("\n", $text);
        $box            = null;
        $ascender       = 0;
        $baselineOffset = $this->baselineOffset($size, $lineSpacing);

        foreach ( $lines as $k => $line ) {
            $lineBox = $this->textMetrics($line, $size, $fontFile);

            if ( $box == null ) {
                $box      = $lineBox;
                $ascender = $lineBox['ascender'];
            }
            else {
                $box['width']     = $lineBox['width'] > $box['width'] ? $lineBox['width'] : $box['width'];
                $box['descender'] = $lineBox['descender'];
                $box['height']    = $ascender + $k * $baselineOffset + $lineBox['descender'];
            }
        }

        return $box;
    }

    /**
     * Рисует текст поверх изображения
     *
     * @param string $text Текс
     * @param int $size Размер шрифта
     * @param string $fontFile Путь к файлу шрифта
     * @param int $x X координата базовой линии первой строки текста
     * @param int $y Y координата базовой линии первой строки текста
     * @param int $color Цвет текста (например 0xffffff)
     * @param float $opacity Непрозрачность текста
     * @param int $wrapWidth Ширина для обертывания текста. Null означает отсутствие упаковки.
     * @param int $lineSpacing Межстрочный интервал
     * @param float $angle Угол поворота текста по часовой стрелке
     *
     * @return $this
     */
    public function text($text, $size, $fontFile, $x, $y, $color = 0x000000, $opacity = 1, $wrapWidth = null, $lineSpacing = 1, $angle = 0)
    {
        if ( $wrapWidth != null ) {
            $text = $this->wrapText($text, $size, $fontFile, $wrapWidth);
        }

        $lines    = explode("\n", $text);
        $offset_x = 0;
        $offset_y = 0;
        $baseline = $this->baselineOffset($size, $lineSpacing);

        foreach ( $lines as $line ) {
            $this->drawText($line, $size, $fontFile, $x + $offset_x, $y + $offset_y, $color, $opacity, $angle);

            $rad      = deg2rad($angle);
            $offset_x += sin($rad) * $baseline;
            $offset_y += cos($rad) * $baseline;
        }

        return $this;
    }

    /**
     * Получает цвет пикселя в заданных координатах.
     *
     * Возвращает массив с ключами 'color' и 'opacity'
     *
     * @param int $x X координата
     * @param int $y Y координата
     *
     * @return array
     */
    public abstract function getPixel($x, $y);

    /**
     * Получает цветовое представление драйвера
     *
     * @param int $color Цвет
     * @param float $opacity Непрозрачность
     *
     * @return mixed
     */
    protected abstract function getColor($color, $opacity);

    //========================================================================//

    /**
     * Возвращает $image который в зависимости от используемого драйвера может
     * быть ресурсом изображения или объектом
     *
     * @return mixed
     */
    public function image()
    {
        return $this->image;
    }

    /**
     * Возвращает текущую ширину изображения
     *
     * @return int
     */
    public function width()
    {
        return $this->width;
    }

    /**
     * Возвращает текущую высоту изображения
     *
     * @return int
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * Возвращает информацию об загруженном изображении
     *
     * @return array
     */
    public function info()
    {
        return $this->info;
    }

}
