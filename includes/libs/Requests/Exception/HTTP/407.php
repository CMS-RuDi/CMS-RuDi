<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 407 Proxy Authentication Required responses
 *
 * @package Requests
 */

/**
 * Exception for 407 Proxy Authentication Required responses
 *
 * @package Requests
 */
class _407 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 407;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Proxy Authentication Required';

}
