<?php
declare(strict_types=1);

// Test Authentication System
echo "Testing Authentication System\n";
echo "============================\n\n";

// Test URLs
$baseUrl = 'http://localhost:8081';
$endpoints = [
    'Registration Page' => '/register',
    'Login Page' => '/login',
    'Dashboard (Protected)' => '/dashboard',
];

foreach ($endpoints as $name => $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "Testing $name: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    
    curl_close($ch);
    
    echo "- HTTP Status: $httpCode\n";
    
    // Check for redirects
    if (preg_match('/Location: (.+)/', $header, $matches)) {
        echo "- Redirects to: " . trim($matches[1]) . "\n";
    }
    
    // Check content type
    if (preg_match('/Content-Type: (.+)/', $header, $matches)) {
        echo "- Content-Type: " . trim($matches[1]) . "\n";
    }
    
    echo "\n";
}

// Test registration form detection
echo "Checking Registration Form:\n";
$ch = curl_init($baseUrl . '/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$registerPage = curl_exec($ch);
curl_close($ch);

if (strpos($registerPage, '<form') !== false) {
    echo "✓ Registration form found\n";
    if (strpos($registerPage, 'username') !== false) {
        echo "✓ Username field found\n";
    }
    if (strpos($registerPage, 'email') !== false) {
        echo "✓ Email field found\n";
    }
    if (strpos($registerPage, 'password') !== false) {
        echo "✓ Password field found\n";
    }
} else {
    echo "✗ No registration form found\n";
}