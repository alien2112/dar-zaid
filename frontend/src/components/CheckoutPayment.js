import React, { useState, useEffect } from 'react';
import PaymentMethods from './PaymentMethods';
import { paymentService } from '../services/paymentService';
import { useCart } from '../contexts/CartContext';
import { apiService } from '../services/api';

const CheckoutPayment = ({ orderData, onPaymentSuccess, onPaymentError }) => {
  const { clearCart } = useCart();
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
  const [paymentStep, setPaymentStep] = useState('select'); // select, processing, completed, failed
  const [paymentResult, setPaymentResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Calculate total amount
  const totalAmount = orderData.items.reduce(
    (total, item) => total + (item.price * item.quantity), 0
  ) + (orderData.shipping_cost || 0) + (orderData.tax_amount || 0);

  const handleMethodSelect = (method) => {
    setSelectedPaymentMethod(method);
    setError(null);
  };

  const handlePaymentInitiate = async (method) => {
    if (!method) return;

    setLoading(true);
    setPaymentStep('processing');
    setError(null);

    try {
      // Validate payment data
      const paymentData = {
        amount: totalAmount,
        currency: orderData.currency || 'SAR',
        order_id: orderData.id || `order_${Date.now()}`,
        customer_info: orderData.customer_info,
        items: orderData.items,
        shipping_address: orderData.shipping_address,
        billing_address: orderData.billing_address || orderData.shipping_address
      };

      paymentService.validatePaymentData(method.id, paymentData);

      let paymentResponse;

      // Route to specific payment method handler
      switch (method.id) {
        case 'stc_pay':
          paymentResponse = await paymentService.initiateSTCPayment(paymentData);
          break;

        case 'tamara':
          paymentResponse = await paymentService.initiateTamaraPayment(paymentData);
          break;

        case 'tabby':
          paymentResponse = await paymentService.initiateTabbyPayment(paymentData);
          break;

        case 'google_pay':
          paymentResponse = await paymentService.initiateGooglePay(paymentData);
          break;

        case 'apple_pay':
          paymentResponse = await paymentService.initiateApplePay(paymentData);
          break;

        case 'bank_transfer':
          paymentResponse = await paymentService.initiateBankTransfer(paymentData);
          break;

        case 'visa':
        case 'mastercard':
        case 'mada':
          // For card payments, we might need additional card details form
          paymentResponse = await handleCardPayment(method, paymentData);
          break;

        default:
          throw new Error(`Payment method ${method.id} not implemented`);
      }

      handlePaymentResponse(paymentResponse, method);

    } catch (error) {
      console.error('Payment initialization error:', error);
      setError(error.message);
      setPaymentStep('failed');
      onPaymentError?.(error);
    } finally {
      setLoading(false);
    }
  };

  const handleCardPayment = async (method, paymentData) => {
    // This would typically open a secure card entry form
    // For now, we'll simulate the process
    return new Promise((resolve) => {
      // In real implementation, this would integrate with a secure payment processor
      setTimeout(() => {
        resolve({
          status: 'redirect',
          redirect_url: `/payment/card-form?method=${method.id}&order=${paymentData.order_id}`,
          transaction_id: `txn_${Date.now()}`
        });
      }, 1000);
    });
  };

  const handlePaymentResponse = (response, method) => {
    if (response.status === 'redirect') {
      // Redirect to payment provider
      window.location.href = response.redirect_url;
    } else if (response.status === 'completed') {
      // Payment completed immediately
      setPaymentResult(response);
      setPaymentStep('completed');
      clearCart();
      onPaymentSuccess?.(response);
    } else if (response.status === 'pending') {
      // Payment is pending (like bank transfer)
      setPaymentResult(response);
      setPaymentStep('pending');
    } else {
      throw new Error(response.message || 'Payment failed');
    }
  };

  const retryPayment = () => {
    setPaymentStep('select');
    setError(null);
    setPaymentResult(null);
  };

  const PaymentProcessing = () => (
    <div className="payment-processing">
      <div className="processing-animation">
        <div className="spinner"></div>
      </div>
      <h3>جاري معالجة الدفع...</h3>
      <p>يرجى عدم إغلاق هذه الصفحة أو الضغط على زر الرجوع</p>
      <div className="payment-method-info">
        <img src={selectedPaymentMethod?.icon} alt={selectedPaymentMethod?.nameAr} />
        <span>{selectedPaymentMethod?.nameAr}</span>
      </div>
    </div>
  );

  const PaymentCompleted = () => (
    <div className="payment-completed">
      <div className="success-icon">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
      </div>
      <h3>تم الدفع بنجاح!</h3>
      <p>شكراً لك على طلبك. سيتم إرسال تفاصيل الطلب إلى بريدك الإلكتروني.</p>
      <div className="payment-details">
        <div className="detail-row">
          <span>رقم العملية:</span>
          <span>{paymentResult?.transaction_id}</span>
        </div>
        <div className="detail-row">
          <span>المبلغ المدفوع:</span>
          <span>{paymentService.formatAmount(totalAmount, orderData.currency || 'SAR')}</span>
        </div>
        <div className="detail-row">
          <span>طريقة الدفع:</span>
          <span>{selectedPaymentMethod?.nameAr}</span>
        </div>
      </div>
    </div>
  );

  const PaymentPending = () => (
    <div className="payment-pending">
      <div className="pending-icon">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
      </div>
      <h3>الدفع في الانتظار</h3>
      <p>تم إنشاء طلب الدفع بنجاح. يرجى إكمال عملية الدفع.</p>
      {selectedPaymentMethod?.id === 'bank_transfer' && paymentResult?.bank_details && (
        <div className="bank-transfer-details">
          <h4>تفاصيل التحويل البنكي</h4>
          <div className="bank-info">
            <div className="detail-row">
              <span>اسم البنك:</span>
              <span>{paymentResult.bank_details.bank_name}</span>
            </div>
            <div className="detail-row">
              <span>رقم الحساب:</span>
              <span>{paymentResult.bank_details.account_number}</span>
            </div>
            <div className="detail-row">
              <span>IBAN:</span>
              <span>{paymentResult.bank_details.iban}</span>
            </div>
            <div className="detail-row">
              <span>المبلغ:</span>
              <span>{paymentService.formatAmount(totalAmount, orderData.currency || 'SAR')}</span>
            </div>
            <div className="detail-row">
              <span>رقم المرجع:</span>
              <span>{paymentResult.reference_number}</span>
            </div>
          </div>
          <p className="bank-transfer-note">
            يرجى إضافة رقم المرجع في تفاصيل التحويل لسرعة معالجة الطلب.
          </p>
        </div>
      )}
    </div>
  );

  const PaymentFailed = () => (
    <div className="payment-failed">
      <div className="error-icon">
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
        </svg>
      </div>
      <h3>فشل في معالجة الدفع</h3>
      <p>{error || 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.'}</p>
      <div className="payment-failed-actions">
        <button className="btn btn-primary" onClick={retryPayment}>
          إعادة المحاولة
        </button>
        <button className="btn btn-secondary" onClick={() => setPaymentStep('select')}>
          تغيير طريقة الدفع
        </button>
      </div>
    </div>
  );

  const OrderSummary = () => (
    <div className="order-summary">
      <h3>ملخص الطلب</h3>
      <div className="order-items">
        {orderData.items.map(item => (
          <div key={item.id} className="order-item">
            <span>{item.title}</span>
            <span>{item.quantity}x</span>
            <span>{paymentService.formatAmount(item.price * item.quantity, orderData.currency || 'SAR')}</span>
          </div>
        ))}
      </div>
      <div className="order-totals">
        {orderData.shipping_cost > 0 && (
          <div className="total-row">
            <span>الشحن:</span>
            <span>{paymentService.formatAmount(orderData.shipping_cost, orderData.currency || 'SAR')}</span>
          </div>
        )}
        {orderData.tax_amount > 0 && (
          <div className="total-row">
            <span>الضريبة:</span>
            <span>{paymentService.formatAmount(orderData.tax_amount, orderData.currency || 'SAR')}</span>
          </div>
        )}
        <div className="total-row total">
          <span>الإجمالي:</span>
          <span>{paymentService.formatAmount(totalAmount, orderData.currency || 'SAR')}</span>
        </div>
      </div>
    </div>
  );

  return (
    <div className="checkout-payment">
      <div className="payment-content">
        {paymentStep === 'select' && (
          <>
            <h2>اختر طريقة الدفع</h2>
            <PaymentMethods
              amount={totalAmount}
              currency={orderData.currency || 'SAR'}
              selectedMethod={selectedPaymentMethod}
              onMethodSelect={handleMethodSelect}
              onPaymentInitiate={handlePaymentInitiate}
              disabled={loading}
            />
          </>
        )}

        {paymentStep === 'processing' && <PaymentProcessing />}
        {paymentStep === 'completed' && <PaymentCompleted />}
        {paymentStep === 'pending' && <PaymentPending />}
        {paymentStep === 'failed' && <PaymentFailed />}
      </div>

      <div className="payment-sidebar">
        <OrderSummary />
      </div>
    </div>
  );
};

export default CheckoutPayment;