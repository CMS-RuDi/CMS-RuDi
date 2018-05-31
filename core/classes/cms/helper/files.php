<?php

namespace cms\helper;

/**
 * @package Classes
 * @subpackage Helper
 */
class files
{

    /**
     * Рекурсивно удаляет директорию
     *
     * @param string $directory
     * @param bool $is_clear Если TRUE, то директория будет очищена, но не удалена
     *
     * @return bool
     */
    public static function removeDirectory($directory, $is_clear = false)
    {
        if ( substr($directory, -1) == '/' ) {
            $directory = substr($directory, 0, -1);
        }

        if ( !file_exists($directory) || !is_dir($directory) || !is_readable($directory) ) {
            return false;
        }

        $handle = opendir($directory);

        while ( false !== ($node = readdir($handle)) ) {
            if ( $node != '.' && $node != '..' ) {
                $path = $directory . '/' . $node;

                if ( is_dir($path) ) {
                    if ( !files_remove_directory($path) ) {
                        return false;
                    }
                }
                else {
                    if ( !@unlink($path) ) {
                        return false;
                    }
                }
            }
        }

        closedir($handle);

        if ( $is_clear == false ) {
            if ( !@rmdir($directory) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Очищает директорию
     *
     * @param string $directory
     *
     * @return bool
     */
    public static function clearDirectory($directory)
    {
        return self::removeDirectory($directory, true);
    }

    /**
     * Удаляет файл и его родительские директории
     *
     * @param string $file_path Отностительный или полный путь к файлу
     * @param integer $delete_parent_dir Количество родительских директорий, которые нужно также удалить, если они пустые
     *
     * @return boolean
     */
    public static function deleteFile($file_path, $delete_parent_dir = 0)
    {
        if ( !is_file($file_path) ) {
            $file_path = PATH . '/' . $file_path;
        }

        $success = @unlink($file_path);

        if ( $delete_parent_dir && $success ) {
            $parent_dir = pathinfo($file_path, PATHINFO_DIRNAME);

            for ( $i = 1; $i <= $delete_parent_dir; $i++ ) {
                if ( !@rmdir($parent_dir) ) {
                    break;
                }

                $parent_dir = pathinfo($parent_dir, PATHINFO_DIRNAME);
            }
        }

        return $success;
    }

    /**
     * Возвращает дерево каталогов и файлов по указанному пути в виде рекурсивного массива
     *
     * @param string $path
     *
     * @return array
     */
    public static function treeToArray($path)
    {
        $data = [];

        $dir = new \DirectoryIterator($path);

        foreach ( $dir as $node ) {
            if ( $node->isDir() && !$node->isDot() ) {
                $data[$node->getFilename()] = self::treeToArray($node->getPathname());
            }
            else if ( $node->isFile() ) {
                $data[] = $node->getFilename();
            }
        }

        return $data;
    }

    /**
     * Возвращает список директорий внутри указанной
     *
     * @param string $path
     *
     * @return array
     */
    public static function getDirsList($path)
    {
        $data = [];

        $dir = new \DirectoryIterator($path);

        foreach ( $dir as $node ) {
            if ( $node->isDir() && !$node->isDot() ) {
                $data[] = $node->getFilename();
            }
        }

        return $data;
    }

    /**
     * Возвращает 32-х символьный хэш, привязанный к ip адресу
     * используется для защиты от хотлинка
     *
     * @param string $file_path Путь к файлу
     *
     * @return string
     */
    public static function userFileHash($file_path = '')
    {
        return md5(\cmsUser::getIp() . md5($file_path . PATH));
    }

    /**
     * Очищает имя файла от специальных символов
     *
     * @param string $filename
     *
     * @return string
     */
    public static function sanitizeName($filename)
    {
        $path_parts = pathinfo($filename);
        $filename   = \cms\lang::slug($path_parts['filename']) . '.' . (isset($path_parts['extension']) ? $path_parts['extension'] : '');
        $filename   = mb_strtolower($filename);
        $filename   = preg_replace(array( '/[\&]/', '/[\@]/', '/[\#]/' ), array( '-and-', '-at-', '-number-' ), $filename);
        $filename   = preg_replace('/[^(\x20-\x7F)]*/', '', $filename);
        $filename   = str_replace(' ', '-', $filename);
        $filename   = str_replace('\'', '', $filename);
        $filename   = preg_replace('/[^\w\-\.]+/', '', $filename);
        $filename   = preg_replace('/[\-]+/', '-', $filename);

        return $filename;
    }

    public static function getContent($file)
    {
        if ( is_file($file) ) {
            return file_get_contents($file);
        }
        else {
            return \Requests::get($file)->body;
        }
    }

    public static function saveFile($url, $destination)
    {
        return \Requests::get($url, [], [ 'filename' => realpath($destination) ])->status_code == 200 ? true : false;
    }

}
