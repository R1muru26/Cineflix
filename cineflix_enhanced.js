/**
 * CineFlix Enhanced Features Integration
 * Connects all new features with the existing booking system
 */

class CineflixEnhanced {
    constructor() {
        this.init();
    }
    
    init() {
        this.loadDynamicPricing();
        this.initLiveSeatMap();
        this.setupNotifications();
        this.initMobileCheckin();
        this.setupFoodOrdering();
        this.initInnovationFeatures();
    }
    
    /**
     * Initialize Innovation Features
     */
    initInnovationFeatures() {
        // Poll for "Silence Mode" and "Seat Upgrade" notifications every minute
        this.checkInnovationNotifications();
        setInterval(() => this.checkInnovationNotifications(), 60000);
        
        // Innovation Monitor Status (Real-time update)
        this.updateInnovationMonitor('Active', 'Configured (5m)', 'Configured (15m)');
    }
    
    updateInnovationMonitor(sysStatus, silenceStatus, upgradeStatus) {
        const monitor = document.querySelector('.innovation-monitor');
        if (monitor) {
            const items = monitor.querySelectorAll('.status-item');
            if (items[0]) items[0].innerHTML = `<div class="status-dot active"></div><span class="label">Innovation System:</span> ${sysStatus}`;
            if (items[1]) items[1].innerHTML = `<div class="status-dot active"></div><span class="label">Silence Mode:</span> ${silenceStatus}`;
            if (items[2]) items[2].innerHTML = `<div class="status-dot active"></div><span class="label">Seat Upgrade:</span> ${upgradeStatus}`;
        }
    }
    
    async checkInnovationNotifications() {
        try {
            await fetch('api/innovation_checks.php');
            this.loadNotifications(); // Refresh to show new alerts
        } catch (error) {
            console.error('Innovation checks failed:', error);
        }
    }
    
    /**
     * Dynamic Pricing Integration
     */
    async loadDynamicPricing() {
        // Only run on booking page where bookingData exists
        if (typeof bookingData === 'undefined') return;
        
        const movieTitle = bookingData.movie || '';
        const date = document.getElementById('booking-date')?.value || '';
        const cinema = bookingData.cinema || '';
        
        if (movieTitle && date && cinema) {
            try {
                const response = await fetch(`api/dynamic_pricing.php?action=get_price&movie=${encodeURIComponent(movieTitle)}&date=${date}&cinema=${encodeURIComponent(cinema)}`);
                const data = await response.json();
                
                if (data.price) {
                    this.updatePricingDisplay(data);
                    bookingData.price = data.price;
                    this.updateTotalPrice();
                }
            } catch (error) {
                console.error('Failed to load dynamic pricing:', error);
            }
        }
    }
    
    updatePricingDisplay(data) {
        const priceElement = document.querySelector('.price-display');
        if (priceElement) {
            const savings = data.breakdown.savings;
            const savingsText = savings > 0 ? 
                `<span class="savings">Save ₱${savings}</span>` : '';
            
            priceElement.innerHTML = `
                <strong>Price:</strong> ₱${data.price} ${savingsText}
                <div class="pricing-factors">
                    ${data.breakdown.factors.map(factor => `<small>${factor}</small>`).join(' • ')}
                </div>
            `;
        }
    }
    
    /**
     * Live Seat Availability Map
     */
    initLiveSeatMap() {
        const seatsGrid = document.getElementById('seats-grid');
        if (seatsGrid) {
            this.enhanceSeatGrid();
        }
    }
    
    enhanceSeatGrid() {
        // Add heat map overlay
        const heatMapLegend = document.createElement('div');
        heatMapLegend.className = 'heat-map-legend';
        heatMapLegend.innerHTML = `
            <div class="legend-item">
                <div class="heat-box cold"></div>
                <span>Cold</span>
            </div>
            <div class="legend-item">
                <div class="heat-box warm"></div>
                <span>Warm</span>
            </div>
            <div class="legend-item">
                <div class="heat-box hot"></div>
                <span>Hot</span>
            </div>
        `;
        
        const seatsSection = document.querySelector('.seats-section');
        if (seatsSection) {
            seatsSection.insertBefore(heatMapLegend, seatsGrid);
        }
        
        // Add "Best Available" button
        const bestSeatsBtn = document.createElement('button');
        bestSeatsBtn.className = 'btn btn-secondary best-seats-btn';
        bestSeatsBtn.textContent = 'Select Best Seats';
        bestSeatsBtn.addEventListener('click', () => this.selectBestSeats());
        
        const buttonGroup = document.querySelector('.button-group');
        if (buttonGroup) {
            buttonGroup.appendChild(bestSeatsBtn);
        }
    }
    
    async selectBestSeats() {
        const movieTitle = bookingData.movie;
        const date = bookingData.date;
        const time = bookingData.schedule;
        const cinema = bookingData.cinema;
        
        try {
            const response = await fetch(`/api/seat_availability.php?action=get_seats&movie=${encodeURIComponent(movieTitle)}&date=${date}&time=${time}&cinema=${encodeURIComponent(cinema)}`);
            const data = await response.json();
            
            if (data.statistics && data.statistics.best_available) {
                const bestSeats = data.statistics.best_available.slice(0, 2); // Select 2 best seats
                this.selectSeats(bestSeats);
                this.showNotification('Best seats selected!', 'success');
            }
        } catch (error) {
            console.error('Failed to select best seats:', error);
        }
    }
    
    /**
     * Smart Notifications System
     */
    setupNotifications() {
        this.createNotificationPanel();
        this.loadNotifications();
        
        // Refresh notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }
    
    createNotificationPanel() {
        // Only create the panel, don't create a new bell
        // The bell is already handled in homepage.php
        const notificationPanel = document.createElement('div');
        notificationPanel.id = 'notification-panel';
        notificationPanel.className = 'notification-panel';
        notificationPanel.style.display = 'none'; // Ensure it's hidden by default
        notificationPanel.innerHTML = `
            <div class="notification-header">
                <h4>🔔 Smart Alerts</h4>
                <button class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">×</button>
            </div>
            <div class="notification-list"></div>
        `;
        
        document.body.appendChild(notificationPanel);
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=get_notifications');
            const notifications = await response.json();
            
            this.displayNotifications(notifications);
            this.updateNotificationBadge(notifications.length);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }
    
    displayNotifications(notifications) {
        const smartAlertsList = document.getElementById('smartAlertsItems');
        if (!smartAlertsList) return;
        
        smartAlertsList.innerHTML = notifications.map(notification => `
            <div class="inbox-item smart-alert unread" data-notif-type="smart" data-id="${notification.id}">
                <div class="inbox-item-row">
                    <div class="inbox-item-title">${notification.title}</div>
                    <span class="inbox-item-time">just now</span>
                </div>
                <div class="inbox-item-sub">${notification.message}</div>
                <div class="inbox-item-footer">
                    ${notification.action_text ? `<button class="notification-action" onclick="window.location.href='${notification.action_url}'">${notification.action_text}</button>` : ''}
                    <button class="inbox-dismiss" onclick="cineflix.dismissNotification('${notification.id}')">✕</button>
                </div>
            </div>
        `).join('');
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('inboxBadge');
        if (badge) {
            // Count from the PHP rendered list (refunds/discounts)
            const phpItems = document.querySelectorAll('#inboxItemsWrap .inbox-item:not(.smart-alert)').length;
            const total = phpItems + count;
            
            badge.textContent = total;
            badge.style.display = total > 0 ? 'flex' : 'none';
        }
    }
    
    toggleNotifications() {
        const panel = document.getElementById('userInboxPanel');
        if (panel) {
            panel.classList.toggle('open');
            if (panel.classList.contains('open')) {
                // Switch to smart alerts tab if we're coming from a smart alert action
                const smartTab = document.querySelector('.inbox-tab[data-tab="smart"]');
                if (smartTab) smartTab.click();
            }
        }
    }
    
    async dismissNotification(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);

            await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const notification = document.querySelector(`[data-id="${notificationId}"]`);
            if (notification) {
                notification.remove();
            }
            
            this.loadNotifications(); // Refresh
        } catch (error) {
            console.error('Failed to dismiss notification:', error);
        }
    }
    
    /**
     * Mobile Check-in Integration
     */
    initMobileCheckin() {
        // Add check-in button to status page
        const statusContainer = document.querySelector('.status-container');
        if (statusContainer) {
            this.addCheckinButtons(statusContainer);
        }
    }
    
    addCheckinButtons(container) {
        const bookings = container.querySelectorAll('.booking-card');
        bookings.forEach(booking => {
            const bookingId = booking.dataset.bookingId;
            const checkinBtn = document.createElement('button');
            checkinBtn.className = 'btn btn-primary checkin-btn';
            checkinBtn.textContent = 'Mobile Check-in';
            checkinBtn.onclick = () => this.processCheckin(bookingId);
            
            const actions = booking.querySelector('.booking-actions');
            if (actions) {
                actions.appendChild(checkinBtn);
            }
        });
    }
    
    async processCheckin(bookingId) {
        try {
            const response = await fetch('/api/mobile_checkin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=checkin&booking_id=${bookingId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showDigitalTicket(result.ticket);
                this.showNotification('Check-in successful!', 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Check-in failed:', error);
            this.showNotification('Check-in failed. Please try again.', 'error');
        }
    }
    
    showDigitalTicket(ticket) {
        const modal = document.createElement('div');
        modal.className = 'digital-ticket-modal';
        modal.innerHTML = `
            <div class="ticket-content">
                <div class="ticket-header">
                    <h3>🎬 Digital Ticket</h3>
                    <button class="close-btn" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="ticket-body">
                    <div class="ticket-movie">${ticket.movie_title}</div>
                    <div class="ticket-details">
                        <p><strong>Date:</strong> ${ticket.show_date}</p>
                        <p><strong>Time:</strong> ${ticket.show_time}</p>
                        <p><strong>Cinema:</strong> ${ticket.cinema}</p>
                        <p><strong>Seats:</strong> ${ticket.seats.join(', ')}</p>
                        <p><strong>Gate:</strong> ${ticket.gate_number}</p>
                    </div>
                    <div class="ticket-qr">
                        <div class="qr-placeholder">📱 QR Code</div>
                        <small>Security Code: ${ticket.security_code}</small>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    /**
     * Food Ordering Integration
     */
    setupFoodOrdering() {
        // Add QR code display to booking confirmation
        const confirmation = document.querySelector('.confirmation-card');
        if (confirmation && bookingData.bookingId) {
            this.addFoodOrderingQR(confirmation);
        }
    }
    
    addFoodOrderingQR(container) {
        const qrSection = document.createElement('div');
        qrSection.className = 'food-ordering-qr';
        qrSection.innerHTML = `
            <h4>🍟 Order Food & Drinks</h4>
            <p>Scan this QR code in the cinema to order from your seat!</p>
            <div class="qr-code-placeholder">
                📱 Food Ordering QR
            </div>
        `;
        
        container.appendChild(qrSection);
    }
    
    /**
     * Utility Functions
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    selectSeats(seats) {
        seats.forEach(seatId => {
            const seatElement = document.querySelector(`[data-seat="${seatId}"]`);
            if (seatElement && !seatElement.classList.contains('reserved')) {
                seatElement.classList.add('selected');
                bookingData.seats.push(seatId);
            }
        });
        
        this.updateTotalPrice();
    }
    
    updateTotalPrice() {
        if (bookingData.seats.length > 0) {
            const total = bookingData.price * bookingData.seats.length;
            const totalElement = document.getElementById('total-price');
            if (totalElement) {
                totalElement.textContent = total;
            }
        }
    }
}

// Initialize the enhanced features
const cineflix = new CineflixEnhanced();

// Add CSS for new features
const enhancedStyles = `
    .pricing-factors {
        margin-top: 5px;
        font-size: 0.8em;
        opacity: 0.7;
    }
    
    .savings {
        color: #4CAF50;
        font-weight: bold;
    }
    
    .heat-map-legend {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 0.9em;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .heat-box {
        width: 20px;
        height: 20px;
        border-radius: 3px;
    }
    
    .heat-box.cold { background: #4CAF50; }
    .heat-box.warm { background: #FF9800; }
    .heat-box.hot { background: #F44336; }
    
    .notification-panel {
        position: fixed;
        top: clamp(60px, 10vh, 80px);
        right: clamp(10px, 3vw, 20px);
        width: min(350px, 92vw);
        max-height: min(400px, 70vh);
        background: #1a1a2e;
        border: 1px solid #2a2a2a;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: none;
        z-index: 2000;
        overflow: hidden;
    }
    
    .notification-header {
        padding: clamp(10px, 2vh, 15px);
        border-bottom: 1px solid #2a2a2a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notification-list {
        max-height: min(350px, 60vh);
        overflow-y: auto;
    }
    
    .notification-item {
        padding: clamp(10px, 2vh, 15px);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .notification-bell {
        position: relative;
        cursor: pointer;
        font-size: 1.2em;
    }
    
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #c79f5e;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7em;
        display: none;
        align-items: center;
        justify-content: center;
    }
    
    .digital-ticket-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }
    
    .ticket-content {
        background: white;
        border-radius: 10px;
        max-width: 400px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .food-ordering-qr {
        text-align: center;
        margin-top: 20px;
        padding: 20px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 10px;
    }
    
    .qr-placeholder {
        width: 150px;
        height: 150px;
        margin: 10px auto;
        border: 2px dashed #666;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3em;
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = enhancedStyles;
document.head.appendChild(styleSheet);
