import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../services/api';
import { useCart } from '../contexts/CartContext';

const BookOfTheWeek = () => {
  const [bookOfWeek, setBookOfWeek] = useState(null);
  const [loading, setLoading] = useState(true);
  const { addToCart } = useCart();

  useEffect(() => {
    const loadBookOfWeek = async () => {
      try {
        const response = await apiService.getBookOfWeek();
        setBookOfWeek(response.data.book_of_week);
      } catch (error) {
        console.error('Error loading book of the week:', error);
      } finally {
        setLoading(false);
      }
    };

    loadBookOfWeek();
  }, []);

  const handleAddToCart = () => {
    if (bookOfWeek) {
      addToCart(bookOfWeek);
    }
  };

  if (loading) {
    return (
      <div className="book-of-week-container">
        <div className="book-of-week-loading">
          <div className="spinner"></div>
          <p>جاري تحميل كتاب الأسبوع...</p>
        </div>
      </div>
    );
  }

  if (!bookOfWeek) {
    return null; // Don't show anything if no book is featured
  }

  return (
    <div className="book-of-week-container">
      <div className="book-of-week-layout">
        {/* Main Book Display */}
        <div className="book-of-week-main">
          <div className="book-of-week-image">
            <Link to={`/book/${bookOfWeek.id}`}>
              <img
                src={bookOfWeek.image_url || '/images/book-placeholder.jpg'}
                alt={bookOfWeek.title}
                onError={(e) => {
                  e.target.src = '/images/book-placeholder.jpg';
                }}
              />
              <div className="book-overlay">
                <span>عرض التفاصيل</span>
              </div>
            </Link>
          </div>

          <div className="book-of-week-info">
            <div className="book-category">
              {bookOfWeek.category_name && (
                <span className="category-badge">{bookOfWeek.category_name}</span>
              )}
            </div>

            <h3 className="book-title">
              <Link to={`/book/${bookOfWeek.id}`}>{bookOfWeek.title}</Link>
            </h3>

            <p className="book-author">بقلم: {bookOfWeek.author}</p>

            {bookOfWeek.description && (
              <p className="book-description">
                {bookOfWeek.description.length > 150
                  ? `${bookOfWeek.description.substring(0, 150)}...`
                  : bookOfWeek.description}
              </p>
            )}

            <div className="book-price-section">
              <span className="book-price">{bookOfWeek.price} ريال</span>
              {bookOfWeek.stock_quantity > 0 ? (
                <span className="stock-status in-stock">متوفر</span>
              ) : (
                <span className="stock-status out-of-stock">نفدت الكمية</span>
              )}
            </div>

            <div className="book-actions">
              <Link to={`/book/${bookOfWeek.id}`} className="btn btn-primary book-details-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
                عرض التفاصيل
              </Link>

              {bookOfWeek.stock_quantity > 0 && (
                <button
                  onClick={handleAddToCart}
                  className="btn btn-secondary add-to-cart-btn"
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                  </svg>
                  أضف للسلة
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Book of the Week Badge - Right Side */}
        <div className="book-of-week-badge-section">
          <div className="book-of-week-badge">
            <span className="book-text">كتاب</span>
            <span className="week-text">الأسبوع</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BookOfTheWeek;