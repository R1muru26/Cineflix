<?php
/**
 * OAuth Debug Test Page
 * Shows exactly what redirect URI will be generated
 */
session_start();

$configFile = __DIR__ . '/includes/oauth_config.php';
$config = require $configFile;

// Test Google OAuth redirect URI generation
$provider = 'google';
$redirectUri = rtrim($config['base_url'], '/') . '/api/oauth_callback.php?provider=' . $provider;

$params = [
    'client_id' => $config['google']['client_id'],
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $config['google']['scopes'],
    'prompt' => 'select_account'
];

// Add device_id and device_name for mobile devices
if (isset($_SERVER['HTTP_USER_AGENT']) && (
    strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || 
    strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || 
    strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || 
    strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false
)) {
    $params['device_id'] = 'cineflix_mobile_' . substr(md5($_SERVER['HTTP_USER_AGENT']), 0, 16);
    $params['device_name'] = 'CineFlix Mobile';
}

$authUrl = $config['google']['auth_url'] . '?' . http_build_query($params);
?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth Debug - CineFlix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .uri { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; word-break: break-all; }
        .test-btn { background: #4285f4; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>OAuth Debug Information</h1>
    
    <div class="info">
        <h3>Current Configuration</h3>
        <p><strong>HTTP_HOST:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'not set'; ?></p>
        <p><strong>Base URL:</strong> <?php echo $config['base_url']; ?></p>
        <p><strong>SCRIPT_NAME:</strong> <?php echo $_SERVER['SCRIPT_NAME'] ?? 'not set'; ?></p>
        <p><strong>User Agent:</strong> <?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'not set'; ?></p>
        <p><strong>Is Mobile:</strong> <?php echo (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Mobile') !== false || strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'iPhone') !== false) ? 'Yes' : 'No'; ?></p>
    </div>
    
    <div class="uri">
        <h3>Generated Redirect URI</h3>
        <p><strong>Redirect URI:</strong> <?php echo htmlspecialchars($redirectUri); ?></p>
        <p><strong>Full Auth URL:</strong> <?php echo htmlspecialchars($authUrl); ?></p>
    </div>
    
    <div class="info">
        <h3>What to Add to Google Cloud Console</h3>
        <p>Add this exact redirect URI to your Google Cloud Console:</p>
        <p><code><?php echo htmlspecialchars($redirectUri); ?></code></p>
        
        <h3>Google Cloud Console Link</h3>
        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="test-btn">Open Google Cloud Console</a>
    </div>
    
    <div class="info">
        <h3>Test OAuth Flow</h3>
        <a href="<?php echo htmlspecialchars($authUrl); ?>" class="test-btn">Test Google OAuth</a>
    </div>
</body>
</html>
