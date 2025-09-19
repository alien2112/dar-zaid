# Gmail Email System Setup Guide for Dar Zaid

This guide explains how to set up and use the Gmail-based email system for the Dar Zaid website.

## 📧 Email Features

The system provides comprehensive email functionality:

### 🔐 Authentication & Verification
- **Signup Verification**: Send verification codes during user registration
- **Login Verification**: 2FA codes for secure login
- **Password Reset**: Secure password reset tokens
- **Welcome Emails**: Greeting new users after successful signup

### 📦 Order Management
- **Order Confirmations**: Detailed order receipts sent to customers
- **Order Status Updates**: Notifications when order status changes
- **Payment Confirmations**: Email receipts for successful payments

### 📬 Contact & Communication
- **Contact Form**: Admin notifications for new contact messages
- **Customer Confirmations**: Auto-replies to contact form submissions
- **General Notifications**: System-wide email communications

### ⚡ Advanced Features
- **Rate Limiting**: Prevents email spam and abuse
- **Email Queue**: High-volume email processing (optional)
- **Template System**: Beautiful, responsive email templates
- **Error Handling**: Robust error management and logging

## 🛠️ Setup Instructions

### Step 1: Gmail App Password Setup

1. **Enable 2-Factor Authentication** on the Gmail account `Dar.zaid.2022@gmail.com`
   - Go to [Google Account Security](https://myaccount.google.com/security)
   - Enable 2-Step Verification if not already enabled

2. **Generate App Password**
   - Visit [App Passwords](https://myaccount.google.com/apppasswords)
   - Select "Mail" as the app
   - Select "Other" as the device and enter "Dar Zaid Website"
   - Copy the generated 16-character password

3. **Set Environment Variable**
   ```bash
   # On Windows (Command Prompt)
   set GMAIL_APP_PASSWORD=your-16-character-app-password

   # On Windows (PowerShell)
   $env:GMAIL_APP_PASSWORD="your-16-character-app-password"

   # On Linux/Mac
   export GMAIL_APP_PASSWORD="your-16-character-app-password"
   ```

   Or add to your web server configuration:
   ```apache
   # In .htaccess or virtual host
   SetEnv GMAIL_APP_PASSWORD "your-16-character-app-password"
   ```

### Step 2: Test Email Functionality

Run the test script to verify everything is working:

```bash
cd backend
php test_gmail_service.php
```

To send a test email:
```bash
php test_gmail_service.php test-send your-email@example.com
```

### Step 3: Production Configuration

For production, consider setting additional environment variables:

```bash
# Optional configurations
EMAIL_DEBUG=false                    # Set to true for debugging
EMAIL_TEST_MODE=false               # Set to true for testing
EMAIL_TEST_RECIPIENT=test@example.com # Test recipient email
```

## 📝 API Endpoints

### Signup with Email Verification

**Send Verification Code:**
```javascript
POST /api/signup/send-verification
{
  "email": "user@example.com"
}
```

**Verify Code and Complete Signup:**
```javascript
POST /api/signup/verify-and-signup
{
  "name": "أحمد محمد",
  "email": "user@example.com",
  "password": "password123",
  "code": "123456"
}
```

### Contact Form with Email Notifications

```javascript
POST /api/contact
{
  "name": "أحمد محمد",
  "email": "user@example.com",
  "phone": "+966501234567",
  "subject": "استفسار عن الكتب",
  "message": "مرحباً، أريد معرفة المزيد عن الكتب المتوفرة"
}
```

## 🎨 Email Templates

The system includes professionally designed email templates:

### Verification Code Email
- Clean, modern design
- Large, easy-to-read verification code
- Arabic language support
- Mobile-responsive layout

### Welcome Email
- Warm greeting for new users
- Feature highlights
- Call-to-action buttons
- Branded design

### Order Confirmation
- Detailed order summary
- Itemized pricing
- Shipping information
- Professional layout

### Contact Form Emails
- Admin notification with all details
- Customer confirmation with message summary
- Professional formatting

## 🔧 Customization

### Modifying Email Templates

Email templates are built into the `GmailService` class. To customize:

1. Edit the template methods in `backend/services/gmail_service.php`
2. Modify HTML/CSS in the template strings
3. Test changes using the test script

### Adding New Email Types

1. Add new method to `GmailService` class
2. Create template method for the email
3. Add email subject to `EmailConfig::getSubjects()`
4. Update API endpoints to use new email type

### Rate Limiting Configuration

Modify limits in `backend/config/email_config.php`:

```php
const MAX_EMAILS_PER_HOUR = 20;  // Emails per hour per email address
const MAX_EMAILS_PER_DAY = 100;  // Emails per day per email address
```

## 🚨 Troubleshooting

### Common Issues

**1. "Gmail app password not configured"**
- Ensure `GMAIL_APP_PASSWORD` environment variable is set
- Verify the app password is correct (16 characters)

**2. "SMTP authentication failed"**
- Check if 2FA is enabled on Gmail account
- Regenerate app password if needed
- Verify the Gmail account `Dar.zaid.2022@gmail.com` is accessible

**3. "Rate limit exceeded"**
- User has reached email sending limits
- Check rate limit tables in database
- Adjust limits in email configuration

**4. "Template rendering errors"**
- Check for syntax errors in template HTML
- Verify all variables are properly escaped
- Test templates with the test script

### Debugging

Enable debug mode:
```bash
export EMAIL_DEBUG=true
```

Check server error logs for detailed error messages.

## 📊 Monitoring

### Database Tables

The system creates these tables automatically:

- `verification_codes`: Stores verification codes with expiration
- `email_rate_limits`: Tracks email sending limits
- `email_queue`: Queue for high-volume email processing (optional)
- `contact_messages`: Stores contact form submissions

### Email Logs

Monitor email activity through:
- Server error logs (failed sends)
- Rate limit tables (usage patterns)
- Verification code tables (code usage)

## 🔒 Security Features

- **Rate Limiting**: Prevents email bombing attacks
- **Code Expiration**: Verification codes expire after 10 minutes
- **One-time Use**: Verification codes can only be used once
- **Input Validation**: All email content is sanitized
- **SMTP Security**: Uses TLS encryption for email transmission

## 🚀 Production Recommendations

1. **Monitor Email Volume**: Set up alerts for high email volumes
2. **Regular Cleanup**: Implement scheduled cleanup of expired codes
3. **Backup Configuration**: Keep environment variables backed up securely
4. **Log Monitoring**: Monitor error logs for email failures
5. **Rate Limit Tuning**: Adjust limits based on usage patterns

## 📞 Support

For issues with the email system:

1. Run the test script first: `php test_gmail_service.php`
2. Check server error logs
3. Verify Gmail account status and app password
4. Test with a different email address

The email system is now ready to handle all communication needs for the Dar Zaid website!