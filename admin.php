<?php
// 1. Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Los_Angeles');

// 2. Database & Session (via db.php - this loads your .env automatically)
require_once 'db.php';

// 3. Handle Logout
if (isset($_GET['logout'])) {
    // Completely clear the session data
    $_SESSION = array();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }    
    header("Location: admin.php"); 
    exit;
}

// 4. Handle Login using Environment Variable
if (isset($_POST['password'])) {
    $admin_password = getenv('ADMIN_PASS');
    
    if ($admin_password && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Incorrect password!";
    }
}

// 5. CSV Export Logic (Must be before any HTML)
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    
    // Download Order Info (The Customer/Order List)
    if (isset($_POST['download_orders_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="order_info_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Date', 'Customer', 'Address', 'Email', 'Scout', 'Payment', 'Total', 'Status', 'Comments']);
        $stmt = $pdo->query("SELECT * FROM orders where status != 'Cancelled' ORDER BY order_date DESC");
        $grandTotal = 0;
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'], 
                formatLocalDate($row['order_date']), 
                $row['customer_name'], 
                $row['address'], 
                $row['email'], 
                $row['scout_name'], 
                $row['payment_mode'], 
                $row['total_amount'], 
                $row['status'],
                $row['comments']
            ]);
            $grandTotal += $row['total_amount'];
        }
        fputcsv($output, ['==', '==', '==', '==', '==', '==', '==', number_format($grandTotal, 2), '==', 'Total Sales']);
        fclose($output);
        exit;
    }

    // Download Product Orders (The Shopping List)
    if (isset($_POST['download_products_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_totals_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Status', 'Product Name', 'Total Quantity Ordered']);
        $sql = "SELECT oi.product_name, o.status, SUM(oi.quantity) as total_qty FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id GROUP BY status, product_name ORDER BY status, product_name";
        $stmt = $pdo->query($sql);
        $grandTotal = 0;
        while ($row = $stmt->fetch()) {
            fputcsv($output, [$row['status'], $row['product_name'], $row['total_qty']]);
            $grandTotal += $row['total_qty'];
        }
        fputcsv($output, ['== TOTAL ==', '== All Products ==', $grandTotal]);
        fclose($output);
        exit;
    }

    // Handle "Mark as Paid"
    if (isset($_POST['mark_paid'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
        $stmt->execute([$_POST['order_id']]);
        header("Location: admin.php");
        exit;
    }

    // Handle "Mark as Unpaid" (Pending)
    if (isset($_POST['mark_unpaid'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Pending' WHERE id = ?");
        $stmt->execute([$_POST['order_id']]);
        header("Location: admin.php");
        exit;
    }    
}

// 6. Show Login Page if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true): ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="stylesheet" href="styles.css">
        <style>
            :root {
                --primary-color: #2e7d32;
                --accent-color: #f57c00;
                --bg-color: #f9f9f9;
            }
            .small-body { display: flex; justify-content: center; align-items: center; height: 100vh;}
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
            input[type="password"] { padding: 12px; width: 220px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; }

            button[name="mark_paid"], button[name="mark_unpaid"] {
                appearance: none;          /* Removes system-default styling */
                -webkit-appearance: none;   /* Specific fix for Safari/iOS */
                background-color: #ccc !important;
                border: 1px solid #ccc;
                padding: 3px 8px;
                border-radius: 2px;
                font-size: 0.75rem;
                cursor: pointer;
                color: inherit;
            }
            button[name="mark_paid"]:hover { background: #e8f5e9; }
            button[name="mark_unpaid"]:hover { background: #ffebee; }    
            input[type="text"], 
            input[type="password"], 
            input[type="email"], 
            input[type="tel"], 
            input[type="number"], 
            textarea, 
            select {
                font-size: 16px !important;
            }
            .order-comments {
                margin: 10px 0; padding: 10px; 
                background: #fffde7; border-left: 4px solid #fdd835; 
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body class="small-body">
        <div class="login-box">
            <h2>Scout Fundraiser Admin</h2>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Password" required><br>
                <button type="submit" class="btn btn-confirm">Login</button>
                <a href="index.php" class="btn btn-back">Back Home</a>
            </form>
        </div>
    </body>
    </html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 1080px; margin: 0 auto; }
        .nav-bar { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        .order-card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        
        .item-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .item-table th { text-align: left; color: #666; font-size: 0.85rem; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .item-table td { padding: 8px 0; border-bottom: 1px solid #fafafa; }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .status-paid { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        @media (max-width: 600px) {
        }

    </style>
</head>
<body>

<?php $page_title = 'Order Management'; include 'header-html.php'; ?>

<div class="container">
    <div class="nav-bar">
        <div>
            <div style="margin-top:0px;">
                <form method="POST" style="width: 100%; display: contents;">
                    <button type="submit" name="download_orders_csv" class="btn btn-green">Download Order Info (CSV)</button>
                    <button type="submit" name="download_products_csv" class="btn btn-orange">Download Product Orders (CSV)</button>
                    <a href="reports.php" class="btn btn-purple">View Scout Reports</a>
                    <a href="?logout=1" class="btn btn-logout">Logout</a>
                </form>
            </div>
        </div>
        
    </div>

    <?php
    $orders = $pdo->query("SELECT * FROM orders where status != 'Cancelled'ORDER BY order_date DESC")->fetchAll();
    foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <strong>Order #<?php echo $order['id']; ?></strong><br>
                    <small style="color:#888;">
                        <?php 
                            echo formatLocalDate($order['order_date']);
                        ?>
                    </small>
                </div>
                <div>
                    <?php if ($order['status'] === 'Paid'): ?>
                        <span class="status-badge status-paid">✅ PAID (<?php echo $order['payment_mode']; ?>)</span>
                        <form method="POST" style="display:inline; margin-left:10px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="mark_unpaid" style="background: #ccc; color: #d32f2f;">Mark Unpaid</button>
                        </form>
                    <?php else: ?>
                        <span class="status-badge status-pending">⏳ PENDING (<?php echo $order['payment_mode']; ?>)</span>
                        <form method="POST" style="display:inline; margin-left:10px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="mark_paid" style="background: #ccc; color: #2e7d32;">Mark Paid</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <p style="margin: 5px 0;"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</p>
            <p style="margin: 5px 0;"><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p style="margin: 5px 0;"><strong>Scout Name:</strong> <?php echo htmlspecialchars($order['scout_name']); ?></p>
            <?php if (!empty($order['comments'])): ?>
                <p class="order-comments">
                    <strong>Comments:</strong> <?php echo htmlspecialchars($order['comments']); ?>
                </p>
            <?php endif; ?>            

            <table class="item-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Qty</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $itemStmt->execute([$order['id']]);
                    while ($item = $itemStmt->fetch()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="text-align:right;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="text-align: right; font-size: 1.2rem; font-weight: bold; color: #2e7d32;">
                Total: $<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>