<?php 
require_once 'db.php'; 

// Handle Add to Cart Logic
if (isset($_POST['add_to_cart'])) {
    $p_id = $_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    if ($qty >= 1 && $qty <= 9) {
        $_SESSION['cart'][$p_id] = ($_SESSION['cart'][$p_id] ?? 0) + $qty;
    }
    header("Location: index.php?" . SID_STR);
    exit;
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scout Fundraiser</title>
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
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header img { height: 50px; }
        .stadium_logo { height: 50px; }

        .cart-badge {
            transition: transform 0.2s ease;
            display: inline-block; /* Required for transform to work */
            background: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fundraiser-info {
            text-align: center;
            margin-bottom: 40px;
        }

        .fundraiser-info h2 { color: var(--primary-color); margin-bottom: 10px; }

        /* Product Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .product-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: transform 0.2s;
        }

        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        .product-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .price { color: var(--primary-color); font-weight: bold; font-size: 1.2rem; display: block; margin: 10px 0; }

        .qty-input { width: 50px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .footer-nav {
            margin-top: 50px;
            text-align: center;
        }

        .btn-checkout-large {
            background: var(--accent-color);
            color: white;
            padding: 20px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: bold;
            display: inline-block;
        }

        /* Mobile Layout: 1 Column */
        @media (max-width: 800px) {
             body { padding: 10px; }
            .main-container { padding: 20px; }
            .grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="fundraiser-info">
        <h2>Troop 60 Fundraiser - Plant Sales - Spring 2026</h2>
        <p>
            We are selling plants to raise money to enable us to participate in adventures during the year, including summer camp. <br/>
            Thanks for your support!
        </p>
        <h3>Orders are due by April 16 2026. Delivery will be made on May 1 2026.</h3>
        <h3 style="vertical-align:top;">Products Sponsored By: 
        <a href="https://www.stadiumflowers.com/" target="_blank"><img class="stadium_logo" src="media/Stadium_Flowers_Logo.png" alt="Stadium Flowers Logo"></a>
        </h3>

    </div>
    <div class="header">
        <!-- <img src="media/Troop_60_Logo.png" alt="Logo"> -->
        <a href="checkout.php?<?php echo SID_STR; ?>" class="cart-badge" id="cart-anchor">
            View Cart (<span id="cart-qty"><?php echo $cart_count; ?></span>)
        </a>


    </div>
    <div class="grid">
        <?php foreach ($products as $p): ?>
        <div class="product-card">
            <img src="images/<?php echo htmlspecialchars($p['image_url']); ?>" alt="product">
            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
            <span class="price">$<?php echo number_format($p['price'], 2); ?></span>
            
            <form class="ajax-form">
                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                <input type="number" name="quantity" value="1" min="1" max="9" class="qty-input">
                <button type="submit" class="btn btn-primary">Add to Cart</button>
                <span class="added-msg" style="display:none; color:green; font-size:0.8rem;">Added!</span>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-nav">
        <h3>Products are Sponsored By: 
        <a href="https://www.stadiumflowers.com/" target="_blank"><img class="stadium_logo" src="media/Stadium_Flowers_Logo.png" alt="Stadium Flowers Logo"></a>
        </h3>
    </div>

</div>

<script>
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = this.querySelector('button');
        const cart = document.querySelector('#cart-anchor');
        const formData = new FormData(this);

        // 1. Send the data to the server
        fetch('add_ajax.php?<?php echo SID_STR; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(cartCount => {
            // 2. Create the "Flying" element
            const flyer = document.createElement('div');
            flyer.innerText = ' 🌸🌸🌸 '; // You can change this to any icon
            flyer.style.cssText = `
                position: fixed;
                z-index: 9999;
                left: ${btn.getBoundingClientRect().left}px;
                top: ${btn.getBoundingClientRect().top}px;
                transition: all 0.8s cubic-bezier(0.42, 0, 0.58, 1);
                font-size: 24px;
                pointer-events: none;
            `;
            document.body.appendChild(flyer);

            // 3. Trigger the animation to the cart badge position
            setTimeout(() => {
                flyer.style.left = `${cart.getBoundingClientRect().left}px`;
                flyer.style.top = `${cart.getBoundingClientRect().top}px`;
                flyer.style.opacity = '0';
                flyer.style.transform = 'scale(0.5)';
            }, 10);

            // 4. Update the number and clean up
            setTimeout(() => {
                document.getElementById('cart-qty').innerText = cartCount.trim();
                flyer.remove();
                
                // A little "pop" effect on the cart
                cart.style.transform = 'scale(1.2)';
                setTimeout(() => { cart.style.transform = 'scale(1)'; }, 200);
            }, 800);
        });
    });
});
</script>
</body>
</html>