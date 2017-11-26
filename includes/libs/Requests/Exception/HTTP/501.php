<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 501 Not Implemented responses
 *
 * @package Requests
 */

/**
 * Exception for 501 Not Implemented responses
 *
 * @package Requests
 */
class _501 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 501;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Implemented';

}
