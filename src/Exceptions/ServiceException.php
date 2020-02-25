<?php

namespace STK\Exceptions;

use Exception;
use Throwable;

/**
 * @author  : Bilal ATLI
 * @date    : 30.03.2019 15:44
 * @mail    : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
 * @phone   : +90 0-542-433-09-19
 *
 * @package STK\Exceptions;
 */
class ServiceException extends Exception
{
    /**
     * ServiceException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "Service Not Respond Correctly", $code = 0xF362B, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}