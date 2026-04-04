<?php
require_once 'db.php';

// 1. Get details from the Session (saved by confirmation.php)
$details = $_SESSION['checkout_details'] ?? null;

// Security Check: If the cart is empty or the session details are missing, kick back to cart
if (empty($_SESSION['cart']) || !$details) {
    header("Location: checkout.php");
    exit;
}

// Extract variables from the session array
$name    = htmlspecialchars($details['name']);
$email   = htmlspecialchars($details['email']);
$scout_name   = htmlspecialchars($details['scout_name']);
$payment = $details['payment'];
$address = htmlspecialchars($details['address']);
$comments = isset($details['comments']) ? htmlspecialchars($details['comments']) : 'N/A';

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
$sqlOrder = "INSERT INTO orders (customer_name, address, email, scout_name, payment_mode, total_amount, comments) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmtOrder = $pdo->prepare($sqlOrder);
$stmtOrder->execute([$name, $address, $email, $scout_name, $payment, $grand_total, $comments]);

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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
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
        /* The Main Bordered Container */
        .main-container {
            max-width: 1000px; /* Changed from 1100px */
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
        h2 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }
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
        /* Mobile Layout: 1 Column */
        @media (max-width: 800px) {
            body { padding: 10px; }
            .order-summary { padding: 2px; }
            .main-container { padding: 20px; }
            .grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }

        .only-print {
            display: none !important;
        }



        /* This CSS only triggers when the user hits 'Print' */
        @media print {
            /* Hide buttons and navigation links */
            .no-print, .header, .btn {
                display: none !important;
            }

            .only-print {
                display: block !important;
            }

            /* Remove background colors/shadows to save ink */
            body {
                background: white !important;
                padding: 0;
            }
            
            .main-container {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            h1 {
                color: black !important;
                font-size: 24pt;
            }

            /* Ensure the order summary box has a visible border on paper */
            .order-summary {
                border: 1px solid #000 !important;
                background: transparent !important;
            }
        }

    </style>
</head>
<body>
    <div class="main-container">
        <h2>Order Placed</h2>
        <div class="only-print">
            <h2>Troop 60 Plant Sale Receipt</h2>
        </div>
        
        <div style="font-size: 60px;">🌸</div>
        <h1>Thanks <?php echo htmlspecialchars($name); ?>!</h1>
        <p>Your order for has been received.</p>
        <p>Your order number is <b>#<?php echo $newOrderId; ?></b></p>
        <div style="margin-top: 30px;" class="no-print">
            <p>Please print this page for your records. 
                You can lookup your order details in <a href="view_order.php?email=<?php echo htmlspecialchars($email); ?>" target="_blank">this page</a></p>
        </div>        
        
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
            <a href="index.php" class="btn">Return Home</a>
        </div>        
    </div>
</body>
</html>