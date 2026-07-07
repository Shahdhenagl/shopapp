<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Categories;

use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Either a plain string or an { en, ar } object — normalised in the
            // action, so only presence is validated here.
            'name' => ['required'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', $this->parentExistsRule()],
            'label_key' => ['nullable', 'string'],
            'icon_key' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /**
     * The parent must be an existing, non-deleted category within the same
     * tenant.
     */
    protected function parentExistsRule(): Exists
    {
        $tenantId = app(TenantContext::class)->id();

        return Rule::exists('categories', 'id')->where(
            fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
