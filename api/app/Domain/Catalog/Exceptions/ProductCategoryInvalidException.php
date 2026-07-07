<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ProductCategoryInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.product_category_invalid'), 422, [
            'category_id' => [__('api.product_category_invalid')],
        ]);
    }
}
