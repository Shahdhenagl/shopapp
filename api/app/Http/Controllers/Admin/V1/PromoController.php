<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Cart\Contracts\AdminPromoRepositoryInterface;
use App\Domain\Cart\Exceptions\PromoCodeTakenException;
use App\Domain\Cart\Models\PromoCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Promos\StorePromoRequest;
use App\Http\Requests\Admin\V1\Promos\UpdatePromoRequest;
use App\Http\Resources\Admin\AdminPromoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PromoController extends Controller
{
    public function __construct(
        private readonly AdminPromoRepositoryInterface $promos,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return AdminPromoResource::collection($this->promos->all());
    }

    public function store(StorePromoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $code = strtoupper((string) $data['code']);

        if ($this->promos->codeExists($code)) {
            throw new PromoCodeTakenException;
        }

        $promo = $this->promos->create([
            'code' => $code,
            'type' => $data['type'],
            'fraction' => $data['fraction'],
            'active' => $data['active'] ?? true,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
        ]);

        $this->audit->log($this->actor($request), 'promo.created', $promo, null, $promo->toArray());

        return AdminPromoResource::make($promo)->response()->setStatusCode(201);
    }

    public function update(UpdatePromoRequest $request, string $id): AdminPromoResource
    {
        $promo = $this->findOrFail($id);
        $before = $promo->toArray();

        $data = $request->validated();

        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper((string) $data['code']);

            if ($this->promos->codeExists($data['code'], $id)) {
                throw new PromoCodeTakenException;
            }
        }

        $promo = $this->promos->update($promo, $data);

        $this->audit->log($this->actor($request), 'promo.updated', $promo, $before, $promo->toArray());

        return AdminPromoResource::make($promo);
    }

    public function destroy(Request $request, string $id): Response
    {
        $promo = $this->findOrFail($id);
        $before = $promo->toArray();

        $this->promos->delete($promo);

        $this->audit->log($this->actor($request), 'promo.deleted', $promo, $before, null);

        return response()->noContent();
    }

    private function findOrFail(string $id): PromoCode
    {
        $promo = $this->promos->find($id);

        abort_if($promo === null, 404, __('api.not_found'));

        return $promo;
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
