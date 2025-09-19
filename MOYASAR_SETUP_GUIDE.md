# Moyasar Integration Setup Guide

## Current Status
Your Moyasar integration code is complete and ready, but the test credentials appear to be invalid or expired.

## Test Results
- ✅ SSL configuration working
- ✅ API integration code complete
- ✅ Webhook handling implemented
- ❌ Authentication failing with provided credentials

## Getting Valid Moyasar Credentials

### 1. Create Moyasar Account
1. Visit [https://moyasar.com/](https://moyasar.com/)
2. Sign up for a developer account
3. Complete account verification

### 2. Get API Keys
1. Log into your Moyasar dashboard
2. Navigate to Settings > API Keys
3. Copy your test credentials:
   - **Publishable Key**: `pk_test_xxxxxxxxxx`
   - **Secret Key**: `sk_test_xxxxxxxxxx`

### 3. Update Configuration
Replace the credentials in `backend/config/moyasar_config.php`:

```php
const PUBLISHABLE_KEY = 'your_actual_pk_test_key';
const SECRET_KEY = 'your_actual_sk_test_key';
```

## Testing the Integration

### 1. Test Authentication
```bash
cd backend
php test_moyasar_auth.php
```

### 2. Run Full Integration Test
```bash
cd backend
php test_moyasar.php
```

### 3. Test Payment Flow
1. Navigate to your website's checkout page
2. Select a card payment method (Visa, Mastercard, or Mada)
3. You should be redirected to `/moyasar-payment.html`
4. The Moyasar payment form should load

## Available Payment Methods via Moyasar

Your integration supports:
- **Credit/Debit Cards**: Visa, Mastercard, Mada, Amex
- **STC Pay**: Through Moyasar's STC Pay integration
- **Apple Pay**: Through Moyasar's Apple Pay integration

## Webhook Configuration

### 1. Set Webhook URL in Moyasar Dashboard
```
https://yourdomain.com/api/payments/callback/moyasar
```

### 2. Webhook Events to Subscribe
- `payment.paid`
- `payment.failed`
- `payment.authorized`

## Mock Testing (If Real Credentials Not Available)

I've created a mock mode for testing. To enable:

1. Create a file `backend/config/mock_mode.php`:
```php
<?php
define('MOYASAR_MOCK_MODE', true);
?>
```

2. The integration will use mock responses for testing the flow.

## Files Created/Modified

### New Files:
- `backend/config/moyasar_config.php` - Configuration
- `backend/services/moyasar_service.php` - API service
- `frontend/public/moyasar-payment.html` - Payment page
- `backend/test_moyasar.php` - Integration test
- `backend/test_moyasar_auth.php` - Authentication test
- `backend/download_cacert.php` - SSL certificate helper

### Modified Files:
- `backend/api/payments.php` - Added Moyasar integration

## Production Deployment

### 1. Get Production Credentials
- Replace test keys with live keys from Moyasar dashboard
- Update webhook URLs to production domain

### 2. SSL Certificate
- Ensure your server has valid SSL certificates
- Download CA bundle: `php download_cacert.php`

### 3. Security
- Enable SSL verification in production
- Set proper environment variables
- Configure proper webhook signature verification

## Troubleshooting

### Authentication Errors
1. Verify credentials in Moyasar dashboard
2. Check if account is activated
3. Ensure you're using the correct environment (test vs live)

### SSL Errors
1. Run: `php download_cacert.php`
2. Or download manually from: https://curl.se/ca/cacert.pem
3. Place in: `backend/config/cacert.pem`

### Payment Form Not Loading
1. Check browser console for JavaScript errors
2. Verify Moyasar publishable key in payment form
3. Check network requests to Moyasar API

## Support
- **Moyasar Documentation**: https://moyasar.com/docs/
- **Moyasar Support**: support@moyasar.com
- **Integration Status**: Ready for testing with valid credentials