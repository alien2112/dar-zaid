import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';

const AdminCategories = () => {
  const [categories, setCategories] = useState([]);
  const [newCategory, setNewCategory] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!isAuthenticated()) {
      navigate('/login');
      return;
    }
    loadCategories();
  }, [isAuthenticated, navigate]);

  const loadCategories = async () => {
    try {
      const response = await apiService.getCategories();
      const list = Array.isArray(response.data) ? response.data : 
                  (response.data && Array.isArray(response.data.categories) ? response.data.categories : []);
      setCategories(list);
      setLoading(false);
    } catch (error) {
      console.error('Failed to load categories:', error);
      setError('فشل تحميل التصنيفات');
      setLoading(false);
    }
  };

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!newCategory.trim()) {
      setError('يرجى إدخال اسم التصنيف');
      return;
    }

    try {
      await apiService.addCategory({ name: newCategory.trim() });
      setNewCategory('');
      setError('');
      loadCategories(); // Reload the list
    } catch (error) {
      console.error('Failed to add category:', error);
      setError('فشل إضافة التصنيف');
    }
  };

  const handleDeleteCategory = async (categoryId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا التصنيف؟')) {
      return;
    }

    try {
      await apiService.deleteCategory(categoryId);
      loadCategories(); // Reload the list
    } catch (error) {
      console.error('Failed to delete category:', error);
      setError('فشل حذف التصنيف');
    }
  };

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="container" style={{ padding: '2rem 1rem' }}>
      <h1 style={{
        textAlign: 'center',
        marginBottom: '2rem',
        color: '#1e3a8a',
        fontFamily: 'Amiri, serif'
      }}>
        إدارة التصنيفات
      </h1>

      {/* Add new category form */}
      <form onSubmit={handleAddCategory} className="modern-form" style={{ maxWidth: '600px', margin: '0 auto 2rem' }}>
        <div className="form-group">
          <label>إضافة تصنيف جديد</label>
          <div style={{ display: 'flex', gap: '1rem' }}>
            <input
              type="text"
              value={newCategory}
              onChange={(e) => setNewCategory(e.target.value)}
              placeholder="اسم التصنيف"
              style={{ flex: 1 }}
            />
            <button type="submit" className="btn btn-primary">
              إضافة
            </button>
          </div>
        </div>
        {error && (
          <div className="error-message" style={{ color: '#dc2626', marginTop: '0.5rem' }}>
            {error}
          </div>
        )}
      </form>

      {/* Categories list */}
      <div style={{ maxWidth: '800px', margin: '0 auto' }}>
        <h2 style={{
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif',
          marginBottom: '1rem'
        }}>
          التصنيفات الحالية
        </h2>
        {categories.length === 0 ? (
          <p style={{ textAlign: 'center', color: '#6b7280' }}>
            لا توجد تصنيفات حالياً
          </p>
        ) : (
          <div className="categories-grid" style={{
            display: 'grid',
            gap: '1rem',
            gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))'
          }}>
            {categories.map((category) => (
              <div
                key={category.id}
                style={{
                  background: '#f8fafc',
                  padding: '1rem',
                  borderRadius: '8px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  gap: '0.5rem'
                }}
              >
                <span style={{ fontWeight: 500 }}>{category.name}</span>
                <button
                  onClick={() => handleDeleteCategory(category.id)}
                  className="btn btn-danger"
                  style={{
                    padding: '4px 8px',
                    minHeight: 'unset',
                    fontSize: '0.875rem'
                  }}
                >
                  حذف
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminCategories;
