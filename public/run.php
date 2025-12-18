<?php

header("Content-Type: text/plain");

/**
 * =====================================
 * INPUT CONTEXT (FROM USER / API)
 * =====================================
 */
$CONTEXT = [
    "website_url"    => "https://cyfamod.com",
    "business_name"  => "cyfamod technologies",
    "industry"       => "Technology",
    "country"        => ["Nigeria"],
    "city"           => "abuja",
    "target_audience"=> "Consumers and professionals seeking premium technology services",
    "keywords"       => ["branding", "web-development", "marketing"]
];

$SERPER_API_KEY = "";
$COUNTRY_CODE   = "ng";

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
 * =====================================
 * HELPERS
 * =====================================
 */

/**
 * Normalize business name into strong tokens
 */
function normalizeName(string $name): array
{
    $name = strtolower($name);
    $name = preg_replace('/\b(ltd|limited|inc|llc|company|technologies|technology)\b/', '', $name);
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
 * Build CONTEXT-AWARE search query
 */
function buildSearchQuery(array $ctx, string $platform, string $domain): string
{
    $base = "{$ctx['business_name']} {$ctx['city']} {$ctx['country'][0]}";

    return match ($platform) {
        'youtube' => "{$base} YouTube channel",
        'x'       => "{$base} X",
        'tiktok'  => "{$base} TikTok",
        default   => "{$base} site:{$domain}",
    };
}

/**
 * Find social profile via Serper
 */
function findViaSerper(
    array $ctx,
    string $platform,
    string $domain,
    string $apiKey,
    string $countryCode
): ?string {

    $tokens = normalizeName($ctx['business_name']);
    $query  = buildSearchQuery($ctx, $platform, $domain);

    $payload = json_encode([
        "q"  => $query,
        "gl" => $countryCode
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
 * Google Business Profile detection (CONTEXT-AWARE)
 */
function getGoogleBusinessProfile(
    array $ctx,
    string $apiKey,
    string $countryCode
): array {

    $queries = [
        "{$ctx['business_name']} {$ctx['city']} {$ctx['country'][0]}",
        "{$ctx['business_name']} {$ctx['city']} address",
        "{$ctx['business_name']} {$ctx['city']} Google Maps",
        "{$ctx['business_name']} {$ctx['city']} phone",
    ];

    $profile = [
        "found" => false,
        "name" => null,
        "address" => null,
        "phone" => null,
        "rating" => null,
        "reviews_count" => null,
        "confidence_level" => "low"
    ];

    foreach ($queries as $query) {

        $payload = json_encode([
            "q"  => $query,
            "gl" => $countryCode,
            "hl" => "en"
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

        if (!$response) continue;

        $data = json_decode($response, true);

        if (!empty($data['knowledgeGraph'])) {
            $kg = $data['knowledgeGraph'];

            return [
                "found" => true,
                "name" => $kg['title'] ?? null,
                "address" => $kg['address'] ?? null,
                "phone" => $kg['phone'] ?? null,
                "rating" => $kg['rating'] ?? null,
                "reviews_count" => $kg['reviews'] ?? null,
                "confidence_level" => "high"
            ];
        }

        if (!empty($data['places'])) {
            $p = $data['places'][0];

            return [
                "found" => true,
                "name" => $p['title'] ?? null,
                "address" => $p['address'] ?? null,
                "phone" => $p['phoneNumber'] ?? null,
                "rating" => $p['rating'] ?? null,
                "reviews_count" => $p['reviews'] ?? null,
                "confidence_level" => "medium"
            ];
        }
    }

    return $profile;
}

/**
 * =====================================
 * EXECUTION
 * =====================================
 */

$websiteSocials = extractSocialsFromWebsite($CONTEXT['website_url']);

echo "SOCIAL PRESENCE REPORT\n";
echo "======================\n";
echo "Business: {$CONTEXT['business_name']}\n";
echo "Website: {$CONTEXT['website_url']}\n\n";

foreach ($PLATFORMS as $platform => $domain) {

    if (!empty($websiteSocials[$platform])) {
        echo strtoupper($platform) . ":\n";
        echo "  URL: {$websiteSocials[$platform]}\n";
        echo "  Source: website\n";
        echo "  Confidence: HIGH\n\n";
        continue;
    }

    $serperUrl = findViaSerper(
        $CONTEXT,
        $platform,
        $domain,
        $SERPER_API_KEY,
        $COUNTRY_CODE
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

$gbp = getGoogleBusinessProfile(
    $CONTEXT,
    $SERPER_API_KEY,
    $COUNTRY_CODE
);

echo "GOOGLE BUSINESS PROFILE\n";
echo "======================\n";
echo "Found: " . ($gbp['found'] ? "YES" : "NO") . "\n";
echo "Name: " . ($gbp['name'] ?? "N/A") . "\n";
echo "Address: " . ($gbp['address'] ?? "N/A") . "\n";
echo "Phone: " . ($gbp['phone'] ?? "N/A") . "\n";
echo "Rating: " . ($gbp['rating'] ?? "N/A") . "\n";
echo "Reviews Count: " . ($gbp['reviews_count'] ?? "N/A") . "\n";
echo "Confidence: {$gbp['confidence_level']}\n";
