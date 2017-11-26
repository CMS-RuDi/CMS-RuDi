<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 500 Internal Server Error responses
 *
 * @package Requests
 */

/**
 * Exception for 500 Internal Server Error responses
 *
 * @package Requests
 */
class _500 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 500;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Internal Server Error';

}
