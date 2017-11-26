<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 406 Not Acceptable responses
 *
 * @package Requests
 */

/**
 * Exception for 406 Not Acceptable responses
 *
 * @package Requests
 */
class _406 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 406;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Acceptable';

}
