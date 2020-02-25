<?php

namespace STK;


/**
 * @author  : Bilal ATLI
 * @date    : 30.03.2019 15:44
 * @mail    : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
 * @phone   : +90 0-542-433-09-19
 *
 * @package STK;
 */
class SignedPayload
{
    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $payloadString, $signature;

    /**
     * SignedPayload constructor.
     *
     * @param array  $payload
     * @param string $payloadString
     * @param string $signature
     */
    public function __construct(array $payload, string $payloadString, string $signature)
    {
        $this->payload = $payload;
        $this->payloadString = $payloadString;
        $this->signature = $signature;
    }

    /**
     * Get Payload
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Get Payload String
     *
     * @return string
     */
    public function getPayloadString()
    {
        return $this->payloadString;
    }

    /**
     * Get Signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }
}