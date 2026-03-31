#!/usr/bin/env php
<?php

/**
 * Test script to verify Pusher Beams authentication endpoint
 * 
 * Usage: php test-beams-auth.php
 * 
 * This script tests the /api/v1/user/pusher/beams-auth endpoint
 * to ensure it's returning the correct token format for authenticated users.
 */

echo "═══════════════════════════════════════════════════\n";
echo "🔔 PUSHER BEAMS AUTH ENDPOINT TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

// Configuration
$baseUrl = 'http://localhost/api/v1/user/pusher/beams-auth';  // Update this
$bearerToken = 'YOUR_USER_TOKEN_HERE';  // Update this with a real user token

echo "Testing endpoint: $baseUrl\n";
echo "Using bearer token: " . substr($bearerToken, 0, 20) . "...\n\n";

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $bearerToken,
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo "─────────────────────────────────────────────────────\n";
echo $response . "\n";
echo "─────────────────────────────────────────────────────\n\n";

if ($httpCode === 200) {
    $decoded = json_decode($response, true);
    if (isset($decoded['token'])) {
        echo "✅ SUCCESS: Token found in response\n";
        echo "Token (first 50 chars): " . substr($decoded['token'], 0, 50) . "...\n";
    } else {
        echo "❌ ERROR: Response is 200 but no 'token' field found\n";
        echo "Expected format: {\"token\": \"eyJ0eXAi...\"}\n";
        echo "Got: " . json_encode($decoded) . "\n";
    }
} else {
    echo "❌ ERROR: HTTP $httpCode - Authentication failed\n";
    echo "Check:\n";
    echo "  1. Base URL is correct\n";
    echo "  2. Bearer token is valid and not expired\n";
    echo "  3. User exists and is authenticated\n";
    echo "  4. Pusher Beams is configured in admin panel\n";
}

echo "\n═══════════════════════════════════════════════════\n";
