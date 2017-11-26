<?php

namespace cms;

/**
 * @package Classes
 * @subpackage Cache
 */
class cache
{

    use \Singeltone;

    protected $cacher;
    protected $cache_ttl;

    protected function __construct()
    {
        $config = \cmsConfig::getInstance();

        if ( $config->cache_enabled ) {
            $cacher_class = 'cache\\' . $config->cache_method;

            $this->cacher = new $cacher_class($config);

            $this->cache_ttl = $config->cache_ttl;
        }
    }

    public function __call($method_name, $arguments)
    {
        // кеширование отключено
        if ( !isset($this->cacher) ) {
            return false;
        }

        // есть метод здесь, вызываем его
        if ( method_exists($this, '_' . $method_name) ) {
            return $this->{'_' . $method_name}(...$arguments);
        }

        // есть метод в кешере, вызываем его
        if ( method_exists($this->cacher, $method_name) ) {
            return $this->cacher->{$method_name}(...$arguments);
        }

        // ничего нет
        trigger_error('not defined method name ' . $method_name, E_USER_NOTICE);

        return false;
    }

    private function _set($key, $value, $ttl = false)
    {
        if ( !$ttl ) {
            $ttl = $this->cache_ttl;
        }

        return $this->cacher->set($key, $value, $ttl);
    }

    private function _get($key)
    {
        if ( !$this->cacher->has($key) ) {
            return false;
        }

        debug::startTimer('cache');

        $value = $this->cacher->get($key);

        debug::setDebugInfo('cache', $key, false, 5);

        return $value;
    }

}
