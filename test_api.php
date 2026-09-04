<?php

// Test API Connection
$baseUrl = "http://localhost/api"; // Adjust if necessary

function callApi($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "Testing Login API...\n";
$loginResponse = callApi('POST', "$baseUrl/login", [
    'email' => 'admin@yura.com',
    'password' => 'password123'
]);

if ($loginResponse['status'] == 200) {
    echo "Login successful!\n";
    $token = $loginResponse['body']['access_token'];
    
    echo "Testing Students API...\n";
    $studentsResponse = callApi('GET', "$baseUrl/students", null, $token);
    
    if ($studentsResponse['status'] == 200) {
        echo "Students fetched successfully: " . count($studentsResponse['body']['data']) . " students found.\n";
    } else {
        echo "Failed to fetch students. Status: " . $studentsResponse['status'] . "\n";
        print_r($studentsResponse['body']);
    }
} else {
    echo "Login failed. Status: " . $loginResponse['status'] . "\n";
    print_r($loginResponse['body']);
}
