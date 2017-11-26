<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 408 Request Timeout responses
 *
 * @package Requests
 */

/**
 * Exception for 408 Request Timeout responses
 *
 * @package Requests
 */
class _408 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 408;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request Timeout';

}
