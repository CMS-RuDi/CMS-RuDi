<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 402 Payment Required responses
 *
 * @package Requests
 */

/**
 * Exception for 402 Payment Required responses
 *
 * @package Requests
 */
class _402 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 402;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Payment Required';

}
