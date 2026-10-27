<?php
declare(strict_types=1);

namespace App\Core;

final class Upload
{
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * Store an uploaded image under public/uploads/{shop}/{folder}. Returns the
     * public relative path, or null when nothing was uploaded.
     */
    public static function image(?array $file, string $folder, int $maxKb = 4096): ?string
    {
        if (!$file) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new HttpException(422, __('Upload failed.'));
        }
        if ($file['size'] > $maxKb * 1024) {
            throw new HttpException(422, __('Image is too large (max :size MB).', ['size' => round($maxKb / 1024, 1)]));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = self::IMAGE_TYPES[$mime] ?? null;
        if (!$ext || @getimagesize($file['tmp_name']) === false) {
            throw new HttpException(422, __('Only JPG, PNG, WEBP or GIF images are allowed.'));
        }

        $scope = Tenant::slug() ?? '_platform';
        $relDir = 'uploads/' . $scope . '/' . preg_replace('/[^a-z0-9_-]/', '', strtolower($folder));
        $absDir = BASE_PATH . '/public/' . $relDir;
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            throw new \RuntimeException('Cannot create upload directory');
        }
        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) {
            throw new \RuntimeException('Cannot save upload');
        }
        return $relDir . '/' . $name;
    }

    public static function delete(?string $relPath): void
    {
        if ($relPath && str_starts_with($relPath, 'uploads/') && !str_contains($relPath, '..')) {
            @unlink(BASE_PATH . '/public/' . $relPath);
        }
    }
}
