import React, { useState, useEffect } from 'react';
import { paymentService, PAYMENT_METHODS } from '../services/paymentService';

const PaymentMethods = ({
  amount,
  currency = 'SAR',
  selectedMethod,
  onMethodSelect,
  onPaymentInitiate,
  disabled = false
}) => {
  const [paymentCategories, setPaymentCategories] = useState({});
  const [loading, setLoading] = useState(false);
  const [expandedCategory, setExpandedCategory] = useState(null);

  useEffect(() => {
    const categories = paymentService.getPaymentMethodsByCategory(amount, currency);
    setPaymentCategories(categories);

    // Auto-expand first category with available methods
    const firstCategoryWithMethods = Object.keys(categories).find(
      categoryId => categories[categoryId].methods.length > 0
    );
    if (firstCategoryWithMethods) {
      setExpandedCategory(firstCategoryWithMethods);
    }
  }, [amount, currency]);

  const handleMethodSelect = (method) => {
    if (disabled) return;
    onMethodSelect(method);
  };

  const handlePayment = async (method) => {
    if (disabled || loading) return;

    setLoading(true);
    try {
      await onPaymentInitiate(method);
    } catch (error) {
      console.error('Payment initiation error:', error);
    } finally {
      setLoading(false);
    }
  };

  const toggleCategory = (categoryId) => {
    setExpandedCategory(expandedCategory === categoryId ? null : categoryId);
  };

  const PaymentMethodCard = ({ method, isSelected }) => (
    <div
      className={`payment-method-card ${isSelected ? 'selected' : ''} ${disabled ? 'disabled' : ''}`}
      onClick={() => handleMethodSelect(method)}
    >
      <div className="payment-method-icon">
        <img
          src={method.icon}
          alt={method.nameAr}
          onError={(e) => {
            e.target.src = '/images/payments/default.svg';
          }}
        />
      </div>
      <div className="payment-method-info">
        <h4>{method.nameAr}</h4>
        <p>{method.description}</p>
        <span className="processing-time">{method.processingTime}</span>
        {method.type === 'bnpl' && (
          <div className="installment-info">
            {method.installments.map(installments => (
              <span key={installments} className="installment-badge">
                {installments} دفعات
              </span>
            ))}
          </div>
        )}
      </div>
      {isSelected && (
        <div className="selected-indicator">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
          </svg>
        </div>
      )}
    </div>
  );

  const InstallmentOptions = ({ method }) => {
    if (method.type !== 'bnpl') return null;

    const options = paymentService.getInstallmentOptions(method.id, amount);

    return (
      <div className="installment-options">
        <h4>خيارات التقسيط</h4>
        {options.map(option => (
          <div key={option.count} className="installment-option">
            <span className="installment-count">{option.count} دفعات</span>
            <span className="installment-amount">
              {paymentService.formatAmount(option.amount_per_installment, currency)} لكل دفعة
            </span>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="payment-methods">
      <div className="payment-amount-display">
        <h3>المبلغ الإجمالي: {paymentService.formatAmount(amount, currency)}</h3>
      </div>

      {Object.entries(paymentCategories).map(([categoryId, category]) => {
        if (category.methods.length === 0) return null;

        const isExpanded = expandedCategory === categoryId;

        return (
          <div key={categoryId} className="payment-category">
            <button
              className="payment-category-header"
              onClick={() => toggleCategory(categoryId)}
            >
              <span>{category.name}</span>
              <span className={`category-arrow ${isExpanded ? 'expanded' : ''}`}>
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M7 10l5 5 5-5z"/>
                </svg>
              </span>
            </button>

            {isExpanded && (
              <div className="payment-category-content">
                <div className="payment-methods-grid">
                  {category.methods.map(method => (
                    <PaymentMethodCard
                      key={method.id}
                      method={method}
                      isSelected={selectedMethod?.id === method.id}
                    />
                  ))}
                </div>

                {selectedMethod &&
                 category.methods.some(m => m.id === selectedMethod.id) && (
                  <div className="selected-method-details">
                    <InstallmentOptions method={selectedMethod} />

                    <button
                      className="btn btn-primary payment-proceed-btn"
                      onClick={() => handlePayment(selectedMethod)}
                      disabled={loading || disabled}
                    >
                      {loading ? (
                        <>
                          <div className="spinner-small"></div>
                          جاري المعالجة...
                        </>
                      ) : (
                        `الدفع باستخدام ${selectedMethod.nameAr}`
                      )}
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        );
      })}

      {Object.values(paymentCategories).every(cat => cat.methods.length === 0) && (
        <div className="no-payment-methods">
          <p>لا توجد طرق دفع متاحة للمبلغ المحدد</p>
        </div>
      )}
    </div>
  );
};

export default PaymentMethods;