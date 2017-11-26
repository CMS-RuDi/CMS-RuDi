<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 306 Switch Proxy responses
 *
 * @package Requests
 */

/**
 * Exception for 306 Switch Proxy responses
 *
 * @package Requests
 */
class _306 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 306;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Switch Proxy';

}
