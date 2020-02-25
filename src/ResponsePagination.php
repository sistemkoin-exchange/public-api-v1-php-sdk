<?php

namespace STK;


use STK\Exceptions\UnknownPaginationData;

/**
 * @author  : Bilal ATLI
 * @date    : 30.03.2019 15:44
 * @mail    : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
 * @phone   : +90 0-542-433-09-19
 *
 * @package STK;
 */
class ResponsePagination
{
    /**
     * @var array
     */
    private $meta;

    /**
     * ResponsePagination constructor.
     *
     * @param array $meta
     *
     * @throws UnknownPaginationData
     */
    public function __construct(array $meta)
    {
        $this->meta = $meta;
        if (
            !isset($meta['current_page']) ||
            !isset($meta['per_page']) ||
            !isset($meta['total'])
        ) {
            throw new UnknownPaginationData();
        }
    }

    /**
     * Current page is first page ?
     *
     * @return bool
     */
    public function isFirstPage()
    {
        return ( $this->meta['current_page'] === 1 );
    }

    /**
     * Current page is last page ?
     *
     * @return bool
     */
    public function isLastPage()
    {
        return ( $this->meta['current_page'] === $this->meta['total'] );
    }

    /**
     * Current Page
     *
     * @return int
     */
    public function currentPage()
    {
        return ( $this->meta['current_page'] );
    }

    /**
     * Get previous page
     *
     * @return int
     */
    public function prevPage()
    {
        return ( $this->isFirstPage() ? 1 : ( $this->currentPage() - 1 ) );
    }

    /**
     * Get next page
     *
     * @return int
     */
    public function nextPage()
    {
        return ( $this->isLastPage() ? $this->totalPage() : ( $this->currentPage() + 1 ) );
    }

    /**
     * Get total page
     *
     * @return int
     */
    public function totalPage()
    {
        return ( $this->meta['total'] );
    }

    /**
     * Get item per page
     *
     * @return int
     */
    public function itemPerPage()
    {
        return $this->meta['per_page'];
    }
}