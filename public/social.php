<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists(Dotenv::class)) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$SERPER_API_KEY = $_ENV['SERPER_API_KEY'] ?? getenv('SERPER_API_KEY') ?? '';

if ($SERPER_API_KEY === '') {
    http_response_code(500);
    echo "SERPER_API_KEY is not configured.";
    exit;
}

header("Content-Type: text/plain");

/**
 * CONFIG
 */
$BUSINESS_NAME  = "Cyfamod Technologies";
$WEBSITE_URL    = "https://cyfamod.com/";
$COUNTRY        = "Nigeria";

/**
 * Platforms
 */
$PLATFORMS = [
    "facebook"  => "facebook.com",
    "instagram" => "instagram.com",
    "linkedin"  => "linkedin.com",
    "youtube"   => "youtube.com",
    "x"         => "x.com",
    "tiktok"    => "tiktok.com",
];

/**
 * Normalize business name into strong tokens
 */
function normalizeName(string $name): array
{
    $name = strtolower($name);
    $name = preg_replace('/\b(ltd|limited|inc|llc|company)\b/', '', $name);
    $name = preg_replace('/[^a-z0-9 ]/', '', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));

    $parts = explode(' ', $name);
    $tokens = [];

    foreach ($parts as $p) {
        if (strlen($p) >= 4) {
            $tokens[] = $p;
        }
    }

    if (count($parts) > 1) {
        $tokens[] = implode('', $parts); // joined token
    }

    return array_unique($tokens);
}

/**
 * Extract socials directly from website (NO SERPER)
 */
function extractSocialsFromWebsite(string $website): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $website,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return [];

    preg_match_all(
        '/https?:\/\/(www\.)?(facebook|instagram|linkedin|youtube|tiktok|x)\.com\/[^\s"\'<>]+/i',
        $html,
        $matches
    );

    $found = [];

    foreach ($matches[0] as $url) {
        if (str_contains($url, 'facebook.com'))  $found['facebook']  = $url;
        if (str_contains($url, 'instagram.com')) $found['instagram'] = $url;
        if (str_contains($url, 'linkedin.com'))  $found['linkedin']  = $url;
        if (str_contains($url, 'youtube.com'))   $found['youtube']   = $url;
        if (str_contains($url, 'tiktok.com'))    $found['tiktok']    = $url;
        if (str_contains($url, 'x.com'))         $found['x']          = $url;
    }

    return $found;
}

/**
 * Extract username strictly per platform
 */
function extractUsername(string $url, string $domain): ?string
{
    $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    if (!$path) return null;

    // Reject content URLs
    $blocked = ['p/', 'reel/', 'tv/', 'watch', 'shorts', 'video'];
    foreach ($blocked as $b) {
        if (str_contains($path, $b)) return null;
    }

    if ($domain === 'youtube.com') {
        if (str_starts_with($path, '@')) return substr($path, 1);
        if (str_starts_with($path, 'c/')) return substr($path, 2);
        if (str_starts_with($path, 'channel/')) return substr($path, 8);
        return null;
    }

    if ($domain === 'instagram.com') {
        return str_contains($path, '/') ? null : $path;
    }

    if ($domain === 'tiktok.com') {
        return str_starts_with($path, '@') ? substr($path, 1) : null;
    }

    if ($domain === 'linkedin.com') {
        return str_starts_with($path, 'company/') ? substr($path, 8) : null;
    }

    return explode('/', $path)[0];
}

/**
 * Build platform-aware Serper query
 */
function buildQuery(string $business, string $platform, string $domain): string
{
    return match ($platform) {
        'youtube' => "{$business} YouTube channel",
        'x'       => "{$business} X",
        'tiktok'  => "{$business} TikTok",
        default   => "{$business} site:{$domain}",
    };
}

/**
 * Serper-based social search
 */
function findViaSerper(
    string $business,
    string $platform,
    string $domain,
    string $apiKey,
    string $country
): ?string {

    $tokens = normalizeName($business);
    $query  = buildQuery($business, $platform, $domain);

    $payload = json_encode([
        "q"  => $query,
        "gl" => $country
    ]);

    $ch = curl_init("https://google.serper.dev/search");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-API-KEY: {$apiKey}"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;

    $data = json_decode($response, true);
    if (empty($data['organic'])) return null;

    foreach ($data['organic'] as $result) {
        $url = $result['link'] ?? '';
        if (!str_contains($url, $domain)) continue;

        $username = extractUsername($url, $domain);
        if (!$username) continue;

        $username = strtolower($username);

        foreach ($tokens as $token) {
            if (str_contains($username, $token)) {
                return $url;
            }
        }
    }

    return null;
}

/**
 * RESOLUTION LOGIC
 */
$websiteSocials = extractSocialsFromWebsite($WEBSITE_URL);

echo "SOCIAL PRESENCE REPORT\n";
echo "======================\n";
echo "Business: {$BUSINESS_NAME}\n";
echo "Website: {$WEBSITE_URL}\n\n";

foreach ($PLATFORMS as $platform => $domain) {

    if (!empty($websiteSocials[$platform])) {
        echo strtoupper($platform) . ":\n";
        echo "  URL: {$websiteSocials[$platform]}\n";
        echo "  Source: website\n";
        echo "  Confidence: HIGH\n\n";
        continue;
    }

    $serperUrl = findViaSerper(
        $BUSINESS_NAME,
        $platform,
        $domain,
        $SERPER_API_KEY,
        $COUNTRY
    );

    if ($serperUrl) {
        echo strtoupper($platform) . ":\n";
        echo "  URL: {$serperUrl}\n";
        echo "  Source: search\n";
        echo "  Confidence: LOW\n\n";
    } else {
        echo strtoupper($platform) . ":\n";
        echo "  URL: NOT FOUND\n";
        echo "  Source: none\n";
        echo "  Confidence: NONE\n\n";
    }
}
