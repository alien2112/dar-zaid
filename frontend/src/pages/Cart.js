import React, { useState } from 'react';
import { useCart } from '../contexts/CartContext';
import { Link } from 'react-router-dom';
import { orderService } from '../services/orderService';
import PaymentMethodSelector from '../components/PaymentMethodSelector';

const Cart = () => {
  const { cartItems, removeFromCart, updateQuantity, getCartTotal, clearCart } = useCart();
  const [showCheckoutForm, setShowCheckoutForm] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
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
      const result = await orderService.createOrder({ 
        customerInfo, 
        items, 
        paymentMethod: selectedPaymentMethod?.id || 'stc_pay', 
        total 
      });
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
    <div style={{ padding: '2rem 0' }}>
      <div className="container">
        <h1 style={{ 
          textAlign: 'center', 
          marginBottom: '2rem', 
          fontSize: 'clamp(1.8rem, 4vw, 2.5rem)',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif',
          lineHeight: 1.3
        }}>
          سلة التسوق
        </h1>
        {cartItems.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '3rem 1rem' }}>
            <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🛒</div>
            <p style={{ fontSize: '1.1rem', marginBottom: '2rem', color: '#6b7280' }}>سلة التسوق فارغة</p>
            <Link to="/bookstore" className="btn btn-primary" style={{ minHeight: '44px', padding: '12px 24px' }}>تصفح الكتب</Link>
          </div>
        ) : (
          <div>
            {cartItems.map(item => (
              <div key={item.id} className="card" style={{ 
                marginBottom: '1rem', 
                display: 'flex', 
                flexDirection: 'column',
                gap: '1rem',
                padding: '1rem'
              }}>
                {/* Mobile layout */}
                <div style={{ 
                  display: 'flex', 
                  alignItems: 'flex-start', 
                  gap: '1rem'
                }}>
                  <img 
                    src={item.cover_image_url} 
                    alt={item.title} 
                    style={{ 
                      width: '80px', 
                      height: '120px', 
                      objectFit: 'cover', 
                      borderRadius: '8px',
                      flexShrink: 0
                    }} 
                  />
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <h4 style={{ 
                      marginBottom: '0.5rem', 
                      fontSize: '1.1rem',
                      lineHeight: 1.3,
                      display: '-webkit-box',
                      WebkitLineClamp: 2,
                      WebkitBoxOrient: 'vertical',
                      overflow: 'hidden'
                    }}>{item.title}</h4>
                    <p style={{ 
                      marginBottom: '0.5rem', 
                      color: '#1e3a8a',
                      fontWeight: '600',
                      fontSize: '1rem'
                    }}>{item.price} ريال</p>
                    <div style={{ 
                      display: 'flex', 
                      alignItems: 'center', 
                      gap: '0.5rem',
                      marginBottom: '0.5rem'
                    }}>
                      <button 
                        onClick={() => updateQuantity(item.id, item.quantity - 1)} 
                        className="btn btn-secondary btn-sm"
                        style={{ 
                          minWidth: '32px', 
                          minHeight: '32px',
                          padding: '0.25rem',
                          fontSize: '0.9rem'
                        }}
                      >-</button>
                      <span style={{ 
                        margin: '0 0.5rem',
                        minWidth: '20px',
                        textAlign: 'center',
                        fontWeight: '600'
                      }}>{item.quantity}</span>
                      <button 
                        onClick={() => updateQuantity(item.id, item.quantity + 1)} 
                        className="btn btn-secondary btn-sm"
                        style={{ 
                          minWidth: '32px', 
                          minHeight: '32px',
                          padding: '0.25rem',
                          fontSize: '0.9rem'
                        }}
                      >+</button>
                    </div>
                  </div>
                </div>
                <button 
                  onClick={() => removeFromCart(item.id)} 
                  className="btn btn-danger"
                  style={{ 
                    width: '100%',
                    minHeight: '44px',
                    fontSize: '0.9rem'
                  }}
                >إزالة من السلة</button>
              </div>
            ))}
            <div style={{ 
              marginTop: '2rem', 
              textAlign: 'center',
              padding: '1.5rem',
              background: '#f8fafc',
              borderRadius: '12px',
              border: '1px solid #e2e8f0'
            }}>
              <h3 style={{ 
                fontSize: '1.5rem',
                color: '#1e3a8a',
                marginBottom: '1rem'
              }}>المجموع: {getCartTotal()} ريال</h3>
              {!showCheckoutForm ? (
                <button 
                  className="btn btn-primary" 
                  onClick={() => setShowCheckoutForm(true)}
                  style={{ 
                    minHeight: '44px',
                    padding: '12px 24px',
                    fontSize: '1rem'
                  }}
                >الانتقال إلى الدفع</button>
              ) : (
                <form onSubmit={handleCheckout} className="modern-form" style={{ marginTop: '1rem' }}>
                  <div className="form-grid" style={{ 
                    gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                    gap: '1rem'
                  }}>
                    <div className="form-group">
                      <label>الاسم الكامل</label>
                      <input 
                        type="text" 
                        value={name} 
                        onChange={(e) => setName(e.target.value)} 
                        required 
                        style={{ fontSize: '16px', minHeight: '44px' }}
                      />
                    </div>
                    <div className="form-group">
                      <label>البريد الإلكتروني</label>
                      <input 
                        type="email" 
                        value={email} 
                        onChange={(e) => setEmail(e.target.value)} 
                        required 
                        style={{ fontSize: '16px', minHeight: '44px' }}
                      />
                    </div>
                    <div className="form-group">
                      <label>رقم الجوال</label>
                      <input 
                        type="tel" 
                        value={phone} 
                        onChange={(e) => setPhone(e.target.value)} 
                        required 
                        style={{ fontSize: '16px', minHeight: '44px' }}
                      />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <label>العنوان</label>
                      <input 
                        type="text" 
                        value={address} 
                        onChange={(e) => setAddress(e.target.value)} 
                        placeholder="المدينة، الحي، الشارع، تفاصيل إضافية" 
                        style={{ fontSize: '16px', minHeight: '44px' }}
                      />
                    </div>
                    <div className="form-group">
                      <label>رقم جوال آخر (اختياري)</label>
                      <input 
                        type="tel" 
                        value={altPhone} 
                        onChange={(e) => setAltPhone(e.target.value)} 
                        style={{ fontSize: '16px', minHeight: '44px' }}
                      />
                    </div>
                    <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                      <PaymentMethodSelector
                        selectedMethod={selectedPaymentMethod}
                        onMethodSelect={setSelectedPaymentMethod}
                        amount={getCartTotal()}
                        currency="SAR"
                        compact={true}
                      />
                    </div>
                  </div>
                  {errorMsg && (
                    <div style={{ 
                      color: '#dc2626', 
                      marginTop: '0.5rem',
                      padding: '0.75rem',
                      background: '#fef2f2',
                      border: '1px solid #fecaca',
                      borderRadius: '8px',
                      textAlign: 'center'
                    }}>{errorMsg}</div>
                  )}
                  <div style={{ 
                    display: 'flex', 
                    gap: '0.5rem', 
                    marginTop: '1rem',
                    flexDirection: 'column'
                  }}>
                    <button 
                      type="submit" 
                      className="btn btn-primary" 
                      disabled={submitting}
                      style={{ 
                        minHeight: '44px',
                        fontSize: '1rem'
                      }}
                    >{submitting ? 'جاري المعالجة...' : 'إتمام الدفع'}</button>
                    <button 
                      type="button" 
                      className="btn btn-secondary" 
                      onClick={() => setShowCheckoutForm(false)} 
                      disabled={submitting}
                      style={{ 
                        minHeight: '44px',
                        fontSize: '1rem'
                      }}
                    >إلغاء</button>
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
