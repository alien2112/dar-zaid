import React, { useState, useRef, useCallback } from 'react';
import { apiService } from '../services/api';

const ImageUpload = ({
  onImageSelect,
  onImageUpload,
  onError,
  uploadType = 'general',
  entityId = null,
  entityType = null,
  entityTitle = null, // Added for meaningful filenames
  maxFileSize = 10 * 1024 * 1024, // 10MB
  allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
  multiple = false,
  preview = true,
  showUrl = true,
  className = '',
  style = {},
  placeholder = 'اختر الصورة أو اسحبها هنا',
  uploadText = 'رفع الصورة'
}) => {
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [dragOver, setDragOver] = useState(false);
  const [preview_url, setPreviewUrl] = useState('');
  const [imageUrl, setImageUrl] = useState('');
  const [hasSelectedFiles, setHasSelectedFiles] = useState(false);
  const fileInputRef = useRef(null);

  const validateFile = (file) => {
    if (!file) return { valid: false, error: 'لم يتم اختيار ملف' };

    if (file.size > maxFileSize) {
      return {
        valid: false,
        error: `حجم الملف كبير جداً. الحد الأقصى ${Math.round(maxFileSize / 1024 / 1024)}MB`
      };
    }

    if (!allowedTypes.includes(file.type)) {
      return {
        valid: false,
        error: 'نوع الملف غير مدعوم. يجب أن يكون JPEG, PNG, WebP, أو GIF'
      };
    }

    return { valid: true };
  };

  const handleFileSelect = useCallback(async (files) => {
    if (!files || files.length === 0) return;

    const fileArray = Array.from(files);

    // Validate files
    for (const file of fileArray) {
      const validation = validateFile(file);
      if (!validation.valid) {
        onError && onError(validation.error);
        return;
      }
    }

    // Create preview for single file
    if (!multiple && fileArray.length > 0 && preview) {
      const file = fileArray[0];
      const reader = new FileReader();
      reader.onload = (e) => setPreviewUrl(e.target.result);
      reader.readAsDataURL(file);
    }

    // Set files selected state
    setHasSelectedFiles(true);

    // Call callback with selected files
    onImageSelect && onImageSelect(multiple ? fileArray : fileArray[0]);

    // Note: User can manually upload using the upload button below

  }, [multiple, preview, onImageSelect, onError, maxFileSize, allowedTypes]);

  const handleUpload = async (files) => {
    if (!files) {
      files = fileInputRef.current?.files;
    }

    if (!files || files.length === 0) {
      onError && onError('لم يتم اختيار ملف للرفع');
      return;
    }

    setUploading(true);
    setUploadProgress(0);

    try {
      if (multiple && files.length > 1) {
        // Handle multiple file upload
        const formData = new FormData();
        Array.from(files).forEach((file, index) => {
          formData.append('images[]', file);
        });

        if (uploadType) formData.append('upload_type', uploadType);
        if (entityId) formData.append('entity_id', entityId);
        if (entityType) formData.append('entity_type', entityType);
        if (entityTitle) formData.append('entity_title', entityTitle);

        const response = await apiService.uploadMultipleImages(formData, {
          onUploadProgress: (progressEvent) => {
            const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            setUploadProgress(progress);
          }
        });

        onImageUpload && onImageUpload(response.data);

      } else {
        // Handle single file upload
        const file = files[0] || (files instanceof File ? files : null);
    if (!file) {
      onError && onError('لم يتم العثور على ملف صالح للرفع');
      setUploading(false);
      return;
    }
        const formData = new FormData();
        formData.append('image', file);

        if (uploadType) formData.append('upload_type', uploadType);
        if (entityId) formData.append('entity_id', entityId);
        if (entityType) formData.append('entity_type', entityType);
        if (entityTitle) formData.append('entity_title', entityTitle);

        const response = await apiService.uploadImage(formData, {
          onUploadProgress: (progressEvent) => {
            const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            setUploadProgress(progress);
          }
        });

        if (response.data.url) {
          setImageUrl(response.data.url);
        }

        onImageUpload && onImageUpload(response.data);
      }

    } catch (error) {
      console.error('Upload failed:', error);
      const errorMessage = error.response?.data?.error || 'فشل في رفع الصورة';
      onError && onError(errorMessage);
    } finally {
      setUploading(false);
      setUploadProgress(0);
      setHasSelectedFiles(false);
    }
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setDragOver(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    setDragOver(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setDragOver(false);

    const files = e.dataTransfer.files;
    handleFileSelect(files);
  };

  const handleInputChange = (e) => {
    handleFileSelect(e.target.files);
  };

  const handleUrlChange = (e) => {
    const url = e.target.value;
    setImageUrl(url);
    if (url && (url.startsWith('http') || url.startsWith('/') || url.startsWith('./') || url.startsWith('../'))) {
      setPreviewUrl(url);
      onImageSelect && onImageSelect({ url, type: 'url' });
    } else if (!url) {
      setPreviewUrl('');
      onImageSelect && onImageSelect(null);
    }
  };

  const clearSelection = () => {
    setPreviewUrl('');
    setImageUrl('');
    setHasSelectedFiles(false);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    onImageSelect && onImageSelect(null);
  };

  return (
    <div className={`image-upload-container ${className}`} style={style}>
      {/* URL Input */}
      {showUrl && (
        <div className="url-input-section" style={{ marginBottom: '1rem' }}>
          <label style={{
            display: 'block',
            marginBottom: '0.5rem',
            fontWeight: 'bold',
            color: '#1e3a8a'
          }}>
            رابط الصورة (اختياري)
          </label>
          <input
            type="url"
            placeholder="https://example.com/image.jpg"
            value={imageUrl}
            onChange={handleUrlChange}
            style={{
              width: '100%',
              padding: '12px',
              border: '2px solid #e2e8f0',
              borderRadius: '8px',
              fontSize: '16px',
              fontFamily: 'inherit'
            }}
          />
        </div>
      )}

      {/* File Upload Area */}
      <div
        className={`upload-area ${dragOver ? 'drag-over' : ''}`}
        style={{
          border: `2px dashed ${dragOver ? '#3b82f6' : '#cbd5e1'}`,
          borderRadius: '12px',
          padding: '2rem',
          textAlign: 'center',
          backgroundColor: dragOver ? '#f0f9ff' : '#f8fafc',
          cursor: 'pointer',
          transition: 'all 0.3s ease',
          position: 'relative'
        }}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => fileInputRef.current?.click()}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept={allowedTypes.join(',')}
          multiple={multiple}
          onChange={handleInputChange}
          style={{ display: 'none' }}
        />

        {uploading ? (
          <div className="upload-progress">
            <div style={{ marginBottom: '1rem' }}>
              <div style={{
                width: '100%',
                height: '8px',
                backgroundColor: '#e2e8f0',
                borderRadius: '4px',
                overflow: 'hidden'
              }}>
                <div
                  style={{
                    width: `${uploadProgress}%`,
                    height: '100%',
                    backgroundColor: '#3b82f6',
                    transition: 'width 0.3s ease'
                  }}
                />
              </div>
            </div>
            <p style={{ margin: 0, color: '#64748b' }}>
              جاري الرفع... {uploadProgress}%
            </p>
          </div>
        ) : (
          <>
            <div style={{ fontSize: '48px', marginBottom: '1rem', color: '#94a3b8' }}>
              📁
            </div>
            <p style={{
              margin: '0 0 1rem 0',
              fontSize: '18px',
              fontWeight: 'bold',
              color: '#475569'
            }}>
              {placeholder}
            </p>
            <p style={{
              margin: '0 0 1rem 0',
              fontSize: '14px',
              color: '#64748b'
            }}>
              أو انقر لاختيار الملفات
            </p>
            <button
              type="button"
              style={{
                backgroundColor: hasSelectedFiles ? '#10b981' : '#3b82f6',
                color: 'white',
                border: 'none',
                padding: '12px 24px',
                borderRadius: '8px',
                fontSize: '16px',
                fontWeight: 'bold',
                cursor: 'pointer',
                transition: 'all 0.3s ease',
                boxShadow: hasSelectedFiles ? '0 4px 12px rgba(16, 185, 129, 0.4)' : 'none'
              }}
              onClick={(e) => {
                e.stopPropagation();
                handleUpload();
              }}
              disabled={uploading}
            >
              {hasSelectedFiles ? '⬆️ ' + uploadText : uploadText}
            </button>
          </>
        )}
      </div>

      {/* Preview */}
      {preview && preview_url && (
        <div className="image-preview" style={{ marginTop: '1rem', textAlign: 'center' }}>
          <div style={{ position: 'relative', display: 'inline-block' }}>
            <img
              src={preview_url}
              alt="معاينة"
              style={{
                maxWidth: '200px',
                maxHeight: '200px',
                border: '2px solid #e2e8f0',
                borderRadius: '8px',
                objectFit: 'cover'
              }}
            />
            <button
              type="button"
              onClick={clearSelection}
              style={{
                position: 'absolute',
                top: '-10px',
                right: '-10px',
                backgroundColor: '#ef4444',
                color: 'white',
                border: 'none',
                borderRadius: '50%',
                width: '24px',
                height: '24px',
                cursor: 'pointer',
                fontSize: '12px'
              }}
            >
              ×
            </button>
          </div>
        </div>
      )}

      {/* File Info */}
      <div className="upload-info" style={{
        marginTop: '0.5rem',
        fontSize: '12px',
        color: '#64748b',
        textAlign: 'center'
      }}>
        الحد الأقصى: {Math.round(maxFileSize / 1024 / 1024)}MB •
        الأنواع المدعومة: JPEG, PNG, WebP, GIF
        {multiple && ' • يمكن اختيار ملفات متعددة'}
      </div>
    </div>
  );
};

export default ImageUpload;