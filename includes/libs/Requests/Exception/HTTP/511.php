<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 511 Network Authentication Required responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */

/**
 * Exception for 511 Network Authentication Required responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */
class _511 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 511;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Network Authentication Required';

}
