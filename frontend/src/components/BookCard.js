import React from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../contexts/CartContext';
import { FiShoppingBag } from 'react-icons/fi';

const BookCard = React.memo(({ book }) => {
  const { addToCart } = useCart();

  return (
    <div className="book-card" style={{ 
      background: 'white',
      borderRadius: '8px',
      overflow: 'hidden',
      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
      transition: 'all 0.3s ease',
      border: '1px solid #eee',
      height: '100%',
      display: 'flex',
      flexDirection: 'column',
      position: 'relative'
    }}>
      <Link 
        to={`/book/${book.id}`} 
        style={{ 
          textDecoration: 'none',
          color: 'inherit',
          display: 'block',
          height: '140px',
          position: 'relative'
        }}
      >
        <div style={{
          height: '100%',
          background: `url(${book.image_url || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="24">No Image</text></svg>'}) no-repeat center center / cover`,
          position: 'relative',
          overflow: 'hidden',
          backgroundColor: '#f8fafc'
        }}/>
      </Link>

      <div className="book-content" style={{ 
        padding: '1rem',
        display: 'flex',
        flexDirection: 'column',
        gap: '0.5rem',
        textAlign: 'center'
      }}>
        <Link 
          to={`/book/${book.id}`} 
          style={{ 
            color: 'inherit', 
            textDecoration: 'none',
            display: 'block'
          }}
        >
          <h3 className="book-title" style={{ 
            fontSize: '1rem',
            fontWeight: '600',
            color: '#1e293b',
            lineHeight: 1.4,
            display: '-webkit-box',
            WebkitLineClamp: 2,
            'line-clamp': 2,
            WebkitBoxOrient: 'vertical',
            overflow: 'hidden',
            marginBottom: '0.25rem',
            minHeight: '2.8em'
          }}>
            {book.title}
          </h3>
        </Link>

        {book.original_price && book.original_price > book.price && (
          <div className="original-price" style={{
            color: '#94a3b8',
            fontSize: '0.9rem',
            textDecoration: 'line-through'
          }}>
            {book.original_price} ر.س
          </div>
        )}

        <div className="book-price" style={{ 
          fontSize: '1.1rem',
          fontWeight: '700',
          color: '#2563eb'
        }}>
          {book.price} ر.س
        </div>

        <button
          onClick={() => addToCart(book)}
          style={{
            width: '100%',
            backgroundColor: '#1e3a8a',
            color: 'white',
            border: 'none',
            borderRadius: '25px',
            padding: '0.75rem 1rem',
            fontSize: '0.9rem',
            fontWeight: '500',
            cursor: 'pointer',
            transition: 'all 0.2s ease',
            marginTop: '0.5rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '0.5rem',
            minHeight: '44px'
          }}
          onMouseEnter={(e) => {
            e.target.style.backgroundColor = '#1e40af';
          }}
          onMouseLeave={(e) => {
            e.target.style.backgroundColor = '#1e3a8a';
          }}
          onMouseDown={(e) => {
            e.target.style.transform = 'scale(0.98)';
          }}
          onMouseUp={(e) => {
            e.target.style.transform = 'scale(1)';
          }}
        >
          <span>أضف للسلة</span>
          <FiShoppingBag size={16} />
        </button>
      </div>

      {book.discount_percentage && (
        <div style={{
          position: 'absolute',
          top: '1rem',
          right: '1rem',
          background: '#dc2626',
          color: 'white',
          padding: '0.25rem 0.5rem',
          borderRadius: '4px',
          fontSize: '0.8rem',
          fontWeight: '600'
        }}>
          خصم {book.discount_percentage}%
        </div>
      )}
    </div>
  );
});

export default BookCard;
