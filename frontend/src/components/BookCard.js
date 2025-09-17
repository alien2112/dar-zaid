import React from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../contexts/CartContext';

const BookCard = React.memo(({ book }) => {
  const { addToCart } = useCart();

  return (
    <div className="book-card" style={{ 
      background: 'white',
      borderRadius: '12px',
      overflow: 'hidden',
      boxShadow: '0 4px 20px rgba(0,0,0,0.08)',
      transition: 'all 0.3s ease',
      border: '1px solid #f0f0f0',
      height: '100%',
      display: 'flex',
      flexDirection: 'column'
    }}>
      <div style={{
        height: '250px',
        background: `url(${book.image_url || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="24">No Image</text></svg>'}) no-repeat center center / cover`,
        position: 'relative',
        overflow: 'hidden',
        backgroundColor: '#f3f4f6'
      }}>
        <div style={{
          position: 'absolute',
          top: '8px',
          right: '8px',
          background: book.stock_quantity > 0 ? '#10b981' : '#ef4444',
          color: 'white',
          padding: '0.25rem 0.5rem',
          borderRadius: '8px',
          fontSize: '0.7rem',
          fontWeight: '600',
          maxWidth: 'calc(100% - 16px)',
          textAlign: 'center'
        }}>
          {book.stock_quantity > 0 ? `متوفر (${book.stock_quantity})` : 'نفد المخزون'}
        </div>
      </div>

      <div className="book-content" style={{ 
        padding: '1rem',
        display: 'flex',
        flexDirection: 'column',
        flex: 1
      }}>
        <h3 className="book-title" style={{ 
          fontSize: '1.1rem',
          fontWeight: '600',
          color: '#2c3e50',
          marginBottom: '0.5rem',
          lineHeight: 1.4,
          display: '-webkit-box',
          WebkitLineClamp: 2,
          'line-clamp': 2,
          WebkitBoxOrient: 'vertical',
          overflow: 'hidden'
        }}>
          <Link to={`/book/${book.id}`} style={{ color: 'inherit', textDecoration: 'none' }}>{book.title}</Link>
        </h3>

        <p className="book-author" style={{ 
          color: '#64748b',
          fontSize: '0.9rem',
          marginBottom: '0.75rem',
          display: '-webkit-box',
          WebkitLineClamp: 1,
          'line-clamp': 1,
          WebkitBoxOrient: 'vertical',
          overflow: 'hidden'
        }}>
          المؤلف: {book.author}
        </p>

        <p className="book-description" style={{ 
          color: '#6b7280',
          fontSize: '0.85rem',
          lineHeight: 1.5,
          marginBottom: '1rem',
          display: '-webkit-box',
          WebkitLineClamp: 3,
          'line-clamp': 3,
          WebkitBoxOrient: 'vertical',
          overflow: 'hidden',
          flex: 1
        }}>
          {book.description}
        </p>

        <div className="book-price" style={{ 
          fontSize: '1.2rem',
          fontWeight: '700',
          color: '#1e3a8a',
          marginBottom: '0.75rem'
        }}>
          {book.price} ريال
        </div>

        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '1rem'
        }}>
          <span style={{
            background: '#f3f4f6',
            color: '#6b7280',
            padding: '0.25rem 0.5rem',
            borderRadius: '12px',
            fontSize: '0.75rem',
            fontWeight: '500',
            maxWidth: '100%',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap'
          }}>
            {book.category}
          </span>
        </div>

        <button
          className={`btn ${book.stock_quantity > 0 ? 'btn-primary' : 'btn-secondary'}`}
          disabled={book.stock_quantity === 0}
          onClick={() => addToCart(book)}
          style={{
            width: '100%',
            opacity: book.stock_quantity === 0 ? 0.6 : 1,
            cursor: book.stock_quantity === 0 ? 'not-allowed' : 'pointer',
            minHeight: '44px',
            fontSize: '0.9rem',
            padding: '12px 16px'
          }}
        >
          {book.stock_quantity > 0 ? 'أضف للسلة' : 'نفد المخزون'}
        </button>
      </div>
    </div>
  );
});

export default BookCard;
