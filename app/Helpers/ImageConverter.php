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
        int $quality = 85,
        int $maxDimension = 1600
    ): ?string {
        // TURUNKAN THRESHOLD DARI 100 KE 50 BYTES
        // Jika GD tidak ada ATAU GD tanpa dukungan WebP, biarkan pemanggil
        // menyimpan gambar asli (jangan sampai fatal error 500).
        if (!extension_loaded('gd') || !function_exists('imagewebp') || strlen($binary) < 50) {
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

        // Downscale: batasi sisi terpanjang. Buffer GD sebesar W*H*4 byte per
        // lapisan, jadi foto 4000x3000 memakan ~48MB hanya untuk 1 buffer.
        // Membatasi ke 1600px memangkas memori & ukuran file WebP drastis
        // tanpa mengurangi kualitas tampilan (soal ditampilkan kecil).
        $maxDimension = max(1, (int) $maxDimension);
        $scale = 1.0;
        $longest = max($width, $height);
        if ($longest > $maxDimension) {
            $scale = $maxDimension / $longest;
        }
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $dst = imagecreatetruecolor($newW, $newH);

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

        if ($scale < 1.0) {
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        } else {
            imagecopy($dst, $src, 0, 0, 0, 0, $newW, $newH);
        }
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
        int $quality = 85,
        int $maxDimension = 1600
    ): ?string {
        $binary = file_get_contents($file->getPathname());
        if (!$binary) return null;

        return static::convertAndStore($binary, $directory, $quality, $maxDimension);
    }
}
