<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 401 Unauthorized responses
 *
 * @package Requests
 */

/**
 * Exception for 401 Unauthorized responses
 *
 * @package Requests
 */
class _401 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 401;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Unauthorized';

}
