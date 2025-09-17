import React from 'react';
import { useCart } from '../contexts/CartContext';
import { Link } from 'react-router-dom';

const SideCart = () => {
  const { cartItems, removeFromCart, updateQuantity, getCartTotal, isCartOpen, closeCart } = useCart();

  return (
    <div className={`side-cart ${isCartOpen ? 'open' : ''}`}>
      <div className="side-cart-header">
        <h3>سلة التسوق</h3>
        <button onClick={closeCart} className="close-btn">&times;</button>
      </div>
      <div className="side-cart-body">
        {cartItems.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '2rem' }}>
            <p>سلة التسوق فارغة.</p>
          </div>
        ) : (
          cartItems.map(item => (
            <div key={item.id} className="side-cart-item">
              <img src={item.image_url} alt={item.title} />
              <div className="item-details">
                <h4>{item.title}</h4>
                <p>{item.price} ريال</p>
                <div className="quantity-controls">
                  <button onClick={() => updateQuantity(item.id, item.quantity - 1)}>-</button>
                  <span>{item.quantity}</span>
                  <button onClick={() => updateQuantity(item.id, item.quantity + 1)}>+</button>
                </div>
              </div>
              <button onClick={() => removeFromCart(item.id)} className="remove-btn">&times;</button>
            </div>
          ))
        )}
      </div>
      <div className="side-cart-footer">
        <div className="total">
          <span>المجموع</span>
          <span>{getCartTotal()} ريال</span>
        </div>
        <Link to="/cart" className="btn btn-primary" onClick={closeCart}>عرض السلة</Link>
        <button className="btn btn-secondary">الانتقال للدفع</button>
      </div>
    </div>
  );
};

export default SideCart;
