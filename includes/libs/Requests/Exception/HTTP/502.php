<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 502 Bad Gateway responses
 *
 * @package Requests
 */

/**
 * Exception for 502 Bad Gateway responses
 *
 * @package Requests
 */
class _502 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 502;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Bad Gateway';

}
