<?php
/**
 * PWD Discount Admin Approval Interface
 * Admin can view, approve, or reject PWD discount requests
 */

session_start();
require_once __DIR__ . '/includes/db.php';

// Check if user is admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>Access Denied</h1><p>Admin access required.</p>';
    exit;
}

$conn = db_get_connection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($action === 'approve' && $requestId > 0) {
        $stmt = $conn->prepare("UPDATE pwd_discount_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->bind_param('ii', $_SESSION['user_id'], $requestId);
        $stmt->execute();
        header('Location: pwd-admin.php?approved=1');
        exit;
    } elseif ($action === 'reject' && $requestId > 0) {
        $stmt = $conn->prepare("UPDATE pwd_discount_requests SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param('isi', $_SESSION['user_id'], $reason, $requestId);
        $stmt->execute();
        header('Location: pwd-admin.php?rejected=1');
        exit;
    }
}

// Get all pending requests
$pendingStmt = $conn->prepare("SELECT r.*, u.user_name FROM pwd_discount_requests r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.requested_at DESC");
$pendingStmt->execute();
$pendingRequests = $pendingStmt->get_result();

// Get recent approved/rejected requests
$recentStmt = $conn->prepare("SELECT r.*, u.user_name FROM pwd_discount_requests r LEFT JOIN users u ON r.user_id = u.id WHERE r.status != 'pending' ORDER BY r.reviewed_at DESC LIMIT 10");
$recentStmt->execute();
$recentRequests = $recentStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Discount Admin - CineFlix</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #c79f5e, #d98639);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .header p {
            color: #aaa;
            font-size: 1.1rem;
        }
        .success-msg, .error-msg {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .success-msg {
            background: rgba(38, 222, 129, 0.2);
            border: 1px solid rgba(38, 222, 129, 0.4);
            color: #26de81;
        }
        .error-msg {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.4);
            color: #f44336;
        }
        .section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }
        .section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #c79f5e;
        }
        .request-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .request-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .request-id {
            font-weight: 600;
            color: #c79f5e;
        }
        .request-time {
            color: #aaa;
            font-size: 0.9rem;
        }
        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 5px;
        }
        .detail-value {
            font-weight: 500;
            color: #fff;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        .btn-approve {
            background: linear-gradient(135deg, #26de81, #20c76f);
            color: white;
        }
        .btn-approve:hover {
            background: linear-gradient(135deg, #20c76f, #1ba65f);
            transform: translateY(-1px);
        }
        .btn-reject {
            background: linear-gradient(135deg, #e53935, #d32f2f);
            color: white;
        }
        .btn-reject:hover {
            background: linear-gradient(135deg, #d32f2f, #c62828);
            transform: translateY(-1px);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
            border: 1px solid rgba(255, 152, 0, 0.4);
        }
        .status-approved {
            background: rgba(38, 222, 129, 0.2);
            color: #26de81;
            border: 1px solid rgba(38, 222, 129, 0.4);
        }
        .status-rejected {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.4);
        }
        .rejection-reason {
            margin-top: 10px;
            padding: 10px;
            background: rgba(244, 67, 54, 0.1);
            border-radius: 6px;
            border-left: 3px solid #f44336;
            font-size: 0.9rem;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #aaa;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #c79f5e;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .reject-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: rgba(244, 67, 54, 0.1);
            border-radius: 8px;
        }
        .reject-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(244, 67, 54, 0.4);
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.2);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 80px;
        }
        .reject-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="homepage.php" class="back-link">← Back to Homepage</a>
        
        <div class="header">
            <h1>🪪 PWD Discount Admin</h1>
            <p>Manage PWD/Senior Citizen discount requests</p>
        </div>

        <?php if (isset($_GET['approved'])): ?>
            <div class="success-msg">✅ Discount request approved successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['rejected'])): ?>
            <div class="error-msg">❌ Discount request rejected.</div>
        <?php endif; ?>

        <div class="section">
            <h2>📋 Pending Requests (<?php echo $pendingRequests->num_rows; ?>)</h2>
            
            <?php if ($pendingRequests->num_rows > 0): ?>
                <?php while ($request = $pendingRequests->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <span class="request-id">Request #<?php echo $request['id']; ?></span>
                            <span class="request-time"><?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?></span>
                        </div>
                        
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">PWD ID</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['pwd_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">User</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['user_name'] ?? 'Guest'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Cart Total</span>
                                <span class="detail-value">₱<?php echo number_format($request['cart_total'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Discount Amount</span>
                                <span class="detail-value" style="color: #26de81;">₱<?php echo number_format($request['cart_total'] * 0.20, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="btn btn-approve">✅ Approve</button>
                            </form>
                            
                            <button class="btn btn-reject" onclick="showRejectForm(<?php echo $request['id']; ?>)">❌ Reject</button>
                            
                            <div id="reject-form-<?php echo $request['id']; ?>" class="reject-form">
                                <form method="POST">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <textarea name="reason" placeholder="Reason for rejection (optional)..."></textarea>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-reject">Confirm Reject</button>
                                        <button type="button" class="btn" style="background: #666;" onclick="hideRejectForm(<?php echo $request['id']; ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 No pending PWD discount requests</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📜 Recent Activity</h2>
            
            <?php if ($recentRequests->num_rows > 0): ?>
                <?php while ($request = $recentRequests->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <span class="request-id">Request #<?php echo $request['id']; ?></span>
                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                        
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">PWD ID</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['pwd_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">User</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['user_name'] ?? 'Guest'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Cart Total</span>
                                <span class="detail-value">₱<?php echo number_format($request['cart_total'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reviewed</span>
                                <span class="detail-value"><?php echo $request['reviewed_at'] ? date('M j, g:i A', strtotime($request['reviewed_at'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($request['status'] === 'rejected' && $request['rejection_reason']): ?>
                            <div class="rejection-reason">
                                <strong>Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 No recent activity</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showRejectForm(requestId) {
            document.getElementById('reject-form-' + requestId).style.display = 'block';
        }
        
        function hideRejectForm(requestId) {
            document.getElementById('reject-form-' + requestId).style.display = 'none';
        }
    </script>
</body>
</html>
