<?php
require_once 'db.php';
date_default_timezone_set('America/Los_Angeles');

$email_query = $_GET['email'] ?? '';
$email_query = trim($email_query);
$orders = [];

if ($email_query) {
    $stmt = $pdo->prepare("SELECT id, scout_name, status, order_date, total_amount FROM orders WHERE email = ? ORDER BY order_date DESC");
    $stmt->execute([$email_query]);
    $orders = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Your Orders <?php echo htmlspecialchars($email_query); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="styles.css">
    <style>
        .search-box { margin-bottom: 30px; }
        input[type="email"] { width: 80%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px; }
        .order-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: #fff; }
        .order-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .Paid { background: #e8f5e9; color: #2e7d32; }
        .Pending { background: #fff3e0; color: #ef6c00; }
        .item-row { display: flex; justify-content: space-between; font-size: 0.9rem; margin: 5px 0; color: #555; }
    </style>
</head>
<body>
<?php $page_title = 'Plant Sales'; include 'header-html.php'; ?>


<div class="main-container">
    <h2 class="headings">Lookup Your Orders</h2>
    
    <div class="search-box">
        <form method="GET" action="view_order.php">
            <input type="email" name="email" 
                placeholder="Enter the email used for order" 
                required value="<?php echo htmlspecialchars($email_query); ?>">
            <br>
            <button type="submit" class="btn">Find My Orders</button>
        </form>
    </div>

    <?php if ($email_query): ?>
        <?php if (empty($orders)): ?>
            <p style="color: #d32f2f;">No orders found for this email address.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong><br>
                            <small><?php echo formatLocalDate($order['order_date'], 'M j, Y'); ?></small>
                        </div>
                        <span class="status-badge <?php echo $order['status']; ?>">
                            <?php echo strtoupper($order['status']); ?>
                        </span>
                    </div>
                    
                    <p style="margin: 5px 0;"><strong>Scout:</strong> <?php echo htmlspecialchars($order['scout_name']); ?></p>
                    
                    <div style="margin-top:10px; border-top: 1px dashed #ccc; padding-top: 10px;">
                        <?php
                        $itemStmt = $pdo->prepare("SELECT product_name, quantity, subtotal FROM order_items WHERE order_id = ?");
                        $itemStmt->execute([$order['id']]);
                        while ($item = $itemStmt->fetch()):
                        ?>
                            <div class="item-row">
                                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></span>
                                <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px; font-weight: bold; color: var(--primary-color);">
                        Total: $<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="index.php" style="color: #666; font-size: 0.9rem;">Back to Homepage</a>
    </div>
</div>

</body>
</html>