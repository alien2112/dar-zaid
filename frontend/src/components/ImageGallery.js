import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';

const ImageGallery = ({
  uploadType = null,
  entityType = null,
  entityId = null,
  onImageSelect = null,
  selectable = false,
  showDetails = true,
  showDelete = true,
  className = '',
  style = {}
}) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedImage, setSelectedImage] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalImages, setTotalImages] = useState(0);
  const [filter, setFilter] = useState({
    upload_type: uploadType,
    entity_type: entityType,
    entity_id: entityId
  });

  const imagesPerPage = 20;

  useEffect(() => {
    loadImages();
  }, [currentPage, filter]);

  const loadImages = async () => {
    setLoading(true);
    try {
      const params = {
        limit: imagesPerPage,
        offset: (currentPage - 1) * imagesPerPage,
        ...filter
      };

      // Remove null/undefined values
      Object.keys(params).forEach(key => {
        if (params[key] === null || params[key] === undefined || params[key] === '') {
          delete params[key];
        }
      });

      const response = await apiService.getImageList(params);
      setImages(response.data.images || []);
      setTotalImages(response.data.total || response.data.images?.length || 0);
      setError('');
    } catch (err) {
      console.error('Failed to load images:', err);
      setError('فشل في تحميل الصور');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteImage = async (imageId) => {
    if (!window.confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
      return;
    }

    try {
      await apiService.deleteImage(imageId);
      setImages(images.filter(img => img.id !== imageId));
      setTotalImages(prev => prev - 1);
    } catch (err) {
      console.error('Failed to delete image:', err);
      alert('فشل في حذف الصورة');
    }
  };

  const handleImageClick = (image) => {
    if (selectable && onImageSelect) {
      onImageSelect(image);
    } else {
      setSelectedImage(image);
    }
  };

  const handleCleanupUnused = async () => {
    if (!window.confirm('هل أنت متأكد من حذف جميع الصور غير المستخدمة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
      return;
    }

    try {
      const response = await apiService.cleanupUnusedImages();
      alert(`تم حذف ${response.data.cleaned_count} صورة غير مستخدمة`);
      loadImages();
    } catch (err) {
      console.error('Failed to cleanup images:', err);
      alert('فشل في تنظيف الصور');
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const totalPages = Math.ceil(totalImages / imagesPerPage);

  if (loading) {
    return (
      <div className={`image-gallery ${className}`} style={style}>
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <div style={{ fontSize: '48px', marginBottom: '1rem' }}>⏳</div>
          <p>جاري تحميل الصور...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={`image-gallery ${className}`} style={style}>
        <div style={{ textAlign: 'center', padding: '2rem', color: '#ef4444' }}>
          <div style={{ fontSize: '48px', marginBottom: '1rem' }}>⚠️</div>
          <p>{error}</p>
          <button
            onClick={loadImages}
            style={{
              marginTop: '1rem',
              padding: '8px 16px',
              backgroundColor: '#3b82f6',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer'
            }}
          >
            إعادة المحاولة
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={`image-gallery ${className}`} style={style}>
      {/* Header and Controls */}
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '1rem',
        padding: '1rem',
        backgroundColor: '#f8fafc',
        borderRadius: '8px',
        border: '1px solid #e2e8f0'
      }}>
        <div>
          <h3 style={{ margin: 0, color: '#1e3a8a' }}>
            معرض الصور ({totalImages} صورة)
          </h3>
          {filter.upload_type && (
            <p style={{ margin: '0.25rem 0 0 0', fontSize: '14px', color: '#64748b' }}>
              النوع: {filter.upload_type}
            </p>
          )}
        </div>
        <div style={{ display: 'flex', gap: '0.5rem' }}>
          <button
            onClick={loadImages}
            style={{
              padding: '8px 12px',
              backgroundColor: '#10b981',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '14px'
            }}
          >
            🔄 تحديث
          </button>
          <button
            onClick={handleCleanupUnused}
            style={{
              padding: '8px 12px',
              backgroundColor: '#f59e0b',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '14px'
            }}
          >
            🧹 تنظيف
          </button>
        </div>
      </div>

      {/* Filter Controls */}
      <div style={{
        display: 'flex',
        gap: '1rem',
        marginBottom: '1rem',
        padding: '1rem',
        backgroundColor: '#f1f5f9',
        borderRadius: '8px'
      }}>
        <div>
          <label style={{ display: 'block', marginBottom: '0.25rem', fontSize: '14px', fontWeight: 'bold' }}>
            نوع الرفع:
          </label>
          <select
            value={filter.upload_type || ''}
            onChange={(e) => setFilter({ ...filter, upload_type: e.target.value || null })}
            style={{
              padding: '4px 8px',
              border: '1px solid #d1d5db',
              borderRadius: '4px',
              fontSize: '14px'
            }}
          >
            <option value="">الكل</option>
            <option value="book_cover">أغلفة الكتب</option>
            <option value="blog_image">صور المقالات</option>
            <option value="slider_image">صور الشرائح</option>
            <option value="general">عام</option>
          </select>
        </div>
        <div>
          <label style={{ display: 'block', marginBottom: '0.25rem', fontSize: '14px', fontWeight: 'bold' }}>
            نوع الكيان:
          </label>
          <select
            value={filter.entity_type || ''}
            onChange={(e) => setFilter({ ...filter, entity_type: e.target.value || null })}
            style={{
              padding: '4px 8px',
              border: '1px solid #d1d5db',
              borderRadius: '4px',
              fontSize: '14px'
            }}
          >
            <option value="">الكل</option>
            <option value="book">كتاب</option>
            <option value="blog">مقال</option>
            <option value="slider">شريحة</option>
          </select>
        </div>
      </div>

      {/* Images Grid */}
      {images.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '3rem', color: '#64748b' }}>
          <div style={{ fontSize: '64px', marginBottom: '1rem' }}>📁</div>
          <p style={{ fontSize: '18px', marginBottom: '0.5rem' }}>لا توجد صور</p>
          <p style={{ fontSize: '14px' }}>لم يتم العثور على صور بالمعايير المحددة</p>
        </div>
      ) : (
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
          gap: '1rem',
          marginBottom: '2rem'
        }}>
          {images.map((image) => (
            <div
              key={image.id}
              className="image-card"
              style={{
                border: '2px solid #e2e8f0',
                borderRadius: '8px',
                overflow: 'hidden',
                backgroundColor: 'white',
                cursor: selectable ? 'pointer' : 'default',
                transition: 'all 0.3s ease',
                ':hover': {
                  borderColor: '#3b82f6',
                  transform: 'translateY(-2px)',
                  boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)'
                }
              }}
              onClick={() => handleImageClick(image)}
            >
              {/* Image */}
              <div style={{
                width: '100%',
                height: '150px',
                backgroundColor: '#f8fafc',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                overflow: 'hidden'
              }}>
                <img
                  src={image.public_url}
                  alt={image.original_name}
                  style={{
                    maxWidth: '100%',
                    maxHeight: '100%',
                    objectFit: 'cover'
                  }}
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'flex';
                  }}
                />
                <div style={{
                  display: 'none',
                  alignItems: 'center',
                  justifyContent: 'center',
                  width: '100%',
                  height: '100%',
                  color: '#64748b',
                  fontSize: '48px'
                }}>
                  🖼️
                </div>
              </div>

              {/* Image Info */}
              {showDetails && (
                <div style={{ padding: '0.75rem' }}>
                  <h4 style={{
                    margin: '0 0 0.25rem 0',
                    fontSize: '14px',
                    fontWeight: 'bold',
                    color: '#1e293b',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap'
                  }}>
                    {image.original_name}
                  </h4>

                  <div style={{ fontSize: '12px', color: '#64748b', lineHeight: '1.4' }}>
                    <div>الحجم: {formatFileSize(image.file_size)}</div>
                    <div>الأبعاد: {image.width}×{image.height}</div>
                    <div>النوع: {image.upload_type}</div>
                    <div>التاريخ: {new Date(image.created_at).toLocaleDateString('ar-SA')}</div>
                  </div>

                  {/* Actions */}
                  <div style={{
                    display: 'flex',
                    gap: '0.5rem',
                    marginTop: '0.75rem'
                  }}>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        navigator.clipboard.writeText(image.public_url);
                        alert('تم نسخ رابط الصورة');
                      }}
                      style={{
                        flex: 1,
                        padding: '4px 8px',
                        backgroundColor: '#3b82f6',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '12px'
                      }}
                    >
                      نسخ الرابط
                    </button>

                    {showDelete && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDeleteImage(image.id);
                        }}
                        style={{
                          padding: '4px 8px',
                          backgroundColor: '#ef4444',
                          color: 'white',
                          border: 'none',
                          borderRadius: '4px',
                          cursor: 'pointer',
                          fontSize: '12px'
                        }}
                      >
                        حذف
                      </button>
                    )}
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div style={{
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          gap: '0.5rem',
          marginTop: '2rem'
        }}>
          <button
            onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
            disabled={currentPage === 1}
            style={{
              padding: '8px 12px',
              backgroundColor: currentPage === 1 ? '#f1f5f9' : '#3b82f6',
              color: currentPage === 1 ? '#64748b' : 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: currentPage === 1 ? 'not-allowed' : 'pointer'
            }}
          >
            السابق
          </button>

          <span style={{
            padding: '8px 16px',
            backgroundColor: '#f8fafc',
            borderRadius: '4px',
            fontSize: '14px',
            color: '#475569'
          }}>
            صفحة {currentPage} من {totalPages}
          </span>

          <button
            onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
            disabled={currentPage === totalPages}
            style={{
              padding: '8px 12px',
              backgroundColor: currentPage === totalPages ? '#f1f5f9' : '#3b82f6',
              color: currentPage === totalPages ? '#64748b' : 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: currentPage === totalPages ? 'not-allowed' : 'pointer'
            }}
          >
            التالي
          </button>
        </div>
      )}

      {/* Image Preview Modal */}
      {selectedImage && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000,
          padding: '2rem'
        }} onClick={() => setSelectedImage(null)}>
          <div style={{
            backgroundColor: 'white',
            borderRadius: '12px',
            maxWidth: '90vw',
            maxHeight: '90vh',
            overflow: 'auto'
          }} onClick={(e) => e.stopPropagation()}>
            <div style={{ padding: '1rem' }}>
              <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: '1rem'
              }}>
                <h3 style={{ margin: 0, color: '#1e3a8a' }}>
                  {selectedImage.original_name}
                </h3>
                <button
                  onClick={() => setSelectedImage(null)}
                  style={{
                    backgroundColor: '#ef4444',
                    color: 'white',
                    border: 'none',
                    borderRadius: '50%',
                    width: '32px',
                    height: '32px',
                    cursor: 'pointer',
                    fontSize: '18px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                >
                  ×
                </button>
              </div>

              <img
                src={selectedImage.public_url}
                alt={selectedImage.original_name}
                style={{
                  maxWidth: '100%',
                  maxHeight: '70vh',
                  objectFit: 'contain',
                  display: 'block',
                  margin: '0 auto'
                }}
              />

              <div style={{
                marginTop: '1rem',
                padding: '1rem',
                backgroundColor: '#f8fafc',
                borderRadius: '8px',
                fontSize: '14px',
                color: '#475569'
              }}>
                <div><strong>الحجم:</strong> {formatFileSize(selectedImage.file_size)}</div>
                <div><strong>الأبعاد:</strong> {selectedImage.width}×{selectedImage.height}</div>
                <div><strong>النوع:</strong> {selectedImage.mime_type}</div>
                <div><strong>فئة الرفع:</strong> {selectedImage.upload_type}</div>
                <div><strong>تاريخ الرفع:</strong> {new Date(selectedImage.created_at).toLocaleString('ar-SA')}</div>
                <div style={{ marginTop: '0.5rem' }}>
                  <strong>الرابط:</strong>
                  <input
                    type="text"
                    value={selectedImage.public_url}
                    readOnly
                    style={{
                      width: '100%',
                      padding: '4px 8px',
                      border: '1px solid #d1d5db',
                      borderRadius: '4px',
                      marginTop: '0.25rem',
                      fontSize: '13px'
                    }}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ImageGallery;