<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 414 Request-URI Too Large responses
 *
 * @package Requests
 */

/**
 * Exception for 414 Request-URI Too Large responses
 *
 * @package Requests
 */
class _414 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 414;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request-URI Too Large';

}
