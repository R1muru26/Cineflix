# CineFlix API Documentation

This document lists all API endpoints, where they are used, and whether additional APIs are needed.

---

## 1. Google & Microsoft Login (OAuth)

| API | File | Used In | Purpose |
|-----|------|---------|---------|
| **OAuth Start** | `api/oauth_start.php` | `login.html` (links: `api/oauth_start.php?provider=google`, `api/oauth_start.php?provider=microsoft`) | Redirects user to Google or Microsoft sign-in page |
| **OAuth Callback** | `api/oauth_callback.php` | Redirect target from OAuth provider | Exchanges auth code for tokens, fetches user info, creates/logs in user |

**External API calls made by OAuth:**
- Google: `https://oauth2.googleapis.com/token` (token exchange)
- Google: `https://www.googleapis.com/oauth2/v2/userinfo` (user info)
- Microsoft: `https://login.microsoftonline.com/common/oauth2/v2.0/token`
- Microsoft: `https://graph.microsoft.com/v1.0/me` (user info)

**Config:** `includes/oauth_config.php` – set `client_id` and `client_secret` for each provider.

**Additional API needed?** No. OAuth flow is complete. Microsoft requires valid Azure AD app credentials.

---

## 2. Seat Recommendation System

| API | File | Used In | Purpose |
|-----|------|---------|---------|
| **Chatbot (seat recommendations)** | `api/chatbot.php` | `chatbot.js` | Returns seat recommendations based on user preference (alone, view, comfort) |
| **Reserved Seats** | `api/reserved_seats.php` | `booking.js` (when loading seat grid) | Returns list of already-reserved seats for a given show |

**External API calls:** None. Seat recommendations are logic-based in `chatbot.php` (`getSeatRecommendationByPreference`, `getSeatRecommendation`).

**Additional API needed?** No. Seat recommendation is handled entirely within the chatbot. For real-time seat availability, `reserved_seats.php` is used.

---

## 3. Food Ordering & Tracking

| API | File | Used In | Purpose |
|-----|------|---------|---------|
| **Chatbot (food order)** | `api/chatbot.php` | `chatbot.js` | Handles chat, order parsing, and order creation (POST `action=order`) |
| **Food Track** | `api/food_track.php` | `chatbot.js` | Returns real-time order status (GET `?orderId=FD...`) |
| **Receipt** | `api/receipt.php` | `chatbot.js` | Returns order details for receipt download (GET `?orderId=FD...`) |

**External API calls:** OpenAI API (chatbot fallback) – `https://api.openai.com/v1/chat/completions`

**Additional API needed?**
- **Food order status updates:** Currently `food_track.php` only reads status. A staff/admin API to update food order status (received → preparing → ready → delivering → delivered) would enable accurate real-time tracking. *Suggested: `api/food_order_status.php` (POST) for staff use.*

---

## 4. Other APIs

| API | File | Purpose |
|-----|------|---------|
| `api/save_booking.php` | Booking creation | Saves ticket booking |
| `api/get_bookings.php` | Status page | Fetches user bookings |
| `api/request_refund.php` | Refund flow | Submits refund request |
| `api/admin_refunds.php` | Admin dashboard | Approve/reject refunds |
| `api/admin_discounts.php` | Admin dashboard | Approve discount applications |
| `api/captcha.php` | Login/signup | reCAPTCHA verification |
| `api/request_password_change.php` | Forgot password | Request OTP |
| `api/confirm_password_change.php` | Reset password | Confirm OTP and set new password |

---

## Summary

- **OAuth:** Google and Microsoft login work via `oauth_start.php` and `oauth_callback.php`. No additional API calls needed.
- **Seat Recommendation:** Handled in chatbot; no external API.
- **Food Ordering & Tracking:** Chatbot + `food_track.php` + `receipt.php`. Optional: staff API to update food order status for accurate tracking.
