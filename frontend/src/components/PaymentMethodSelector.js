import React, { useState, useEffect } from 'react';
import { paymentService } from '../services/paymentService';
import '../styles/PaymentMethodSelector.css';

const PaymentMethodSelector = ({ 
  selectedMethod, 
  onMethodSelect, 
  amount, 
  currency = 'SAR',
  compact = false 
}) => {
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadMethods = async () => {
      try {
        await paymentService.loadPaymentMethods();
        const availableMethods = paymentService.getAvailablePaymentMethods(amount, currency);
        // Use only the new payment system; do not fallback locally
        setPaymentMethods(Array.isArray(availableMethods) ? availableMethods : []);
      } catch (error) {
        console.error('Error loading payment methods:', error);
        setPaymentMethods([]);
      } finally {
        setLoading(false);
      }
    };
    loadMethods();
  }, [amount, currency]);

  const getMethodIcon = (methodId) => {
    const icons = {
      'stc_pay': '🟢',
      'tamara': '🟡',
      'tabby': '🔵',
      'google_pay': '🔴',
      'apple_pay': '⚫',
      'paypal': '🔵',
      'urpay': '🟣',
      'benefit': '🟠',
      'visa': '💳',
      'mastercard': '💳',
      'amex': '💳',
      'unionpay': '💳',
      'mada': '💳',
      'sadad': '🏦',
      'fawry': '🏪',
      'bank_transfer': '🏦',
      'moyasar': '💳'
    };
    return icons[methodId] || '💳';
  };

  const getMethodCategory = (methodId) => {
    const categories = {
      'stc_pay': 'digital_wallet',
      'google_pay': 'digital_wallet',
      'apple_pay': 'digital_wallet',
      'paypal': 'digital_wallet',
      'urpay': 'digital_wallet',
      'benefit': 'digital_wallet',
      'tamara': 'bnpl',
      'tabby': 'bnpl',
      'visa': 'card',
      'mastercard': 'card',
      'amex': 'card',
      'unionpay': 'card',
      'mada': 'card',
      'sadad': 'bank_transfer',
      'fawry': 'bank_transfer',
      'bank_transfer': 'bank_transfer',
      'moyasar': 'processor'
    };
    return categories[methodId] || 'other';
  };

  const getCategoryName = (category) => {
    const names = {
      'digital_wallet': 'المحافظ الرقمية',
      'bnpl': 'اشتري الآن وادفع لاحقاً',
      'card': 'البطاقات البنكية',
      'bank_transfer': 'التحويل البنكي',
      'processor': 'معالجات الدفع',
      'other': 'أخرى'
    };
    return names[category] || 'أخرى';
  };

  const groupedMethods = paymentMethods.reduce((groups, method) => {
    const category = getMethodCategory(method.id);
    if (!groups[category]) {
      groups[category] = [];
    }
    groups[category].push(method);
    return groups;
  }, {});

  if (loading) {
    return (
      <div className="payment-method-selector loading">
        <div className="loading-spinner"></div>
        <span>جاري تحميل طرق الدفع...</span>
      </div>
    );
  }

  if (compact) {
    // Compact dropdown version for Cart page
    return (
      <div className="payment-method-selector compact">
        <label>طريقة الدفع</label>
        <select 
          value={selectedMethod?.id || ''} 
          onChange={(e) => {
            const method = paymentMethods.find(m => m.id === e.target.value);
            onMethodSelect(method);
          }}
          style={{ fontSize: '16px', minHeight: '44px' }}
        >
          <option value="">اختر طريقة الدفع</option>
          {Object.entries(groupedMethods).map(([category, methods]) => (
            <optgroup key={category} label={getCategoryName(category)}>
              {methods.map(method => (
                <option key={method.id} value={method.id}>
                  {getMethodIcon(method.id)} {method.nameAr}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
      </div>
    );
  }

  // Full visual selector version
  return (
    <div className="payment-method-selector">
      <label>طريقة الدفع</label>
      <div className="payment-methods-grid">
        {Object.entries(groupedMethods).map(([category, methods]) => (
          <div key={category} className="payment-category">
            <h4 className="category-title">{getCategoryName(category)}</h4>
            <div className="methods-grid">
              {methods.map(method => (
                <div
                  key={method.id}
                  className={`payment-method-option ${selectedMethod?.id === method.id ? 'selected' : ''}`}
                  onClick={() => onMethodSelect(method)}
                >
                  <div className="method-icon">
                    {getMethodIcon(method.id)}
                  </div>
                  <div className="method-info">
                    <span className="method-name">{method.nameAr}</span>
                    <span className="method-description">{method.description}</span>
                  </div>
                  {selectedMethod?.id === method.id && (
                    <div className="selected-indicator">✓</div>
                  )}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default PaymentMethodSelector;
