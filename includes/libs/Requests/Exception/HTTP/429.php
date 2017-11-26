<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 429 Too Many Requests responses
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */

/**
 * Exception for 429 Too Many Requests responses
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-new-status-04
 * @package Requests
 */
class _429 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 429;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Too Many Requests';

}
