<?php

namespace cms;

/**
 * @package Classes
 */
class csrf_token {
    const TOKEN_NAME = 'csrf_token';
    const TIME_NAME = 'csrf_token_time';
    const SECRET_NAME = 'csrf_token_secret';

    /**
     * Время жизни токена в секундах.
     *
     * @var integer
     */
    protected static $lifetime = 86400;

    /**
     * Допустимое расхождение часов при проверке timestamp.
     *
     * @var integer
     */
    protected static $clock_skew = 300;

    /**
     * Проверяет корректность хеша из request переданного в параметре $token_name
     *
     * @param string $token_name Название параметра с хешем в массиве request
     *
     * @return boolean
     */
    public static function checkToken($token_name = self::TOKEN_NAME) {
        return self::check(request::getInstance()->get($token_name));
    }

    /**
     * Проверяет токен только из POST (для state-changing действий).
     *
     * @param string $token_name
     *
     * @return boolean
     */
    public static function checkPost($token_name = self::TOKEN_NAME) {
        return self::check(request::getInstance()->get($token_name, 'string', '', 'post'));
    }

    /**
     * Проверяет корректность переданного хеша
     *
     * @param string $hash
     *
     * @return boolean
     */
    public static function check($hash) {
        if (!is_string($hash) || $hash === '') {
            return false;
        }

        $hash = base64_decode($hash, true);

        if ($hash === false || substr_count($hash, ':') !== 1) {
            return false;
        }

        list($timestamp, $signature) = explode(':', $hash, 2);

        if (!ctype_digit((string) $timestamp) || $signature === '') {
            return false;
        }

        $timestamp = (int) $timestamp;
        $now = time();

        // Старые токены и подписи с большим расхождением времени не принимаем.
        if ($timestamp < ($now - self::$lifetime) || $timestamp > ($now + self::$clock_skew)) {
            return false;
        }

        $token = session::get(self::TOKEN_NAME);

        if (empty($token)) {
            return false;
        }

        $created = (int) session::get(self::TIME_NAME);

        if (!$created || $created < ($now - self::$lifetime)) {
            return false;
        }

        $secret = session::get(self::SECRET_NAME);

        if (empty($secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . ':' . $token, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Возвращает сгенерированный хеш
     *
     * @return string
     */
    public static function get() {
        $token = session::get(self::TOKEN_NAME);
        $created = (int) session::get(self::TIME_NAME);
        $now = time();

        if (empty($token) || !$created) {
            $token = self::genToken();
            $created = $now;
            session::set(self::TIME_NAME, $created);
        } elseif ($created < ($now - self::$lifetime)) {
            // Абсолютный срок жизни токена истёк — ротируем.
            return self::rotate();
        }

        $timestamp = $now;

        $signature = self::genSignature($token, $timestamp);

        return base64_encode($timestamp . ':' . $signature);
    }

    /**
     * Удаляет текущий токен из сессии.
     *
     * @return bool
     */
    public static function clear() {
        session::delete(self::TOKEN_NAME);
        session::delete(self::TIME_NAME);
        session::delete(self::SECRET_NAME);

        return true;
    }

    /**
     * Удаляет текущий токен и сразу выдаёт новый.
     *
     * @return string
     */
    public static function rotate() {
        self::clear();

        return self::get();
    }

    /**
     * Возвращает скрытый input с указанным именем и токеном
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getInput($token_name = self::TOKEN_NAME) {
        return '<input type="hidden" name="' . $token_name . '" value="' . self::get() . '" />';
    }

    /**
     * Возвращает строку в формате token=token
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getQuery($token_name = self::TOKEN_NAME) {
        return $token_name . '=' . self::get();
    }

    /**
     * Возвращает строку в формате token: token
     *
     * @param string $token_name
     *
     * @return string
     */
    public static function getJsQuery($token_name = self::TOKEN_NAME) {
        return $token_name . ": '" . self::get() . "'";
    }

    /**
     * Возвращает токен в виде массива
     *
     * @param string $token_name
     *
     * @return array
     */
    public static function getArray($token_name = self::TOKEN_NAME) {
        return [$token_name => self::get()];
    }

    //========================================================================//

    /**
     * Возвращает сгенерированный токен
     *
     * @return string
     */
    protected static function genToken() {
        $token = self::randomHex(32);

        session::set(self::TOKEN_NAME, $token);

        return $token;
    }

    /**
     * Генерирует подпись из токена и метки времени.
     *
     * @param string $token
     * @param integer $timestamp
     *
     * @return string
     */
    protected static function genSignature($token, $timestamp) {
        return hash_hmac('sha256', $timestamp . ':' . $token, self::secret());
    }

    /**
     * Возвращает серверный секрет сессии, создавая его при необходимости.
     *
     * @return string
     */
    protected static function secret() {
        $secret = session::get(self::SECRET_NAME);

        if (empty($secret)) {
            $secret = self::randomHex(32);
            session::set(self::SECRET_NAME, $secret);
        }

        return $secret;
    }

    /**
     * Криптостойкая случайная строка в hex.
     *
     * @param int $bytes
     *
     * @return string
     */
    protected static function randomHex($bytes = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($bytes));
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $random = openssl_random_pseudo_bytes($bytes, $strong);

            if ($random !== false && $strong) {
                return bin2hex($random);
            }
        }

        // Резерв для очень старых сборок PHP без random_bytes/openssl.
        return helper\str::random($bytes * 2);
    }
}