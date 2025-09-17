import React, { useState, useEffect, useCallback } from 'react';
import { useDebounce } from '../utils/performance';
import { useCart } from '../contexts/CartContext';
import { Link, useLocation } from 'react-router-dom';
import { apiService } from '../services/api';
import { useAuth } from '../contexts/AuthContext';

import BookCard from '../components/BookCard';
import BookFilters from '../components/BookFilters';
import '../styles/BookFilters.css';

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
  const [authors, setAuthors] = useState([]);
  const [publishers, setPublishers] = useState([]);
  const [priceRange, setPriceRange] = useState({ min: 0, max: Infinity });
  
  // New filter state
  const [filters, setFilters] = useState({
    categories: [],
    priceRange: { min: 0, max: Infinity },
    authors: [],
    publishers: [],
    customFilters: {}
  });
  const [sortBy, setSortBy] = useState('default');
  const [showFilters, setShowFilters] = useState(false);
  
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

  // Load filter options
  useEffect(() => {
    const loadFilterOptions = async () => {
      try {
        const response = await apiService.get('/filter_options');
        const options = response.data;
        console.log('Filter options response:', options);
        console.log('Categories from API:', options.categories);
        setCategories(['الكل', ...(options.categories || [])]);
        setAuthors(options.authors || []);
        setPublishers(options.publishers || []);
        setPriceRange(options.priceRange || { min: 0, max: Infinity });
      } catch (error) {
        console.error('Failed to load filter options:', error);
      }
    };
    loadFilterOptions();
  }, []);

  useEffect(() => {
    const fetchPage = async () => {
      setLoading(true);
      try {
        const params = {
          page,
          limit,
          search: searchTerm || undefined,
          category: selectedCategory !== 'الكل' ? selectedCategory : undefined,
          filters: JSON.stringify(filters),
          sort: sortBy
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
  }, [page, limit, searchTerm, selectedCategory, filters, sortBy]);

  // Remove the old client-side filtering since we're now doing it server-side
  // useEffect(() => {
  //   let filtered = books;
  //   // ... old filtering logic removed
  // }, [books, searchTerm, selectedCategory]);

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div style={{ padding: '1rem 0', minHeight: '70vh' }}>
      <div className="container">
        <h1 style={{
          textAlign: 'center',
          marginBottom: '2rem',
          fontSize: 'clamp(1.8rem, 4vw, 2.5rem)',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif',
          lineHeight: 1.3
        }}>
          متجر الكتب
        </h1>

        {/* Search Bar */}
        <div className="search-filter-bar" style={{ marginBottom: '2rem' }}>
          <input
            type="text"
            placeholder="ابحث عن كتاب أو مؤلف..."
            className="search-input"
            defaultValue={searchTerm}
            onChange={(e) => debouncedSetSearch(e.target.value)}
            style={{ 
              fontSize: '16px', // Prevents zoom on iOS
              minHeight: '44px',
              padding: '12px 16px'
            }}
          />
          {/* Mobile Filter Toggle Button */}
          <button
            className="mobile-filter-toggle"
            onClick={() => setShowFilters(!showFilters)}
            style={{
              display: 'none',
              background: '#1e3a8a',
              color: 'white',
              border: 'none',
              padding: '12px 20px',
              borderRadius: '8px',
              fontSize: '16px',
              fontWeight: '600',
              cursor: 'pointer',
              minHeight: '44px',
              marginLeft: '1rem'
            }}
          >
            {showFilters ? 'إخفاء الفلاتر' : 'عرض الفلاتر'}
          </button>
        </div>

        {/* Main Content with Filters and Books */}
        <div className="bookstore-layout">
          {/* Filters Sidebar */}
          <div className={`filters-sidebar ${showFilters ? 'mobile-show' : ''}`}>
            <BookFilters
              filters={filters}
              onFiltersChange={setFilters}
              sortBy={sortBy}
              onSortChange={setSortBy}
              categories={categories.filter(cat => cat !== 'الكل')}
              authors={authors}
              publishers={publishers}
            />
          </div>

          {/* Books Grid */}
          <div className="books-content">
            <div className="books-grid">
              {filteredBooks.map((book) => (
                <BookCard key={book.id} book={book} />
              ))}
            </div>
          </div>
        </div>

        {/* No Results Message */}
        {filteredBooks.length === 0 && !loading && (
          <div style={{
            textAlign: 'center',
            padding: '3rem 1rem',
            color: '#6b7280'
          }}>
            <div style={{ fontSize: '2.5rem', marginBottom: '1rem' }}>📚</div>
            <h3 style={{ marginBottom: '1rem', color: '#374151', fontSize: '1.3rem' }}>لم نجد أي كتب</h3>
            <p style={{ fontSize: '0.95rem' }}>جرب البحث بكلمات مختلفة أو اختر تصنيفاً آخر</p>
          </div>
        )}

        {/* Pagination */}
        {total > 0 && (
          <div style={{ 
            display: 'flex', 
            flexDirection: 'column',
            alignItems: 'center', 
            gap: '1rem', 
            marginTop: '2rem',
            padding: '0 1rem'
          }}>
            <div style={{ 
              display: 'flex', 
              justifyContent: 'center', 
              alignItems: 'center', 
              gap: '0.5rem',
              flexWrap: 'wrap'
            }}>
              <button 
                className="btn btn-secondary" 
                disabled={page === 1} 
                onClick={() => debouncedSetPage(Math.max(1, page - 1))}
                style={{ minHeight: '44px', padding: '12px 20px' }}
              >
                السابق
              </button>
              <span style={{ color: '#6b7280', fontSize: '0.9rem', padding: '0 0.5rem' }}>
                صفحة {page} من {Math.max(1, Math.ceil(total / limit))}
              </span>
              <button 
                className="btn btn-secondary" 
                disabled={page >= Math.ceil(total / limit)} 
                onClick={() => debouncedSetPage(page + 1)}
                style={{ minHeight: '44px', padding: '12px 20px' }}
              >
                التالي
              </button>
            </div>
            <select 
              className="filter-select" 
              value={limit} 
              onChange={(e) => { setPage(1); setLimit(parseInt(e.target.value)); }}
              style={{ 
                fontSize: '16px',
                minHeight: '44px',
                padding: '12px 16px',
                minWidth: '120px'
              }}
            >
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