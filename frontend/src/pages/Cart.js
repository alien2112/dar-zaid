import React, { useState } from 'react';
import { useCart } from '../contexts/CartContext';
import { Link } from 'react-router-dom';
import { orderService } from '../services/orderService';

const Cart = () => {
  const { cartItems, removeFromCart, updateQuantity, getCartTotal, clearCart } = useCart();
  const [showCheckoutForm, setShowCheckoutForm] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('moyasar');
  const [address, setAddress] = useState('');
  const [altPhone, setAltPhone] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const handleCheckout = async (e) => {
    e.preventDefault();
    if (cartItems.length === 0) return;
    setErrorMsg('');
    if (!name || !email || !phone) {
      setErrorMsg('يرجى إدخال الاسم والبريد ورقم الجوال');
      return;
    }
    const customerInfo = { name, email, phone, address, alt_phone: altPhone };
    const items = cartItems.map(i => ({ id: i.id, title: i.title, quantity: i.quantity, price: i.price }));
    const total = getCartTotal();
    try {
      setSubmitting(true);
      const result = await orderService.createOrder({ customerInfo, items, paymentMethod, total });
      if (result?.success) {
        const redirectUrl = result?.redirectUrl || result?.paymentResult?.redirectUrl || result?.paymentResult?.redirect_url || result?.paymentResult?.source?.transaction_url;
        if (redirectUrl) {
          window.location.href = redirectUrl;
          return;
        }
        alert('تم إكمال الدفع بنجاح');
        clearCart();
        setShowCheckoutForm(false);
      } else {
        setErrorMsg(result?.error || 'فشل في إنشاء الطلب/الدفع');
      }
    } catch (e) {
      setErrorMsg('حدث خطأ أثناء عملية الدفع');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div style={{ padding: '4rem 0' }}>
      <div className="container">
        <h1 style={{ textAlign: 'center', marginBottom: '3rem', fontSize: '2.5rem' }}>
          سلة التسوق
        </h1>
        {cartItems.length === 0 ? (
          <div style={{ textAlign: 'center' }}>
            <p>سلة التسوق فارغة.</p>
            <Link to="/bookstore" className="btn btn-primary">تصفح الكتب</Link>
          </div>
        ) : (
          <div>
            {cartItems.map(item => (
              <div key={item.id} className="card" style={{ marginBottom: '1rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div style={{ display: 'flex', alignItems: 'center' }}>
                  <img src={item.cover_image_url} alt={item.title} style={{ width: '80px', height: '120px', objectFit: 'cover', marginRight: '1rem' }} />
                  <div>
                    <h4 style={{ marginBottom: '0.5rem' }}>{item.title}</h4>
                    <p style={{ marginBottom: '0.5rem' }}>{item.price} ريال</p>
                    <div style={{ display: 'flex', alignItems: 'center' }}>
                      <button onClick={() => updateQuantity(item.id, item.quantity - 1)} className="btn btn-secondary btn-sm">-</button>
                      <span style={{ margin: '0 0.5rem' }}>{item.quantity}</span>
                      <button onClick={() => updateQuantity(item.id, item.quantity + 1)} className="btn btn-secondary btn-sm">+</button>
                    </div>
                  </div>
                </div>
                <button onClick={() => removeFromCart(item.id)} className="btn btn-danger">إزالة</button>
              </div>
            ))}
            <div style={{ marginTop: '2rem', textAlign: 'left' }}>
              <h3>المجموع: {getCartTotal()} ريال</h3>
              {!showCheckoutForm ? (
                <button className="btn btn-primary" onClick={() => setShowCheckoutForm(true)}>الانتقال إلى الدفع</button>
              ) : (
                <form onSubmit={handleCheckout} className="modern-form" style={{ marginTop: '1rem' }}>
                  <div className="form-grid" style={{ gridTemplateColumns: '1fr 1fr 1fr' }}>
                    <div className="form-group">
                      <label>الاسم الكامل</label>
                      <input type="text" value={name} onChange={(e) => setName(e.target.value)} required />
                    </div>
                    <div className="form-group">
                      <label>البريد الإلكتروني</label>
                      <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                    </div>
                    <div className="form-group">
                      <label>رقم الجوال</label>
                      <input type="tel" value={phone} onChange={(e) => setPhone(e.target.value)} required />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>العنوان</label>
                      <input type="text" value={address} onChange={(e) => setAddress(e.target.value)} placeholder="المدينة، الحي، الشارع، تفاصيل إضافية" />
                    </div>
                    <div className="form-group">
                      <label>رقم جوال آخر (اختياري)</label>
                      <input type="tel" value={altPhone} onChange={(e) => setAltPhone(e.target.value)} />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>طريقة الدفع</label>
                      <select value={paymentMethod} onChange={(e) => setPaymentMethod(e.target.value)}>
                        <option value="moyasar">Visa/Mastercard (Moyasar)</option>
                        <option value="stc_pay">STC Pay</option>
                        <option value="visa">Visa (بديل)</option>
                      </select>
                    </div>
                  </div>
                  {errorMsg && (
                    <div style={{ color: '#dc2626', marginTop: '0.5rem' }}>{errorMsg}</div>
                  )}
                  <div style={{ display: 'flex', gap: '0.5rem', marginTop: '1rem' }}>
                    <button type="button" className="btn btn-secondary" onClick={() => setShowCheckoutForm(false)} disabled={submitting}>إلغاء</button>
                    <button type="submit" className="btn btn-primary" disabled={submitting}>{submitting ? 'جاري المعالجة...' : 'إتمام الدفع'}</button>
                  </div>
                </form>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Cart;
