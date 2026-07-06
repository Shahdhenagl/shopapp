<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Support;

use App\Domain\Checkout\Contracts\PaymentProcessor;
use App\Domain\Checkout\Exceptions\UnsupportedPaymentMethodException;

final class PaymentProcessorFactory
{
    /**
     * @var list<PaymentProcessor>
     */
    private array $processors;

    /**
     * @param  iterable<PaymentProcessor>  $processors
     */
    public function __construct(iterable $processors)
    {
        $this->processors = is_array($processors) ? array_values($processors) : iterator_to_array($processors, false);
    }

    public function for(string $method): PaymentProcessor
    {
        foreach ($this->processors as $processor) {
            if ($processor->method() === $method) {
                return $processor;
            }
        }

        throw new UnsupportedPaymentMethodException;
    }
}
