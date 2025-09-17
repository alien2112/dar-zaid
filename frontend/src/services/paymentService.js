import { apiService } from './api';

// Payment method configurations
export const PAYMENT_METHODS = {
  STC_PAY: {
    id: 'stc_pay',
    name: 'STC Pay',
    nameAr: 'إس تي سي باي',
    icon: '/images/payments/stc-pay.svg',
    type: 'digital_wallet',
    enabled: true,
    supportedCurrencies: ['SAR'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع بسهولة باستخدام STC Pay',
    processingTime: 'فوري'
  },
  TAMARA: {
    id: 'tamara',
    name: 'Tamara',
    nameAr: 'تمارا',
    icon: '/images/payments/tamara.svg',
    type: 'bnpl', // Buy Now Pay Later
    enabled: true,
    supportedCurrencies: ['SAR'],
    minAmount: 50,
    maxAmount: 5000,
    description: 'اشتري الآن وادفع لاحقاً على 3 دفعات',
    processingTime: 'فوري',
    installments: [3, 6]
  },
  TABBY: {
    id: 'tabby',
    name: 'Tabby',
    nameAr: 'تابي',
    icon: '/images/payments/tabby.svg',
    type: 'bnpl',
    enabled: true,
    supportedCurrencies: ['SAR'],
    minAmount: 30,
    maxAmount: 3000,
    description: 'اشتري الآن وادفع لاحقاً على 4 دفعات',
    processingTime: 'فوري',
    installments: [4]
  },
  GOOGLE_PAY: {
    id: 'google_pay',
    name: 'Google Pay',
    nameAr: 'جوجل باي',
    icon: '/images/payments/google-pay.svg',
    type: 'digital_wallet',
    enabled: true,
    supportedCurrencies: ['SAR', 'USD'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع بأمان باستخدام Google Pay',
    processingTime: 'فوري'
  },
  APPLE_PAY: {
    id: 'apple_pay',
    name: 'Apple Pay',
    nameAr: 'آبل باي',
    icon: '/images/payments/apple-pay.svg',
    type: 'digital_wallet',
    enabled: true,
    supportedCurrencies: ['SAR', 'USD'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع بسهولة باستخدام Apple Pay',
    processingTime: 'فوري'
  },
  BANK_TRANSFER: {
    id: 'bank_transfer',
    name: 'Bank Transfer',
    nameAr: 'تحويل بنكي',
    icon: '/images/payments/bank-transfer.svg',
    type: 'bank_transfer',
    enabled: true,
    supportedCurrencies: ['SAR'],
    minAmount: 10,
    maxAmount: 50000,
    description: 'تحويل مباشر من البنك الخاص بك',
    processingTime: '1-3 أيام عمل'
  },
  VISA: {
    id: 'visa',
    name: 'Visa',
    nameAr: 'فيزا',
    icon: '/images/payments/visa.svg',
    type: 'credit_debit',
    enabled: true,
    supportedCurrencies: ['SAR', 'USD', 'EUR'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع باستخدام بطاقة الفيزا',
    processingTime: 'فوري'
  },
  MASTERCARD: {
    id: 'mastercard',
    name: 'Mastercard',
    nameAr: 'ماستركارد',
    icon: '/images/payments/mastercard.svg',
    type: 'credit_debit',
    enabled: true,
    supportedCurrencies: ['SAR', 'USD', 'EUR'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع باستخدام بطاقة الماستركارد',
    processingTime: 'فوري'
  },
  MADA: {
    id: 'mada',
    name: 'mada',
    nameAr: 'مدى',
    icon: '/images/payments/mada.svg',
    type: 'debit_only',
    enabled: true,
    supportedCurrencies: ['SAR'],
    minAmount: 1,
    maxAmount: 10000,
    description: 'ادفع باستخدام بطاقة مدى السعودية',
    processingTime: 'فوري'
  }
};

// Payment categories for UI grouping
export const PAYMENT_CATEGORIES = {
  DIGITAL_WALLETS: {
    id: 'digital_wallets',
    name: 'المحافظ الرقمية',
    methods: ['stc_pay', 'google_pay', 'apple_pay']
  },
  BUY_NOW_PAY_LATER: {
    id: 'bnpl',
    name: 'اشتري الآن وادفع لاحقاً',
    methods: ['tamara', 'tabby']
  },
  CARDS: {
    id: 'cards',
    name: 'البطاقات البنكية',
    methods: ['visa', 'mastercard', 'mada']
  },
  BANK_TRANSFER: {
    id: 'bank_transfer',
    name: 'التحويل البنكي',
    methods: ['bank_transfer']
  }
};

class PaymentService {
  constructor() {
    this.config = {
      environment: process.env.NODE_ENV === 'production' ? 'production' : 'sandbox',
      currency: 'SAR',
      locale: 'ar-SA'
    };
  }

  // Get available payment methods for a given amount and currency
  getAvailablePaymentMethods(amount, currency = 'SAR') {
    return Object.values(PAYMENT_METHODS).filter(method => {
      return method.enabled &&
             method.supportedCurrencies.includes(currency) &&
             amount >= method.minAmount &&
             amount <= method.maxAmount;
    });
  }

  // Group payment methods by category
  getPaymentMethodsByCategory(amount, currency = 'SAR') {
    const availableMethods = this.getAvailablePaymentMethods(amount, currency);
    const categorized = {};

    Object.values(PAYMENT_CATEGORIES).forEach(category => {
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

  // Card payment methods (Visa, Mastercard, mada)
  async initiateCardPayment(paymentMethodId, orderData, cardDetails) {
    return this.initializePayment(paymentMethodId, {
      ...orderData,
      card_details: cardDetails,
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
    const method = PAYMENT_METHODS[paymentMethodId.toUpperCase()];
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
    const method = PAYMENT_METHODS[paymentMethodId.toUpperCase()];
    if (!method || method.type !== 'bnpl') {
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