#!/usr/bin/env php
<?php
/**
 * Pusher Beams Auth Endpoint Tester
 * 
 * This script tests the Pusher Beams authentication endpoint
 * to verify it's working correctly before testing with the mobile app.
 * 
 * Usage:
 *   php test-pusher-endpoint.php
 * 
 * Requirements:
 *   - PHP with cURL extension
 *   - Valid user authentication token
 */

// ANSI color codes for better output
define('COLOR_RESET', "\033[0m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_CYAN', "\033[36m");

function log_header($message) {
    echo COLOR_CYAN . str_repeat('=', 60) . COLOR_RESET . PHP_EOL;
    echo COLOR_CYAN . $message . COLOR_RESET . PHP_EOL;
    echo COLOR_CYAN . str_repeat('=', 60) . COLOR_RESET . PHP_EOL;
}

function log_success($message) {
    echo COLOR_GREEN . '✅ ' . $message . COLOR_RESET . PHP_EOL;
}

function log_error($message) {
    echo COLOR_RED . '❌ ' . $message . COLOR_RESET . PHP_EOL;
}

function log_warning($message) {
    echo COLOR_YELLOW . '⚠️  ' . $message . COLOR_RESET . PHP_EOL;
}

function log_info($message) {
    echo COLOR_BLUE . '📍 ' . $message . COLOR_RESET . PHP_EOL;
}

// Configuration
$baseUrl = 'http://192.168.1.211:8001/api/v1';

echo PHP_EOL;
log_header('🧪 PUSHER BEAMS AUTH ENDPOINT TESTER');
echo PHP_EOL;

// Step 1: Get auth token from user
log_info('To test the endpoint, we need a valid authentication token.');
echo PHP_EOL;
echo 'You can get your token by:' . PHP_EOL;
echo '  1. Login to the mobile app' . PHP_EOL;
echo '  2. Check the app logs or database for the access token' . PHP_EOL;
echo '  3. Or use the /api/v1/login endpoint to get a new token' . PHP_EOL;
echo PHP_EOL;
echo 'Enter your authentication token (or press Enter to skip): ';
$token = trim(fgets(STDIN));

if (empty($token)) {
    log_warning('No token provided. Will test without authentication.');
    echo PHP_EOL;
}

// Step 2: Test basic connectivity
log_header('TEST 1: Basic Connectivity');
log_info('Testing connection to: ' . $baseUrl);

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    log_error('Connection failed: ' . $error);
    log_error('Please check:');
    echo '  • Is the server running?' . PHP_EOL;
    echo '  • Is the IP address correct (192.168.1.211)?' . PHP_EOL;
    echo '  • Is port 8001 accessible?' . PHP_EOL;
    exit(1);
} else {
    log_success('Connection successful (HTTP ' . $httpCode . ')');
}

echo PHP_EOL;

// Step 3: Test test-auth endpoint
log_header('TEST 2: Test Authentication Endpoint');
$testAuthUrl = $baseUrl . '/user/pusher/test-auth';
log_info('Testing: ' . $testAuthUrl);

$ch = curl_init($testAuthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
];

if (!empty($token)) {
    $headers[] = 'Authorization: Bearer ' . $token;
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    log_error('Request failed: ' . $error);
} else {
    log_info('HTTP Status: ' . $httpCode);
    $jsonResponse = json_decode($response, true);
    
    if ($httpCode === 200 && isset($jsonResponse['success']) && $jsonResponse['success']) {
        log_success('Authentication working!');
        log_info('User ID: ' . $jsonResponse['user_id']);
        log_info('Publishable ID: ' . $jsonResponse['publishable_id']);
        log_info('Timestamp: ' . $jsonResponse['timestamp']);
    } elseif ($httpCode === 401) {
        log_error('Authentication failed - Invalid or missing token');
        if (isset($jsonResponse['message'])) {
            echo '  Message: ' . $jsonResponse['message'] . PHP_EOL;
        }
    } else {
        log_warning('Unexpected response:');
        echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . PHP_EOL;
    }
}

echo PHP_EOL;

// Step 4: Test beams-auth endpoint (the main one)
log_header('TEST 3: Pusher Beams Auth Endpoint');
$beamsAuthUrl = $baseUrl . '/user/pusher/beams-auth';
log_info('Testing: ' . $beamsAuthUrl);

if (empty($token)) {
    log_warning('Skipping - no token provided');
    echo PHP_EOL;
    log_info('To test this endpoint, restart the script with a valid token.');
    exit(0);
}

$ch = curl_init($beamsAuthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    log_error('Request failed: ' . $error);
} else {
    log_info('HTTP Status: ' . $httpCode);
    $jsonResponse = json_decode($response, true);
    
    if ($httpCode === 200 && isset($jsonResponse['token'])) {
        log_success('Beams token generated successfully!');
        log_info('Token length: ' . strlen($jsonResponse['token']) . ' characters');
        log_info('Token preview: ' . substr($jsonResponse['token'], 0, 50) . '...');
        echo PHP_EOL;
        log_success('✨ ENDPOINT IS WORKING CORRECTLY! ✨');
        echo PHP_EOL;
        echo 'Next steps:' . PHP_EOL;
        echo '  1. The mobile app should now be able to authenticate' . PHP_EOL;
        echo '  2. Check Laravel logs for detailed request information' . PHP_EOL;
        echo '  3. Test sending notifications via Pusher Dashboard' . PHP_EOL;
    } elseif ($httpCode === 401) {
        log_error('Authentication failed');
        if (is_array($jsonResponse)) {
            echo '  Response: ' . json_encode($jsonResponse) . PHP_EOL;
        } else {
            echo '  Response: ' . $response . PHP_EOL;
        }
    } elseif ($httpCode === 404) {
        log_error('Pusher Beams not configured in admin panel');
        echo '  Please configure Instance ID and Primary Key in admin settings' . PHP_EOL;
    } else {
        log_warning('Unexpected response:');
        if (is_array($jsonResponse)) {
            echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . PHP_EOL;
        } else {
            echo $response . PHP_EOL;
        }
    }
}

echo PHP_EOL;
log_header('TEST COMPLETE');
echo PHP_EOL;

// Step 5: Show how to get a token
if (empty($token)) {
    echo COLOR_YELLOW . 'To get an authentication token, use this curl command:' . COLOR_RESET . PHP_EOL;
    echo PHP_EOL;
    echo 'curl -X POST "' . $baseUrl . '/login" \\' . PHP_EOL;
    echo '  -H "Accept: application/json" \\' . PHP_EOL;
    echo '  -H "Content-Type: application/json" \\' . PHP_EOL;
    echo '  -d \'{"email":"your@email.com","password":"yourpassword"}\'' . PHP_EOL;
    echo PHP_EOL;
}
