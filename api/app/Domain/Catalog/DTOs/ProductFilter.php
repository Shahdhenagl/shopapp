<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTOs;

final readonly class ProductFilter
{
    public function __construct(
        public ?string $categoryId = null,
        public ?string $search = null,
        public ?bool $newestOnly = null,
    ) {
    }

    public function hasCategory(): bool
    {
        return $this->categoryId !== null && $this->categoryId !== '';
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search !== '';
    }
}
