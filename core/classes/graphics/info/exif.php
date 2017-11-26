<?php

namespace graphics\info;

class exif extends \graphics\abstracts\info
{

    protected $map   = [
        // Апертурное число объектива
        'aperture'             => [ 'FNumber', 'ApertureFNumber' ],
        // Диафрагма
        'apertureValue'        => 'ApertureValue',
        // Время экспозиции
        'exposure'             => 'ExposureTime',
        // Режим экспозиции
        'exposureMode'         => 'ExposureMode',
        // Программа экспозиции
        'exposureProgram'      => 'ExposureProgram',
        // Выдержка в APEX
        'shutterSpeedValue'    => 'ShutterSpeedValue',
        // Производитель и модель камеры
        'camera'               => 'Model',
        // Производитель камеры
        'camera_make'          => 'Make',
        // Модель камеры
        'camera_model'         => 'Model',
        // Програмное обеспечение
        'software'             => 'Software',
        // Дата съемки
        'date'                 => 'DateTimeOriginal',
        // Дата создания цифрового файла
        'creationdate'         => 'DateTimeDigitized',
        // Дата последнего изменения изображения
        'editdate'             => 'DateTime',
        // Светочувствительность ISO
        'iso'                  => 'ISOSpeedRatings',
        // Фокусное расстояние объектива на камере
        'focalLength'          => 'FocalLength',
        // Расстояние фокусировки
        'focusDistance'        => 'FocusDistance',
        // Ориентация изображения относительно строк и столбцов.
        'orientation'          => 'Orientation',
        // Разрешение по горизонтали (Х координате)
        'horizontalResolution' => 'XResolution',
        // Разрешение по вертикали (У координате)
        'verticalResolution'   => 'YResolution',
        // in, sm единица длины, на которую приведено разрешение, «in» дюймы, «sm» сантиметры
        'resolutionUnit'       => 'ResolutionUnit',
        // Цветовое пространство
        'colorSpace'           => 'ColorSpace',
        // Ширина
        'width'                => 'ExifImageWidth',
        // Высота
        'height'               => 'ExifImageLength',
        // Размер в байтах
        'size'                 => 'FileSize',
        // Mime тип
        'mime'                 => 'MimeType',
        // Широта
        'latitude'             => 'GPSLatitude',
        // Широта в формате градусы минуты секунды
        'GPSLatitude'          => 'GPSLatitude',
        // Долгота
        'longitude'            => 'GPSLongitude',
        // Долгота в формате градусы минуты секунды
        'GPSLongitude'         => 'GPSLongitude',
        'fileName'             => 'FileName',
        // Название
        'name'                 => 'DocumentName',
        // Описание
        'description'          => 'ImageDescription',
        // YCbCr позиционирование
        'YCbCrPositioning'     => 'YCbCrPositioning ',
        // Значение яркости
        'BrightnessValue'      => 'BrightnessValue',
        // Режим замера
        'MeteringMode'         => 'MeteringMode',
        // Указывает на состояние вспышки при съемке изображения
        'flashMode'            => 'Flash',
        // Указывает тип датчика изображения на камере или устройстве ввода
        'SensingMethod'        => 'SensingMethod',
        // Указывает режим баланса белого, установленный при съемке изображения.
        'WhiteBalance'         => 'WhiteBalance',
        // Указывает на тип сцены
        'SceneCaptureType'     => 'SceneCaptureType',
        // Версия FlashPix
        'FlashPixVersion'      => 'FlashPixVersion',
        // Версия Exif
        'ExifVersion'          => 'ExifVersion',
    ];
    protected $modes = [
        'ExposureMode'     => [
            // Auto exposure - Автоматическая
            0 => 'Auto',
            // Manual exposure - Ручная
            1 => 'Manual',
            // Auto bracket
            2 => 'Auto bracket',
        ],
        'ExposureProgram'  => [
            // Not defined - Неопределено
            0 => 'Not defined',
            // Manual - Ручная
            1 => 'Manual',
            // Normal program - Стандартная
            2 => 'Normal',
            // Aperture priority - Приоритет диафрагмы
            3 => 'Aperture Priority',
            // Shutter priority - Приоритет выдержки
            4 => 'Shutter Priority',
            // Creative program (biased toward depth of field) - Глубина резкости
            5 => 'Creative',
            // Action program (biased toward fast shutter speed) - Спортивный режим
            6 => 'Action',
            // Portrait mode (for closeup photos with the background out of focus) - Портретный режим
            7 => 'Portrait',
            // Landscape mode (for landscape photos with the background in focus) - Ландшафтный режим
            8 => 'Landscape',
        ],
        'Orientation'      => [
            // The 0th row represents the visual top of the image, and the 0th column represents the visual left-hand side.
            1 => 'TOPLEFT',
            // The 0th row represents the visual top of the image, and the 0th column represents the visual right-hand side.
            2 => 'TOPRIGHT',
            // The 0th row represents the visual bottom of the image, and the 0th column represents the visual right-hand side.
            3 => 'BOTRIGHT',
            // The 0th row represents the visual bottom of the image, and the 0th column represents the visual left-hand side.
            4 => 'BOTLEFT',
            // The 0th row represents the visual left-hand side of the image, and the 0th column represents the visual top.
            5 => 'LEFTTOP',
            // The 0th row represents the visual right-hand side of the image, and the 0th column represents the visual top.
            6 => 'RIGHTTOP',
            // The 0th row represents the visual right-hand side of the image, and the 0th column represents the visual bottom.
            7 => 'RIGHTBOT',
            // The 0th row represents the visual left-hand side of the image, and the 0th column represents the visual bottom.
            8 => 'LEFTBOT'
        ],
        'MeteringMode'     => [
            // Unknown - Неизвестно
            0   => 'Unknown',
            // Average - Среднее
            1   => 'Average',
            // CenterWeightedAverage - Центральный фокус со средними замерами
            2   => 'CenterWeightedAverage',
            // Spot - По точкам
            3   => 'Spot',
            // MultiSpot - Область
            4   => 'MultiSpot',
            // Pattern - Оценочный
            5   => 'Pattern',
            // Partial - Локальный
            6   => 'Partial',
            // other - Другое
            255 => 'other',
        ],
        'Flash'            => [
            // Вспышка не сработала
            0  => 'Flash did not fire',
            // Вспышка сработала
            1  => 'Flash fired',
            // Возвратный свет не обнаружен
            5  => 'Strobe return light not detected',
            // Возвратный свет обнаружен
            7  => 'Strobe return light detected',
            // Вспышка сработала, включена принудительно
            9  => 'Flash fired, compulsory flash mode',
            // Вспышка сработала, включена принудительно, возвратный свет не обнаружен
            13 => 'Flash fired, compulsory flash mode, return light not detected',
            // Вспышка сработала, включена принудительно, возвратный свет обнаружен
            15 => 'Flash fired, compulsory flash mode, return light detected',
            // Вспышка не сработала, отключена принудительно
            16 => 'Flash did not fire, compulsory flash suppression mode',
            // Вспышка не сработала, автоматический режим
            24 => 'Flash did not fire, auto mode',
            // Вспышка сработала, автоматический режим
            25 => 'Flash fired, auto mode',
            // Вспышка сработала, автоматический режим, возвратный свет не обнаружен
            29 => 'Flash fired, auto mode, return light not detected',
            // Вспышка сработала, автоматический режим, возвратный свет обнаружен
            31 => 'Flash fired, auto mode, return light detected',
            // Нет функции вспышки
            32 => 'No flash function',
            // Вспышка сработала, режим подавления красных глаз
            65 => 'Flash fired, red-eye reduction mode',
            // Вспышка сработала, режим подавления красных глаз, возвратный свет не обнаружен
            69 => 'Flash fired, red-eye reduction mode, return light not detected',
            // Вспышка сработала, режим подавления красных глаз, возвратный свет обнаружен
            71 => 'Flash fired, red-eye reduction mode, return light detected',
            // Вспышка сработала, включена принудительно, режим подавления красных глаз
            73 => 'Flash fired, compulsory flash mode, red-eye reduction mode',
            // Вспышка сработала, включена принудительно, режим подавления красных глаз, возвратный свет не обнаружен
            77 => 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected',
            // Вспышка сработала, включена принудительно, режим подавления красных глаз, возвратный свет обнаружен
            79 => 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected',
            // Вспышка сработала, автоматический режим, режим подавления красных глаз
            89 => 'Flash fired, auto mode, red-eye reduction mode',
            // Вспышка сработала, автоматический режим, режим подавления красных глаз, возвратный свет не обнаружен
            93 => 'Flash fired, auto mode, return light not detected, red-eye reduction mode',
            // Вспышка сработала, автоматический режим, режим подавления красных глаз, возвратный свет обнаружен
            95 => 'Flash fired, auto mode, return light detected, red-eye reduction mode',
        ],
        'SensingMethod'    => [
            // Not defined - Не определен
            1 => 'Not defined',
            // One-chip color area sensor - Одночиповый датчик
            2 => 'One-chip',
            // Two-chip color area sensor - 1х чиповый датчик
            3 => 'Two-chip',
            // Three-chip color area sensor - 3х чиповый датчик
            4 => 'Three-chip',
            // Color sequential area sensor - Цветной последовательный по площади датчик
            5 => 'Color sequential area sensor',
            // Trilinear sensor - Трехлинейный датчик
            7 => 'Trilinear',
            // Color sequential linear sensor - Цветной последовательный линейный датчик
            8 => 'Color sequential linear sensor',
        ],
        'WhiteBalance'     => [
            // Auto white balance - Автоматический режим
            0 => 'Auto',
            // Manual white balance - Ручной режим
            1 => 'Manual',
        ],
        'SceneCaptureType' => [
            // Standard - Стандартный
            0 => 'Standard',
            // Landscape - Ландшафтный
            1 => 'Landscape',
            // Portrait - Портретный
            2 => 'Portrait',
            // Night scene - Ночной
            3 => 'Night scene',
        ],
    ];

    protected function __construct($raw_data)
    {
        $this->raw_data = $raw_data;

        foreach ( $this->map as $field => $name ) {
            $value = $this->getValue($name);

            $this->getModeIsset($value, $name);

            // Если есть метод то вызываем его
            $this->processingMethod($value, $field);

            if ( !empty($value) ) {
                $this->data[$field] = $value;
            }
        }
    }

    public static function load($bytes)
    {
        return self::read('data://image/jpeg;base64,' . base64_encode($bytes));
    }

    public static function read($file)
    {
        if ( function_exists('exif_read_data') ) {
            return new self(@exif_read_data($file, 'ANY_TAG'));
        }

        return false;
    }

//========================================================================//

    protected function getValue($name)
    {
        if ( is_array($name) ) {
            foreach ( $name as $n ) {
                if ( isset($this->raw_data[$n]) ) {
                    $value = $this->raw_data[$n];
                }
            }
        }
        else {
            if ( isset($this->raw_data[$name]) ) {
                $value = $this->raw_data[$name];
            }
        }

        return (!empty($value) ? $value : false);
    }

    protected function processingMethod(&$value, $field)
    {
        if ( method_exists($this, 'get' . ucfirst($field)) ) {
            $this->{'get' . ucfirst($field)}($value);
        }
    }

//========================================================================//

    protected function getAperture(&$value)
    {
        if ( !empty($this->raw_data['COMPUTED']['ApertureFNumber']) ) {
            $value = $this->raw_data['COMPUTED']['ApertureFNumber'];
        }
        else if ( !empty($value) ) {
            $num   = explode('/', $value);
            $value = 'f/' . ($num[0] / $num[1]);
        }
    }

    protected function getApertureValue(&$value)
    {
        if ( !empty($value) && strstr($value, '/') ) {
            $value = $this->normalizeComponent($value);
        }
    }

    protected function getExposure(&$value)
    {
        if ( !is_float($value) ) {
            $value = $this->normalizeComponent($value);
        }

        // Based on the source code of Exiftool (PrintExposureTime subroutine):
        // http://cpansearch.perl.org/src/EXIFTOOL/Image-ExifTool-9.90/lib/Image/ExifTool/Exif.pm
        if ( $value < 0.25001 && $value > 0 ) {
            $value = sprintf('1/%d', intval(0.5 + 1 / $value));
        }
        else {
            $value = sprintf('%.1f', $value);
            $value = preg_replace('/.0$/', '', $value);
        }
    }

    protected function getShutterSpeedValue(&$value)
    {
        if ( !empty($value) ) {
            $value = $this->normalizeComponent($value);
        }
    }

    protected function getCamera(&$value)
    {
        if ( !empty($this->raw_data['Make']) ) {
            $value = $this->raw_data['Make'] . (!empty($value) ? ', ' . $value : '');
        }
    }

    protected function getDate(&$value, $field = false)
    {
        if ( !empty($value) ) {
            try {
                $value = new \DateTime($value);
            }
            catch ( \Exception $e ) {
                $value = null;
            }
        }

        if ( $field == 'creationdate' && empty($value) && !empty($this->data['date']) ) {
            $value = $this->data['date'];
        }
    }

    protected function getCreationdate(&$value)
    {
        $this->getDate($value, 'creationdate');
    }

    protected function getEditdate(&$value)
    {
        $this->getDate($value);
    }

    protected function getIso(&$value)
    {
        if ( is_array($value) ) {
            $value = current($value);
        }
    }

    protected function getFocalLength(&$value)
    {
        $parts = explode('/', $value);

        if ( (int) end($parts) == 0 ) {
            $value = 0;
        }
        else {
            $value = (int) reset($parts) / (int) end($parts);
        }
    }

    protected function getHorizontalResolution(&$value)
    {
        $parts = explode('/', $value);
        $value = (int) reset($parts);
    }

    protected function getVerticalResolution(&$value)
    {
        $this->getHorizontalResolution($value);
    }

    protected function getResolutionUnit(&$value)
    {
        $value = (int) $value;

        if ( $value == 3 ) {
            $value = 'sm';
        }

        $value = 'in';
    }

    protected function getColorSpace(&$value)
    {
        if ( $value == 1 ) {
            $value = 'sRGB';
        }
        else {
            $value = 'Uncalibrated';
        }
    }

    protected function getWidth(&$value)
    {
        if ( !empty($this->raw_data['COMPUTED']['Width']) ) {
            $value = $this->raw_data['COMPUTED']['Width'];
        }
    }

    protected function getHeight(&$value)
    {
        if ( !empty($this->raw_data['COMPUTED']['Height']) ) {
            $value = $this->raw_data['COMPUTED']['Height'];
        }
    }

    protected function getLatitude(&$value)
    {
        if ( !empty($value) ) {
            $value = $this->extractGPSCoordinate($value);
            $ref   = $this->getGPSRef($this->raw_data['GPSLatitudeRef']);
            $value = (strtoupper($ref) === 'S' ? -1 : 1) * $value;
        }
    }

    protected function getLongitude(&$value)
    {
        if ( !empty($value) ) {
            $value = $this->extractGPSCoordinate($value);
            $ref   = $this->getGPSRef($this->raw_data['GPSLongitudeRef'], 'E');
            $value = (strtoupper($ref) === 'W' ? -1 : 1) * $value;
        }
    }

    protected function getGPSLatitude($value)
    {
        if ( !empty($value) ) {
            $coordinate = array_map(array( $this, 'normalizeComponent' ), $value);
            $ref        = $this->getGPSRef($this->raw_data['GPSLatitudeRef']);
            $value      = $coordinate[0] . '°' . (isset($coordinate[1]) ? (round($coordinate[1], 2) . "′" . (isset($coordinate[2]) ? round($coordinate[2], 4) : '')) : '') . $ref;
        }
    }

    protected function getGPSLongitude($value)
    {
        if ( !empty($value) ) {
            $coordinate = array_map(array( $this, 'normalizeComponent' ), $value);
            $ref        = $this->getGPSRef($this->raw_data['GPSLongitudeRef'], 'E');
            $value      = $coordinate[0] . '°' . (isset($coordinate[1]) ? (round($coordinate[1], 2) . "′" . (isset($coordinate[2]) ? round($coordinate[2], 4) : '')) : '') . $ref;
        }
    }

    protected function getBrightnessValue(&$value)
    {
        if ( empty($value) || !strstr($value, '/') ) {
            return;
        }

        $parts = explode('/', $value);

        $dec = hexdec($parts[1]);
        $hex = dechex($dec);

        if ( $hex == $parts[1] ) {
            $value = null;
        }
        else {
            $value = intval($parts[0]) / intval($parts[1]);
        }
    }

    protected function getFlashPixVersion(&$value)
    {
        if ( !empty($value) ) {
            $value = $this->normalizeVersion($value);
        }
    }

    protected function getExifVersion(&$value)
    {
        if ( !empty($value) ) {
            $value = $this->normalizeVersion($value);
        }
    }

//========================================================================//

    protected function normalizeVersion($value)
    {
        $version = (int) $value;

        $v2 = fmod($version, 100);

        $v1 = ($version - $v2) / 100;

        return $v1 . '.' . $v2;
    }

    protected function normalizeComponent($component)
    {
        $parts = explode('/', $component);

        if ( count($parts) > 1 ) {
            if ( $parts[1] ) {
                return intval($parts[0]) / intval($parts[1]);
            }

            return 0;
        }

        return floatval(reset($parts));
    }

    protected function getGPSRef($value, $default = 'N')
    {
        return (empty($value) ? $default : $value);
    }

    protected function extractGPSCoordinate($coordinate)
    {
        $coordinate = array_map(array( $this, 'normalizeComponent' ), $coordinate);

        if ( count($coordinate) > 2 ) {
            return intval($coordinate[0]) + (floatval($coordinate[1]) / 60) + (floatval($coordinate[2]) / 3600);
        }

        return reset($coordinate);
    }

    protected function getModeIsset(&$value, $name)
    {
        if ( is_array($value) ) {
            return;
        }

        if ( is_array($name) ) {
            $temp_value = $value;

            foreach ( $name as $n ) {
                $this->getModeIsset($value, $n);

                if ( $temp_value !== $value ) {
                    break;
                }
            }
        }
        else {
            if ( isset($this->modes[$name][$value]) ) {
                $value = $this->modes[$name][$value];
            }
        }
    }

    //========================================================================//

    public function get($name)
    {
        $value = false;

        $lcname = lcfirst($name);
        $ucname = ucfirst($name);

        if ( isset($this->data[$lcname]) ) {
            $value = $this->data[$lcname];
        }
        else if ( isset($this->data[$ucname]) ) {
            $value = $this->data[$ucname];
        }
        else {
            $exist = false;

            foreach ( $this->data as $key => $val ) {
                if ( is_array($val) ) {
                    if ( in_array($ucname, $val) ) {
                        $exist = true;
                    }
                }
                else if ( $val == $ucname ) {
                    $exist = true;
                }

                if ( $exist === true ) {
                    if ( isset($this->data[$key]) ) {
                        $value = $this->data[$key];
                    }
                    break;
                }
            }
        }

        if ( empty($value) && isset($this->raw_data[$ucname]) ) {
            $value = $this->raw_data[$ucname];
        }


        return $value;
    }

    public function getRaw($name)
    {
        $name = ucfirst($name);

        if ( isset($this->raw_data[$name]) ) {
            return $this->raw_data[$name];
        }

        return false;
    }

}
