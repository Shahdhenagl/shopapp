<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class MultiDepartmentRequiresTreeException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('api.multi_requires_tree'), 422, [
            'storefront_mode' => [__('api.multi_requires_tree')],
        ], 'settings.multi_requires_tree');
    }
}
