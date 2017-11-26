<?php

namespace cms\cache;

/**
 * @package Classes
 * @subpackage Cache
 */
class files
{

    private $cache_path;

    public function __construct($config = false)
    {
        $this->cache_path = PATH . '/' . trim($config['cache_path'], '/') . '/';
    }

    public function set($key, $value, $ttl)
    {
        $data = array(
            'ttl'   => $ttl,
            'time'  => time(),
            'value' => $value
        );

        list($path, $file) = $this->getPathAndFile($key);

        mkdir($path, 0755, true);
        chmod($path, 0755);
        chmod(pathinfo($path, PATHINFO_DIRNAME), 0755);

        return file_put_contents($file, serialize($data));
    }

    public function has($key)
    {
        list($path, $file) = $this->getPathAndFile($key);

        return file_exists($file);
    }

    public function get($key)
    {
        $data = unserialize(file_get_contents($file));

        if ( empty($data['value']) || time() > $data['time'] + $data['ttl'] ) {
            $this->clean($key);
            return null;
        }

        return $data['value'];
    }

    public function clean($key = false)
    {
        if ( $key ) {
            $path = $this->cache_path . str_replace('.', '/', $key);

            if ( is_file($path . '.dat') ) {
                unlink($path . '.dat');
            }

            return \cms\helper\files::removeDirectory($path);
        }
        else {
            return \cms\helper\files::clearDirectory($this->cache_path);
        }
    }

    public function getPathAndFile($key)
    {
        $path = $this->cache_path . str_replace('.', '/', $key);

        return array( dirname($path), $path . '.dat' );
    }

    public function start()
    {
        return true;
    }

    public function stop()
    {
        return true;
    }

}
