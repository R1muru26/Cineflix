# OAuth Setup (Google & Microsoft Sign-In)

To enable Google and Microsoft Sign-In, configure your OAuth credentials.

## 1. Google Sign-In

1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create or select a project
3. Create OAuth 2.0 credentials (Web application)
4. Add **Authorized redirect URI**:  
   `http://localhost/CINEFLIX/api/oauth_callback.php?provider=google`  
   (Replace with your actual domain if deployed)
5. Copy **Client ID** and **Client Secret**

## 2. Microsoft Sign-In

1. Go to [Azure Portal - App registrations](https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade)
2. New registration → Web app
3. Add **Redirect URI**:  
   `http://localhost/CINEFLIX/api/oauth_callback.php?provider=microsoft`
4. Under **Certificates & secrets**, create a client secret
5. Copy **Application (client) ID** and **Client secret**

## 3. Configure CineFlix

Edit `includes/oauth_config.php` and replace:

- `YOUR_GOOGLE_CLIENT_ID` with your Google Client ID
- `YOUR_GOOGLE_CLIENT_SECRET` with your Google Client Secret
- `YOUR_MICROSOFT_CLIENT_ID` with your Microsoft Application ID
- `YOUR_MICROSOFT_CLIENT_SECRET` with your Microsoft Client Secret

Or use environment variables:
- `CINEFLIX_GOOGLE_CLIENT_ID`
- `CINEFLIX_GOOGLE_CLIENT_SECRET`
- `CINEFLIX_MICROSOFT_CLIENT_ID`
- `CINEFLIX_MICROSOFT_CLIENT_SECRET`
