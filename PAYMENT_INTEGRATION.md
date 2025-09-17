# Payment Integration Guide

This document provides a comprehensive guide for integrating payment methods with the Dar Zaid Publishing website.

## Overview

The payment system supports multiple payment providers popular in Saudi Arabia and the MENA region:

- **STC Pay** - Digital wallet
- **Tamara** - Buy now, pay later (3-6 installments)
- **Tabby** - Buy now, pay later (4 installments)
- **Google Pay** - Digital wallet
- **Apple Pay** - Digital wallet
- **Visa/Mastercard** - Credit/debit cards
- **mada** - Saudi domestic payment network
- **Bank Transfer** - Direct bank transfer

## Architecture

### Frontend Components

1. **PaymentMethods Component** (`/src/components/PaymentMethods.js`)
   - Displays available payment methods based on amount and currency
   - Groups methods by category (digital wallets, BNPL, cards, bank transfer)
   - Shows installment options for BNPL methods
   - Handles method selection and payment initiation

2. **CheckoutPayment Component** (`/src/components/CheckoutPayment.js`)
   - Complete checkout flow with payment integration
   - Order summary display
   - Payment status handling (processing, success, failure, pending)
   - Specialized flows for different payment types

3. **Payment Service** (`/src/services/paymentService.js`)
   - Centralized payment logic
   - Method availability validation
   - Amount/currency validation
   - API integration wrapper

### Backend API

**Endpoint:** `/api/payments.php`

Supported operations:
- `POST /payments/initialize` - Initialize payment
- `POST /payments/process` - Process payment
- `GET /payments/verify/{id}` - Verify payment status
- `POST /payments/callback` - Handle provider callbacks
- `POST /payments/refund` - Process refunds
- `GET /payments/history` - Get payment history

### Database Schema

**payments table:**
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    order_id VARCHAR(255) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'SAR',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'),
    provider_transaction_id VARCHAR(255),
    provider_response JSON,
    customer_info JSON,
    payment_details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**orders table:**
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(255) UNIQUE NOT NULL,
    customer_id INT,
    customer_info JSON NOT NULL,
    items JSON NOT NULL,
    shipping_address JSON,
    billing_address JSON,
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_cost DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'SAR',
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Integration Steps

### 1. STC Pay Integration

1. Register with STC Pay and obtain credentials
2. Update `initializeSTCPay()` function in `/backend/api/payments.php`
3. Set environment variables:
   ```env
   STC_PAY_MERCHANT_ID=your_merchant_id
   STC_PAY_API_KEY=your_api_key
   STC_PAY_SECRET=your_secret
   ```

Example integration:
```php
function initializeSTCPay($transaction_id, $data) {
    $stc_config = [
        'merchant_id' => $_ENV['STC_PAY_MERCHANT_ID'],
        'api_key' => $_ENV['STC_PAY_API_KEY'],
        'secret' => $_ENV['STC_PAY_SECRET']
    ];

    $payment_data = [
        'amount' => $data['amount'],
        'currency' => $data['currency'],
        'reference' => $transaction_id,
        'callback_url' => 'https://yourdomain.com/api/payments/callback',
        'return_url' => 'https://yourdomain.com/payment/success'
    ];

    // Make API call to STC Pay
    $response = makeSTCPayRequest($stc_config, $payment_data);

    return [
        'status' => 'redirect',
        'redirect_url' => $response['payment_url'],
        'transaction_id' => $transaction_id
    ];
}
```

### 2. Tamara Integration

1. Register with Tamara and get API credentials
2. Update `initializeTamara()` function
3. Set environment variables:
   ```env
   TAMARA_API_URL=https://api.tamara.co
   TAMARA_API_TOKEN=your_api_token
   TAMARA_PUBLIC_KEY=your_public_key
   ```

Example integration:
```php
function initializeTamara($transaction_id, $data) {
    $tamara_config = [
        'api_url' => $_ENV['TAMARA_API_URL'],
        'token' => $_ENV['TAMARA_API_TOKEN']
    ];

    $checkout_data = [
        'total_amount' => [
            'amount' => $data['amount'],
            'currency' => $data['currency']
        ],
        'shipping_amount' => [
            'amount' => $data['shipping_cost'] ?? 0,
            'currency' => $data['currency']
        ],
        'order_reference_id' => $data['order_id'],
        'order_number' => $transaction_id,
        'consumer' => [
            'first_name' => $data['customer_info']['name'],
            'email' => $data['customer_info']['email'],
            'phone_number' => $data['customer_info']['phone']
        ],
        'shipping_address' => $data['shipping_address'],
        'merchant_url' => [
            'success' => 'https://yourdomain.com/payment/success',
            'failure' => 'https://yourdomain.com/payment/failure',
            'cancel' => 'https://yourdomain.com/payment/cancel',
            'notification' => 'https://yourdomain.com/api/payments/callback'
        ]
    ];

    $response = makeTamaraRequest($tamara_config, $checkout_data);

    return [
        'status' => 'redirect',
        'redirect_url' => $response['checkout_url'],
        'transaction_id' => $transaction_id
    ];
}
```

### 3. Tabby Integration

Similar to Tamara but with 4 installments:
```env
TABBY_API_URL=https://api.tabby.ai
TABBY_PUBLIC_KEY=your_public_key
TABBY_SECRET_KEY=your_secret_key
```

### 4. Card Payments (Visa/Mastercard/mada)

Integrate with payment processors like:
- **HyperPay**
- **PayTabs**
- **Moyasar**
- **Checkout.com**

Example with HyperPay:
```env
HYPERPAY_ENTITY_ID=your_entity_id
HYPERPAY_ACCESS_TOKEN=your_access_token
HYPERPAY_TEST_MODE=true
```

### 5. Google Pay Integration

1. Set up Google Pay merchant account
2. Add Google Pay JavaScript SDK
3. Configure merchant ID:
   ```env
   GOOGLE_PAY_MERCHANT_ID=your_merchant_id
   GOOGLE_PAY_GATEWAY=your_gateway
   ```

### 6. Apple Pay Integration

1. Register Apple Pay merchant ID
2. Set up merchant domain verification
3. Configure environment:
   ```env
   APPLE_PAY_MERCHANT_ID=merchant.yourdomain.com
   APPLE_PAY_COUNTRY_CODE=SA
   ```

## Frontend Usage

### Basic Payment Method Selection

```jsx
import PaymentMethods from './components/PaymentMethods';
import { paymentService } from './services/paymentService';

const Checkout = () => {
  const [selectedMethod, setSelectedMethod] = useState(null);
  const amount = 150; // SAR

  const handlePayment = async (method) => {
    try {
      const result = await paymentService.initializePayment(method.id, {
        amount,
        currency: 'SAR',
        order_id: 'order_123',
        customer_info: {
          name: 'أحمد محمد',
          email: 'ahmed@example.com',
          phone: '+966501234567'
        }
      });

      if (result.status === 'redirect') {
        window.location.href = result.redirect_url;
      }
    } catch (error) {
      console.error('Payment failed:', error);
    }
  };

  return (
    <PaymentMethods
      amount={amount}
      currency="SAR"
      selectedMethod={selectedMethod}
      onMethodSelect={setSelectedMethod}
      onPaymentInitiate={handlePayment}
    />
  );
};
```

### Payment Verification

```jsx
const verifyPayment = async (transactionId) => {
  try {
    const result = await paymentService.verifyPayment(transactionId);

    if (result.status === 'completed') {
      // Payment successful
      console.log('Payment completed');
    } else if (result.status === 'pending') {
      // Payment pending (e.g., bank transfer)
      console.log('Payment pending');
    } else {
      // Payment failed
      console.log('Payment failed');
    }
  } catch (error) {
    console.error('Verification failed:', error);
  }
};
```

## Configuration

### Payment Method Configuration

Each payment method has configurable properties:

```javascript
const PAYMENT_METHODS = {
  STC_PAY: {
    id: 'stc_pay',
    name: 'STC Pay',
    nameAr: 'إس تي سي باي',
    enabled: true,
    minAmount: 1,
    maxAmount: 10000,
    supportedCurrencies: ['SAR']
  }
  // ... other methods
};
```

### Environment Variables

Create `.env` file in your backend directory:

```env
# STC Pay
STC_PAY_MERCHANT_ID=your_merchant_id
STC_PAY_API_KEY=your_api_key
STC_PAY_SECRET=your_secret

# Tamara
TAMARA_API_URL=https://api.tamara.co
TAMARA_API_TOKEN=your_api_token

# Tabby
TABBY_API_URL=https://api.tabby.ai
TABBY_PUBLIC_KEY=your_public_key
TABBY_SECRET_KEY=your_secret_key

# HyperPay (for cards)
HYPERPAY_ENTITY_ID=your_entity_id
HYPERPAY_ACCESS_TOKEN=your_access_token
HYPERPAY_TEST_MODE=true

# Google Pay
GOOGLE_PAY_MERCHANT_ID=your_merchant_id

# Apple Pay
APPLE_PAY_MERCHANT_ID=merchant.yourdomain.com
```

## Testing

### Test the Payment Demo

Visit `/payment-demo` to test all payment methods with mock data.

### Test Data

Use these test values:
- **Amount:** 100 SAR (within all method limits)
- **Phone:** +966501234567
- **Email:** test@example.com

### Webhook Testing

Use tools like ngrok to test webhooks locally:
```bash
ngrok http 8000
# Use the HTTPS URL for webhook endpoints
```

## Security Considerations

1. **HTTPS Only:** All payment operations must use HTTPS
2. **API Keys:** Store all API keys in environment variables
3. **Validation:** Validate all payment data on server-side
4. **Webhooks:** Verify webhook signatures from payment providers
5. **PCI Compliance:** Never store card details directly

## Error Handling

The system includes comprehensive error handling:

```javascript
try {
  const result = await paymentService.initializePayment(method.id, data);
  // Handle success
} catch (error) {
  if (error.message.includes('amount')) {
    // Handle amount validation error
  } else if (error.message.includes('currency')) {
    // Handle currency error
  } else {
    // Handle general error
  }
}
```

## Monitoring and Logs

- All payment attempts are logged in the database
- Failed payments include error details
- Transaction IDs can be traced across the entire flow
- Payment history is available through the API

## Support

For technical support with payment integrations:

1. Check the provider's documentation
2. Review error logs in the database
3. Test with the demo page
4. Contact the payment provider's technical support

Each payment provider has specific documentation and test environments that should be consulted during integration.