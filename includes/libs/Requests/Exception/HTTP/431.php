<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 431 Request Header Fields Too Large responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */

/**
 * Exception for 431 Request Header Fields Too Large responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */
class _431 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 431;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request Header Fields Too Large';

}
