import { apiService } from './api';

class PaymentService {
  constructor() {
    this.config = {
      environment: process.env.NODE_ENV === 'production' ? 'production' : 'sandbox',
      currency: 'SAR',
      locale: 'ar-SA'
    };
    this.paymentMethods = [];
    this.paymentCategories = {
      DIGITAL_WALLETS: {
        id: 'digital_wallets',
        name: 'المحافظ الرقمية',
        methods: ['stc_pay', 'google_pay', 'apple_pay', 'paypal', 'urpay', 'benefit']
      },
      BUY_NOW_PAY_LATER: {
        id: 'bnpl',
        name: 'اشتري الآن وادفع لاحقاً',
        methods: ['tamara', 'tabby']
      },
      CARDS: {
        id: 'cards',
        name: 'البطاقات البنكية',
        methods: ['visa', 'mastercard', 'mada', 'amex', 'unionpay']
      },
      BANK_TRANSFER: {
        id: 'bank_transfer',
        name: 'التحويل البنكي',
        methods: ['bank_transfer', 'sadad', 'fawry']
      }
    };
  }

  // Load payment methods from backend
  async loadPaymentMethods() {
    try {
      const response = await apiService.getPaymentMethods(true);
      // Normalize across possible backends: { payment_methods: [...] } or array directly
      const raw = response?.data;
      this.paymentMethods = Array.isArray(raw)
        ? raw
        : (raw?.payment_methods || raw?.methods || []);
      return this.paymentMethods;
    } catch (error) {
      console.error('Error loading payment methods:', error);
      this.paymentMethods = [];
      return [];
    }
  }

  // Get available payment methods for a given amount and currency
  getAvailablePaymentMethods(amount, currency = 'SAR') {
    if (this.paymentMethods.length === 0) {
      return [];
    }

    const isAmountProvided = typeof amount === 'number' && !Number.isNaN(amount) && amount > 0;

    return this.paymentMethods.filter(method => {
      const baseMatch = method.enabled && method.supportedCurrencies.includes(currency);
      if (!isAmountProvided) {
        // When amount is not yet known, return all enabled methods for the currency
        return baseMatch;
      }
      return (
        baseMatch &&
        amount >= method.minAmount &&
        amount <= method.maxAmount
      );
    });
  }

  // Group payment methods by category
  getPaymentMethodsByCategory(amount, currency = 'SAR') {
    const availableMethods = this.getAvailablePaymentMethods(amount, currency);
    const categorized = {};

    Object.values(this.paymentCategories).forEach(category => {
      categorized[category.id] = {
        ...category,
        methods: category.methods
          .map(methodId => availableMethods.find(m => m.id === methodId))
          .filter(Boolean)
      };
    });

    return categorized;
  }

  // Initialize payment for specific method
  async initializePayment(paymentMethodId, paymentData) {
    try {
      const response = await apiService.initializePayment({
        payment_method: paymentMethodId,
        ...paymentData
      });
      return response.data;
    } catch (error) {
      throw new Error(`Payment initialization failed: ${error.message}`);
    }
  }

  // Process payment
  async processPayment(paymentMethodId, paymentData) {
    try {
      const response = await apiService.processPayment({
        payment_method: paymentMethodId,
        ...paymentData
      });
      return response.data;
    } catch (error) {
      throw new Error(`Payment processing failed: ${error.message}`);
    }
  }

  // Verify payment status
  async verifyPayment(transactionId) {
    try {
      const response = await apiService.verifyPayment(transactionId);
      return response.data;
    } catch (error) {
      throw new Error(`Payment verification failed: ${error.message}`);
    }
  }

  // Handle payment callback
  async handlePaymentCallback(callbackData) {
    try {
      const response = await apiService.handlePaymentCallback(callbackData);
      return response.data;
    } catch (error) {
      throw new Error(`Payment callback handling failed: ${error.message}`);
    }
  }

  // Refund payment
  async refundPayment(transactionId, amount, reason) {
    try {
      const response = await apiService.refundPayment({
        transaction_id: transactionId,
        amount,
        reason
      });
      return response.data;
    } catch (error) {
      throw new Error(`Payment refund failed: ${error.message}`);
    }
  }

  // Get payment history
  async getPaymentHistory(userId, filters = {}) {
    try {
      const response = await apiService.getPaymentHistory({
        user_id: userId,
        ...filters
      });
      return response.data;
    } catch (error) {
      throw new Error(`Failed to fetch payment history: ${error.message}`);
    }
  }

  // STC Pay specific methods
  async initiateSTCPayment(orderData) {
    return this.initializePayment('stc_pay', {
      ...orderData,
      redirect_url: `${window.location.origin}/payment/callback/stc`,
      cancel_url: `${window.location.origin}/payment/cancel`
    });
  }

  // Tamara specific methods
  async initiateTamaraPayment(orderData, installments = 3) {
    return this.initializePayment('tamara', {
      ...orderData,
      installments,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`,
      cancel_url: `${window.location.origin}/payment/cancel`
    });
  }

  // Tabby specific methods
  async initiateTabbyPayment(orderData) {
    return this.initializePayment('tabby', {
      ...orderData,
      installments: 4,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`,
      cancel_url: `${window.location.origin}/payment/cancel`
    });
  }

  // Google Pay specific methods
  async initiateGooglePay(orderData) {
    return this.initializePayment('google_pay', {
      ...orderData,
      merchant_id: process.env.REACT_APP_GOOGLE_PAY_MERCHANT_ID,
      environment: this.config.environment
    });
  }

  // Apple Pay specific methods
  async initiateApplePay(orderData) {
    return this.initializePayment('apple_pay', {
      ...orderData,
      merchant_id: process.env.REACT_APP_APPLE_PAY_MERCHANT_ID,
      country_code: 'SA'
    });
  }

  // Bank Transfer specific methods
  async initiateBankTransfer(orderData) {
    return this.initializePayment('bank_transfer', {
      ...orderData,
      callback_url: `${window.location.origin}/payment/callback/bank-transfer`
    });
  }

  // Card payment methods (Visa, Mastercard, mada, Amex, UnionPay)
  async initiateCardPayment(paymentMethodId, orderData, cardDetails) {
    return this.initializePayment(paymentMethodId, {
      ...orderData,
      card_details: cardDetails,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`
    });
  }

  // PayPal specific methods
  async initiatePayPalPayment(orderData) {
    return this.initializePayment('paypal', {
      ...orderData,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`,
      cancel_url: `${window.location.origin}/payment/cancel`
    });
  }

  // Sadad specific methods
  async initiateSadadPayment(orderData) {
    return this.initializePayment('sadad', {
      ...orderData,
      callback_url: `${window.location.origin}/payment/callback/sadad`
    });
  }

  // Fawry specific methods
  async initiateFawryPayment(orderData) {
    return this.initializePayment('fawry', {
      ...orderData,
      callback_url: `${window.location.origin}/payment/callback/fawry`
    });
  }

  // UrPay specific methods
  async initiateUrPayPayment(orderData) {
    return this.initializePayment('urpay', {
      ...orderData,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`
    });
  }

  // Benefit specific methods
  async initiateBenefitPayment(orderData) {
    return this.initializePayment('benefit', {
      ...orderData,
      success_url: `${window.location.origin}/payment/success`,
      failure_url: `${window.location.origin}/payment/failure`
    });
  }

  // Utility methods
  formatAmount(amount, currency = 'SAR') {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2
    }).format(amount);
  }

  validatePaymentData(paymentMethodId, paymentData) {
    const method = this.paymentMethods.find(m => m.id === paymentMethodId);
    if (!method) {
      throw new Error('Invalid payment method');
    }

    if (paymentData.amount < method.minAmount || paymentData.amount > method.maxAmount) {
      throw new Error(`Amount must be between ${method.minAmount} and ${method.maxAmount} ${paymentData.currency}`);
    }

    if (!method.supportedCurrencies.includes(paymentData.currency)) {
      throw new Error(`Currency ${paymentData.currency} not supported for ${method.name}`);
    }

    return true;
  }

  // Get installment options for BNPL methods
  getInstallmentOptions(paymentMethodId, amount) {
    const method = this.paymentMethods.find(m => m.id === paymentMethodId);
    if (!method || method.type !== 'bnpl' || !method.installments) {
      return [];
    }

    return method.installments.map(count => ({
      count,
      amount_per_installment: amount / count,
      total_amount: amount,
      fees: 0 // Usually BNPL services don't charge fees to customers
    }));
  }
}

export const paymentService = new PaymentService();
export default paymentService;