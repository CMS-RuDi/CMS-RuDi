<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 304 Not Modified responses
 *
 * @package Requests
 */

/**
 * Exception for 304 Not Modified responses
 *
 * @package Requests
 */
class _304 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 304;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Modified';

}
