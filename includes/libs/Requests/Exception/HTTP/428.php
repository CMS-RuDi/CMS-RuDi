<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 428 Precondition Required responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */

/**
 * Exception for 428 Precondition Required responses
 *
 * @see https://tools.ietf.org/html/rfc6585
 * @package Requests
 */
class _428 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 428;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Precondition Required';

}
