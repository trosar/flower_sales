<?php
require_once 'db.php';

// 1. Get details from the Session (saved by confirmation.php)
$details = $_SESSION['checkout_details'] ?? null;

// Security Check: If the cart is empty or the session details are missing, kick back to cart
if (empty($_SESSION['cart']) || !$details) {
    header("Location: checkout.php?" . SID_STR);
    exit;
}

// Extract variables from the session array
$name    = htmlspecialchars($details['name']);
$email   = htmlspecialchars($details['email']);
$scout_name   = htmlspecialchars($details['scout_name']);
$payment = $details['payment'];
// If you aren't using an address field anymore, we can default it or remove it
$address = isset($details['address']) ? htmlspecialchars($details['address']) : 'N/A';

// 2. Calculate Grand Total and Prepare Items
$grand_total = 0;
$items_to_save = [];

foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        $subtotal = $product['price'] * $qty;
        $grand_total += $subtotal;
        
        $items_to_save[] = [
            'name' => $product['name'],
            'qty' => $qty,
            'price' => $product['price'],
            'subtotal' => $subtotal
        ];
    }
}

// 3. Insert Main Order
$sqlOrder = "INSERT INTO orders (customer_name, address, email, scout_name, payment_mode, total_amount) 
             VALUES (?, ?, ?, ?, ?, ?)";
$stmtOrder = $pdo->prepare($sqlOrder);
$stmtOrder->execute([$name, $address, $email, $scout_name, $payment, $grand_total]);

$newOrderId = $pdo->lastInsertId(); 

// 4. Insert Individual Items
$sqlItems = "INSERT INTO order_items (order_id, product_name, quantity, price_per_item, subtotal) 
             VALUES (?, ?, ?, ?, ?)";
$stmtItems = $pdo->prepare($sqlItems);

foreach ($items_to_save as $item) {
    $stmtItems->execute([
        $newOrderId, 
        $item['name'], 
        $item['qty'], 
        $item['price'], 
        $item['subtotal']
    ]);
}

// 5. Clear the Cart and the temporary checkout session
unset($_SESSION['cart']); 
unset($_SESSION['checkout_details']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #2e7d32;
            --accent-color: #f57c00;
            --bg-color: #f0f2f5;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            text-align: left;
        }
        h2 { color: var(--primary-color); }
        .btn {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .order-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <a href="index.php?<?php echo SID_STR; ?>" style="text-decoration: none; color: var(--primary-color); font-weight: bold;">← Back to Shop</a>
        </div>
        
        <div style="font-size: 60px;">🌸</div>
        <h1>Thanks <?php echo htmlspecialchars($name); ?>!</h1>
        <p>Your order for has been received.</p>
        <p>Your order number is #<?php echo $newOrderId; ?></p>
        
        <div class="order-summary">
            <h3 style="margin-top: 0;">Total Amount: $<?php echo number_format($grand_total, 2); ?></h3>
            <p>Payment Method Selected: <strong><?php echo htmlspecialchars($payment); ?></strong></p>
            <?php if ($payment === 'Venmo'): ?>
                <p><a href="https://account.venmo.com/pay?amount=<?php echo rawurlencode($grand_total); ?>&note=Plant%20Sales%20<?php echo rawurlencode($scout_name); ?>&recipients=troop60" target="_blank">Click here</a> to pay now</p>
            <?php endif; ?>
            <p style="font-size: 0.9rem; color: #666;">Please follow the Troop's standard instructions for your payment.</p>

        </div>
        <div class="order-summary">
            <p>
            For Venmo payments, You are in the right place if it says, "<b>Greg LeBlanc</b> @Troop60". Please indicate in the comment of your payment what and who it is for. For instance, you can mention the fundraiser and/or the scouts name.
            </p>
            <p>
            <a href="https://venmo.com/troop60" target="_blank">
                <img src="media/Troop_60_Venmo.png" alt="Troop 60 Venmo" style="margin-top: 20px; width: 200px; border-radius: 8px; border: 1px solid #ddd;">
            </a>
            </p>
        </div>
        <div style="margin-top: 40px;">
            <a href="index.php?<?php echo SID_STR; ?>" class="btn">Return Home</a>
        </div>
    </div>
</body>
</html>