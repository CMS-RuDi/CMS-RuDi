<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 410 Gone responses
 *
 * @package Requests
 */

/**
 * Exception for 410 Gone responses
 *
 * @package Requests
 */
class _410 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 410;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Gone';

}
