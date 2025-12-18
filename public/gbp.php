<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists(Dotenv::class)) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$SERPER_API_KEY        = $_ENV['SERPER_API_KEY'] ?? getenv('SERPER_API_KEY') ?? '';
$GOOGLE_PLACES_API_KEY = $_ENV['GOOGLE_PLACES_API_KEY'] ?? getenv('GOOGLE_PLACES_API_KEY') ?? '';

if ($SERPER_API_KEY === '') {
    http_response_code(500);
    echo "SERPER_API_KEY is not configured.";
    exit;
}

if ($GOOGLE_PLACES_API_KEY === '') {
    http_response_code(500);
    echo "GOOGLE_PLACES_API_KEY is not configured.";
    exit;
}

header("Content-Type: text/plain");

/**
 * =========================
 * CONFIG
 * =========================
 */
$BUSINESS_NAME  = "Cyfamod Technologies";
$WEBSITE_URL    = "https://cyfamod.com/";
$COUNTRY        = "Nigeria";

/**
 * Platforms (UNCHANGED)
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
 * =========================
 * SOCIAL DISCOVERY (UNCHANGED – WORKING)
 * =========================
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
        $tokens[] = implode('', $parts);
    }

    return array_unique($tokens);
}

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

function extractUsername(string $url, string $domain): ?string
{
    $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    if (!$path) return null;

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

function buildQuery(string $business, string $platform, string $domain): string
{
    return match ($platform) {
        'youtube' => "{$business} YouTube channel",
        'x'       => "{$business} X",
        'tiktok'  => "{$business} TikTok",
        default   => "{$business} site:{$domain}",
    };
}

function findViaSerper(
    string $business,
    string $platform,
    string $domain,
    string $apiKey,
    string $country
): array {

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

    if (!$response) {
        return ["url" => null, "source" => "none", "confidence" => "NONE"];
    }

    $data = json_decode($response, true);
    if (empty($data['organic'])) {
        return ["url" => null, "source" => "none", "confidence" => "NONE"];
    }

    foreach ($data['organic'] as $result) {
        $url = $result['link'] ?? '';
        if (!str_contains($url, $domain)) continue;

        $username = extractUsername($url, $domain);
        if (!$username) continue;

        $username = strtolower($username);

        foreach ($tokens as $token) {
            if (str_contains($username, $token)) {
                return [
                    "url" => $url,
                    "source" => "search",
                    "confidence" => "LOW"
                ];
            }
        }
    }

    return ["url" => null, "source" => "none", "confidence" => "NONE"];
}

/**
 * =========================
 * GOOGLE BUSINESS PROFILE (GOOGLE PLACES API)
 * =========================
 */
function detectGoogleBusinessProfileViaPlaces(
    string $business,
    string $country,
    string $apiKey
): array {

    $query = urlencode("{$business} {$country}");
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&key={$apiKey}";

    // Use curl for better error handling and SSL certificate bypass if needed
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
        CURLOPT_HTTPHEADER => [
            "Referer: " . getenv('APP_URL') ?? 'http://localhost:8000'
        ]
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$res) {
        error_log("GBP API Error - Text Search CURL: {$curlError}, URL: {$url}");
        return gbpNotFound();
    }

    if ($httpCode !== 200) {
        error_log("GBP API Error - Text Search HTTP: {$httpCode}, Response: {$res}");
        return gbpNotFound();
    }

    $data = json_decode($res, true);

    if (!isset($data['status'])) {
        error_log("GBP API Error - Invalid response (no status): " . substr($res, 0, 200));
        return gbpNotFound();
    }

    if ($data['status'] !== 'OK') {
        error_log("GBP API Error - Text Search Status: {$data['status']}, Query: {$query}, Full Response: {$res}");
        return gbpNotFound();
    }

    if (empty($data['results'][0])) {
        error_log("GBP API Warning - No results found for: {$business} in {$country}");
        return gbpNotFound();
    }

    $place = $data['results'][0];
    $placeId = $place['place_id'];

    // Get place details
    $detailsUrl = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,formatted_address,formatted_phone_number,rating,user_ratings_total&key={$apiKey}";
    
    $ch = curl_init($detailsUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);

    $detailsRes = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$detailsRes || $httpCode !== 200) {
        error_log("GBP API Error - Details: HTTP {$httpCode}, Place ID: {$placeId}");
        return gbpNotFound();
    }

    $detailsData = json_decode($detailsRes, true);

    if ($detailsData['status'] !== 'OK') {
        error_log("GBP API Error - Details Status: {$detailsData['status']}, Place ID: {$placeId}");
        return gbpNotFound();
    }

    $details = $detailsData['result'] ?? [];

    if (empty($details)) {
        error_log("GBP API Warning - No details found for Place ID: {$placeId}");
        return gbpNotFound();
    }

    return [
        "found" => "YES",
        "name" => $details['name'] ?? $business,
        "address" => $details['formatted_address'] ?? "N/A",
        "phone" => $details['formatted_phone_number'] ?? "N/A",
        "rating" => $details['rating'] ?? "N/A",
        "reviews" => $details['user_ratings_total'] ?? "N/A",
        "confidence" => "very_high"
    ];
}

function gbpNotFound(): array
{
    return [
        "found" => "NO",
        "name" => "N/A",
        "address" => "N/A",
        "phone" => "N/A",
        "rating" => "N/A",
        "reviews" => "N/A",
        "confidence" => "low"
    ];
}

/**
 * =========================
 * OUTPUT
 * =========================
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

    $result = findViaSerper(
        $BUSINESS_NAME,
        $platform,
        $domain,
        $SERPER_API_KEY,
        $COUNTRY
    );

    echo strtoupper($platform) . ":\n";
    echo "  URL: " . ($result['url'] ?? "NOT FOUND") . "\n";
    echo "  Source: {$result['source']}\n";
    echo "  Confidence: {$result['confidence']}\n\n";
}

$gbp = detectGoogleBusinessProfileViaPlaces(
    $BUSINESS_NAME,
    $COUNTRY,
    $GOOGLE_PLACES_API_KEY
);

echo "GOOGLE BUSINESS PROFILE\n";
echo "======================\n";
echo "Found: {$gbp['found']}\n";
echo "Name: {$gbp['name']}\n";
echo "Address: {$gbp['address']}\n";
echo "Phone: {$gbp['phone']}\n";
echo "Rating: {$gbp['rating']}\n";
echo "Reviews Count: {$gbp['reviews']}\n";
echo "Confidence: {$gbp['confidence']}\n\n";

// Show log file location for debugging
echo "DEBUG INFO\n";
echo "==========\n";
$logFile = dirname(__DIR__) . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo "Check error logs at: {$logFile}\n";
    echo "Run: tail -f {$logFile}\n";
} else {
    echo "Log file not found at: {$logFile}\n";
}
