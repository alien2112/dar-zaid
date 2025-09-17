import React, { useState, useEffect } from 'react';
import { apiService } from '../services/api';

const BookFilters = ({ 
  filters, 
  onFiltersChange, 
  sortBy, 
  onSortChange,
  categories = [],
  authors = [],
  publishers = []
}) => {
  console.log('BookFilters received categories:', categories);
  const [expandedFilters, setExpandedFilters] = useState({
    categories: true,
    price: true,
    authors: false,
    publishers: false
  });

  const [customFilters, setCustomFilters] = useState([]);

  useEffect(() => {
    // Load custom filters from backend
    const loadCustomFilters = async () => {
      try {
        const response = await apiService.get('/custom_filters');
        setCustomFilters(response.data || []);
      } catch (error) {
        console.error('Failed to load custom filters:', error);
      }
    };
    loadCustomFilters();
  }, []);

  const toggleFilter = (filterName) => {
    setExpandedFilters(prev => ({
      ...prev,
      [filterName]: !prev[filterName]
    }));
  };

  const handleFilterChange = (filterType, value) => {
    onFiltersChange(prev => ({
      ...prev,
      [filterType]: value
    }));
  };

  const handlePriceRangeChange = (min, max) => {
    onFiltersChange(prev => ({
      ...prev,
      priceRange: { min: min || 0, max: max || Infinity }
    }));
  };

  const clearAllFilters = () => {
    onFiltersChange({
      categories: [],
      priceRange: { min: 0, max: Infinity },
      authors: [],
      publishers: [],
      customFilters: {}
    });
  };

  const sortOptions = [
    { value: 'default', label: 'مقترحاتنا' },
    { value: 'price_high', label: 'الأغلى أولاً' },
    { value: 'price_low', label: 'الأرخص أولاً' },
    { value: 'date_recent', label: 'الأحدث أولاً' },
    { value: 'date_old', label: 'الأقدم أولاً' },
    { value: 'title_asc', label: 'العنوان أ-ي' },
    { value: 'title_desc', label: 'العنوان ي-أ' }
  ];

  return (
    <div className="book-filters">
      {/* Sort Options */}
      <div className="filter-section">
        <div className="filter-header">
          <h3>ترتيب حسب:</h3>
        </div>
        <select 
          className="sort-select"
          value={sortBy}
          onChange={(e) => onSortChange(e.target.value)}
        >
          {sortOptions.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>

      {/* Categories Filter */}
      <div className="filter-section">
        <div 
          className="filter-header expandable"
          onClick={() => toggleFilter('categories')}
        >
          <h3>الأقسام</h3>
          <span className="expand-icon">
            {expandedFilters.categories ? '−' : '+'}
          </span>
        </div>
        {expandedFilters.categories && (
          <div className="filter-content">
            {categories.map(category => (
              <label key={category} className="filter-option">
                <input
                  type="radio"
                  name="category"
                  value={category}
                  checked={filters.categories.includes(category)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      handleFilterChange('categories', [category]);
                    }
                  }}
                />
                <span className="filter-label">{category}</span>
              </label>
            ))}
          </div>
        )}
      </div>

      {/* Price Filter */}
      <div className="filter-section">
        <div 
          className="filter-header expandable"
          onClick={() => toggleFilter('price')}
        >
          <h3>السعر</h3>
          <span className="expand-icon">
            {expandedFilters.price ? '−' : '+'}
          </span>
        </div>
        {expandedFilters.price && (
          <div className="filter-content">
            <div className="price-range">
              <div className="price-inputs">
                <input
                  type="number"
                  placeholder="من"
                  value={filters.priceRange.min || ''}
                  onChange={(e) => handlePriceRangeChange(
                    e.target.value ? parseFloat(e.target.value) : 0,
                    filters.priceRange.max
                  )}
                  className="price-input"
                />
                <span className="currency">ج.م</span>
                <input
                  type="number"
                  placeholder="إلى"
                  value={filters.priceRange.max === Infinity ? '' : filters.priceRange.max}
                  onChange={(e) => handlePriceRangeChange(
                    filters.priceRange.min,
                    e.target.value ? parseFloat(e.target.value) : Infinity
                  )}
                  className="price-input"
                />
                <span className="currency">ج.م</span>
              </div>
              <button 
                className="apply-price-btn"
                onClick={() => {
                  // Price range is already applied on change
                }}
              >
                تطبيق
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Authors Filter */}
      {authors.length > 0 && (
        <div className="filter-section">
          <div 
            className="filter-header expandable"
            onClick={() => toggleFilter('authors')}
          >
            <h3>المؤلفون</h3>
            <span className="expand-icon">
              {expandedFilters.authors ? '−' : '+'}
            </span>
          </div>
          {expandedFilters.authors && (
            <div className="filter-content">
              {authors.map(author => (
                <label key={author} className="filter-option">
                  <input
                    type="checkbox"
                    checked={filters.authors.includes(author)}
                    onChange={(e) => {
                      const newAuthors = e.target.checked
                        ? [...filters.authors, author]
                        : filters.authors.filter(a => a !== author);
                      handleFilterChange('authors', newAuthors);
                    }}
                  />
                  <span className="filter-label">{author}</span>
                </label>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Publishers Filter */}
      {publishers.length > 0 && (
        <div className="filter-section">
          <div 
            className="filter-header expandable"
            onClick={() => toggleFilter('publishers')}
          >
            <h3>الماركات</h3>
            <span className="expand-icon">
              {expandedFilters.publishers ? '−' : '+'}
            </span>
          </div>
          {expandedFilters.publishers && (
            <div className="filter-content">
              {publishers.map(publisher => (
                <label key={publisher} className="filter-option">
                  <input
                    type="checkbox"
                    checked={filters.publishers.includes(publisher)}
                    onChange={(e) => {
                      const newPublishers = e.target.checked
                        ? [...filters.publishers, publisher]
                        : filters.publishers.filter(p => p !== publisher);
                      handleFilterChange('publishers', newPublishers);
                    }}
                  />
                  <span className="filter-label">{publisher}</span>
                </label>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Custom Filters */}
      {customFilters.map(filter => (
        <div key={filter.id} className="filter-section">
          <div 
            className="filter-header expandable"
            onClick={() => toggleFilter(`custom_${filter.id}`)}
          >
            <h3>{filter.name}</h3>
            <span className="expand-icon">
              {expandedFilters[`custom_${filter.id}`] ? '−' : '+'}
            </span>
          </div>
          {expandedFilters[`custom_${filter.id}`] && (
            <div className="filter-content">
              {filter.type === 'select' && filter.options && (
                filter.options.map(option => (
                  <label key={option} className="filter-option">
                    <input
                      type="checkbox"
                      checked={(filters.customFilters[filter.id] || []).includes(option)}
                      onChange={(e) => {
                        const currentValues = filters.customFilters[filter.id] || [];
                        const newValues = e.target.checked
                          ? [...currentValues, option]
                          : currentValues.filter(v => v !== option);
                        handleFilterChange('customFilters', {
                          ...filters.customFilters,
                          [filter.id]: newValues
                        });
                      }}
                    />
                    <span className="filter-label">{option}</span>
                  </label>
                ))
              )}
              {filter.type === 'range' && (
                <div className="price-range">
                  <div className="price-inputs">
                    <input
                      type="number"
                      placeholder="من"
                      value={filters.customFilters[filter.id]?.min || ''}
                      onChange={(e) => handleFilterChange('customFilters', {
                        ...filters.customFilters,
                        [filter.id]: {
                          ...filters.customFilters[filter.id],
                          min: e.target.value ? parseFloat(e.target.value) : 0
                        }
                      })}
                      className="price-input"
                    />
                    <span className="currency">{filter.unit || ''}</span>
                    <input
                      type="number"
                      placeholder="إلى"
                      value={filters.customFilters[filter.id]?.max || ''}
                      onChange={(e) => handleFilterChange('customFilters', {
                        ...filters.customFilters,
                        [filter.id]: {
                          ...filters.customFilters[filter.id],
                          max: e.target.value ? parseFloat(e.target.value) : Infinity
                        }
                      })}
                      className="price-input"
                    />
                    <span className="currency">{filter.unit || ''}</span>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      ))}

      {/* Clear Filters Button */}
      <button 
        className="clear-filters-btn"
        onClick={clearAllFilters}
      >
        مسح الفلاتر
      </button>
    </div>
  );
};

export default BookFilters;
