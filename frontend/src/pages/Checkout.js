import React, { useState, useEffect } from 'react';
import { useCart } from '../contexts/CartContext';
import { useNavigate } from 'react-router-dom';
import CheckoutPayment from '../components/CheckoutPayment';

const Checkout = () => {
  const { cart, clearCart } = useCart();
  const navigate = useNavigate();
  const [step, setStep] = useState('details'); // details, payment, success
  const [orderData, setOrderData] = useState(null);
  const [customerInfo, setCustomerInfo] = useState({
    name: '',
    email: '',
    phone: '',
    notes: ''
  });
  const [shippingAddress, setShippingAddress] = useState({
    address: '',
    city: '',
    region: '',
    postal_code: '',
    country: 'Saudi Arabia'
  });

  // Redirect to cart if empty
  useEffect(() => {
    if (cart.length === 0 && step === 'details') {
      navigate('/cart');
    }
  }, [cart, step, navigate]);

  const calculateTotals = () => {
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const shipping_cost = subtotal > 200 ? 0 : 25; // Free shipping over 200 SAR
    const tax_rate = 0.15; // 15% VAT
    const tax_amount = subtotal * tax_rate;
    const total = subtotal + shipping_cost + tax_amount;

    return {
      subtotal,
      shipping_cost,
      tax_amount,
      total
    };
  };

  const handleCustomerInfoSubmit = (e) => {
    e.preventDefault();

    // Validate required fields
    if (!customerInfo.name || !customerInfo.email || !customerInfo.phone) {
      alert('يرجى ملء جميع الحقول المطلوبة');
      return;
    }

    if (!shippingAddress.address || !shippingAddress.city) {
      alert('يرجى ملء عنوان الشحن');
      return;
    }

    // Create order data
    const totals = calculateTotals();
    const order = {
      id: `order_${Date.now()}`,
      items: cart.map(item => ({
        id: item.id,
        title: item.title,
        price: item.price,
        quantity: item.quantity
      })),
      customer_info: customerInfo,
      shipping_address: shippingAddress,
      billing_address: shippingAddress, // Same as shipping for now
      subtotal: totals.subtotal,
      shipping_cost: totals.shipping_cost,
      tax_amount: totals.tax_amount,
      total_amount: totals.total,
      currency: 'SAR'
    };

    setOrderData(order);
    setStep('payment');
  };

  const handlePaymentSuccess = (paymentResult) => {
    console.log('Payment successful:', paymentResult);
    setStep('success');
    // Clear cart after successful payment
    setTimeout(() => {
      clearCart();
    }, 2000);
  };

  const handlePaymentError = (error) => {
    console.error('Payment error:', error);
    // Handle payment error - maybe show error message or go back to payment selection
  };

  if (cart.length === 0 && step !== 'success') {
    return (
      <div className="container">
        <div className="checkout-empty">
          <h2>السلة فارغة</h2>
          <p>يرجى إضافة بعض الكتب إلى السلة أولاً</p>
          <button className="btn btn-primary" onClick={() => navigate('/bookstore')}>
            تصفح الكتب
          </button>
        </div>
      </div>
    );
  }

  const totals = calculateTotals();

  const CustomerDetailsForm = () => (
    <div className="checkout-form">
      <h2>معلومات العميل والشحن</h2>

      <form onSubmit={handleCustomerInfoSubmit}>
        <div className="form-section">
          <h3>معلومات العميل</h3>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="name">الاسم الكامل *</label>
              <input
                type="text"
                id="name"
                value={customerInfo.name}
                onChange={(e) => setCustomerInfo({ ...customerInfo, name: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="email">البريد الإلكتروني *</label>
              <input
                type="email"
                id="email"
                value={customerInfo.email}
                onChange={(e) => setCustomerInfo({ ...customerInfo, email: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="phone">رقم الهاتف *</label>
              <input
                type="tel"
                id="phone"
                value={customerInfo.phone}
                onChange={(e) => setCustomerInfo({ ...customerInfo, phone: e.target.value })}
                required
              />
            </div>
            <div className="form-group" style={{ gridColumn: '1 / -1' }}>
              <label htmlFor="notes">ملاحظات (اختياري)</label>
              <textarea
                id="notes"
                rows="3"
                value={customerInfo.notes}
                onChange={(e) => setCustomerInfo({ ...customerInfo, notes: e.target.value })}
                placeholder="أي ملاحظات خاصة بالطلب..."
              />
            </div>
          </div>
        </div>

        <div className="form-section">
          <h3>عنوان الشحن</h3>
          <div className="form-grid">
            <div className="form-group" style={{ gridColumn: '1 / -1' }}>
              <label htmlFor="address">العنوان *</label>
              <input
                type="text"
                id="address"
                value={shippingAddress.address}
                onChange={(e) => setShippingAddress({ ...shippingAddress, address: e.target.value })}
                placeholder="الشارع والحي والمنطقة"
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="city">المدينة *</label>
              <input
                type="text"
                id="city"
                value={shippingAddress.city}
                onChange={(e) => setShippingAddress({ ...shippingAddress, city: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="region">المنطقة</label>
              <select
                id="region"
                value={shippingAddress.region}
                onChange={(e) => setShippingAddress({ ...shippingAddress, region: e.target.value })}
              >
                <option value="">اختر المنطقة</option>
                <option value="riyadh">الرياض</option>
                <option value="mecca">مكة المكرمة</option>
                <option value="medina">المدينة المنورة</option>
                <option value="eastern">المنطقة الشرقية</option>
                <option value="asir">عسير</option>
                <option value="tabuk">تبوك</option>
                <option value="qassim">القصيم</option>
                <option value="hail">حائل</option>
                <option value="northern">الحدود الشمالية</option>
                <option value="jazan">جازان</option>
                <option value="najran">نجران</option>
                <option value="albaha">الباحة</option>
                <option value="jouf">الجوف</option>
              </select>
            </div>
            <div className="form-group">
              <label htmlFor="postal_code">الرمز البريدي</label>
              <input
                type="text"
                id="postal_code"
                value={shippingAddress.postal_code}
                onChange={(e) => setShippingAddress({ ...shippingAddress, postal_code: e.target.value })}
              />
            </div>
          </div>
        </div>

        <div className="checkout-summary">
          <h3>ملخص الطلب</h3>
          <div className="order-items">
            {cart.map(item => (
              <div key={item.id} className="order-item">
                <span>{item.title}</span>
                <span>{item.quantity}x</span>
                <span>{(item.price * item.quantity).toFixed(2)} ريال</span>
              </div>
            ))}
          </div>
          <div className="order-totals">
            <div className="total-row">
              <span>المجموع الفرعي:</span>
              <span>{totals.subtotal.toFixed(2)} ريال</span>
            </div>
            <div className="total-row">
              <span>الشحن:</span>
              <span>{totals.shipping_cost > 0 ? `${totals.shipping_cost.toFixed(2)} ريال` : 'مجاناً'}</span>
            </div>
            <div className="total-row">
              <span>الضريبة (15%):</span>
              <span>{totals.tax_amount.toFixed(2)} ريال</span>
            </div>
            <div className="total-row total">
              <span>الإجمالي:</span>
              <span>{totals.total.toFixed(2)} ريال</span>
            </div>
          </div>
          {totals.shipping_cost === 0 && (
            <div className="free-shipping-notice">
              <p>🎉 شحن مجاني! (للطلبات أكثر من 200 ريال)</p>
            </div>
          )}
        </div>

        <div className="form-actions">
          <button type="button" className="btn btn-secondary" onClick={() => navigate('/cart')}>
            العودة للسلة
          </button>
          <button type="submit" className="btn btn-primary">
            المتابعة للدفع
          </button>
        </div>
      </form>
    </div>
  );

  const OrderSuccess = () => (
    <div className="order-success">
      <div className="success-animation">
        <div className="success-checkmark">✓</div>
      </div>
      <h2>تم تأكيد الطلب بنجاح!</h2>
      <p>شكراً لك على طلبك من دار زيد للنشر والتوزيع</p>
      <div className="order-details">
        <h3>تفاصيل الطلب</h3>
        <p><strong>رقم الطلب:</strong> {orderData?.id}</p>
        <p><strong>البريد الإلكتروني:</strong> {orderData?.customer_info.email}</p>
        <p><strong>المبلغ الإجمالي:</strong> {orderData?.total_amount.toFixed(2)} ريال</p>
      </div>
      <div className="success-actions">
        <button className="btn btn-primary" onClick={() => navigate('/bookstore')}>
          مواصلة التسوق
        </button>
        <button className="btn btn-secondary" onClick={() => navigate('/')}>
          العودة للرئيسية
        </button>
      </div>
    </div>
  );

  return (
    <div className="checkout-page">
      <div className="container">
        {step === 'details' && <CustomerDetailsForm />}
        {step === 'payment' && orderData && (
          <CheckoutPayment
            orderData={orderData}
            onPaymentSuccess={handlePaymentSuccess}
            onPaymentError={handlePaymentError}
          />
        )}
        {step === 'success' && <OrderSuccess />}
      </div>
    </div>
  );
};

export default Checkout;