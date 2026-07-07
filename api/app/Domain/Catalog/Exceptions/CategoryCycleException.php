<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CategoryCycleException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.category_cycle'), 422, [
            'parent_id' => [__('api.category_cycle')],
        ], 'category.cycle');
    }
}
