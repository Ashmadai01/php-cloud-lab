<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Upload;

final class UploadController extends Controller
{
    public function serve(string $path): void
    {
        // Public listing/avatar images are served from storage/uploads via this
        // endpoint so the document root stays limited to public/.
        $base = realpath(\SdutSwap\Core\Config::basePath() . '/' . trim(\SdutSwap\Core\Config::get('UPLOAD_DIR', 'storage/uploads'), '/'));
        if ($base === false) abort(404, 'Not found');
        $full = realpath($base . '/' . ltrim($path, '/'));
        if ($full === false || !str_starts_with($full, $base) || !is_file($full)) abort(404, 'Not found');
        $mime = mime_content_type($full) ?: 'application/octet-stream';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) abort(404, 'Not found');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($full);
        exit;
    }
}
