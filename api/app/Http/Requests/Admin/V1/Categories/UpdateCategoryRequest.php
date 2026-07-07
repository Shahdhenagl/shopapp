<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Categories;

use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['sometimes', 'required'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'string', $this->parentExistsRule()],
            'label_key' => ['sometimes', 'nullable', 'string'],
            'icon_key' => ['sometimes', 'nullable', 'string'],
            'image_url' => ['sometimes', 'nullable', 'url'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * The new parent must be an existing, non-deleted category within the same
     * tenant. Cycle safety (§7.4) is enforced in the action.
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
