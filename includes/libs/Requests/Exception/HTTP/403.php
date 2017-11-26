<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 403 Forbidden responses
 *
 * @package Requests
 */

/**
 * Exception for 403 Forbidden responses
 *
 * @package Requests
 */
class _403 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 403;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Forbidden';

}
