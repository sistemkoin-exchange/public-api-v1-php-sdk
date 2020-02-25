<?php

namespace STK;


use Psr\Http\Message\ResponseInterface;
use STK\Exceptions\ServiceException;
use STK\Types\ResponseStatus;

/**
 * @author  : Bilal ATLI
 * @date    : 30.03.2019 15:44
 * @mail    : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
 * @phone   : +90 0-542-433-09-19
 *
 * @package STK;
 */
class APIResponse
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ResponsePagination|null
     */
    private $pagination = null;

    /**
     * @var object
     */
    private $body;

    /**
     * APIResponse constructor.
     *
     * @param ResponseInterface $response
     *
     * @throws ServiceException
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $bodyContent = $response->getBody()->getContents();
        $this->body = json_decode($bodyContent);

        if (json_last_error() !== JSON_ERROR_NONE || empty($bodyContent)) {
            throw new ServiceException('API service is not responding');
        }
    }

    /**
     * Get Status Code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Get API Status Message
     *
     * @return string
     */
    public function getStatusMessage()
    {
        if (property_exists($this->body, 'status')) {
            $status = $this->body->status;

            return ( $status === ResponseStatus::SUCCESS ? ResponseStatus::SUCCESS :
                ( $status === ResponseStatus::FAILURE ? ResponseStatus::FAILURE :
                    ( $status === ResponseStatus::PENDING ? ResponseStatus::PENDING :
                        ResponseStatus::UNKNOWN
                    ) ) );
        }

        return ResponseStatus::UNKNOWN;
    }

    /**
     * Get API Response Data
     *
     * @return array|object|null
     */
    public function getBody()
    {
        if (property_exists($this->body, 'data')) {
            return $this->body->data;
        }

        return null;
    }

    /**
     * Get Pagination
     *
     * @return ResponsePagination|null
     */
    public function getPagination()
    {
        if ($this->pagination !== null) {
            return $this->pagination;
        }

        if (property_exists($this->body, 'meta')) {
            try {
                $this->pagination = new ResponsePagination((array)$this->body->meta);

                return $this->pagination;
            } catch (Exceptions\UnknownPaginationData $e) {
                // Do Nothing... Function returns null value
            }
        }

        return null;
    }
}