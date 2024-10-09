<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_id = $_POST['transaction_id'];
    $payment = $_POST['payment'];
    $amount = $_POST['amount'];

    // Validate payment
    if ($payment >= $amount) {
        // Update transaction status
        $update_query = $conn->prepare("UPDATE pending_transactions SET payment_status = 'Paid', transaction_date = NOW() WHERE id = ?");
        $update_query->bind_param("i", $transaction_id);
        if ($update_query->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Payment processing failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient payment.']);
    }
}

