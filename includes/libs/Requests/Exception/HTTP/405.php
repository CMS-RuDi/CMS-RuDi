<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 405 Method Not Allowed responses
 *
 * @package Requests
 */

/**
 * Exception for 405 Method Not Allowed responses
 *
 * @package Requests
 */
class _405 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 405;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Method Not Allowed';

}
