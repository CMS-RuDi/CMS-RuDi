<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 503 Service Unavailable responses
 *
 * @package Requests
 */

/**
 * Exception for 503 Service Unavailable responses
 *
 * @package Requests
 */
class _503 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 503;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Service Unavailable';

}
