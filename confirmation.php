<?php
require_once 'db.php';

// Save POST data into session so it persists if they go back
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['checkout_details'] = [
        'name'       => $_POST['name'] ?? '',
        'email'      => $_POST['email'] ?? '',
        'address'    => $_POST['address'] ?? '',
        'scout_name' => $_POST['scout_name'] ?? '',
        'payment'    => $_POST['payment'] ?? '',
        'comments'   => isset($_POST['comments']) ? trim($_POST['comments']) : ''
    ];
}

// If they didn't come from the checkout form, send them back
if (!isset($_POST['name'])) {
    header("Location: checkout.php");
    exit;
}

$grand_total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm Your Order</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        :root { --primary-color: #2e7d32; --accent-color: #f57c00; }
        body { font-family: sans-serif; background: #f9f9f9; padding: 20px; }
        .main-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .review-section { border-bottom: 1px solid #eee; padding: 15px 0; }
        .review-label { font-weight: bold; color: #666; font-size: 0.9rem; }
        .review-value { font-size: 1.1rem; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; background: #f2f2f2; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; flex: 1; }
        .btn-back { background: #eee; color: #333; border: 1px solid #ccc; }
        .btn-confirm { background: var(--accent-color); color: white; border: none; font-size: 1.1rem; }
        .headings { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header {
            max-width: 1000px; /* Changed from 1100px */
            margin: 0 auto;
            background: white;
            padding: 10px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);

            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
            border-bottom: 2px solid #eee;
        }
        .troop_logo { height: 40px; }
        .header h2 { color: var(--primary-color); margin-bottom: 10px; }
        .headings { color: var(--accent-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }      
        @media (max-width: 600px) {
            body { padding: 10px; }
            .main-container { padding: 20px; }
        }

    </style>
</head>
<body>
<div class="header">
    <a href="https://www.troop60.co/"><img class="troop_logo" src="media/Troop_60_Logo.png" alt="Troop 60 Logo"></a>
    <h2>Plant Sales</h2>
</div>
<div class="main-container">
    <h2 class="headings">Review Your Order</h2>
        <!-- <div class="header">
    Please double-check the following before placing your order.
    </div> -->

    <div class="review-section">
        <div class="review-label">Customer Name</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['name']); ?></div>
    </div>
    <div class="review-section">
        <div class="review-label">Delivery Address</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['address']); ?></div>
    </div>
    <div class="review-section">
        <div class="review-label">Email Address</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['email']); ?></div>
    </div>
    <div class="review-section">
        <div class="review-label">Credit to Scout</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['scout_name']); ?></div>
    </div>
    <div class="review-section">
        <div class="review-label">Comments</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['comments']); ?></div>
    </div>
    <div class="review-section">
        <div class="review-label">Payment Method</div>
        <div class="review-value"><?php echo htmlspecialchars($_POST['payment']); ?></div>
    </div>

    <h3>Items in Cart</h3>
    <table>
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['cart'] as $id => $qty): 
                $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch();
                if ($product):
                    $subtotal = $product['price'] * $qty;
                    $grand_total += $subtotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo $qty; ?></td>
                <td>$<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
    </table>

    <h2 style="text-align: right; color: var(--primary-color);">Grand Total: $<?php echo number_format($grand_total, 2); ?></h2>

    <div class="btn-group">
        <a href="checkout.php" class="btn btn-back">Back to Cart</a>
        <form action="process_order.php" method="POST" style="flex: 1;">
            <button type="submit" class="btn btn-confirm" style="width:100%;">Place Order</button>
        </form>
    </div>
</div>

</body>
</html>