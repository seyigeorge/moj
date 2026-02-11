<?php
session_start();
define('LOG_FILE', __DIR__ . '/invoice.log');

function logCallback($msg) {
    file_put_contents(LOG_FILE, "[" . date('c') . "] CALLBACK: $msg\n", FILE_APPEND);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
logCallback("Raw: $input");

if (isset($data['status']) && $data['status'] === 'PAID') {
    $paymentRef = $data['payment_ref'] ?? 'UNKNOWN';
    $abssin = $data['abssin'] ?? '';
    $amount = $data['amount'] ?? '';
    $txnRef = $data['transaction_reference'] ?? '';
    $txnDate = $data['transaction_date'] ?? '';
    $method = $data['payment_method'] ?? '';
    $payer = $data['taxpayer_name'] ?? '';

    logCallback("Payment successful: Ref=$paymentRef, ABSSIN=$abssin, Amount=$amount, Method=$method");

    file_put_contents(__DIR__ . "/paid_$paymentRef.flag", json_encode($data));

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    logCallback("Payment failed or status not 'PAID'");
    http_response_code(400);
    echo json_encode(['status' => 'failed']);
}
