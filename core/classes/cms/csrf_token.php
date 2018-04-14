<?php

namespace cms;

/**
 * @package Classes
 */
class csrf_token
{

    const TOKEN_NAME = 'csrf_token';

    /**
     * Используемый алгоритм хеширования
     * @var integer
     */
    protected static $hash_method = PASSWORD_BCRYPT;

    /**
     * массив с опциями хеширования
     * @var array
     */
    protected static $hash_options = [ 'cost' => 7 ];

    /**
     * Время жизни токена в секундах
     * @var integer
     */
    protected static $lifetime = 86400;

    /**
     * Проверяет корректность хеша из request переданного в параметре $token_name
     *
     * @param string $token_name Название параметра с хешем в массиве request
     *
     * @return boolean
     */
    public static function checkToken($token_name = self::TOKEN_NAME)
    {
        return self::check(request::getInstance()->get($token_name));
    }

    /**
     * Проверяет корректность переданного хеша
     *
     * @param string $hash
     *
     * @return boolean
     */
    public static function check($hash)
    {
        $hash = base64_decode($hash);

        if ( empty($hash) ) {
            return false;
        }

        list($timestamp, $signature) = explode(':', $hash);

        $token = session::get(self::TOKEN_NAME);

        if ( empty($timestamp) || empty($signature) || empty($token) ) {
            return false;
        }

        if ( $timestamp < (time() - self::$lifetime) ) {
            return false;
        }

        if ( !password_verify($timestamp . ':' . $token, $signature) ) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает сгенерированный хеш
     *
     * @return string
     */
    public static function get()
    {
        $token = session::get(self::TOKEN_NAME);

        if ( empty($token) ) {
            $token = self::genToken();
        }

        $timestamp = time();

        $signature = self::genSignature($token, $timestamp);

        return base64_encode($timestamp . ':' . $signature);
    }

    /**
     * Возвращает скрытый input с указанным именем и токеном
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getInput($token_name = self::TOKEN_NAME)
    {
        return '<input type="hidden" name="' . $token_name . '" value="' . self::get() . '" />';
    }

    /**
     * Возвращает строку в формате token=token
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getQuery($token_name = self::TOKEN_NAME)
    {
        return $token_name . '=' . self::get();
    }

    /**
     * Возвращает строку в формате token: token
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getJsQuery($token_name = self::TOKEN_NAME)
    {
        return $token_name . ": '" . self::get() . "'";
    }

    /**
     * Возвращает токен в виде массива
     *
     * @param string $token_name
     *
     * @return array
     */
    public static function getArray($token_name = self::TOKEN_NAME)
    {
        return [ $token_name => self::get() ];
    }

    //========================================================================//

    /**
     * Возвращает сгенерированный токен
     *
     * @return string
     */
    protected static function genToken()
    {
        $token = helper\str::random();

        session::set(self::TOKEN_NAME, $token);

        return $token;
    }

    /**
     * Генерирует хеш из токена и метки времени
     *
     * @param string $token
     * @param integer $timestamp
     *
     * @return string
     */
    protected static function genSignature($token, $timestamp)
    {
        return password_hash($timestamp . ':' . $token, self::$hash_method, self::$hash_options);
    }

}
