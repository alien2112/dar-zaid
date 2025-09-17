import React, { useMemo } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { useCart } from '../contexts/CartContext';
import { useAuth } from '../contexts/AuthContext';
import { apiService } from '../services/api';

const BookDetails = () => {
  const { id } = useParams();
  const { addToCart } = useCart();
  const [book, setBook] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBook = async () => {
      try {
        const response = await apiService.getBooks();
        const foundBook = response.data.items?.find(b => String(b.id) === String(id));
        setBook(foundBook || null);
      } catch (error) {
        console.error('Error fetching book:', error);
        setBook(null);
      } finally {
        setLoading(false);
      }
    };

    fetchBook();
  }, [id]);

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner"></div>
      </div>
    );
  }

  if (!book) {
    return (
      <div className="container" style={{ padding: '3rem 0' }}>
        <h2>الكتاب غير موجود</h2>
        <Link to="/bookstore" className="btn btn-secondary" style={{ marginTop: '1rem' }}>العودة للمتجر</Link>
      </div>
    );
  }

  return (
    <div className="container" style={{ padding: '1rem 0' }}>
      <div style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', 
        gap: '2rem',
        marginBottom: '2rem'
      }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{
            height: '400px',
            background: `url(${book.image_url || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="48">📚</text></svg>'}) no-repeat center center / cover`,
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center', 
            color: 'white', 
            fontSize: '4rem',
            borderRadius: 12,
            boxShadow: '0 8px 25px rgba(0,0,0,0.15)',
            margin: '0 auto',
            maxWidth: '300px'
          }}>
            {!book.image_url && '📚'}
          </div>
        </div>
        <div style={{ padding: '0 1rem' }}>
          <h1 style={{ 
            marginBottom: '0.5rem', 
            fontSize: 'clamp(1.5rem, 4vw, 2rem)',
            lineHeight: 1.3,
            color: '#1e3a8a'
          }}>{book.title}</h1>
          <div style={{ 
            color: '#6b7280', 
            marginBottom: '1rem',
            fontSize: '1.1rem'
          }}>المؤلف: {book.author}</div>
          <div style={{ 
            marginBottom: '1rem',
            lineHeight: 1.6,
            color: '#4a5568'
          }}>{book.description}</div>
          <div style={{ 
            fontSize: '1.5rem', 
            fontWeight: 700, 
            color: '#1e3a8a', 
            marginBottom: '1.5rem' 
          }}>{book.price} ريال</div>
          <button 
            className="btn btn-primary" 
            onClick={() => addToCart(book)}
            style={{
              minHeight: '44px',
              padding: '12px 24px',
              fontSize: '1rem',
              width: '100%'
            }}
          >أضف للسلة</button>
        </div>
      </div>

      <div style={{ marginTop: '2rem', padding: '0 1rem' }}>
        <h2 style={{ 
          marginBottom: '1rem',
          fontSize: '1.5rem',
          color: '#1e3a8a'
        }}>التقييمات</h2>
        <BookReviewsFull bookId={book.id} />
      </div>
    </div>
  );
};

const BookReviewsFull = ({ bookId }) => {
  const { isAuthenticated, user } = useAuth();
  const [reviews, setReviews] = React.useState([]);
  const [average, setAverage] = React.useState(0);
  const [count, setCount] = React.useState(0);
  const [rating, setRating] = React.useState(5);
  const [hoverRating, setHoverRating] = React.useState(0);
  const [isDragging, setIsDragging] = React.useState(false);
  const [comment, setComment] = React.useState('');
  const [submitting, setSubmitting] = React.useState(false);
  const [showModal, setShowModal] = React.useState(false);

  React.useEffect(() => {
    const fetchReviews = async () => {
      try {
        const res = await apiService.getReviews(bookId);
        setReviews(res.data.reviews || []);
        setAverage(res.data.average || 0);
        setCount(res.data.count || 0);
      } catch {}
    };
    fetchReviews();
  }, [bookId]);

  const submitReview = async (e) => {
    e.preventDefault();
    if (!isAuthenticated()) {
      alert('يجب تسجيل الدخول لإضافة تقييم');
      return;
    }
    setSubmitting(true);
    try {
      await apiService.addReview({ book_id: bookId, user_id: user.id, rating, comment });
      const res = await apiService.getReviews(bookId);
      setReviews(res.data.reviews || []);
      setAverage(res.data.average || 0);
      setCount(res.data.count || 0);
      setComment('');
      setRating(5);
      setShowModal(false);
    } catch {
      alert('فشل إرسال التقييم');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 12 }}>
        <span>
          {Array.from({ length: 5 }).map((_, i) => {
            const filled = i < Math.round(average);
            return <span key={i} style={{ color: filled ? '#f59e0b' : '#e5e7eb', fontSize: 18 }}>★</span>;
          })}
        </span>
        <span style={{ fontWeight: 700 }}>{average.toFixed(1)}</span>
        <span style={{ color: '#6b7280' }}>{count} تقييم</span>
      </div>

      {reviews.length === 0 && (
        <div style={{ color: '#6b7280', marginBottom: 12 }}>لا توجد تقييمات بعد.</div>
      )}
      <div style={{ display: 'grid', gap: 8, marginBottom: 16 }}>
        {reviews.map(r => (
          <div key={r.id} style={{ background: '#f9fafb', padding: '0.75rem 1rem', borderRadius: 8 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 6 }}>
              <div style={{ color: '#374151', fontWeight: 600 }}>{r.user_name}</div>
              <div style={{ color: '#f59e0b' }}>⭐ {r.rating}</div>
            </div>
            {r.comment && <div style={{ color: '#374151' }}>{r.comment}</div>}
          </div>
        ))}
      </div>

      <button className="btn btn-secondary" onClick={() => setShowModal(true)}>أضف تقييم</button>

      {showModal && (
        <div className="modal-overlay" onClick={() => !submitting && setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>إضافة تقييم</h3>
            <form onSubmit={submitReview}>
              <div className="form-group">
                <label>التقييم</label>
                <div
                  style={{ display: 'inline-flex', gap: 6, cursor: 'pointer', userSelect: 'none' }}
                  onMouseLeave={() => { setHoverRating(0); setIsDragging(false); }}
                  onMouseUp={() => setIsDragging(false)}
                >
                  {Array.from({ length: 5 }).map((_, i) => {
                    const index = i + 1;
                    const active = (hoverRating || rating) >= index;
                    return (
                      <span
                        key={index}
                        role="button"
                        aria-label={`اختر ${index} نجوم`}
                        onMouseDown={(e) => { e.preventDefault(); setIsDragging(true); setRating(index); }}
                        onMouseEnter={() => setHoverRating(index)}
                        onMouseMove={(e) => { if (isDragging) setRating(index); }}
                        onClick={() => setRating(index)}
                        style={{ color: active ? '#f59e0b' : '#e5e7eb', fontSize: 24 }}
                      >
                        ★
                      </span>
                    );
                  })}
                  <span style={{ marginInlineStart: 8, fontWeight: 700 }}>{rating}</span>
                </div>
              </div>
              <div className="form-group">
                <label>تعليق (اختياري)</label>
                <textarea rows="3" value={comment} onChange={(e) => setComment(e.target.value)} placeholder="اكتب تعليقك"></textarea>
              </div>
              <div className="form-actions">
                <button type="button" className="btn btn-secondary" onClick={() => !submitting && setShowModal(false)}>إلغاء</button>
                <button type="submit" className="btn btn-primary" disabled={submitting}>
                  {submitting ? 'جاري الإرسال...' : 'إرسال التقييم'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default BookDetails;


