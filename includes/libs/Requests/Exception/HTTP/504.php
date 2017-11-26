<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 504 Gateway Timeout responses
 *
 * @package Requests
 */

/**
 * Exception for 504 Gateway Timeout responses
 *
 * @package Requests
 */
class _504 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 504;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Gateway Timeout';

}
