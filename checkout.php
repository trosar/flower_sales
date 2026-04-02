<?php
require_once 'db.php'; // Includes session_start and PDO connection

// Get the saved details if they exist
$saved = $_SESSION['checkout_details'] ?? [];
$selectedPayment = $saved['payment'] ?? '';

// 1. Handle "Remove" Action
if (isset($_GET['remove'])) {
    $id_to_remove = $_GET['remove'];
    unset($_SESSION['cart'][$id_to_remove]);
    header("Location: checkout.php?" . SID_STR);
    exit;
}

// 2. Handle "Update Quantity" Action
if (isset($_POST['update_qty'])) {
    $id = $_POST['product_id'];
    $new_qty = (int)$_POST['quantity'];
    if ($new_qty >= 1 && $new_qty <= 9) {
        $_SESSION['cart'][$id] = $new_qty;
    } elseif ($new_qty <= 0) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: checkout.php?" . SID_STR);
    exit;
}

$grand_total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        :root {
            --primary-color: #2e7d32;
            --accent-color: #f57c00;
            --bg-color: #f9f9f9;
        }
        body { font-family: sans-serif; background-color: var(--bg-color); margin: 0; padding: 20px; color: #333; }
        
        .main-container {
            max-width: 1000px; /* Changed from 800px */
            margin: 0 auto;
            background: white;
            padding: 40px; /* Match the 40px from index.php */
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
        
        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; background: #f2f2f2; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .qty-input { width: 30px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-update { background: #666; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .btn-remove { color: #d32f2f; text-decoration: none; font-weight: bold; font-size: 0.8rem; margin-left: 0px; }
        
        /* Form Styling */
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"], select { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; 
        }
        .btn { padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; flex: 1; }
        .btn-back { background: #eee; color: #333; border: 1px solid #ccc; }
        .btn-confirm { background: var(--accent-color); color: white; border: none; font-size: 1.1rem; }

        /* .btn-checkout { 
            background: var(--accent-color); color: white; border: none; padding: 15px; 
            width: 100%; border-radius: 8px; font-size: 1.2rem; font-weight: bold; cursor: pointer; margin-top: 20px;
        } */
        .btn-checkout:hover { background: #e67300; }
        .total-row { font-size: 1.3rem; font-weight: bold; text-align: right; color: var(--primary-color); }
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        
        @media (max-width: 600px) {
            body { padding: 10px; }
            .main-container { padding: 20px; }
            td, th { padding: 8px; font-size: 0.9rem; }
        }

        input[type="text"], 
        input[type="password"], 
        input[type="email"], 
        input[type="tel"], 
        input[type="number"], 
        textarea, 
        select {
            font-size: 16px !important;
        }        
    </style>
</head>
<body>

<div class="main-container">

    
    <h2>Your Cart</h2>

    <!-- <div class="header">
        <img src="media/Troop_60_Logo.png" alt="Logo">
        <a href="checkout.php" class="cart-badge">
            <a href="index.php?<?php echo SID_STR; ?>" style="text-decoration: none; color: var(--primary-color);">Back to Shopping</a>
        </a>
    </div> -->


    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty.</p><br/>
        <a href="index.php?<?php echo SID_STR; ?>" class="btn btn-back">Back Home</a>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($_SESSION['cart'] as $id => $qty): 
                    $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    $product = $stmt->fetch();
                    if ($product):
                        $subtotal = $product['price'] * $qty;
                        $grand_total += $subtotal;
                ?>
                <tr id="row-<?php echo $id; ?>">
                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong><br><small>$<?php echo number_format($product['price'], 2); ?> ea</small></td>
                    <td>
                        <div class="qty-controls">
                            <input type="number" value="<?php echo $qty; ?>" min="1" max="9" 
                                class="qty-input ajax-qty" data-id="<?php echo $id; ?>">
                            </div>
                    </td>
                    <td class="subtotal" id="subtotal-<?php echo $id; ?>">$<?php echo number_format($subtotal, 2); ?></td>
                    <td>
                        <a href="#" class="btn-remove ajax-remove" data-id="<?php echo $id; ?>">Remove</a>
                    </td>                    
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>

        <div class="total-row">Grand Total: $<span id="grand-total-display"><?php echo number_format($grand_total, 2); ?></span></div>

        <form action="confirmation.php?<?php echo SID_STR; ?>" method="POST">
            <h2>Checkout Information</h2>
            <div class="form-group">
                <label>How will you pay?</label>
                <select name="payment" required>
                    <option value="Venmo" <?php echo ($selectedPayment === 'Venmo') ? 'selected' : ''; ?>>Venmo</option>
                    <option value="Cash" <?php echo ($selectedPayment === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                    <option value="Check" <?php echo ($selectedPayment === 'Check') ? 'selected' : ''; ?>>Check</option>
                </select>
            </div>
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($saved['name'] ?? ''); ?>" placeholder="" >
            </div>
            <div class="form-group">
                <label>Delivery Address</label>
                <input type="text" name="address" required value="<?php echo htmlspecialchars($saved['address'] ?? ''); ?>" placeholder="">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($saved['email'] ?? ''); ?>" placeholder="">
            </div>
            <div class="form-group">
                <label>Scout's Name</label>
                <input type="text" name="scout_name" required value="<?php echo htmlspecialchars($saved['scout_name'] ?? ''); ?>" placeholder="">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Comments (Optional)</label>
                <input type="text" name="comments" id="comments" value="<?php echo htmlspecialchars($saved['comments'] ?? ''); ?>" placeholder="">
            </div>            
            <div class="btn-group">
                <a href="index.php?<?php echo SID_STR; ?>" class="btn btn-back">Back Home</a>
                <button type="submit" class="btn btn-confirm">Review Order</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
const sidParam = '<?php echo SID_STR; ?>';
let itemToDelete = null;

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('ajax-remove')) {
        e.preventDefault();
        itemToDelete = e.target.dataset.id;
        document.getElementById('custom-modal').style.display = 'block';
    }

    if (e.target.id === 'modal-cancel') {
        document.getElementById('custom-modal').style.display = 'none';
        itemToDelete = null;
    }

    if (e.target.id === 'modal-confirm') {
        if (itemToDelete) {
            updateCart(itemToDelete, 0, 'remove');
            document.getElementById('custom-modal').style.display = 'none';
        }
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('ajax-qty')) {
        updateCart(e.target.dataset.id, e.target.value, 'update');
    }
});

function updateCart(pId, qty, action) {

    if (qty <= 0 && action === 'update') {
        action = 'remove';
    }
    
    // If user typed something huge, cap it at 9 so it doesn't break PHP logic
    if (qty > 9) {
        qty = 9;
        document.querySelector(`.ajax-qty[data-id="${pId}"]`).value = 9;
    }

    const formData = new FormData();
    formData.append('product_id', pId);
    formData.append('quantity', qty);
    formData.append('action', action);

    fetch(`update_cart_ajax.php?${sidParam}`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // 1. Handle Removal
            if (action === 'remove' || qty <= 0) {
                const row = document.getElementById(`row-${pId}`);
                if (row) row.remove();
            } else {
                // 2. Handle Subtotal Update (This was the missing piece!)
                const subtotalDisplay = document.getElementById(`subtotal-${pId}`);
                if (subtotalDisplay) {
                    subtotalDisplay.innerText = '$' + data.new_subtotal;
                }
            }
            
            // 3. Update Grand Total
            const totalDisplay = document.getElementById('grand-total-display');
            if (totalDisplay) {
                totalDisplay.innerText = data.grand_total;
            }

            if (data.cart_empty) {
                location.reload(); 
            }
        }
    })
    .catch(err => console.error('Fetch error:', err));
}
</script>

<div id="custom-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:white; width:300px; margin:15% auto; padding:20px; border-radius:10px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <p style="font-weight:bold; margin-bottom:20px;">Remove this item from your cart?</p>
        <button id="modal-confirm" style="padding:10px 20px; margin-right:10px; background:#d32f2f; color:white; border:none; border-radius:5px; cursor:pointer;">Remove</button>
        <button id="modal-cancel" style="padding:10px 20px; background:#666; color:white; border:none; border-radius:5px; cursor:pointer;">Cancel</button>
    </div>
</div>

</body>
</html>