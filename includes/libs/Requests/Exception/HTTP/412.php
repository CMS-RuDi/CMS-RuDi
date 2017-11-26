<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 412 Precondition Failed responses
 *
 * @package Requests
 */

/**
 * Exception for 412 Precondition Failed responses
 *
 * @package Requests
 */
class _412 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 412;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Precondition Failed';

}
