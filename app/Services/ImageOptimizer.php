<?php

namespace App\Services;

use GdImage;

class ImageOptimizer
{
    /**
     * Downscale and re-encode raw image bytes into an optimized JPEG.
     *
     * Only ever shrinks (never upscales), honors EXIF orientation, and
     * flattens transparency onto white so the result renders correctly on
     * the web and in dompdf (which has no WebP support).
     */
    public function optimizeToJpeg(string $contents, int $maxWidth = 800, int $quality = 82): string
    {
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return $contents;
        }

        $source = $this->applyExifOrientation($source, $contents);

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = min($width, $maxWidth);
        $targetHeight = (int) round($height * ($targetWidth / $width));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = (int) imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, $quality);

        return (string) ob_get_clean();
    }

    private function applyExifOrientation(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));
        $rotated = match ($exif['Orientation'] ?? null) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
