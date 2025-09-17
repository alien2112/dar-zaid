import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useCart } from '../contexts/CartContext';

const Navbar = () => {
  const [isSideMenuOpen, setIsSideMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const { isAuthenticated, isAdmin, user, logout } = useAuth();
  const { cartItems, openCart } = useCart();
  const totalQty = cartItems.reduce((sum, item) => sum + (item.quantity || 0), 0);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50);
    };

    window.addEventListener('scroll', handleScroll);

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  const toggleSideMenu = () => {
    setIsSideMenuOpen(!isSideMenuOpen);
  };

  const handleLogout = () => {
    logout();
    setIsSideMenuOpen(false);
  };

  const handleCartClick = () => {
    openCart();
  }

  const handleLinkClick = () => {
    setIsSideMenuOpen(false);
  };

  // Close side menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (isSideMenuOpen && !event.target.closest('.side-menu') && !event.target.closest('.menu-toggle-btn')) {
        setIsSideMenuOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isSideMenuOpen]);

  return (
    <>
      <nav className={`clean-navbar ${isScrolled ? 'scrolled' : ''}`} style={{ position: 'sticky', top: 0, zIndex: 100, background: '#fff', boxShadow: isScrolled ? '0 2px 8px rgba(0,0,0,0.06)' : 'none' }}>
        <div className="navbar-container" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 12px', minHeight: '70px' }}>
          {/* Left side - Account & Cart */}
          <div className="navbar-left" style={{ display: 'flex', alignItems: 'center', gap: 8, order: 3 }}>
            {isAuthenticated() ? (
              <div className="user-section">
                <span className="user-greeting" style={{ fontSize: '0.85rem', padding: '0.4rem 0.8rem' }}>أهلاً، {user?.name}</span>
              </div>
            ) : (
              <Link to="/login" className="login-link" onClick={handleLinkClick} style={{ padding: '0.5rem 0.75rem', fontSize: '0.9rem' }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <span style={{ display: 'none' }}>تسجيل الدخول</span>
              </Link>
            )}

            <button onClick={handleCartClick} className="cart-button" title="سلة التسوق" style={{ padding: '0.6rem', minWidth: '44px', minHeight: '44px' }}>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
              </svg>
              {totalQty > 0 && <span className="cart-badge" style={{ fontSize: '0.7rem', width: '18px', height: '18px' }}>{totalQty}</span>}
            </button>
          </div>

          {/* Center - Logo */}
          <div className="navbar-center" style={{ textAlign: 'center', flex: '0 1 auto', order: 2 }}>
            <Link to="/" className="logo-link" onClick={handleLinkClick}>
              <img src="/logo.png" alt="دار زيد للنشر والتوزيع" className="navbar-logo" style={{ height: '120px', objectFit: 'contain', maxWidth: '240px' }} />
            </Link>
          </div>

          {/* Right side - Menu Toggle */}
          <div className="navbar-right" style={{ display: 'flex', alignItems: 'center', order: 1 }}>
            <button
              className="menu-toggle-btn"
              onClick={toggleSideMenu}
              aria-label="Toggle menu"
              style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '8px', minWidth: '44px', minHeight: '44px' }}
            >
              <span className={`hamburger ${isSideMenuOpen ? 'open' : ''}`} style={{ display: 'inline-block', width: 22, height: 16, position: 'relative' }}>
                <span style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 2, background: '#1f2937', transition: 'transform 0.2s' }}></span>
                <span style={{ position: 'absolute', top: 7, left: 0, right: 0, height: 2, background: '#1f2937', transition: 'opacity 0.2s' }}></span>
                <span style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 2, background: '#1f2937', transition: 'transform 0.2s' }}></span>
              </span>
            </button>
          </div>
        </div>
      </nav>

      {/* Side Menu Overlay */}
      <div
        className={`side-menu-overlay ${isSideMenuOpen ? 'open' : ''}`}
        onClick={() => setIsSideMenuOpen(false)}
        style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.3)', opacity: isSideMenuOpen ? 1 : 0, pointerEvents: isSideMenuOpen ? 'auto' : 'none', transition: 'opacity 0.2s', zIndex: 99 }}
      ></div>

      {/* Side Menu */}
      <nav className={`side-menu ${isSideMenuOpen ? 'open' : ''}`} style={{ position: 'fixed', top: 0, bottom: 0, right: 0, width: 'min(300px, 85vw)', background: '#fff', boxShadow: '-2px 0 8px rgba(0,0,0,0.08)', transform: `translateX(${isSideMenuOpen ? 0 : '100%'})`, transition: 'transform 0.3s ease', zIndex: 100 }}>
        <div className="side-menu-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '16px', borderBottom: '1px solid #e5e7eb', background: 'linear-gradient(135deg, #1e3a8a, #3b82f6)', color: 'white' }}>
          <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: '600' }}>القائمة الرئيسية</h3>
          <button className="close-menu-btn" onClick={() => setIsSideMenuOpen(false)} style={{ background: 'rgba(255,255,255,0.2)', border: 'none', fontSize: 18, cursor: 'pointer', width: '32px', height: '32px', borderRadius: '50%', color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>✕</button>
        </div>

        <ul className="side-menu-list" style={{ listStyle: 'none', margin: 0, padding: 0 }}>
          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>الرئيسية</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/bookstore" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>متجر الكتب</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/packages" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 8.5 12 3 3 8.5l9 5.5 9-5.5z"/>
                <path d="M3 10v6.5C3 18.88 4.12 20 5.5 20h13c1.38 0 2.5-1.12 2.5-2.5V10l-9 5.5L3 10z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>باقات النشر</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/releases" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>الإصدارات</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/blog" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>المدونة</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/about" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>من نحن</span>
            </Link>
          </li>

          <li className="side-menu-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
            <Link to="/contact" className="side-menu-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#111827', minHeight: '56px', transition: 'all 0.2s ease' }}>
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
              </svg>
              <span style={{ fontSize: '1rem', fontWeight: '500' }}>اتصل بنا</span>
            </Link>
          </li>

          {isAdmin() && (
            <li className="side-menu-item admin-item" style={{ borderBottom: '1px solid #f3f4f6' }}>
              <Link to="/admin" className="side-menu-link admin-link" onClick={handleLinkClick} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: 'white', minHeight: '56px', transition: 'all 0.2s ease', background: 'linear-gradient(135deg, #dc2626, #ef4444)', margin: '8px 16px', borderRadius: '8px' }}>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1ZM12 7C13.1 7 14 7.9 14 9C14 10.1 13.1 11 12 11C10.9 11 10 10.1 10 9C10 7.9 10.9 7 12 7ZM18 15C18 16.5 15 18 12 18C9 18 6 16.5 6 15C6 14 9 13 12 13C15 13 18 14 18 15Z"/>
                </svg>
                <span style={{ fontSize: '1rem', fontWeight: '500' }}>الإدارة</span>
              </Link>
            </li>
          )}

          {isAuthenticated() && (
            <li className="side-menu-item logout-item" style={{ borderBottom: 'none', padding: '8px 16px' }}>
              <button className="side-menu-logout" onClick={handleLogout} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px', color: '#dc2626', minHeight: '56px', transition: 'all 0.2s ease', background: 'none', border: '2px solid #fecaca', borderRadius: '8px', width: '100%', cursor: 'pointer', fontSize: '1rem', fontWeight: '500' }}>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                <span>تسجيل الخروج</span>
              </button>
            </li>
          )}
        </ul>
      </nav>
    </>
  );
};

export default Navbar;
