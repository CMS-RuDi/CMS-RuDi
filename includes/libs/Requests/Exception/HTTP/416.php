<?php

namespace Requests\Exception\HTTP;

/**
 * Exception for 416 Requested Range Not Satisfiable responses
 *
 * @package Requests
 */

/**
 * Exception for 416 Requested Range Not Satisfiable responses
 *
 * @package Requests
 */
class _416 extends \Requests\Exception\HTTP
{

    /**
     * HTTP status code
     *
     * @var integer
     */
    protected $code = 416;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Requested Range Not Satisfiable';

}
