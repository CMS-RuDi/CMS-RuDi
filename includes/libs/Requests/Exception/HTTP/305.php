<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 305 Use Proxy responses
 *
 * @package Requests
 */

/**
 * Exception for 305 Use Proxy responses
 *
 * @package Requests
 */
class _305 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 305;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Use Proxy';

}
