<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Core\Config;

/**
 * Google Cloud Vision SafeSearch moderation for listing photos.
 * No image is stored or published until the remote check succeeds.
 */
final class ImageModeration
{
    private const LIKELIHOODS = [
        'UNKNOWN' => 0,
        'VERY_UNLIKELY' => 1,
        'UNLIKELY' => 2,
        'POSSIBLE' => 3,
        'LIKELY' => 4,
        'VERY_LIKELY' => 5,
    ];

    /**
     * @return array{ok: bool, rejected?: bool, error?: string, categories?: array<string,string>}
     */
    public static function inspect(string $imagePath): array
    {
        if (!Config::bool('IMAGE_MODERATION_ENABLED')) {
            return ['ok' => true];
        }

        $apiKey = trim(Config::get('GOOGLE_CLOUD_VISION_API_KEY'));
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Image safety checks are not configured. Please contact support.'];
        }
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            return ['ok' => false, 'error' => 'The image could not be checked. Please try again.'];
        }

        $bytes = file_get_contents($imagePath);
        if ($bytes === false || $bytes === '') {
            return ['ok' => false, 'error' => 'The image could not be checked. Please try again.'];
        }

        $payload = json_encode([
            'requests' => [[
                'image' => ['content' => base64_encode($bytes)],
                'features' => [['type' => 'SAFE_SEARCH_DETECTION']],
            ]],
        ], JSON_THROW_ON_ERROR);

        $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . rawurlencode($apiKey);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $payload,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return ['ok' => false, 'error' => 'Image safety check is temporarily unavailable. Please try again later.'];
        }

        $decoded = json_decode($response, true);
        $statusLine = $http_response_header[0] ?? '';
        if (!is_array($decoded) || !str_contains($statusLine, ' 200 ')) {
            return ['ok' => false, 'error' => 'Image safety check failed. Please try again later.'];
        }

        $annotation = $decoded['responses'][0]['safeSearchAnnotation'] ?? null;
        if (!is_array($annotation)) {
            return ['ok' => false, 'error' => 'The image could not be checked. Please choose another image.'];
        }

        $categories = [];
        foreach (['adult', 'racy', 'violence'] as $category) {
            $value = strtoupper((string) ($annotation[$category] ?? 'UNKNOWN'));
            $categories[$category] = $value;
        }

        // Reject likely/very-likely adult content; reject very-likely suggestive or violent content.
        // POSSIBLE/LIKELY racy scores are conservatively rejected to reduce sexualized marketplace photos.
        $adultScore = self::LIKELIHOODS[$categories['adult']] ?? 0;
        $racyScore = self::LIKELIHOODS[$categories['racy']] ?? 0;
        $violenceScore = self::LIKELIHOODS[$categories['violence']] ?? 0;

        if ($adultScore >= 4 || $racyScore >= 3 || $violenceScore >= 5) {
            return [
                'ok' => false,
                'rejected' => true,
                'categories' => $categories,
                'error' => 'This photo may contain adult, suggestive, or violent content and cannot be uploaded. Please choose a different photo.',
            ];
        }

        return ['ok' => true, 'categories' => $categories];
    }
}
