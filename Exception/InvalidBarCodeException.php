<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Exception;

use Throwable;

class InvalidBarCodeException extends Exception
{
    protected $productNumbers;

    public function __construct(string $message = '', array $productNumbers, int $code = 0, Throwable $previous = null)
    {
        $this->productNumbers = $productNumbers;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getProductNumbers(): array
    {
        return $this->productNumbers;
    }
}
