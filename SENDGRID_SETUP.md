# SendGrid Integration Setup

This document explains how to set up SendGrid for email verification, order confirmations, and contact form notifications.

## Environment Variables

Add the following environment variables to your server configuration:

```bash
# SendGrid Configuration
SENDGRID_API_KEY=your_sendgrid_api_key_here
SENDGRID_FROM_EMAIL=noreply@darzaid.com
SENDGRID_FROM_NAME=دار زيد

# Admin email for notifications
ADMIN_EMAIL=admin@darzaid.com
```

## Features Implemented

### 1. Email Verification for Signup
- **Endpoint**: `POST /api/signup/send-verification`
- **Purpose**: Send verification code to email before account creation
- **Flow**: 
  1. User enters email
  2. System sends 6-digit verification code
  3. User enters code + other details
  4. Account is created after verification

### 2. Order Confirmation Emails
- **Trigger**: Automatically sent when order is created
- **Recipients**: Customer email address
- **Content**: Order details, items, totals, order ID

### 3. Contact Form Notifications
- **Admin Notification**: Sent to admin email when contact form is submitted
- **Customer Confirmation**: Sent to customer confirming message receipt
- **Content**: Contact details and message content

## API Endpoints

### Signup Verification
```
POST /api/signup/send-verification
Body: { "email": "user@example.com" }

POST /api/signup/verify-and-signup
Body: { 
  "name": "User Name", 
  "email": "user@example.com", 
  "password": "password123", 
  "code": "123456" 
}
```

### Contact Form
```
POST /api/contact
Body: {
  "name": "User Name",
  "email": "user@example.com",
  "phone": "+966501234567",
  "subject": "Subject",
  "message": "Message content"
}
```

## Database Tables

The system automatically creates these tables:

### verification_codes
- Stores email verification codes
- Auto-expires after 10 minutes
- Prevents code reuse

### contact_messages
- Stores contact form submissions
- Includes all form fields

## Email Templates

All emails are sent in HTML format with:
- RTL (Arabic) support
- Responsive design
- Branded styling
- Fallback text versions

## Testing

To test the integration:

1. Set up SendGrid account and get API key
2. Configure environment variables
3. Test signup flow with email verification
4. Place a test order to verify confirmation emails
5. Submit contact form to test notifications

## Error Handling

- Email sending failures don't break core functionality
- Errors are logged for debugging
- Graceful fallbacks for missing configuration
