<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageSanitizer
{
    /** Re-encode an uploaded image to remove metadata and non-image payloads. */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($image === false) {
            throw new RuntimeException('The uploaded image could not be processed.');
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.jpg';
        ob_start();
        imagejpeg($image, null, 88);
        $contents = ob_get_clean();
        imagedestroy($image);

        if ($contents === false || ! Storage::disk($disk)->put($path, $contents)) {
            throw new RuntimeException('The uploaded image could not be stored.');
        }

        return $path;
    }
}
