<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 415 Unsupported Media Type responses
 *
 * @package Requests
 */

/**
 * Exception for 415 Unsupported Media Type responses
 *
 * @package Requests
 */
class _415 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 415;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Unsupported Media Type';

}
