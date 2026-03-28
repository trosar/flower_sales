<?php
$is_ajax = true;
require_once 'db.php';

$response = ['status' => 'error', 'grand_total' => '0.00'];

if (isset($_POST['action'])) {
    $id = $_POST['product_id'];
    
    if ($_POST['action'] === 'update') {
        $qty = (int)$_POST['quantity'];
        if ($qty >= 1 && $qty <= 9) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['cart'][$id]);
    }

    // Calculate new grand total
    $total = 0;
    foreach ($_SESSION['cart'] as $pid => $pqty) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        if ($res = $stmt->fetch()) { $total += ($res['price'] * $pqty); }
    }

    $response = [
        'status' => 'success',
        'grand_total' => number_format($total, 2),
        'cart_empty' => empty($_SESSION['cart'])
    ];
}

header('Content-Type: application/json');
echo json_encode($response);