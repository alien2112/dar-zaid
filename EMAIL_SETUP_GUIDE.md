# 📧 Complete Email Setup Guide for Dar Zaid

Since Gmail App Passwords are not available in your region, this guide provides **multiple email solutions** that work globally.

## 🚀 **Option 1: SMTP2GO (Recommended)**

**Best choice for your region** - Works everywhere and is free for up to 1,000 emails per month.

### Step 1: Create SMTP2GO Account
1. Go to [smtp2go.com](https://www.smtp2go.com/)
2. Click "Sign Up Free"
3. Enter your details and verify your email
4. No credit card required for free plan

### Step 2: Get API Key
1. Login to SMTP2GO dashboard
2. Go to **Settings** → **API Keys**
3. Click **Create API Key**
4. Copy the generated API key

### Step 3: Configure Environment
Set the environment variable:

```bash
# Windows Command Prompt
set SMTP2GO_API_KEY=your-api-key-here

# Windows PowerShell
$env:SMTP2GO_API_KEY="your-api-key-here"

# Linux/Mac
export SMTP2GO_API_KEY="your-api-key-here"
```

Or create a `.env` file:
```env
SMTP2GO_API_KEY=your-api-key-here
```

### Step 4: Verify Domain (Optional but Recommended)
1. In SMTP2GO dashboard, go to **Settings** → **Sender Domains**
2. Add `gmail.com` or your custom domain
3. Follow verification instructions
4. This improves email deliverability

---

## 🔄 **Option 2: PHP Mail (Fallback)**

If no other service is configured, the system will use PHP's built-in mail function.

**Note:** This method has lower reliability and may not work on all servers.

### Configuration
No additional setup required - it works automatically as a fallback.

---

## 🧪 **Testing Your Email Setup**

### Test Script
Run the test script to check your configuration:

```bash
cd backend
php test_universal_email.php
```

### Send Test Email
```bash
php test_universal_email.php test-send your-email@example.com
```

---

## 📋 **Email Features Available**

### 🔐 **Authentication & Verification**
- **Signup Verification**: Beautiful verification codes for new users
- **Login Verification**: 2FA support for secure login
- **Password Reset**: Secure password reset functionality
- **Welcome Emails**: Professional welcome messages

### 📦 **Order Management**
- **Order Confirmations**: Detailed receipts with invoice styling
- **Status Updates**: Real-time order status notifications
- **Payment Confirmations**: Transaction confirmations

### 📬 **Communication**
- **Contact Forms**: Admin notifications + customer confirmations
- **Rate Limiting**: Anti-spam protection
- **Professional Templates**: Beautiful, mobile-responsive designs

---

## 🛠 **API Usage Examples**

### Signup with Email Verification

**Send Verification Code:**
```javascript
POST /api/signup/send-verification
{
  "email": "user@example.com"
}
```

**Complete Signup:**
```javascript
POST /api/signup/verify-and-signup
{
  "name": "أحمد محمد",
  "email": "user@example.com",
  "password": "password123",
  "code": "123456"
}
```

### Contact Form
```javascript
POST /api/contact
{
  "name": "أحمد محمد",
  "email": "user@example.com",
  "subject": "استفسار",
  "message": "مرحباً..."
}
```

---

## 🎨 **Email Templates**

All email templates are professionally designed with:
- **Arabic language support**
- **Mobile-responsive design**
- **Company branding**
- **Modern gradients and styling**
- **Clear call-to-action buttons**

### Template Types:
1. **Verification Codes** - Clean, secure code display
2. **Welcome Emails** - Feature highlights and warm greeting
3. **Order Confirmations** - Invoice-style receipts
4. **Contact Forms** - Professional notifications
5. **Password Reset** - Secure reset links

---

## 🔧 **Advanced Configuration**

### Rate Limiting
Prevent email spam by configuring limits in `backend/config/email_config.php`:

```php
const MAX_EMAILS_PER_HOUR = 20;  // Per email address
const MAX_EMAILS_PER_DAY = 100;   // Per email address
```

### Multiple Providers
The system automatically chooses the best available provider:

1. **SMTP2GO** (if API key is set) - Recommended
2. **Gmail SMTP** (if app password is set) - For supported regions
3. **PHP Mail** (fallback) - Basic functionality

---

## 🚨 **Troubleshooting**

### "No email provider configured"
- Set `SMTP2GO_API_KEY` environment variable
- Verify your API key is correct
- Check SMTP2GO account status

### "Rate limit exceeded"
- User has sent too many emails
- Check rate limit settings
- Wait before trying again

### "Email sending failed"
- Check internet connection
- Verify SMTP2GO account status
- Check error logs for details

### Templates not rendering
- Check for PHP syntax errors
- Verify template methods exist
- Run test script for validation

---

## 📊 **Monitoring & Analytics**

### SMTP2GO Dashboard
Monitor your email activity:
- Delivery rates
- Bounce rates
- Email volume
- Performance metrics

### Database Tables
The system creates these tables automatically:
- `verification_codes` - Email verification codes
- `email_rate_limits` - Rate limiting data
- `contact_messages` - Contact form submissions

---

## 💡 **Best Practices**

### For Production:
1. **Use SMTP2GO** for best reliability
2. **Verify your domain** to improve deliverability
3. **Monitor email volume** to stay within limits
4. **Set up rate limiting** to prevent abuse
5. **Regular cleanup** of expired verification codes

### For Development:
1. **Use test mode** to avoid sending real emails
2. **Test all email templates** before deployment
3. **Verify database setup** is working correctly

---

## 📞 **Support**

### Getting Help:
1. **Run test script** first: `php test_universal_email.php`
2. **Check error logs** for detailed messages
3. **Verify environment variables** are set correctly
4. **Test with different email addresses**

### SMTP2GO Support:
- Documentation: [smtp2go.com/docs](https://www.smtp2go.com/docs)
- Support: Available through their dashboard
- Free plan includes basic support

---

## ✅ **Quick Setup Checklist**

- [ ] Create SMTP2GO account (free)
- [ ] Get API key from dashboard
- [ ] Set `SMTP2GO_API_KEY` environment variable
- [ ] Run test script: `php test_universal_email.php`
- [ ] Send test email to verify setup
- [ ] (Optional) Verify domain for better deliverability

**Your email system is now ready to handle all communication for the Dar Zaid website!** 🎉

The system will automatically use the best available email provider and includes professional templates, rate limiting, and comprehensive error handling.