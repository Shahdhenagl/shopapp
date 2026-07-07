<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CategoryNotEmptyException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.category_not_empty'), 422, [], 'category.not_empty');
    }
}
