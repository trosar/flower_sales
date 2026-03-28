<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['cart'])) {
    
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $scout = htmlspecialchars($_POST['scout_name']);
    $payment = $_POST['payment'];
    
    // 1. Calculate Grand Total
    $grand_total = 0;
    $items_to_save = [];

    foreach ($_SESSION['cart'] as $id => $qty) {
        // Fetch current price from DB for security
        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $subtotal = $product['price'] * $qty;
            $grand_total += $subtotal;
            
            // Store for the second insert
            $items_to_save[] = [
                'name' => $product['name'],
                'qty' => $qty,
                'price' => $product['price'],
                'subtotal' => $subtotal
            ];
        }
    }

    // 2. Insert Main Order
    $sqlOrder = "INSERT INTO orders (customer_name, email, scout_name, payment_mode, total_amount) 
                 VALUES (?, ?, ?, ?, ?)";
    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([$name, $email, $scout, $payment, $grand_total]);
    
    $newOrderId = $pdo->lastInsertId(); // This is the "Link"

    // 3. Insert Individual Items
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

            /* The Main Bordered Container */
            .main-container {
                max-width: 1000px; /* Changed from 1100px */
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            }
            .header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 30px;
                border-bottom: 2px solid #eee;
                padding-bottom: 20px;
            }

            .header img { height: 70px; }        
            h2 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }
            .btn {
                background: var(--primary-color);
                color: white;
                padding: 12px 25px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="main-container" style="text-align: center;">
            <div class="header">
                <!-- <img src="media/Troop_60_Logo.png" alt="Logo"> -->
                <a href="checkout.php" class="cart-badge">
                    <a href="index.php" style="text-decoration: none; color: var(--primary-color);">← Return to Homepage</a>
                </a>
            </div>
            <div style="font-size: 50px;">🎉</div>
            <h1>Thank You, <?php echo htmlspecialchars($name); ?>!</h1>
            <p>Your order has been placed successfully.</p>
            <hr>
            <h3>Order Total: $<?php echo number_format($grand_total, 2); ?></h3>
            <p>Please complete your payment via <strong><?php echo $payment; ?></strong>.</p>
            
            <div style="margin-top: 30px;">
                <a href="index.php" class="btn btn-primary">Back to Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php

    // 4. Clear out the cart
    unset($_SESSION['cart']); 
}
?>


