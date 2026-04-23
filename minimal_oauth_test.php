<?php
/**
 * Minimal OAuth Test - Isolate the exact redirect URI issue
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal OAuth Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .test-btn { background: #4285f4; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .test-btn:hover { background: #357ae8; }
        .uri-display { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; font-family: monospace; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Minimal Google OAuth Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Direct Google OAuth with Minimal Parameters</h2>
        <p>This test uses the absolute minimal parameters required by Google OAuth.</p>
        
        <div class="uri-display">
            <strong>Redirect URI:</strong> http://localhost/CINEFLIX/api/oauth_callback.php?provider=google
        </div>
        
        <p><a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=91234755435-d10ca32iqriflobl5hckughokt5pjc5a.apps.googleusercontent.com&redirect_uri=http://localhost/CINEFLIX/api/oauth_callback.php?provider=google&response_type=code&scope=openid email profile&prompt=select_account" class="test-btn">Test Minimal OAuth</a></p>
    </div>
    
    <div class="test-section">
        <h2>Test 2: URL-Encoded Redirect URI</h2>
        <p>This test uses URL-encoded redirect URI to ensure no character issues.</p>
        
        <div class="uri-display">
            <strong>URL-Encoded:</strong> http%3A%2F%2Flocalhost%2FCINEFLIX%2Fapi%2Foauth_callback.php%3Fprovider%3Dgoogle
        </div>
        
        <p><a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=91234755435-d10ca32iqriflobl5hckughokt5pjc5a.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flocalhost%2FCINEFLIX%2Fapi%2Foauth_callback.php%3Fprovider%3Dgoogle&response_type=code&scope=openid+email+profile&prompt=select_account" class="test-btn">Test URL-Encoded OAuth</a></p>
    </div>
    
    <div class="test-section">
        <h2>Test 3: Without Provider Parameter</h2>
        <p>Test if the provider parameter is causing issues.</p>
        
        <div class="uri-display">
            <strong>Redirect URI:</strong> http://localhost/CINEFLIX/api/oauth_callback.php
        </div>
        
        <p><a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=91234755435-d10ca32iqriflobl5hckughokt5pjc5a.apps.googleusercontent.com&redirect_uri=http://localhost/CINEFLIX/api/oauth_callback.php&response_type=code&scope=openid email profile&prompt=select_account&state=test" class="test-btn">Test Without Provider Parameter</a></p>
    </div>
    
    <div class="test-section">
        <h2>Debug Information</h2>
        <p><strong>Current Google Cloud Console should have:</strong></p>
        <div class="uri-display">
            http://localhost/CINEFLIX/api/oauth_callback.php?provider=google
        </div>
        <p><strong>Also add:</strong></p>
        <div class="uri-display">
            http://localhost/CINEFLIX/api/oauth_callback.php
        </div>
    </div>
    
    <div class="test-section">
        <h2>Google Cloud Console</h2>
        <p><a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="test-btn">Open Google Cloud Console</a></p>
    </div>
</body>
</html>
