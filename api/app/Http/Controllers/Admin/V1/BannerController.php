<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Banners\Contracts\AdminBannerRepositoryInterface;
use App\Domain\Banners\Models\Banner;
use App\Domain\Banners\Support\LinkTargetValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Banners\StoreBannerRequest;
use App\Http\Requests\Admin\V1\Banners\UpdateBannerRequest;
use App\Http\Resources\Admin\AdminBannerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BannerController extends Controller
{
    public function __construct(
        private readonly AdminBannerRepositoryInterface $banners,
        private readonly LinkTargetValidator $linkValidator,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return AdminBannerResource::collection($this->banners->all());
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $linkType = $data['link_type'];
        $linkValue = $data['link_value'] ?? null;

        $this->linkValidator->assertValid($linkType, $linkValue);

        $banner = $this->banners->create([
            'image_url' => $data['image_url'],
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'cta_text' => $data['cta_text'] ?? null,
            'link_type' => $linkType,
            'link_value' => $linkType === Banner::LINK_NONE ? null : $linkValue,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $data['is_active'] ?? true,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $this->audit->log($this->actor($request), 'banner.created', $banner, null, $banner->toArray());

        return AdminBannerResource::make($banner)->response()->setStatusCode(201);
    }

    public function update(UpdateBannerRequest $request, string $id): AdminBannerResource
    {
        $banner = $this->findOrFail($id);
        $before = $banner->toArray();

        $data = $request->validated();

        // Validate the link against the effective type/value (falling back to the
        // stored ones when the request omits them), so a partial edit can't leave
        // a dangling target.
        $linkType = $data['link_type'] ?? $banner->link_type;
        $linkValue = array_key_exists('link_value', $data) ? $data['link_value'] : $banner->link_value;

        if (array_key_exists('link_type', $data) || array_key_exists('link_value', $data)) {
            $this->linkValidator->assertValid($linkType, $linkValue);

            $data['link_type'] = $linkType;
            $data['link_value'] = $linkType === Banner::LINK_NONE ? null : $linkValue;
        }

        $banner = $this->banners->update($banner, $data);

        $this->audit->log($this->actor($request), 'banner.updated', $banner, $before, $banner->toArray());

        return AdminBannerResource::make($banner);
    }

    public function destroy(Request $request, string $id): Response
    {
        $banner = $this->findOrFail($id);
        $before = $banner->toArray();

        $this->banners->delete($banner);

        $this->audit->log($this->actor($request), 'banner.deleted', $banner, $before, null);

        return response()->noContent();
    }

    private function findOrFail(string $id): Banner
    {
        $banner = $this->banners->find($id);

        abort_if($banner === null, 404, __('api.not_found'));

        return $banner;
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
