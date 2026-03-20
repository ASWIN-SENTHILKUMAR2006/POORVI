<?php
/**
 * Instagram Feed Fetcher
 * Caches Instagram posts to avoid rate limiting and IP blocks.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function is_valid_instagram_result($payload) {
    if (!$payload) {
        return false;
    }

    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        return false;
    }

    return isset($decoded['success'], $decoded['data'])
        && $decoded['success'] === true
        && is_array($decoded['data'])
        && count($decoded['data']) > 0;
}

function extract_media_edges($data) {
    $paths = [
        ['data', 'user', 'edge_owner_to_timeline_media', 'edges'],
        ['graphql', 'user', 'edge_owner_to_timeline_media', 'edges'],
        ['data', 'xdt_api__v1__feed__user_timeline_graphql_connection', 'edges'],
    ];

    foreach ($paths as $path) {
        $cursor = $data;
        foreach ($path as $key) {
            if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
                $cursor = null;
                break;
            }
            $cursor = $cursor[$key];
        }

        if (is_array($cursor) && count($cursor) > 0) {
            return $cursor;
        }
    }

    return [];
}

function build_posts_from_edges($edges, $maxCount = 6) {
    $posts = [];

    foreach ($edges as $edge) {
        if (count($posts) >= $maxCount) {
            break;
        }

        $node = isset($edge['node']) && is_array($edge['node']) ? $edge['node'] : $edge;
        if (!is_array($node)) {
            continue;
        }

        $shortcode = isset($node['shortcode']) ? $node['shortcode'] : null;
        $image = isset($node['thumbnail_src']) ? $node['thumbnail_src'] : (isset($node['display_url']) ? $node['display_url'] : null);

        if (!$shortcode || !$image || !preg_match('/^https?:\/\//i', $image)) {
            continue;
        }

        $posts[] = [
            'link' => 'https://www.instagram.com/p/' . $shortcode . '/',
            'image' => $image,
        ];
    }

    return $posts;
}

// Cache configuration
$cache_file = 'ig_cache.json';
// Cache for 4 hours (14400 seconds) - IG image URLs usually expire after a few days
$cache_time = 14400; 

// If cache exists and is valid, return it
if (file_exists($cache_file) && time() - filemtime($cache_file) < $cache_time) {
    $cached_data = file_get_contents($cache_file);
    if (is_valid_instagram_result($cached_data)) {
        echo $cached_data;
        exit;
    }
}

// Fetch new data
$username = 'poorviphotography';
$url = "https://www.instagram.com/api/v1/users/web_profile_info/?username={$username}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-ig-app-id: 936619743392459',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
    'Accept-Language: en-US,en;q=0.9',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $http_code === 200) {
    $data = json_decode($response, true);

    if (is_array($data)) {
        $edges = extract_media_edges($data);
        $posts = build_posts_from_edges($edges, 6);

        if (count($posts) > 0) {
            $result = json_encode(['success' => true, 'data' => $posts]);

            // Save only valid payloads to cache.
            file_put_contents($cache_file, $result);
            echo $result;
            exit;
        }
    }
}

// If we reach here, the API request failed or was rate-limited.
// Fallback to expired cache if it exists
if (file_exists($cache_file)) {
    $cached_data = file_get_contents($cache_file);
    if (is_valid_instagram_result($cached_data)) {
        echo $cached_data;
        exit;
    }
}

echo json_encode([
    'success' => false,
    'error' => 'Could not fetch feed and no valid cache available.',
    'data' => [],
]);
?>