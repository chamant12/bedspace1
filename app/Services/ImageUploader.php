<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Resizes, re-encodes and stores uploaded images on the configured uploads
 * disk (config/filesystems.php "uploads_disk"). Switching to S3/GCS later is
 * a config change; nothing here writes to a local path directly.
 */
class ImageUploader
{
    const MAX_DIMENSION = 1600;
    const THUMB_DIMENSION = 480;
    const QUALITY = 80;

    public static function disk(): string
    {
        return config('filesystems.uploads_disk');
    }

    /**
     * Stores a full-size and a thumbnail WebP version of the image.
     * Returns the file name (shared by both versions) and the disk used.
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $directory = trim($directory, '/');
        $fileName = uniqid().'.webp';
        $disk = Storage::disk(self::disk());

        // Re-encoding strips EXIF/metadata; upsize() stops small images being enlarged
        $disk->put($directory.'/'.$fileName, $this->encode($file, self::MAX_DIMENSION), 'public');
        $disk->put($directory.'/thumb_'.$fileName, $this->encode($file, self::THUMB_DIMENSION), 'public');

        return ['file_name' => $fileName, 'disk' => self::disk()];
    }

    public function delete(string $diskName, string $directory, string $fileName): void
    {
        $directory = trim($directory, '/');
        Storage::disk($diskName)->delete([
            $directory.'/'.$fileName,
            $directory.'/thumb_'.$fileName,
        ]);
    }

    private function encode(UploadedFile $file, int $maxDimension): string
    {
        return (string) Image::make($file)
            ->resize($maxDimension, $maxDimension, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('webp', self::QUALITY);
    }
}
