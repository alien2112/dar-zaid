import React, { useState, useEffect, useMemo, useCallback } from 'react';
import * as XLSX from 'xlsx';
import { Link } from 'react-router-dom';
import { categoryService } from '../services/categoryService';
import { apiService } from '../services/api';
import { useDebounce } from '../utils/performance';

// Book Form Modal Component (moved above to avoid HMR/TDZ issues)
const BookFormModal = ({ book, categories, onSave, onCancel }) => {
  const [formData, setFormData] = useState({
    title: book?.title || '',
    author: book?.author || '',
    description: book?.description || '',
    price: book?.price || '',
    category: book?.category || 'أدب',
    image_url: book?.image_url || '',
    stock_quantity: book?.stock_quantity || '',
    isbn: book?.isbn || '',
    published_date: book?.published_date || '',
    is_chosen: book?.is_chosen || false,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    const bookData = {
      ...formData,
      price: parseFloat(formData.price),
      stock_quantity: parseInt(formData.stock_quantity),
      is_chosen: !!formData.is_chosen,
    };

    if (book) {
      bookData.id = book.id;
    }

    onSave(bookData);
  };

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0, 0, 0, 0.5)',
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      zIndex: 1000,
      padding: '1rem'
    }}>
      <div style={{
        background: 'white',
        borderRadius: '12px',
        padding: '2rem',
        width: '100%',
        maxWidth: '600px',
        maxHeight: '90vh',
        overflow: 'auto'
      }}>
        <h2 style={{
          marginBottom: '2rem',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif'
        }}>
          {book ? 'تعديل الكتاب' : 'إضافة كتاب جديد'}
        </h2>

        <form onSubmit={handleSubmit} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
          <div className="form-grid">
            <div className="form-group">
              <label>عنوان الكتاب</label>
              <input
                type="text"
                required
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>المؤلف</label>
              <input
                type="text"
                required
                value={formData.author}
                onChange={(e) => setFormData({ ...formData, author: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>السعر (ريال)</label>
              <input
                type="number"
                step="0.01"
                required
                value={formData.price}
                onChange={(e) => setFormData({ ...formData, price: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>التصنيف</label>
              <select
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              >
                {categories.map(category => (
                  <option key={category} value={category}>{category}</option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label>كمية المخزون</label>
              <input
                type="number"
                required
                value={formData.stock_quantity}
                onChange={(e) => setFormData({ ...formData, stock_quantity: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>ISBN</label>
              <input
                type="text"
                value={formData.isbn}
                onChange={(e) => setFormData({ ...formData, isbn: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>تاريخ النشر</label>
              <input
                type="date"
                value={formData.published_date}
                onChange={(e) => setFormData({ ...formData, published_date: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>صورة الغلاف</label>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '0.5rem' }}>
                <input
                  type="url"
                  placeholder="رابط الصورة (اختياري)"
                  value={formData.image_url}
                  onChange={(e) => setFormData({ ...formData, image_url: e.target.value })}
                />
                <input
                  type="file"
                  accept="image/*"
                  onChange={async (e) => {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;
                    const form = new FormData();
                    form.append('image', file);
                    try {
                      const res = await apiService.uploadImage(form);
                      setFormData({ ...formData, image_url: res.data.url });
                    } catch (err) {
                      console.error('Upload failed:', err);
                      alert('فشل رفع الصورة. See console for details.');
                    }
                  }}
                />
              </div>
            </div>
          </div>

          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label>وصف الكتاب</label>
            <textarea
              rows="4"
              required
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            />
          </div>

          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <input
                type="checkbox"
                checked={formData.is_chosen}
                onChange={(e) => setFormData({ ...formData, is_chosen: e.target.checked })}
                style={{ width: 'auto' }}
              />
              هل هو من الكتب المختارة؟
            </label>
          </div>

          <div style={{
            display: 'flex',
            gap: '1rem',
            justifyContent: 'flex-end',
            marginTop: '2rem'
          }}>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onCancel}
            >
              إلغاء
            </button>
            <button type="submit" className="btn btn-primary">
              {book ? 'تحديث' : 'إضافة'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const AdminDashboard = () => {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingBook, setEditingBook] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('الكل');
  const [filteredBooks, setFilteredBooks] = useState([]);
  const [categories, setCategories] = useState(['الكل']);
  const [showImport, setShowImport] = useState(false);
  const [importRows, setImportRows] = useState([]);
  const [importResult, setImportResult] = useState(null);

  // Debounced search for better performance
  const debouncedSearch = useDebounce((term, category, booksToFilter) => {
    let filtered = booksToFilter;

    if (category !== 'الكل') {
      filtered = filtered.filter(book => book.category === category);
    }

    if (term) {
      filtered = filtered.filter(book =>
        book.title.toLowerCase().includes(term.toLowerCase()) ||
        book.author.toLowerCase().includes(term.toLowerCase()) ||
        (book.isbn && book.isbn.includes(term))
      );
    }

    setFilteredBooks(filtered);
  }, 300);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await apiService.getBooks({ page: 1, limit: 100 });
        const items = res.data.items || [];
        setBooks(items);
        setFilteredBooks(items);
        // load categories from backend
        try {
          const catsRes = await categoryService.getCategories();
          const list = Array.isArray(catsRes.data) ? catsRes.data : (catsRes.data && Array.isArray(catsRes.data.categories) ? catsRes.data.categories : []);
          let names = list.map(c => c.name).filter(Boolean);
          if (names.length === 0) {
            // Fallback: derive categories from current books
            const derived = Array.from(new Set(items.map(b => b.category).filter(Boolean)));
            names = derived;
          }
          setCategories(['الكل', ...names]);
        } catch {
          const derived = Array.from(new Set(items.map(b => b.category).filter(Boolean)));
          setCategories(['الكل', ...derived]);
        }
      } catch (e) {
        setError('فشل تحميل الكتب');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  useEffect(() => {
    debouncedSearch(searchTerm, selectedCategory, books);
  }, [books, searchTerm, selectedCategory, debouncedSearch]);

  // Memoized stats calculation for better performance
  const stats = useMemo(() => ({
    totalBooks: books.length,
    totalValue: books.reduce((sum, book) => sum + (book.price * book.stock_quantity), 0),
    lowStock: books.filter(book => book.stock_quantity < 10).length,
    outOfStock: books.filter(book => book.stock_quantity === 0).length
  }), [books]);

  // Memoized handlers for better performance
  const handleAddBook = useCallback(async (bookData) => {
    try {
      const res = await apiService.addBook(bookData);
      const created = { ...bookData, id: res.data.id };
      setBooks(prev => [created, ...prev]);
      setShowAddForm(false);
    } catch (e) {
      setError('فشل إضافة الكتاب');
    }
  }, []);

  const handleEditBook = useCallback(async (bookData) => {
    try {
      await apiService.updateBook(bookData.id, bookData);
      setBooks(prev => prev.map(b => (b.id === bookData.id ? { ...b, ...bookData } : b)));
      setEditingBook(null);
    } catch (e) {
      setError('فشل تحديث الكتاب');
    }
  }, []);

  const handleDeleteBook = useCallback(async (bookId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا الكتاب؟')) return;
    try {
      await apiService.deleteBook(bookId);
      setBooks(prev => prev.filter(book => book.id !== bookId));
    } catch {
      setError('فشل حذف الكتاب');
    }
  }, []);

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="admin-dashboard">
      <div className="container">
        {/* Dashboard Header */}
        <div className="dashboard-header">
          <h1 style={{
            fontSize: '2.5rem',
            color: '#1e3a8a',
            fontFamily: 'Amiri, serif',
            margin: 0
          }}>
            لوحة الإدارة
          </h1>
          <div>
            <Link to="/admin/settings" className="btn btn-secondary" style={{ marginLeft: '1rem' }}>
              الإعدادات
            </Link>
            <Link to="/admin/reports" className="btn btn-secondary" style={{ marginLeft: '1rem' }}>
              التقارير
            </Link>
            <button
              className="btn btn-primary"
              onClick={() => setShowAddForm(true)}
            >
              إضافة كتاب جديد
            </button>
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="dashboard-stats">
          <div className="stat-card">
            <div className="stat-number">{stats.totalBooks}</div>
            <div className="stat-label">إجمالي الكتب</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' }}>
            <div className="stat-number">{stats.totalValue.toFixed(2)}</div>
            <div className="stat-label">القيمة الإجمالية (ريال)</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' }}>
            <div className="stat-number">{stats.lowStock}</div>
            <div className="stat-label">مخزون منخفض</div>
          </div>
          <div className="stat-card" style={{ background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' }}>
            <div className="stat-number">{stats.outOfStock}</div>
            <div className="stat-label">نفد المخزون</div>
          </div>
        </div>

        <div className="card">
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <h2 style={{ marginBottom: '1rem' }}>إدارة الكتب</h2>
            <div>
              <button className="btn btn-secondary" style={{ marginLeft: '0.5rem' }} onClick={() => setShowImport(v => !v)}>
                {showImport ? 'إغلاق الاستيراد' : 'استيراد (CSV/XLSX)'}
              </button>
              <button
                className="btn btn-primary"
                onClick={() => setShowAddForm(true)}
                style={{ marginLeft: '0.5rem' }}
              >
                إضافة كتاب جديد
              </button>
            </div>
          </div>

          {showImport && (
            <div className="card" style={{ marginBottom: '1rem', background: '#f9fafb' }}>
              <h3 style={{ marginBottom: '0.5rem' }}>استيراد الكتب من ملف</h3>
              <p style={{ color: '#6b7280', marginBottom: '0.5rem' }}>صيغة الأعمدة: title, author, description, price, category, image_url, isbn, published_date, stock_quantity</p>
              <input type="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" onChange={async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                const data = await file.arrayBuffer();
                const wb = XLSX.read(data);
                const ws = wb.Sheets[wb.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(ws, { defval: '' });
                setImportRows(rows);
              }} />
              {importRows.length > 0 && (
                <div style={{ marginTop: '1rem' }}>
                  <div style={{ maxHeight: '220px', overflow: 'auto', border: '1px solid #e5e7eb', borderRadius: '8px', padding: '0.5rem', background: 'white' }}>
                    <pre style={{ fontSize: '0.8rem', margin: 0 }}>{JSON.stringify(importRows.slice(0, 10), null, 2)}{importRows.length > 10 ? '\n... ' + (importRows.length - 10) + ' rows more' : ''}</pre>
                  </div>
                  <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.75rem' }}>
                    <button className="btn btn-primary" onClick={async () => {
                      try {
                        const res = await apiService.post('/books_import', { rows: importRows });
                        setImportResult(res.data);
                        // refresh books
                        const reload = await apiService.getBooks({ page: 1, limit: 100 });
                        const items = reload.data.items || [];
                        setBooks(items);
                        setFilteredBooks(items);
                      } catch (e) {
                        setImportResult({ success: false, error: 'فشل الاستيراد' });
                      }
                    }}>استيراد الآن</button>
                    <button className="btn btn-secondary" onClick={() => { setImportRows([]); setImportResult(null); }}>إلغاء</button>
                  </div>
                  {importResult && (
                    <div style={{ marginTop: '0.5rem', color: importResult.success ? '#166534' : '#dc2626' }}>
                      {importResult.success ? `تم إدخال ${importResult.inserted} صف` : 'فشل الاستيراد'}
                      {importResult.errors && importResult.errors.length > 0 && (
                        <details style={{ marginTop: '0.25rem' }}>
                          <summary>أخطاء</summary>
                          <pre style={{ fontSize: '0.8rem' }}>{JSON.stringify(importResult.errors.slice(0, 10), null, 2)}</pre>
                        </details>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          )}
          {/* Search and Filter */}
          <div className="search-filter-bar">
            <input
              type="text"
              placeholder="ابحث عن كتاب أو مؤلف أو ISBN..."
              className="search-input"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <select
              className="filter-select"
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
            >
              {categories.map(category => (
                <option key={category} value={category}>{category}</option>
              ))}
            </select>
          </div>

          {/* Books Management Table */}
          <div className="admin-table">
            <div className="table-header">
              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr 1fr 1fr 1fr 1fr', gap: '1rem', alignItems: 'center' }}>
                <div>الكتاب</div>
                <div>السعر</div>
                <div>التصنيف</div>
                <div>المخزون</div>
                <div>ISBN</div>
                <div>تاريخ النشر</div>
                <div>الإجراءات</div>
              </div>
            </div>

            {filteredBooks.map((book) => (
              <div key={book.id} className="table-row">
                <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr 1fr 1fr 1fr 1fr', gap: '1rem', alignItems: 'center' }}>
                  <div>
                    <div style={{ fontWeight: '600', marginBottom: '0.25rem' }}>{book.title} {book.is_chosen && '⭐'}</div>
                    <div style={{ color: '#6b7280', fontSize: '0.875rem' }}>{book.author}</div>
                  </div>
                  <div style={{ fontWeight: '600', color: '#1e3a8a' }}>{book.price} ريال</div>
                  <div>
                    <span style={{
                      background: '#f3f4f6',
                      color: '#6b7280',
                      padding: '0.25rem 0.75rem',
                      borderRadius: '20px',
                      fontSize: '0.75rem'
                    }}>
                      {book.category}
                    </span>
                  </div>
                  <div style={{
                    color: book.stock_quantity === 0 ? '#ef4444' : book.stock_quantity < 10 ? '#f59e0b' : '#10b981',
                    fontWeight: '600'
                  }}>
                    {book.stock_quantity}
                  </div>
                  <div style={{ fontSize: '0.875rem', fontFamily: 'monospace' }}>{book.isbn}</div>
                  <div style={{ fontSize: '0.875rem', color: '#6b7280' }}>
                    {new Date(book.published_date).toLocaleDateString('ar-SA')}
                  </div>
                  <div className="table-actions">
                    <button
                      className="btn btn-small btn-edit"
                      onClick={() => setEditingBook(book)}
                    >
                      تعديل
                    </button>
                    <button
                      className="btn btn-small btn-delete"
                      onClick={() => handleDeleteBook(book.id)}
                    >
                      حذف
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* No Results */}
          {filteredBooks.length === 0 && (
            <div style={{
              textAlign: 'center',
              padding: '3rem',
              color: '#6b7280'
            }}>
              <div style={{ fontSize: '2rem', marginBottom: '1rem' }}>📚</div>
              <h3>لا توجد كتب تطابق البحث</h3>
              <button
                className="btn btn-primary"
                style={{ marginTop: '1rem' }}
                onClick={() => {
                  setSearchTerm('');
                  setSelectedCategory('الكل');
                }}
              >
                إظهار جميع الكتب
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Add/Edit Book Modal */}
      {(showAddForm || editingBook) && (
        <BookFormModal
          book={editingBook}
          categories={categories.filter(cat => cat !== 'الكل')}
          onSave={editingBook ? handleEditBook : handleAddBook}
          onCancel={() => {
            setShowAddForm(false);
            setEditingBook(null);
          }}
        />
      )}
    </div>
  );
};

export default AdminDashboard;