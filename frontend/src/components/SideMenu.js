import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useCart } from '../contexts/CartContext';
import '../styles/SideMenu.css';

const SideMenu = () => {
  const [isOpen, setIsOpen] = useState(false);
  const { isAuthenticated, isAdmin, user, logout } = useAuth();
  const { cartItems, openCart } = useCart();
  const totalQty = cartItems.reduce((sum, item) => sum + (item.quantity || 0), 0);

  const toggleMenu = () => {
    setIsOpen(!isOpen);
  };

  const handleLogout = () => {
    logout();
    setIsOpen(false);
  };

  const handleCartClick = () => {
    openCart();
    setIsOpen(false);
  }

  return (
    <>
      <button className="side-menu-toggle" onClick={toggleMenu}>
        ☰
      </button>
      <div className={`side-menu ${isOpen ? 'open' : ''}`}>
        <button className="close-btn" onClick={toggleMenu}>
          &times;
        </button>
        <div className="side-menu-content">
          <Link to="/" className="logo-link">
            <img src="/logo.png" alt="دار زيد للنشر والتوزيع" style={{ height: '140px' }} />
          </Link>
          <ul>
            <li><Link to="/" onClick={() => setIsOpen(false)}>الرئيسية</Link></li>
            <li><Link to="/bookstore" onClick={() => setIsOpen(false)}>متجر الكتب</Link></li>
            <li><Link to="/packages" onClick={() => setIsOpen(false)}>باقات الطباعة والنشر</Link></li>
            <li><Link to="/releases" onClick={() => setIsOpen(false)}>الإصدارات</Link></li>
            <li><Link to="/blog" onClick={() => setIsOpen(false)}>المدونة</Link></li>
            <li><Link to="/about" onClick={() => setIsOpen(false)}>من نحن</Link></li>
            <li><Link to="/contact" onClick={() => setIsOpen(false)}>اتصل بنا</Link></li>

            {isAdmin() && (
              <li><Link to="/admin" onClick={() => setIsOpen(false)}>الإدارة</Link></li>
            )}

            {isAuthenticated() ? (
              <>
                <li style={{ padding: '0.5rem 1rem', color: '#64748b', fontSize: '0.9rem' }}>
                  أهلاً، {user?.name}
                </li>
                <li>
                  <button
                    onClick={handleLogout}
                    style={{
                      background: 'none',
                      border: 'none',
                      color: '#4a5568',
                      cursor: 'pointer',
                      padding: '0.5rem 1rem',
                      borderRadius: '6px',
                      transition: 'all 0.3s ease'
                    }}
                    onMouseOver={(e) => {
                      e.target.style.color = '#1e3a8a';
                      e.target.style.backgroundColor = '#f7fafc';
                    }}
                    onMouseOut={(e) => {
                      e.target.style.color = '#4a5568';
                      e.target.style.backgroundColor = 'transparent';
                    }}
                  >
                    تسجيل الخروج
                  </button>
                </li>
              </>
            ) : (
              <li><Link to="/login" onClick={() => setIsOpen(false)}>تسجيل الدخول</Link></li>
            )}
             <li>
              <button onClick={handleCartClick} className="cart-icon-button" title="سلة التسوق">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                {totalQty > 0 && <span className="cart-count">{totalQty}</span>}
              </button>
            </li>
          </ul>
        </div>
      </div>
    </>
  );
};

export default SideMenu;
