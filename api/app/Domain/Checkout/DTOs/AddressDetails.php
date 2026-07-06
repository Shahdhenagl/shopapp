<?php

declare(strict_types=1);

namespace App\Domain\Checkout\DTOs;

final readonly class AddressDetails
{
    public function __construct(
        public string $address,
        public string $city,
        public ?string $area,
        public ?string $branch,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            address: (string) ($data['address'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            area: isset($data['area']) ? (string) $data['area'] : null,
            branch: isset($data['branch']) ? (string) $data['branch'] : null,
        );
    }
}
