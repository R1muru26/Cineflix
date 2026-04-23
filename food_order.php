<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering | CineFlix</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="booking.css">
    <style>
        .food-ordering-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .menu-category {
            margin-bottom: 30px;
        }
        
        .category-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #c79f5e;
            border-bottom: 2px solid #c79f5e;
            padding-bottom: 5px;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .menu-item {
            background: rgba(40, 40, 40, 0.8);
            border: 2px solid rgba(199, 159, 94, 0.3);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            transform: translateY(-4px);
            border-color: rgba(199, 159, 94, 0.6);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }
        
        .menu-item.selected {
            background: rgba(199, 159, 94, 0.2);
            border-color: #c79f5e;
        }
        
        .item-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
        }
        
        .item-name {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
            color: #fff;
        }
        
        .item-description {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }
        
        .item-price {
            font-size: 1.2em;
            font-weight: bold;
            color: #c79f5e;
            margin-bottom: 15px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            background: #c79f5e;
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }
        
        .quantity-btn:hover {
            background: #a67c42;
        }
        
        .quantity-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }
        
        .quantity-display {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 50px;
            height: 30px;
            text-align: center;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .order-summary {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-top: 2px solid #c79f5e;
            padding: 20px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .order-summary.show {
            transform: translateY(0);
        }
        
        .summary-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .summary-items {
            flex: 1;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .summary-total {
            font-size: 1.3em;
            font-weight: bold;
            color: #c79f5e;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .order-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .prep-time {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9em;
        }
        
        .place-order-btn {
            background: #c79f5e;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .place-order-btn:hover {
            background: #a67c42;
            transform: translateY(-2px);
        }
        
        .place-order-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }
        
        .booking-info {
            background: rgba(199, 159, 94, 0.1);
            border: 1px solid rgba(199, 159, 94, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .booking-info h3 {
            margin: 0 0 10px 0;
            color: #c79f5e;
        }
        
        .order-status {
            text-align: center;
            padding: 20px;
            display: none;
        }
        
        .status-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .status-message {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        .status-details {
            color: rgba(255, 255, 255, 0.7);
        }
        
        @media (max-width: 768px) {
            .menu-items {
                grid-template-columns: 1fr;
            }
            
            .summary-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .order-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body class="has-background">
    <header class="site-header">
        <a class="logo" href="homepage.php">
            <img src="logo/newlogo1.png" alt="CineFlix Logo">
        </a>
        <nav class="top-nav">
            <ul>
                <li><a class="nav-btn" href="status.php">My Bookings</a></li>
            </ul>
        </nav>
    </header>

    <main class="food-ordering-container">
        <div class="booking-info" id="booking-info">
            <h3>🍟 Order for Your Movie</h3>
            <div id="booking-details">Loading booking information...</div>
        </div>

        <div id="menu-container">
            <h2 style="text-align: center; margin-bottom: 30px;">Food & Drinks Menu</h2>
            <div id="menu-categories"></div>
        </div>

        <div class="order-status" id="order-status">
            <div class="status-icon" id="status-icon">⏳</div>
            <div class="status-message" id="status-message">Preparing your order...</div>
            <div class="status-details" id="status-details"></div>
        </div>
    </main>

    <div class="order-summary" id="order-summary">
        <div class="summary-content">
            <div class="summary-items">
                <h4>Order Summary</h4>
                <div id="summary-items"></div>
                <div class="summary-total">
                    Total: ₱<span id="summary-total">0</span>
                </div>
            </div>
            <div class="order-actions">
                <div class="prep-time">
                    ⏱️ <span id="prep-time">0</span> min prep
                </div>
                <button class="place-order-btn" id="place-order-btn" disabled>Place Order</button>
            </div>
        </div>
    </div>

    <script>
        class FoodOrderingApp {
            constructor() {
                this.bookingId = this.getUrlParam('booking');
                this.seatNumber = this.getUrlParam('seat');
                this.menu = [];
                this.order = {};
                this.total = 0;
                this.prepTime = 0;
                
                this.init();
            }
            
            init() {
                this.loadBookingInfo();
                this.loadMenu();
                this.setupEventListeners();
            }
            
            getUrlParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }
            
            async loadBookingInfo() {
                try {
                    // In a real implementation, this would fetch from the API
                    const bookingInfo = {
                        movie: 'Chainsaw Man',
                        date: 'March 26, 2026',
                        time: '7:10 PM',
                        seat: this.seatNumber || 'A5'
                    };
                    
                    document.getElementById('booking-details').innerHTML = `
                        <strong>${bookingInfo.movie}</strong><br>
                        ${bookingInfo.date} at ${bookingInfo.time}<br>
                        Seat: ${bookingInfo.seat}
                    `;
                } catch (error) {
                    console.error('Failed to load booking info:', error);
                }
            }
            
            async loadMenu() {
                try {
                    const response = await fetch('/api/food_ordering.php?action=get_menu');
                    this.menu = await response.json();
                    this.renderMenu();
                } catch (error) {
                    console.error('Failed to load menu:', error);
                    this.renderFallbackMenu();
                }
            }
            
            renderMenu() {
                const container = document.getElementById('menu-categories');
                container.innerHTML = this.menu.categories.map(category => `
                    <div class="menu-category">
                        <div class="category-title">${category.name}</div>
                        <div class="menu-items">
                            ${category.items.map(item => this.renderMenuItem(item)).join('')}
                        </div>
                    </div>
                `).join('');
            }
            
            renderMenuItem(item) {
                const quantity = this.order[item.id] || 0;
                return `
                    <div class="menu-item ${quantity > 0 ? 'selected' : ''}" data-item-id="${item.id}">
                        <div class="item-image">
                            <img src="${item.image}" alt="${item.name}" onerror="this.style.display='none'; this.parentElement.innerHTML='🍿';">
                        </div>
                        <div class="item-name">${item.name}</div>
                        <div class="item-description">${item.description}</div>
                        <div class="item-price">₱${item.price}</div>
                        <div class="quantity-controls">
                            <button class="quantity-btn minus" ${quantity === 0 ? 'disabled' : ''} onclick="foodApp.updateQuantity('${item.id}', -1)">−</button>
                            <div class="quantity-display">${quantity}</div>
                            <button class="quantity-btn plus" onclick="foodApp.updateQuantity('${item.id}', 1)">+</button>
                        </div>
                    </div>
                `;
            }
            
            renderFallbackMenu() {
                const fallbackMenu = {
                    categories: [
                        {
                            name: 'Popcorn',
                            items: [
                                { id: 'pop_s', name: 'Small Popcorn', price: 120, description: 'Buttered popcorn, small size', image: 'food&drinks/popcorn.png', prep_time: 3 },
                                { id: 'pop_m', name: 'Medium Popcorn', price: 150, description: 'Buttered popcorn, medium size', image: 'food&drinks/popcorn.png', prep_time: 3 },
                                { id: 'pop_l', name: 'Large Popcorn', price: 180, description: 'Buttered popcorn, large size', image: 'food&drinks/popcorn.png', prep_time: 3 }
                            ]
                        },
                        {
                            name: 'Drinks',
                            items: [
                                { id: 'coke_s', name: 'Coke (Small)', price: 80, description: 'Coca-Cola, 12oz', image: 'food&drinks/coca-cola.png', prep_time: 1 },
                                { id: 'coke_m', name: 'Coke (Medium)', price: 100, description: 'Coca-Cola, 16oz', image: 'food&drinks/coca-cola.png', prep_time: 1 },
                                { id: 'coke_l', name: 'Coke (Large)', price: 120, description: 'Coca-Cola, 20oz', image: 'food&drinks/coca-cola.png', prep_time: 1 }
                            ]
                        }
                    ]
                };
                
                this.menu = fallbackMenu;
                this.renderMenu();
            }
            
            updateQuantity(itemId, change) {
                const currentQuantity = this.order[itemId] || 0;
                const newQuantity = Math.max(0, currentQuantity + change);
                
                if (newQuantity === 0) {
                    delete this.order[itemId];
                } else {
                    this.order[itemId] = newQuantity;
                }
                
                this.updateMenuItemDisplay(itemId);
                this.updateOrderSummary();
            }
            
            updateMenuItemDisplay(itemId) {
                const menuItem = document.querySelector(`[data-item-id="${itemId}"]`);
                if (menuItem) {
                    const quantity = this.order[itemId] || 0;
                    const quantityDisplay = menuItem.querySelector('.quantity-display');
                    const minusBtn = menuItem.querySelector('.minus');
                    const plusBtn = menuItem.querySelector('.plus');
                    
                    quantityDisplay.textContent = quantity;
                    minusBtn.disabled = quantity === 0;
                    
                    if (quantity > 0) {
                        menuItem.classList.add('selected');
                    } else {
                        menuItem.classList.remove('selected');
                    }
                }
            }
            
            updateOrderSummary() {
                const summaryItems = document.getElementById('summary-items');
                const summaryTotal = document.getElementById('summary-total');
                const prepTimeDisplay = document.getElementById('prep-time');
                const placeOrderBtn = document.getElementById('place-order-btn');
                const orderSummary = document.getElementById('order-summary');
                
                let total = 0;
                let prepTime = 0;
                let itemsHtml = '';
                
                // Find item details from menu
                const allItems = [];
                this.menu.categories.forEach(category => {
                    allItems.push(...category.items);
                });
                
                Object.entries(this.order).forEach(([itemId, quantity]) => {
                    const item = allItems.find(i => i.id === itemId);
                    if (item) {
                        const itemTotal = item.price * quantity;
                        total += itemTotal;
                        prepTime = Math.max(prepTime, item.prep_time);
                        
                        itemsHtml += `
                            <div class="summary-item">
                                <span>${item.name} × ${quantity}</span>
                                <span>₱${itemTotal}</span>
                            </div>
                        `;
                    }
                });
                
                summaryItems.innerHTML = itemsHtml || '<div class="summary-item">No items selected</div>';
                summaryTotal.textContent = total;
                prepTimeDisplay.textContent = prepTime;
                
                placeOrderBtn.disabled = Object.keys(this.order).length === 0;
                
                if (Object.keys(this.order).length > 0) {
                    orderSummary.classList.add('show');
                } else {
                    orderSummary.classList.remove('show');
                }
                
                this.total = total;
                this.prepTime = prepTime;
            }
            
            async placeOrder() {
                if (Object.keys(this.order).length === 0) return;
                
                const placeOrderBtn = document.getElementById('place-order-btn');
                placeOrderBtn.disabled = true;
                placeOrderBtn.textContent = 'Placing Order...';
                
                try {
                    const orderItems = Object.entries(this.order).map(([itemId, quantity]) => ({
                        id: itemId,
                        quantity: quantity
                    }));
                    
                    const formData = new FormData();
                    formData.append('action', 'place_order');
                    formData.append('booking_id', this.bookingId);
                    formData.append('seat_number', this.seatNumber);
                    formData.append('items', JSON.stringify(orderItems));
                    formData.append('customer_name', 'CineFlix Customer');
                    
                    const response = await fetch('/api/food_ordering.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showOrderStatus(result);
                    } else {
                        throw new Error(result.message || 'Order failed');
                    }
                } catch (error) {
                    console.error('Order failed:', error);
                    alert('Failed to place order. Please try again.');
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.textContent = 'Place Order';
                }
            }
            
            showOrderStatus(orderResult) {
                const menuContainer = document.getElementById('menu-container');
                const orderStatus = document.getElementById('order-status');
                const orderSummary = document.getElementById('order-summary');
                
                menuContainer.style.display = 'none';
                orderSummary.classList.remove('show');
                orderStatus.style.display = 'block';
                
                document.getElementById('status-icon').textContent = '👨‍🍳';
                document.getElementById('status-message').textContent = 'Order Placed Successfully!';
                document.getElementById('status-details').innerHTML = `
                    Order ID: ${orderResult.order_id}<br>
                    Total: ₱${orderResult.total}<br>
                    Estimated delivery: ${orderResult.estimated_delivery}<br>
                    <br>
                    <small>Your order will be delivered to your seat. Enjoy the movie!</small>
                `;
                
                // Simulate order progress
                setTimeout(() => {
                    document.getElementById('status-icon').textContent = '🚚';
                    document.getElementById('status-message').textContent = 'Order is on the way!';
                }, orderResult.prep_time * 60000); // Convert minutes to milliseconds
            }
            
            setupEventListeners() {
                document.getElementById('place-order-btn').addEventListener('click', () => {
                    this.placeOrder();
                });
            }
        }
        
        // Initialize the app
        const foodApp = new FoodOrderingApp();
    </script>
</body>
</html>
