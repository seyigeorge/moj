<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require 'config.php';
session_start();

header('Content-Type: application/json');

$ref = $_GET['ref'] ?? '';

if (!$ref) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No payment reference provided'
    ]);
    exit;
}



$payload = [
    'referenceId' => $ref
];

$ch = curl_init(VALIDATE_PAYMENT);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-IBM-Client-Id: ' . CLIENT_ID
    ],
]);

$response = curl_exec($ch);

if ($response === false) {
    curl_close($ch);
    echo json_encode([
        'status' => 'error',
        'message' => 'cURL error'
    ]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['statusCode']) && $data['statusCode'] === '00') {
    $_SESSION['paid'] = true;
    $_SESSION['payment_ref'] = $ref;
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
} else {
    echo json_encode([
        'status' => 'pending',
        'data' => $data
    ]);
}

