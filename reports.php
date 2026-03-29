<?php
require_once 'db.php';
date_default_timezone_set('America/Los_Angeles');

// Security Check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

// CSV Export Logic
if (isset($_POST['download_scout_report_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="scout_sales_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Scout', 'Customer', 'Address', 'Product', 'Qty', 'Subtotal', 'Status', 'Date']);
    
    $sql = "SELECT o.scout_name, o.customer_name, o.address, oi.product_name, oi.quantity, 
                   oi.subtotal, o.status, o.order_date 
            FROM order_items oi 
            LEFT JOIN orders o ON oi.order_id = o.id 
            ORDER BY o.scout_name ASC, o.order_date DESC";
    
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['scout_name'], $row['customer_name'], $row['address'], 
            $row['product_name'], $row['quantity'], $row['subtotal'], 
            $row['status'], $row['order_date']
        ]);
    }
    fclose($output);
    exit;
}

// Leaderboard Query: Get total sales per Scout
$leaderboardSql = "SELECT scout_name, SUM(total_amount) as total_sales, COUNT(id) as order_count 
                  FROM orders  
                  where status != 'Cancelled'
                  GROUP BY scout_name 
                  ORDER BY total_sales DESC 
                  LIMIT 3"; // Top 3 Scouts
$leaderboard = $pdo->query($leaderboardSql)->fetchAll();

// Total Troop Sales
$troopTotal = $pdo->query("SELECT SUM(total_amount) FROM orders where status != 'Cancelled'")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scout Sales Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .nav-bar { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        th { background: #673ab7; color: white; padding: 2px; text-align: left; }
        td { padding: 2px; border-bottom: 1px solid #eee; }
        .scout-header { background: #f3e5f5; font-weight: bold; font-size: 1.1rem; }
        .scout-header-tr { padding: 2px; border-top: 4px solid #C8B6F0; background: #C8B6F0; color: black; }
        .btn { padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; color: white; }
        .btn-purple { background: #673ab7; }
        .btn-back { background: #666;  font-size: 0.8rem; }
        .status-paid { color: #2e7d32; font-weight: bold; }
        .status-pending { color: #f57c00; font-weight: bold; }
        .leaderboard-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .leaderboard-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 300px;
        }
        .leader-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .leader-row:last-child { border-bottom: none; }
        .rank { font-weight: bold; color: #673ab7; margin-right: 10px; }
        .scout-name { flex-grow: 1; }
        .sales-amount { font-weight: bold; color: #2e7d32; }        
        @media print {
            body { background: #fff; margin: 0; padding: 0px; }
                
                /* Start each Scout on a new page */
                .scout-header {
                    break-before: page;
                    page-break-before: always;
                }

                /* Prevent the Scout name from being the last thing on a page */
                .scout-header + tr {
                    break-before: avoid;
                }

                .scout-header-tr { 
                    background: #eee !important; /* Light grey is better for printers than dark purple */
                    color: black !important; 
                    font-size: 1.4rem; /* Make the name bigger for easy sorting */
                    padding: 10px !important;
                }

                .no-print, .header, .btn {
                    display: none !important;
                }
                                
                /* Ensure the table takes up the full width of the paper */
                .container, .card {
                    width: 100% !important;
                    max-width: 100% !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                }
                
        }

    </style>
</head>
<body>

<div class="container">
    <div class="nav-bar no-print">
        <div>
            <h2 style="margin:0;">Scout Sales Report</h2>
            <div style="margin-top:10px;">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="download_scout_report_csv" class="btn btn-purple">Download Report (CSV)</button>
                    <a href="admin.php?<?php echo SID_STR; ?>" class="btn btn-back">Back to Admin</a>
                </form>
            </div>
        </div>
        
    </div>

    <div class="leaderboard-container no-print">
        <div class="leaderboard-card">
            <h3 style="margin-top:0; color: #673ab7; border-bottom: 2px solid #f3e5f5; padding-bottom: 10px;">
                🏆 Top Sellers (Leaderboard)
            </h3>
            <?php 
            $rank = 1;
            foreach ($leaderboard as $row): 
                $medal = ($rank == 1) ? '🥇' : (($rank == 2) ? '🥈' : (($rank == 3) ? '🥉' : ''));
            ?>
                <div class="leader-row">
                    <span class="rank"><?php echo $rank; ?>.</span>
                    <span class="scout-name"><?php echo $medal . ' ' . htmlspecialchars($row['scout_name']); ?></span>
                    <span style="color: #888; font-size: 0.8rem; margin-right: 15px;"><?php echo $row['order_count']; ?> orders</span>
                    <span class="sales-amount">$<?php echo number_format($row['total_sales'], 2); ?></span>
                </div>
            <?php $rank++; endforeach; ?>
        </div>

        <div class="leaderboard-card" style="max-width: 300px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <h3 style="margin: 0; color: #555;">Total Troop Sales</h3>
            <div style="font-size: 2.5rem; font-weight: bold; color: #2e7d32; margin: 10px 0;">
                $<?php echo number_format($troopTotal, 2); ?>
            </div>
            <p style="color: #888; margin: 0;">Way to go, Troop 60!</p>
        </div>
    </div>    

    <div class="card">
        <table>
            <tbody>
                <?php
                $sql = "SELECT o.scout_name, o.id as order_id, o.customer_name, o.email, o.address, 
                            o.status, o.order_date, oi.product_name, oi.quantity, 
                            (oi.subtotal/oi.quantity) as price_per_item, oi.subtotal 
                        FROM order_items oi 
                        LEFT JOIN orders o ON oi.order_id = o.id 
                        ORDER BY o.scout_name ASC, o.order_date DESC, o.id ASC";
                $data = $pdo->query($sql)->fetchAll();

                $currentScout = '';
                $currentOrder = '';

                foreach ($data as $row):
                    // --- 1. NEW SCOUT GROUPING ---
                    if ($currentScout !== $row['scout_name']):
                        $currentScout = $row['scout_name'];
                        $currentOrder = ''; // Reset order tracking for new scout
                ?>
                    <tr class="scout-header">
                        <td colspan="5" class="scout-header-tr">
                            👤 <?php echo htmlspecialchars($currentScout); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php 
                    // --- 2. NEW ORDER SUB-GROUPING ---
                    if ($currentOrder !== $row['order_id']):
                        $currentOrder = $row['order_id'];
                        $orderDate = date('M j, g:i A', strtotime($row['order_date']));
                ?>
                    <tr style="background: #f9f9f9; border-top: 1px solid #ddd;">
                        <td colspan="5" style="padding: 10px 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <strong>Order #<?php echo $row['order_id']; ?></strong> - 
                                    <?php echo htmlspecialchars($row['customer_name']); ?> 
                                    <small style="color: #666;">(<?php echo htmlspecialchars($row['email']); ?>)</small>
                                </span>
                                <span class="<?php echo ($row['status'] === 'Paid') ? 'status-paid' : 'status-pending'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </div>
                            <div style="font-size: 0.85rem; color: #555; margin-top: 4px;">
                                📍 <?php echo htmlspecialchars($row['address']); ?> | 📅 <?php echo $orderDate; ?>
                            </div>
                        </td>
                    </tr>
                    <tr style="font-size: 0.8rem; color: #666;">
                        <td style="padding-left: 40px;"><em>Product Name</em></td>
                        <td></td>
                        <td style="text-align:center;"><em>Qty</em></td>
                        <td style="text-align:right;"><em>Price/ea</em></td>
                        <td style="text-align:right; padding-right: 15px;"><em>Subtotal</em></td>
                    </tr>
                <?php endif; ?>

                    <tr>
                        <td style="padding-left: 40px; border-bottom: none;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td style="border-bottom: none;"></td>
                        <td style="text-align:center; border-bottom: none;"><?php echo $row['quantity']; ?></td>
                        <td style="text-align:right; border-bottom: none;">$<?php echo number_format($row['price_per_item'], 2); ?></td>
                        <td style="text-align:right; padding-right: 15px; border-bottom: none;">$<?php echo number_format($row['subtotal'], 2); ?></td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>