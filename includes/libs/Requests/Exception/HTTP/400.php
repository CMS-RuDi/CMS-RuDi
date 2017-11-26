<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 400 Bad Request responses
 *
 * @package Requests
 */

/**
 * Exception for 400 Bad Request responses
 *
 * @package Requests
 */
class _400 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 400;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Bad Request';

}
