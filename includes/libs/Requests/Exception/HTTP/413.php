<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 413 Request Entity Too Large responses
 *
 * @package Requests
 */

/**
 * Exception for 413 Request Entity Too Large responses
 *
 * @package Requests
 */
class _413 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 413;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request Entity Too Large';

}
