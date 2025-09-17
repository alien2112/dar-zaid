import React, { useState, useEffect, useCallback } from 'react';
import { useDebounce } from '../utils/performance';
import { useCart } from '../contexts/CartContext';
import { Link, useLocation } from 'react-router-dom';
import { apiService } from '../services/api';
import { useAuth } from '../contexts/AuthContext';

import BookCard from '../components/BookCard';

const BookStore = () => {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(12);
  const [total, setTotal] = useState(0);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('الكل');
  const [filteredBooks, setFilteredBooks] = useState([]);
  const [categories, setCategories] = useState(['الكل']);
  const location = useLocation();
  const { addToCart } = useCart();
  const { user, isAuthenticated } = useAuth();

  

  // Debounced handlers to avoid frequent re-fetches
  const debouncedSetSearch = useDebounce((value) => {
    setSearchTerm(value);
  }, 600);
  const debouncedSetCategory = useDebounce((value) => {
    setSelectedCategory(value);
  }, 0);
  const debouncedSetPage = useDebounce((value) => {
    setPage(value);
  }, 0);

  useEffect(() => {
    // Read category filter from query string
    const params = new URLSearchParams(location.search);
    const cat = params.get('category');
    if (cat) {
      setSelectedCategory(cat);
    }
  }, [location.search]);

  useEffect(() => {
    const fetchPage = async () => {
      setLoading(true);
      try {
        const params = {
          page,
          limit,
          search: searchTerm || undefined,
          category: selectedCategory !== 'الكل' ? selectedCategory : undefined
        };
        const res = await apiService.getBooks(params);
        const items = res.data.items || [];
        setBooks(items);
        setFilteredBooks(items);
        setTotal(typeof res.data.total === 'number' ? res.data.total : 0);
      } catch {
        setBooks([]);
        setFilteredBooks([]);
        setTotal(0);
      } finally {
        setLoading(false);
      }
    };
    fetchPage();
  }, [page, limit, searchTerm, selectedCategory]);

  useEffect(() => {
    let filtered = books;

    if (selectedCategory !== 'الكل') {
      filtered = filtered.filter(book => book.category === selectedCategory);
    }

    if (searchTerm) {
      filtered = filtered.filter(book =>
        book.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        book.author.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    setFilteredBooks(filtered);
  }, [books, searchTerm, selectedCategory]);

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div style={{ padding: '2rem 0', minHeight: '70vh' }}>
      <div className="container">
        <h1 style={{
          textAlign: 'center',
          marginBottom: '3rem',
          fontSize: '2.5rem',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif'
        }}>
          متجر الكتب
        </h1>

        {/* Search and Filter Bar */}
        <div className="search-filter-bar">
          <input
            type="text"
            placeholder="ابحث عن كتاب أو مؤلف..."
            className="search-input"
            defaultValue={searchTerm}
            onChange={(e) => debouncedSetSearch(e.target.value)}
          />
          <select
            className="filter-select"
            value={selectedCategory}
            onChange={(e) => debouncedSetCategory(e.target.value)}
          >
            {categories.map(category => (
              <option key={category} value={category}>{category}</option>
            ))}
          </select>
        </div>

        <div className="books-grid">
          {filteredBooks.map((book) => (
            <BookCard key={book.id} book={book} />
          ))}
        </div>

        {/* No Results Message */}
        {filteredBooks.length === 0 && !loading && (
          <div style={{
            textAlign: 'center',
            padding: '4rem 2rem',
            color: '#6b7280'
          }}>
            <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>📚</div>
            <h3 style={{ marginBottom: '1rem', color: '#374151' }}>لم نجد أي كتب</h3>
            <p>جرب البحث بكلمات مختلفة أو اختر تصنيفاً آخر</p>
          </div>
        )}

        {/* Pagination */}
        {total > 0 && (
          <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 12, marginTop: '2rem' }}>
            <button className="btn btn-secondary" disabled={page === 1} onClick={() => debouncedSetPage(Math.max(1, page - 1))}>السابق</button>
            <span style={{ color: '#6b7280' }}>
              صفحة {page} من {Math.max(1, Math.ceil(total / limit))}
            </span>
            <button className="btn btn-secondary" disabled={page >= Math.ceil(total / limit)} onClick={() => debouncedSetPage(page + 1)}>التالي</button>
            <select className="filter-select" value={limit} onChange={(e) => { setPage(1); setLimit(parseInt(e.target.value)); }}>
              {[6,12,24,48].map(v => <option key={v} value={v}>{v}/صفحة</option>)}
            </select>
          </div>
        )}
      </div>
    </div>
  );
};

export default BookStore;

// Rating summary only (BookStore list)
const BookRatingSummary = ({ bookId }) => {
  const [average, setAverage] = useState(0);
  const [count, setCount] = useState(0);

  useEffect(() => {
    const fetchSummary = async () => {
      try {
        const res = await apiService.getReviews(bookId);
        setAverage(res.data.average || 0);
        setCount(res.data.count || 0);
      } catch {}
    };
    fetchSummary();
  }, [bookId]);

  return (
    <div style={{ marginTop: '0.5rem', display: 'flex', alignItems: 'center', gap: 6 }}>
      <span aria-label={`التقييم ${average} من 5`}>
        {Array.from({ length: 5 }).map((_, i) => {
          const filled = i < Math.round(average);
          return <span key={i} style={{ color: filled ? '#f59e0b' : '#e5e7eb', fontSize: 14 }}>★</span>;
        })}
      </span>
      <span style={{ fontWeight: 700, fontSize: 13 }}>{average.toFixed(1)}</span>
      <span style={{ color: '#6b7280', fontSize: 12 }}>{count}</span>
    </div>
  );
};

// Reviews widget
const BookReviews = ({ bookId }) => {
  const { isAuthenticated, user } = useAuth();
  const [reviews, setReviews] = useState([]);
  const [average, setAverage] = useState(0);
  const [count, setCount] = useState(0);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    const fetchReviews = async () => {
      try {
        const res = await apiService.getReviews(bookId);
        setReviews(res.data.reviews || []);
        setAverage(res.data.average || 0);
        setCount(res.data.count || 0);
      } catch (e) {
        // ignore
      }
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
      await apiService.addReview({
        book_id: bookId,
        user_id: user.id,
        rating,
        comment
      });
      // refresh
      const res = await apiService.getReviews(bookId);
      setReviews(res.data.reviews || []);
      setAverage(res.data.average || 0);
      setCount(res.data.count || 0);
      setComment('');
      setRating(5);
    } catch (e) {
      alert('فشل إرسال التقييم');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div style={{ marginTop: '1rem' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
        <span style={{ display: 'inline-flex', gap: 2 }} aria-label={`التقييم ${average} من 5`}>
          {Array.from({ length: 5 }).map((_, i) => {
            const filled = i < Math.round(average);
            return (
              <span key={i} style={{ color: filled ? '#f59e0b' : '#e5e7eb', fontSize: 16 }}>★</span>
            );
          })}
        </span>
        <span style={{ fontWeight: 700 }}>{average.toFixed(1)}</span>
        <span style={{ color: '#6b7280' }}>{count} تقييم</span>
      </div>
      {reviews.slice(0, 2).map(r => (
        <div key={r.id} style={{ background: '#f9fafb', padding: '0.5rem 0.75rem', borderRadius: 8, marginBottom: 6 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, color: '#6b7280' }}>
            <span>{r.user_name}</span>
            <span>⭐ {r.rating}</span>
          </div>
          {r.comment && <div style={{ marginTop: 4 }}>{r.comment}</div>}
        </div>
      ))}

      <form onSubmit={submitReview} className="modern-form" style={{ padding: 0, boxShadow: 'none', marginTop: '0.5rem' }}>
        <div className="form-grid">
          <div className="form-group">
            <label>التقييم</label>
            <select value={rating} onChange={(e) => setRating(parseInt(e.target.value))}>
              {[5,4,3,2,1].map(v => <option key={v} value={v}>{v}</option>)}
            </select>
          </div>
          <div className="form-group" style={{ gridColumn: '2 / -1' }}>
            <label>تعليق (اختياري)</label>
            <input type="text" value={comment} onChange={(e) => setComment(e.target.value)} placeholder="اكتب تعليقك" />
          </div>
        </div>
        <button type="submit" className="btn btn-secondary" disabled={submitting}>
          {submitting ? 'جاري الإرسال...' : 'إضافة تقييم'}
        </button>
      </form>
    </div>
  );
};