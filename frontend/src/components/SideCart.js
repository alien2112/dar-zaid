import React from 'react';
import { useCart } from '../contexts/CartContext';
import { Link } from 'react-router-dom';

const SideCart = () => {
  const { cartItems, removeFromCart, updateQuantity, getCartTotal, isCartOpen, closeCart } = useCart();

  return (
    <>
      {/* Backdrop overlay */}
      {isCartOpen && (
        <div 
          className="cart-backdrop" 
          onClick={closeCart}
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0, 0, 0, 0.5)',
            zIndex: 1000
          }}
        />
      )}
      
      <div className={`side-cart ${isCartOpen ? 'open' : ''}`} style={{
      position: 'fixed',
      top: 0,
      right: isCartOpen ? '0' : '-100%',
      width: 'min(400px, 100vw)',
      height: '100%',
      background: 'white',
      boxShadow: '-2px 0 8px rgba(0,0,0,0.08)',
      transition: 'right 0.3s ease',
      zIndex: 1001,
      display: 'flex',
      flexDirection: 'column'
    }}>
      <div className="side-cart-header" style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '1rem',
        borderBottom: '1px solid #eee',
        background: 'linear-gradient(135deg, #1e3a8a, #3b82f6)',
        color: 'white'
      }}>
        <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: '600' }}>سلة التسوق</h3>
        <button onClick={closeCart} className="close-btn" style={{
          background: 'rgba(255,255,255,0.2)',
          border: 'none',
          fontSize: '1.5rem',
          cursor: 'pointer',
          color: 'white',
          width: '32px',
          height: '32px',
          borderRadius: '50%',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center'
        }}>&times;</button>
      </div>
      <div className="side-cart-body" style={{
        flexGrow: 1,
        overflowY: 'auto',
        padding: '1rem'
      }}>
        {cartItems.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '2rem' }}>
            <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🛒</div>
            <p style={{ color: '#6b7280', fontSize: '1rem' }}>سلة التسوق فارغة</p>
          </div>
        ) : (
          cartItems.map(item => (
            <div key={item.id} className="side-cart-item" style={{
              display: 'flex',
              marginBottom: '1rem',
              borderBottom: '1px solid #eee',
              paddingBottom: '1rem',
              gap: '0.75rem'
            }}>
              <img src={item.image_url} alt={item.title} style={{
                width: '60px',
                height: '80px',
                objectFit: 'cover',
                borderRadius: '6px',
                flexShrink: 0
              }} />
              <div className="item-details" style={{ flex: 1, minWidth: 0 }}>
                <h4 style={{ 
                  margin: '0 0 0.5rem 0', 
                  fontSize: '0.9rem',
                  lineHeight: 1.3,
                  display: '-webkit-box',
                  WebkitLineClamp: 2,
                  WebkitBoxOrient: 'vertical',
                  overflow: 'hidden'
                }}>{item.title}</h4>
                <p style={{ margin: '0 0 0.5rem 0', color: '#1e3a8a', fontWeight: '600', fontSize: '0.9rem' }}>{item.price} ريال</p>
                <div className="quantity-controls" style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem'
                }}>
                  <button 
                    onClick={() => updateQuantity(item.id, item.quantity - 1)} 
                    style={{
                      background: '#f3f4f6',
                      border: 'none',
                      width: '28px',
                      height: '28px',
                      cursor: 'pointer',
                      borderRadius: '4px',
                      fontSize: '1rem',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center'
                    }}
                  >-</button>
                  <span style={{ 
                    padding: '0 0.5rem',
                    fontSize: '0.9rem',
                    fontWeight: '500',
                    minWidth: '20px',
                    textAlign: 'center'
                  }}>{item.quantity}</span>
                  <button 
                    onClick={() => updateQuantity(item.id, item.quantity + 1)} 
                    style={{
                      background: '#f3f4f6',
                      border: 'none',
                      width: '28px',
                      height: '28px',
                      cursor: 'pointer',
                      borderRadius: '4px',
                      fontSize: '1rem',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center'
                    }}
                  >+</button>
                </div>
              </div>
              <button 
                onClick={() => removeFromCart(item.id)} 
                className="remove-btn" 
                style={{
                  background: 'none',
                  border: 'none',
                  fontSize: '1.2rem',
                  cursor: 'pointer',
                  color: '#ef4444',
                  width: '24px',
                  height: '24px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  flexShrink: 0
                }}
              >&times;</button>
            </div>
          ))
        )}
      </div>
      <div className="side-cart-footer" style={{
        padding: '1rem',
        borderTop: '1px solid #eee',
        background: '#f8fafc'
      }}>
        <div className="total" style={{
          display: 'flex',
          justifyContent: 'space-between',
          fontWeight: 'bold',
          marginBottom: '1rem',
          fontSize: '1.1rem',
          color: '#1e3a8a'
        }}>
          <span>المجموع</span>
          <span>{getCartTotal()} ريال</span>
        </div>
        <Link 
          to="/cart" 
          className="btn btn-primary" 
          onClick={closeCart}
          style={{
            width: '100%',
            textAlign: 'center',
            minHeight: '44px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
          }}
        >عرض السلة</Link>
      </div>
    </div>
    </>
  );
};

export default SideCart;
