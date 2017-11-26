<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 505 HTTP Version Not Supported responses
 *
 * @package Requests
 */

/**
 * Exception for 505 HTTP Version Not Supported responses
 *
 * @package Requests
 */
class _505 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 505;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'HTTP Version Not Supported';

}
