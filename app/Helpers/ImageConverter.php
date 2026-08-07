<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Convert & compress images to WebP before saving to storage.
 *
 * Quality guide:
 *   80 – good balance (default): file ~30-50% smaller than PNG, visually lossless
 *   90 – near-lossless: file ~20-30% smaller, recommended for exam images that need to stay sharp
 */
class ImageConverter
{
    /**
     * Convert raw binary image data to WebP and save to public storage.
     *
     * @param  string  $binary    Raw image bytes (PNG / JPEG / GIF / BMP)
     * @param  string  $directory Storage sub-directory, e.g. 'question-images'
     * @param  int     $quality   WebP quality 1-100 (default 85)
     * @return string|null        Public URL, or null on failure
     */
    public static function convertAndStore(
        string $binary,
        string $directory = 'question-images',
        int $quality = 85
    ): ?string {
        // TURUNKAN THRESHOLD DARI 100 KE 50 BYTES
        if (!extension_loaded('gd') || strlen($binary) < 50) {
            return null;
        }

        // Create GD image from raw binary
        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return null;
        }

        // Preserve transparency (PNG / GIF)
        $width  = imagesx($src);
        $height = imagesy($src);
        $dst    = imagecreatetruecolor($width, $height);

        // Fill background white (WebP lossy does not support transparency well at low quality)
        // For lossless-ish quality ≥ 80 with alpha, keep transparency
        if ($quality >= 80) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        } else {
            // Fill white background for lower quality (lossy, no alpha)
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
        }

        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
        imagedestroy($src);

        // Capture WebP output into a buffer
        ob_start();
        imagewebp($dst, null, $quality);
        $webpBinary = ob_get_clean();
        imagedestroy($dst);

        if (!$webpBinary || strlen($webpBinary) < 50) {
            return null;
        }

        $filename = uniqid('img_', true) . '.webp';
        Storage::disk('public')->put($directory . '/' . $filename, $webpBinary);

        return Storage::url($directory . '/' . $filename);
    }

    /**
     * Convert an uploaded file (from TinyMCE or form) to WebP and store it.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @param  int     $quality
     * @return string|null  Public URL or null on failure
     */
    public static function convertUploadedFile(
        $file,
        string $directory = 'question-images',
        int $quality = 85
    ): ?string {
        $binary = file_get_contents($file->getPathname());
        if (!$binary) return null;

        return static::convertAndStore($binary, $directory, $quality);
    }
}
