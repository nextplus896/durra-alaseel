<?php
/**
 * Test script to verify Pusher Beams auth endpoint connectivity
 * 
 * Run this script from command line:
 * php test-pusher-connectivity.php
 */

echo "═══════════════════════════════════════════════════\n";
echo "🧪 PUSHER BEAMS CONNECTIVITY TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

// Configuration
$baseUrl = 'http://192.168.1.211:8001/api/v1';
$token = 'YOUR_AUTH_TOKEN_HERE'; // Replace with actual token

echo "📍 Base URL: $baseUrl\n";
echo "🔑 Token: " . (strlen($token) > 20 ? substr($token, 0, 20) . '...' : $token) . "\n\n";

// Test 1: Simple connectivity test
echo "TEST 1: Testing base URL connectivity...\n";
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Connection failed: $error\n\n";
} else {
    echo "✅ Connection successful (HTTP $httpCode)\n\n";
}

// Test 2: Test auth endpoint
echo "TEST 2: Testing Pusher test-auth endpoint...\n";
$testAuthUrl = "$baseUrl/user/pusher/test-auth";
echo "📍 URL: $testAuthUrl\n";

$ch = curl_init($testAuthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Request failed: $error\n";
} else {
    echo "📤 HTTP Code: $httpCode\n";
    echo "📥 Response:\n";
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse) {
        echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
}

echo "\n";

// Test 3: Test beams-auth endpoint
echo "TEST 3: Testing Pusher beams-auth endpoint...\n";
$beamsAuthUrl = "$baseUrl/user/pusher/beams-auth";
echo "📍 URL: $beamsAuthUrl\n";

$ch = curl_init($beamsAuthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Request failed: $error\n";
} else {
    echo "📤 HTTP Code: $httpCode\n";
    echo "📥 Response:\n";
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse) {
        echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($jsonResponse['token'])) {
            echo "\n✅ Token received successfully!\n";
            echo "Token length: " . strlen($jsonResponse['token']) . " characters\n";
        }
    } else {
        echo $response . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════\n";
echo "TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════\n";
