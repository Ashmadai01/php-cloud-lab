<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Upload
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    public static function storeImage(array $file, string $subdir, int $maxBytes): array
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }
        if (($file['size'] ?? 0) > $maxBytes || ($file['size'] ?? 0) <= 0) {
            return ['ok' => false, 'error' => 'File is too large or empty.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            Audit::security('rejected_upload', 'medium', "Rejected upload mime: {$mime}");
            return ['ok' => false, 'error' => 'Only JPEG, PNG, or WebP images are allowed.'];
        }
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext = $extMap[$mime] ?? 'jpg';
        // Re-encode with GD to strip metadata / neutralize polyglots where possible.
        $image = null;
        try {
            $image = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
                'image/png' => @imagecreatefrompng($file['tmp_name']),
                'image/webp' => @imagecreatefromwebp($file['tmp_name']),
                default => null,
            };
        } catch (\Throwable) {
            $image = null;
        }
        if ($image === false || $image === null) {
            return ['ok' => false, 'error' => 'Could not process this image.'];
        }
        $w = imagesx($image); $h = imagesy($image);
        if ($w > 4000 || $h > 4000 || $w < 10 || $h < 10) {
            imagedestroy($image);
            return ['ok' => false, 'error' => 'Image dimensions are invalid.'];
        }
        $base = Config::basePath() . '/' . trim(Config::get('UPLOAD_DIR', 'storage/uploads'), '/');
        $dir = $base . '/' . trim($subdir, '/');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($image, $dest, 85),
            'image/png' => imagepng($image, $dest, 6),
            'image/webp' => imagewebp($image, $dest, 85),
            default => false,
        };
        imagedestroy($image);
        if (!$saved) return ['ok' => false, 'error' => 'Could not save image.'];
        @chmod($dest, 0644);
        return ['ok' => true, 'path' => trim($subdir, '/') . '/' . $filename, 'mime' => $mime];
    }

    public static function storePrivateImage(array $file, string $subdir, int $maxBytes): array
    {
        // Same validation but stored under PRIVATE_DIR (outside web root).
        $result = self::storeImage($file, $subdir, $maxBytes);
        if (!$result['ok']) return $result;
        // Move from uploads to private if needed — storeImage already used UPLOAD_DIR base.
        // Re-resolve: files are under UPLOAD_DIR; for private we relocate.
        $basePath = Config::basePath();
        $srcBase = realpath($basePath . '/' . trim(Config::get('UPLOAD_DIR', 'storage/uploads'), '/'));
        $privRoot = $basePath . '/' . trim(Config::get('PRIVATE_DIR', 'storage/private'), '/');
        $src = $srcBase === false ? false : realpath($srcBase . '/' . $result['path']);
        $dest = $privRoot . '/' . $result['path'];
        $destDir = dirname($dest);

        $sourcePrefix = $srcBase === false ? '' : rtrim($srcBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($src === false || !str_starts_with($src, $sourcePrefix) || !is_file($src)) {
            if ($src !== false && is_file($src)) @unlink($src);
            return ['ok' => false, 'error' => 'Could not move image to private storage.'];
        }
        if (!is_dir($destDir) && !mkdir($destDir, 0700, true) && !is_dir($destDir)) {
            @unlink($src);
            return ['ok' => false, 'error' => 'Could not prepare private storage.'];
        }
        if (!@rename($src, $dest)) {
            @unlink($src);
            return ['ok' => false, 'error' => 'Could not move image to private storage.'];
        }
        if (!@chmod($dest, 0600)) {
            @unlink($dest);
            return ['ok' => false, 'error' => 'Could not secure private image permissions.'];
        }
        return ['ok' => true, 'path' => $result['path'], 'mime' => $result['mime']];
    }

    /** Turns PHP's $_FILES['x'] (single or x[] multi-upload) into a flat list of single-file arrays. */
    public static function normalizeMulti(array $field): array
    {
        if (!isset($field['name'])) return [];
        if (!is_array($field['name'])) return [$field];
        $out = [];
        foreach (array_keys($field['name']) as $i) {
            $out[] = [
                'name' => $field['name'][$i] ?? '',
                'type' => $field['type'][$i] ?? '',
                'tmp_name' => $field['tmp_name'][$i] ?? '',
                'error' => $field['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $field['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Stores up to $maxFiles images from a multi-upload field in private storage.
     * Every file goes through the same MIME/size/re-encode checks as storePrivateImage().
     * @return array{paths: list<string>, errors: list<string>}
     */
    public static function storePrivateImages(?array $field, string $subdir, int $maxBytes, int $maxFiles = 3): array
    {
        $paths = [];
        $errors = [];
        if (!is_array($field)) return ['paths' => $paths, 'errors' => $errors];
        $files = array_values(array_filter(
            self::normalizeMulti($field),
            fn(array $f): bool => ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ));
        if (count($files) > $maxFiles) {
            $errors[] = "You can attach at most {$maxFiles} images; extra files were ignored.";
            $files = array_slice($files, 0, $maxFiles);
        }
        foreach ($files as $f) {
            $r = self::storePrivateImage($f, $subdir, $maxBytes);
            if ($r['ok']) $paths[] = (string) $r['path'];
            else $errors[] = (string) $r['error'];
        }
        return ['paths' => $paths, 'errors' => $errors];
    }

    public static function publicPath(string $storedPath): string
    {
        return url('uploads/' . ltrim($storedPath, '/'));
    }

    public static function deletePublic(string $storedPath): void
    {
        $base = realpath(Config::basePath() . '/' . trim(Config::get('UPLOAD_DIR', 'storage/uploads'), '/'));
        if ($base === false) return;
        $full = realpath($base . '/' . ltrim($storedPath, '/'));
        if ($full !== false && str_starts_with($full, $base) && is_file($full)) unlink($full);
    }

    /** Deletes a file from private storage (path validated against PRIVATE_DIR). Returns true if a file was removed. */
    public static function deletePrivate(string $storedPath): bool
    {
        if ($storedPath === '') return false;
        $full = self::privateFullPath($storedPath);
        return $full !== null && @unlink($full);
    }

    public static function privateFullPath(string $storedPath): ?string
    {
        $base = realpath(Config::basePath() . '/' . trim(Config::get('PRIVATE_DIR', 'storage/private'), '/'));
        if ($base === false) return null;
        $full = realpath($base . '/' . ltrim($storedPath, '/'));
        $basePrefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($full === false || !str_starts_with($full, $basePrefix) || !is_file($full)) return null;
        return $full;
    }
}
