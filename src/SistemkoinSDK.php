<?php

namespace STK;


use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use STK\Exceptions\ServiceException;
use STK\Types\HttpMethod;
use InvalidArgumentException;

/**
 * @author  : Bilal ATLI
 * @date    : 30.03.2019 15:44
 * @mail    : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
 * @phone   : +90 0-542-433-09-19
 *
 * @package STK;
 */
class SistemkoinSDK
{
    const BASE_URL = 'https://api.sistemkoin.com/api/';
    const VERSION = 'v1';

    /**
     * Http Client
     *
     * @var Client
     */
    private $client;

    /**
     * API Key & Secret
     *
     * @var string
     */
    private $apiKey, $apiSecret;

    /**
     * Time Security Difference [Millisecond]
     *
     * @var int
     */
    private $recvWindow = 5000;

    /**
     * SistemkoinSDK constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @param array  $clientOptions
     */
    public function __construct(string $apiKey, string $apiSecret, array $clientOptions = [])
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;

        $clientOptions = array_merge($clientOptions, [
//            'proxy'  => '127.0.0.1:8888',
//            'verify' => false,
        ]);

        $this->client = new Client($clientOptions);
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get API Secret
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    /**
     * Get Base URL For Http Request
     *
     * @param bool $generalRoute
     *
     * @return string
     */
    public function getBaseUrl(bool $generalRoute = false)
    {
        return ( self::BASE_URL . ( !$generalRoute ? ( self::VERSION . '/' ) : '' ) );
    }

    /**
     * Get Time Security Difference
     *
     * @return int
     */
    public function getRecvWindow(): int
    {
        return $this->recvWindow;
    }

    /**
     * Sen Time Security Difference
     *
     * @param int $recvWindow
     */
    public function setRecvWindow(int $recvWindow)
    {
        $recvWindow = ( ( $recvWindow < 0 ) ? 5000 : ( $recvWindow > 60000 ? 60000 : $recvWindow ) );
        $this->recvWindow = $recvWindow;
    }

    /**
     * Build Uri
     *
     * @param string|array $endpoints
     * @param bool         $generalRoute
     *
     * @return string
     */
    protected function buildUri($endpoints, bool $generalRoute = false)
    {
        $baseUri = $this->getBaseUrl($generalRoute);
        if (is_array($endpoints)) {
            $endpointString = implode('/', $endpoints);
        } else if (is_string($endpoints)) {
            $endpointString = $endpoints;
        } else {
            throw new InvalidArgumentException('Endpoints must be string or array');
        }
        return $baseUri . $endpointString;
    }

    /**
     * Sign & Return Payload Data & Signature
     *
     * @param array $payload
     *
     * @return SignedPayload
     */
    protected function sign(array $payload)
    {
        $recvWindow = $this->getRecvWindow();

        //region Include Recv Window Parameter in Payload
        if (isset($payload['recvWindow'])) {
            $recvWindow = $payload['recvWindow'];
            if ($recvWindow < 0 || $recvWindow > 60000) {
                throw new InvalidArgumentException('Recvwindow values ​​must be within the allowable range [0 ~ 60000]');
            }
        } else {
            $payload['recvWindow'] = $recvWindow;
        }
        //endregion

        //region Include Timestamp Parameter in Payload
        if (isset($payload['timestamp'])) {
            if ($payload['timestamp'] * 1000 <= Carbon::now()->addMillis($recvWindow)->timestamp) {
                throw new InvalidArgumentException('Timestamp is not among acceptable values');
            }
        } else {
            $payload['timestamp'] = Carbon::now()->timestamp;
        }
        //endregion

        $payloadString = http_build_query($payload);
        $signature = hash_hmac('sha256', $payloadString, $this->apiSecret);

        $payload['signature'] = $signature;
        $payloadString = http_build_query($payload);

        return ( new SignedPayload($payload, $payloadString, $signature) );
    }

    /**
     * Send Request & Handle API Response
     *
     * @param string $method
     * @param string $uri
     * @param array  $payload
     * @param array  $headers
     *
     * @return APIResponse
     * @throws Exceptions\ServiceException
     */
    protected function _request(string $method, string $uri, array $payload = [], array $headers = [])
    {
        $headers = array_merge($headers, [
            'X-STK-ApiKey' => $this->getApiKey(),
            'X-STK-Client' => 'PHP-SDK-v1',
        ]);

        switch ($method) {
            case HttpMethod::GET:
                $response = $this->client->get($uri, [
                    'headers' => $headers,
                    'query'   => $payload,
                ]);
                return new APIResponse($response);
                break;

            case HttpMethod::POST:
                $response = $this->client->post($uri, [
                    'headers'     => $headers,
                    'form_params' => $payload,
                ]);
                return new APIResponse($response);
                break;
            case HttpMethod::DELETE:
                $response = $this->client->delete($uri, [
                    'headers'     => $headers,
                    'form_params' => $payload,
                ]);
                return new APIResponse($response);
                break;

            default:
                throw new InvalidArgumentException('Unknown Http Method !');
                break;
        }
    }

    /**
     * Get System Time
     *
     * @return Carbon|null
     * @throws Exceptions\ServiceException
     */
    public function systemTime()
    {
        $uri = $this->buildUri('systime', true);
        $response = $this->_request(HttpMethod::GET, $uri);

        try {
            return Carbon::createFromTimestamp($response->getBody()->timestamp);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Ping API Server
     *
     * @return bool
     */
    public function ping()
    {
        $uri = $this->buildUri('ping', true);
        try {
            $response = $this->_request('GET', $uri);
            return $response->getBody()->message === 'pong';
        } catch (Exceptions\ServiceException $e) {
            return false;
        }
    }

    /**
     * Test Signature Method
     *
     * @param array $payload
     * @param int   $timestamp
     *
     * @return bool|APIResponse
     */
    public function testSignature(array $payload = [], int $timestamp = 0)
    {
        $timestamp = ( $timestamp === 0 ? Carbon::now()->timestamp : $timestamp );

        $uri = $this->buildUri('testSignature', true);

        if (count($payload) === 0) {
            $payload = [
                'name'     => 'Foo',
                'surname'  => 'Bar',
                'location' => 'Milkyway Galaxy, Planet Earth',
            ];
        }

        $payload['timestamp'] = $timestamp;
        $payload['recvWindow'] = $this->getRecvWindow();

        $signedPayload = $this->sign($payload);

        try {
            $response = $this->_request(HttpMethod::POST, $uri, $signedPayload->getPayload());

            return $response;
        } catch (Exceptions\ServiceException $e) {
            return false;
        }
    }

    /**
     * Get User Account Data
     *
     * @return APIResponse|null
     */
    public function getUserData()
    {
        $uri = $this->buildUri([ 'account', 'details' ]);
        try {
            $response = $this->_request(HttpMethod::GET, $uri);
            return $response;
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Balance Method
     *
     * If symbol parameters exists, get {symbol} coin/token balance
     * Else if symbol parameters doesn't exist, get all wallet balances
     *
     * @param string|null $symbol [Coin or Token Symbol]
     *
     * @return APIResponse|null
     */
    public function getBalance(string $symbol = null)
    {
        $uri = $this->buildUri([ 'account', 'balance' ]);
        try {
            $payload = [];
            if ($symbol !== null) {
                $payload['symbol'] = $symbol;
            }

            $payloadData = $this->sign($payload);

            $response = $this->_request(HttpMethod::GET, $uri, $payloadData->getPayload());

            return $response;
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Order History
     *
     * @return APIResponse|null
     */
    public function getAccountOrders()
    {
        $uri = $this->buildUri([ 'account', 'orders' ]);

        try {
            $response = $this->_request(HttpMethod::GET, $uri);

            return $response;
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Account Trades
     *
     * @return array|object|null
     */
    public function getAccountTrades()
    {
        $uri = $this->buildUri([ 'account', 'trades' ]);

        try {

            $response = $this->_request(HttpMethod::GET, $uri);
            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get All Pairs or Market Pairs
     *
     * @param string|null $marketSymbol
     *
     * @return array|object|null
     */
    public function getPairs(?string $marketSymbol = null)
    {
        $uri = $this->buildUri([ 'market', 'pairs' ]);

        try {
            $payload = [];
            if ($marketSymbol !== null) {
                $payload['market'] = strtoupper($marketSymbol);
            }

            $response = $this->_request(HttpMethod::GET, $uri, $payload);

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else if ($response->getStatusCode() === 404) {
                throw new ServiceException('Pair not found', 404);
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Ticker Data
     *
     * @return array|object|null
     */
    public function getTickerData()
    {
        $uri = $this->buildUri([ 'market', 'ticker' ]);

        try {
            $response = $this->_request(HttpMethod::GET, $uri);

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Coin Fee Rates
     *
     * @param string $symbol
     *
     * @return array|object|null
     */
    public function getUserFee(string $symbol)
    {
        $uri = $this->buildUri([ 'coins', 'fee' ]);

        $symbol = strtoupper($symbol);

        try {
            $response = $this->_request(HttpMethod::GET, $uri, [
                'symbol' => $symbol,
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (ServiceException $e) {
            return null;
        }
    }

    /**
     * Store Market Order
     *
     * @param string $pair
     * @param string $orderType
     * @param float  $amount
     * @param float  $price
     *
     * @return array|object|null
     */
    public function storeMarket(string $pair, string $orderType, float $amount, float $price)
    {
        $uri = $this->buildUri([ 'market' ]);

        try {
            $payload = [
                'symbol' => $pair,
                'type'   => $orderType,
                'amount' => $amount,
                'price'  => $price,
            ];

            $signedPayload = $this->sign($payload);
            $response = $this->_request(HttpMethod::POST, $uri, $signedPayload->getPayload());

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Cancel Market Order
     *
     * @param int $orderID
     *
     * @return array|object|null
     */
    public function cancelMarket(int $orderID)
    {
        $uri = $this->buildUri([ 'market' ]);

        try {
            $payload = [
                'orderID' => $orderID,
            ];

            $signedPayload = $this->sign($payload);

            $response = $this->_request(HttpMethod::DELETE, $uri, $signedPayload->getPayload());

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Public Trades
     *
     * @param string $pair
     * @param int    $limit [10, 25, 50]
     *
     * @return array|object|null
     */
    public function getClosedTrades(string $pair, int $limit)
    {
        $uri = $this->buildUri([ 'trade' ]);

        try {
            $payload = [
                'symbol' => $pair,
                'limit'  => $limit,
            ];

            $signedPayload = $this->sign($payload);

            $response = $this->_request(HttpMethod::GET, $uri, $signedPayload->getPayload());
            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

    /**
     * Get Open Orders
     *
     * @param string $pair
     * @param int    $limit
     *
     * @return array|object|null
     */
    public function getOpenOrders(string $pair, int $limit)
    {
        $uri = $this->buildUri([ 'market' ]);

        try {
            $payload = [
                'symbol' => $pair,
                'limit'  => $limit,
            ];

            $signedPayload = $this->sign($payload);

            $response = $this->_request(HttpMethod::GET, $uri, $signedPayload->getPayload());
            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else {
                throw new ServiceException('Service is unavailable');
            }
        } catch (Exceptions\ServiceException $e) {
            return null;
        }
    }

}
