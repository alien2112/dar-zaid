import React from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../contexts/CartContext';

const BookCard = React.memo(({ book }) => {
  const { addToCart } = useCart();

  return (
    <div className="book-card">
      <div style={{
        height: '300px',
        background: `url(${book.image_url || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="24">No Image</text></svg>'}) no-repeat center center / cover`,
        position: 'relative',
        overflow: 'hidden',
        backgroundColor: '#f3f4f6'
      }}>
        <div style={{
          position: 'absolute',
          top: '10px',
          right: '10px',
          background: book.stock_quantity > 0 ? '#10b981' : '#ef4444',
          color: 'white',
          padding: '0.25rem 0.75rem',
          borderRadius: '12px',
          fontSize: '0.75rem',
          fontWeight: '600'
        }}>
          {book.stock_quantity > 0 ? `متوفر (${book.stock_quantity})` : 'نفد المخزون'}
        </div>
      </div>

      <div className="book-content">
        <h3 className="book-title"><Link to={`/book/${book.id}`}>{book.title}</Link></h3>

        <p className="book-author">المؤلف: {book.author}</p>

        <p className="book-description">{book.description}</p>

        <div className="book-price">{book.price} ريال</div>

        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '1rem'
        }}>
          <span style={{
            background: '#f3f4f6',
            color: '#6b7280',
            padding: '0.25rem 0.75rem',
            borderRadius: '20px',
            fontSize: '0.8rem',
            fontWeight: '500'
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
            cursor: book.stock_quantity === 0 ? 'not-allowed' : 'pointer'
          }}
        >
          {book.stock_quantity > 0 ? 'أضف للسلة' : 'نفد المخزون'}
        </button>
      </div>
    </div>
  );
});

export default BookCard;
