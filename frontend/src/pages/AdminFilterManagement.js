import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';
import CustomLoader from '../components/CustomLoader';

const AdminFilterManagement = () => {
  const [filters, setFilters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingFilter, setEditingFilter] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadFilters();
  }, []);

  const loadFilters = async () => {
    try {
      setLoading(true);
      const response = await apiService.get('/custom_filters');
      setFilters(response.data || []);
    } catch (error) {
      setError('فشل تحميل الفلاتر المخصصة');
    } finally {
      setLoading(false);
    }
  };

  const handleAddFilter = async (filterData) => {
    try {
      await apiService.post('/custom_filters', filterData);
      await loadFilters();
      setShowAddForm(false);
    } catch (error) {
      setError('فشل إضافة الفلتر المخصص');
    }
  };

  const handleEditFilter = async (filterData) => {
    try {
      await apiService.put(`/custom_filters/${filterData.id}`, filterData);
      await loadFilters();
      setEditingFilter(null);
    } catch (error) {
      setError('فشل تحديث الفلتر المخصص');
    }
  };

  const handleDeleteFilter = async (filterId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا الفلتر؟')) return;
    try {
      await apiService.delete(`/custom_filters/${filterId}`);
      await loadFilters();
    } catch (error) {
      setError('فشل حذف الفلتر المخصص');
    }
  };

  if (loading) {
    return <CustomLoader />;
  }

  return (
    <div className="admin-filter-management">
      <div className="container">
        <h1 style={{
          fontSize: 'clamp(1.8rem, 4vw, 2.5rem)',
          color: '#1e3a8a',
          fontFamily: 'Amiri, serif',
          marginBottom: '2rem',
          textAlign: 'center'
        }}>
          إدارة الفلاتر المخصصة
        </h1>

        {error && (
          <div className="error-message" style={{
            background: '#fee2e2',
            color: '#dc2626',
            padding: '1rem',
            borderRadius: '8px',
            marginBottom: '1rem',
            textAlign: 'center'
          }}>
            {error}
          </div>
        )}

        <div className="card">
          <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center',
            marginBottom: '1.5rem'
          }}>
            <h2>الفلاتر المخصصة</h2>
            <button
              className="btn btn-primary"
              onClick={() => setShowAddForm(true)}
              style={{ minHeight: '44px', padding: '12px 24px' }}
            >
              إضافة فلتر مخصص
            </button>
          </div>

          {filters.length === 0 ? (
            <div style={{
              textAlign: 'center',
              padding: '3rem',
              color: '#6b7280'
            }}>
              <div style={{ fontSize: '2rem', marginBottom: '1rem' }}>🔍</div>
              <h3>لا توجد فلاتر مخصصة</h3>
              <p>ابدأ بإضافة فلتر مخصص جديد</p>
            </div>
          ) : (
            <div className="filters-list">
              {filters.map(filter => (
                <div key={filter.id} className="filter-item" style={{
                  background: '#f9fafb',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  padding: '1rem',
                  marginBottom: '1rem'
                }}>
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    marginBottom: '0.5rem'
                  }}>
                    <div>
                      <h3 style={{ margin: 0, color: '#1e3a8a' }}>{filter.name}</h3>
                      <p style={{ 
                        margin: '0.25rem 0', 
                        color: '#6b7280',
                        fontSize: '0.9rem'
                      }}>
                        النوع: {filter.type === 'select' ? 'قائمة اختيار' : 'نطاق قيم'} | 
                        الحقل: {filter.field_name}
                        {filter.unit && ` | الوحدة: ${filter.unit}`}
                      </p>
                    </div>
                    <div style={{ display: 'flex', gap: '0.5rem' }}>
                      <button
                        className="btn btn-small btn-edit"
                        onClick={() => setEditingFilter(filter)}
                        style={{ minHeight: '36px', padding: '8px 16px' }}
                      >
                        تعديل
                      </button>
                      <button
                        className="btn btn-small btn-delete"
                        onClick={() => handleDeleteFilter(filter.id)}
                        style={{ minHeight: '36px', padding: '8px 16px' }}
                      >
                        حذف
                      </button>
                    </div>
                  </div>
                  
                  {filter.type === 'select' && filter.options && (
                    <div>
                      <strong>الخيارات:</strong>
                      <div style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: '0.5rem',
                        marginTop: '0.5rem'
                      }}>
                        {filter.options.map((option, index) => (
                          <span key={index} style={{
                            background: '#e5e7eb',
                            color: '#374151',
                            padding: '0.25rem 0.5rem',
                            borderRadius: '12px',
                            fontSize: '0.8rem'
                          }}>
                            {option}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Add/Edit Filter Modal */}
      {(showAddForm || editingFilter) && (
        <FilterFormModal
          filter={editingFilter}
          onSave={editingFilter ? handleEditFilter : handleAddFilter}
          onCancel={() => {
            setShowAddForm(false);
            setEditingFilter(null);
          }}
        />
      )}
    </div>
  );
};

// Filter Form Modal Component
const FilterFormModal = ({ filter, onSave, onCancel }) => {
  const [formData, setFormData] = useState({
    name: filter?.name || '',
    type: filter?.type || 'select',
    field_name: filter?.field_name || '',
    options: filter?.options || [],
    unit: filter?.unit || '',
    sort_order: filter?.sort_order || 0
  });

  const [newOption, setNewOption] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    const filterData = {
      ...formData,
      sort_order: parseInt(formData.sort_order)
    };
    
    if (filter) {
      filterData.id = filter.id;
    }
    
    onSave(filterData);
  };

  const addOption = () => {
    if (newOption.trim()) {
      setFormData(prev => ({
        ...prev,
        options: [...prev.options, newOption.trim()]
      }));
      setNewOption('');
    }
  };

  const removeOption = (index) => {
    setFormData(prev => ({
      ...prev,
      options: prev.options.filter((_, i) => i !== index)
    }));
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
        padding: '1.5rem',
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
          {filter ? 'تعديل الفلتر المخصص' : 'إضافة فلتر مخصص جديد'}
        </h2>

        <form onSubmit={handleSubmit} className="modern-form" style={{ padding: 0, boxShadow: 'none' }}>
          <div className="form-grid">
            <div className="form-group">
              <label>اسم الفلتر</label>
              <input
                type="text"
                required
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="مثال: المؤلفون، الناشرون، اللغة"
                style={{ fontSize: '16px', minHeight: '44px' }}
              />
            </div>

            <div className="form-group">
              <label>نوع الفلتر</label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                style={{ fontSize: '16px', minHeight: '44px' }}
              >
                <option value="select">قائمة اختيار</option>
                <option value="range">نطاق قيم</option>
              </select>
            </div>

            <div className="form-group">
              <label>اسم الحقل في قاعدة البيانات</label>
              <input
                type="text"
                required
                value={formData.field_name}
                onChange={(e) => setFormData({ ...formData, field_name: e.target.value })}
                placeholder="مثال: author, publisher, language"
                style={{ fontSize: '16px', minHeight: '44px' }}
              />
            </div>

            <div className="form-group">
              <label>ترتيب العرض</label>
              <input
                type="number"
                value={formData.sort_order}
                onChange={(e) => setFormData({ ...formData, sort_order: e.target.value })}
                style={{ fontSize: '16px', minHeight: '44px' }}
              />
            </div>

            {formData.type === 'range' && (
              <div className="form-group">
                <label>الوحدة (اختياري)</label>
                <input
                  type="text"
                  value={formData.unit}
                  onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                  placeholder="مثال: صفحة، سنة، ج.م"
                  style={{ fontSize: '16px', minHeight: '44px' }}
                />
              </div>
            )}

            {formData.type === 'select' && (
              <div className="form-group" style={{ gridColumn: '1 / -1' }}>
                <label>خيارات الفلتر</label>
                <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem' }}>
                  <input
                    type="text"
                    value={newOption}
                    onChange={(e) => setNewOption(e.target.value)}
                    placeholder="أضف خيار جديد"
                    style={{ 
                      flex: 1, 
                      fontSize: '16px', 
                      minHeight: '44px',
                      padding: '12px 16px',
                      border: '2px solid #e5e7eb',
                      borderRadius: '8px'
                    }}
                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addOption())}
                  />
                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={addOption}
                    style={{ minHeight: '44px', padding: '12px 16px' }}
                  >
                    إضافة
                  </button>
                </div>
                
                {formData.options.length > 0 && (
                  <div style={{
                    display: 'flex',
                    flexWrap: 'wrap',
                    gap: '0.5rem'
                  }}>
                    {formData.options.map((option, index) => (
                      <span key={index} style={{
                        background: '#e5e7eb',
                        color: '#374151',
                        padding: '0.5rem 1rem',
                        borderRadius: '20px',
                        fontSize: '0.9rem',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.5rem'
                      }}>
                        {option}
                        <button
                          type="button"
                          onClick={() => removeOption(index)}
                          style={{
                            background: 'none',
                            border: 'none',
                            color: '#dc2626',
                            cursor: 'pointer',
                            fontSize: '1.2rem',
                            padding: 0,
                            width: '20px',
                            height: '20px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                          }}
                        >
                          ×
                        </button>
                      </span>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>

          <div style={{
            display: 'flex',
            gap: '1rem',
            justifyContent: 'center',
            marginTop: '2rem',
            flexDirection: 'column'
          }}>
            <button type="submit" className="btn btn-primary" style={{
              minHeight: '44px',
              fontSize: '1rem',
              width: '100%'
            }}>
              {filter ? 'تحديث' : 'إضافة'}
            </button>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onCancel}
              style={{
                minHeight: '44px',
                fontSize: '1rem',
                width: '100%'
              }}
            >
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AdminFilterManagement;
