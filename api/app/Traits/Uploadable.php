<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

trait Uploadable
{
    public function uploadFile($file, string $folder = 'others', ?int $resizeWidth = null, ?int $resizeHeight = null): ?string
    {
        if (blank($file)) {
            return null;
        }

        if (is_string($file)) {
            return basename(str_replace('\\', '/', $file));
        }

        $manager = new ImageManager(new Driver());

        // $fullPath = base_path('../assets/uploads/' . $folder);
        $fullPath = public_path('assets/uploads/' . $folder);
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        if (!is_object($file) || !method_exists($file, 'getClientOriginalExtension')) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;
        $destination = $fullPath . DIRECTORY_SEPARATOR . $fileName;

        $rasterImageExtensions = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp'];

        if (in_array($extension, $rasterImageExtensions, true)) {
            $image = $manager->read($file->getRealPath());

            if ($resizeWidth || $resizeHeight) {
                $image->scaleDown($resizeWidth, $resizeHeight);
            }

            $image->save($destination);
        } else {
            File::copy($file->getRealPath(), $destination);
        }

        return $fileName;
    }

    public function deleteFile(?string $fileName, string $folder = 'others'): void
    {
        if (!$fileName) {
            return;
        }

        // $filePath = base_path("../assets/uploads/{$folder}/{$fileName}");
        $filePath = public_path("assets/uploads/{$folder}/{$fileName}");
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    public function getImagePath(?string $fileName, string $folder = 'others', string $default = 'default.png'): string
    {
        $path = "assets/uploads/{$folder}/{$fileName}";

        // if ($fileName && File::exists(base_path('../' . $path))) {
        if ($fileName && File::exists(public_path($path))) {
            return asset($path);
        }

        return asset("assets/uploads/{$default}");
    }
}
