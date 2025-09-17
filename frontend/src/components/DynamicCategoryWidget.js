import React from 'react';
import { Link } from 'react-router-dom';

const DynamicCategoryWidget = ({ category }) => {
  if (!category || !category.books || category.books.length === 0) {
    return null;
  }

  const renderBookCard = (book) => (
    <div key={book.id} className="book-card">
      <div
        className="book-image"
        style={{
          height: '250px',
          background: `url(${book.image_url || '/images/book-placeholder.jpg'}) no-repeat center center/cover`,
          borderRadius: '8px',
          position: 'relative'
        }}
      >
        {book.discount_percentage > 0 && (
          <div className="discount-badge" style={{
            position: 'absolute',
            top: '10px',
            right: '10px',
            background: '#e74c3c',
            color: 'white',
            padding: '4px 8px',
            borderRadius: '4px',
            fontSize: '0.8rem',
            fontWeight: 'bold'
          }}>
            خصم {book.discount_percentage}%
          </div>
        )}
        {book.is_featured && (
          <div className="featured-badge" style={{
            position: 'absolute',
            top: '10px',
            left: '10px',
            background: '#f39c12',
            color: 'white',
            padding: '4px 8px',
            borderRadius: '4px',
            fontSize: '0.8rem',
            fontWeight: 'bold'
          }}>
            مميز
          </div>
        )}
      </div>
      <div className="book-content" style={{ padding: '1rem' }}>
        <h4 className="book-title" style={{
          fontSize: '1.1rem',
          fontWeight: '600',
          marginBottom: '0.5rem',
          lineHeight: '1.4'
        }}>
          {book.title}
        </h4>
        <p className="book-author" style={{
          color: '#6b7280',
          marginBottom: '0.5rem',
          fontSize: '0.9rem'
        }}>
          {book.author}
        </p>
        <div className="book-price" style={{
          display: 'flex',
          alignItems: 'center',
          gap: '0.5rem',
          marginBottom: '1rem'
        }}>
          <span style={{
            fontSize: '1.2rem',
            fontWeight: '700',
            color: '#1e3a8a'
          }}>
            {book.price} ريال
          </span>
          {book.original_price && book.original_price > book.price && (
            <span style={{
              fontSize: '0.9rem',
              color: '#9ca3af',
              textDecoration: 'line-through'
            }}>
              {book.original_price} ريال
            </span>
          )}
        </div>
        <Link
          to={`/book/${book.id}`}
          className="btn btn-primary"
          style={{ width: '100%', textAlign: 'center' }}
        >
          عرض التفاصيل
        </Link>
      </div>
    </div>
  );

  const renderListItem = (book) => (
    <div key={book.id} className="list-item" style={{
      display: 'flex',
      gap: '1rem',
      padding: '1rem',
      borderBottom: '1px solid #e5e7eb',
      alignItems: 'center'
    }}>
      <div
        style={{
          width: '80px',
          height: '100px',
          background: `url(${book.image_url || '/images/book-placeholder.jpg'}) no-repeat center center/cover`,
          borderRadius: '4px',
          flexShrink: 0
        }}
      />
      <div style={{ flex: 1 }}>
        <h4 style={{ fontSize: '1rem', fontWeight: '600', marginBottom: '0.25rem' }}>
          {book.title}
        </h4>
        <p style={{ color: '#6b7280', fontSize: '0.85rem', marginBottom: '0.5rem' }}>
          {book.author}
        </p>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <span style={{ fontSize: '1rem', fontWeight: '700', color: '#1e3a8a' }}>
            {book.price} ريال
          </span>
          {book.original_price && book.original_price > book.price && (
            <span style={{ fontSize: '0.8rem', color: '#9ca3af', textDecoration: 'line-through' }}>
              {book.original_price} ريال
            </span>
          )}
        </div>
      </div>
      <Link to={`/book/${book.id}`} className="btn btn-primary btn-small">
        عرض
      </Link>
    </div>
  );

  const renderCarousel = () => (
    <div className="carousel-container" style={{
      overflowX: 'auto',
      scrollBehavior: 'smooth',
      paddingBottom: '1rem'
    }}>
      <div style={{
        display: 'flex',
        gap: '1rem',
        minWidth: 'fit-content',
        paddingBottom: '1rem'
      }}>
        {category.books.map(book => (
          <div key={book.id} style={{ minWidth: '250px' }}>
            {renderBookCard(book)}
          </div>
        ))}
      </div>
    </div>
  );

  const renderBanner = () => (
    <div className="banner-widget" style={{
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      borderRadius: '12px',
      padding: '2rem',
      color: 'white',
      textAlign: 'center',
      marginBottom: '2rem'
    }}>
      <h3 style={{ fontSize: '1.8rem', marginBottom: '1rem', fontWeight: '700' }}>
        {category.name}
      </h3>
      {category.description && (
        <p style={{ fontSize: '1.1rem', marginBottom: '2rem', opacity: 0.9 }}>
          {category.description}
        </p>
      )}
      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
        gap: '1rem',
        maxWidth: '800px',
        margin: '0 auto'
      }}>
        {category.books.slice(0, 4).map(book => (
          <div key={book.id} style={{
            background: 'rgba(255, 255, 255, 0.1)',
            borderRadius: '8px',
            padding: '1rem',
            backdropFilter: 'blur(10px)'
          }}>
            <h4 style={{ fontSize: '1rem', marginBottom: '0.5rem' }}>
              {book.title}
            </h4>
            <p style={{ fontSize: '0.85rem', opacity: 0.8, marginBottom: '0.5rem' }}>
              {book.author}
            </p>
            <div style={{ fontSize: '1.1rem', fontWeight: 'bold' }}>
              {book.price} ريال
            </div>
          </div>
        ))}
      </div>
      <Link
        to="/bookstore"
        className="btn"
        style={{
          background: 'white',
          color: '#667eea',
          marginTop: '2rem',
          fontWeight: '600'
        }}
      >
        عرض جميع الكتب
      </Link>
    </div>
  );

  const renderGrid = () => (
    <div className="books-grid" style={{
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))',
      gap: '1.5rem'
    }}>
      {category.books.map(renderBookCard)}
    </div>
  );

  const renderWidget = () => {
    switch (category.widget_style) {
      case 'carousel':
        return renderCarousel();
      case 'list':
        return (
          <div className="list-widget">
            {category.books.map(renderListItem)}
          </div>
        );
      case 'banner':
        return renderBanner();
      case 'grid':
      default:
        return renderGrid();
    }
  };

  return (
    <section style={{ marginBottom: '3rem' }}>
      {category.widget_style !== 'banner' && (
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '2rem'
        }}>
          <h3 style={{
            borderBottom: '2px solid #1e3a8a',
            paddingBottom: '0.5rem',
            fontSize: '1.8rem',
            fontWeight: '700'
          }}>
            {category.name}
          </h3>
          {category.description && (
            <p style={{
              color: '#6b7280',
              fontSize: '0.95rem',
              margin: 0
            }}>
              {category.description}
            </p>
          )}
        </div>
      )}

      {renderWidget()}

      {category.widget_style !== 'banner' && category.books.length >= category.max_items && (
        <div style={{ textAlign: 'center', marginTop: '2rem' }}>
          <Link
            to="/bookstore"
            className="btn btn-secondary"
            style={{ padding: '0.75rem 2rem' }}
          >
            عرض المزيد من {category.name}
          </Link>
        </div>
      )}
    </section>
  );
};

export default DynamicCategoryWidget;