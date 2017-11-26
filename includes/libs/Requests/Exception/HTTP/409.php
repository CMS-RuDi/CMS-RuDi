<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 409 Conflict responses
 *
 * @package Requests
 */

/**
 * Exception for 409 Conflict responses
 *
 * @package Requests
 */
class _409 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 409;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Conflict';

}
