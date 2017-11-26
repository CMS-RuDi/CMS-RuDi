<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 404 Not Found responses
 *
 * @package Requests
 */

/**
 * Exception for 404 Not Found responses
 *
 * @package Requests
 */
class _404 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 404;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Found';

}
