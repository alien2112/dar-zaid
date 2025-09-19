import React, { useState, useEffect, useRef } from 'react';
import './SearchableDropdown.css';

const SearchableDropdown = ({
  options = [],
  value = '',
  onChange = () => {},
  placeholder = 'اختر...',
  searchPlaceholder = 'ابحث...',
  displayKey = 'title',
  valueKey = 'id',
  secondaryKey = 'author',
  priceKey = 'price',
  imageKey = 'image_url',
  loading = false,
  disabled = false,
  className = '',
  style = {},
  onSearch = null,
  searchDelay = 300
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filteredOptions, setFilteredOptions] = useState(options);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const dropdownRef = useRef(null);
  const searchInputRef = useRef(null);
  const searchTimeoutRef = useRef(null);

  // Get selected option
  const selectedOption = options.find(option => option[valueKey] == value);

  // Filter options based on search term
  useEffect(() => {
    if (searchTerm.trim() === '') {
      setFilteredOptions(options);
    } else {
      const filtered = options.filter(option => {
        const title = option[displayKey]?.toLowerCase() || '';
        const author = option[secondaryKey]?.toLowerCase() || '';
        const search = searchTerm.toLowerCase();
        return title.includes(search) || author.includes(search);
      });
      setFilteredOptions(filtered);
    }
    setHighlightedIndex(-1);
  }, [searchTerm, options, displayKey, secondaryKey]);

  // Handle search with debouncing
  useEffect(() => {
    if (onSearch && searchTerm.trim() !== '') {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
      searchTimeoutRef.current = setTimeout(() => {
        onSearch(searchTerm);
      }, searchDelay);
    }
    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, [searchTerm, onSearch, searchDelay]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
        setSearchTerm('');
        setHighlightedIndex(-1);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Focus search input when dropdown opens
  useEffect(() => {
    if (isOpen && searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, [isOpen]);

  const handleToggle = () => {
    if (!disabled) {
      setIsOpen(!isOpen);
      if (!isOpen) {
        setSearchTerm('');
        setHighlightedIndex(-1);
      }
    }
  };

  const handleSelect = (option) => {
    onChange(option[valueKey]);
    setIsOpen(false);
    setSearchTerm('');
    setHighlightedIndex(-1);
  };

  const handleKeyDown = (e) => {
    if (!isOpen) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setHighlightedIndex(prev => 
          prev < filteredOptions.length - 1 ? prev + 1 : 0
        );
        break;
      case 'ArrowUp':
        e.preventDefault();
        setHighlightedIndex(prev => 
          prev > 0 ? prev - 1 : filteredOptions.length - 1
        );
        break;
      case 'Enter':
        e.preventDefault();
        if (highlightedIndex >= 0 && filteredOptions[highlightedIndex]) {
          handleSelect(filteredOptions[highlightedIndex]);
        }
        break;
      case 'Escape':
        setIsOpen(false);
        setSearchTerm('');
        setHighlightedIndex(-1);
        break;
    }
  };

  const formatOptionText = (option) => {
    const title = option[displayKey] || '';
    const author = option[secondaryKey] || '';
    const price = option[priceKey] || '';
    
    if (author && price) {
      return `${title} - ${author} (${price} ريال)`;
    } else if (author) {
      return `${title} - ${author}`;
    } else if (price) {
      return `${title} (${price} ريال)`;
    }
    return title;
  };

  return (
    <div 
      ref={dropdownRef}
      className={`searchable-dropdown ${className} ${disabled ? 'disabled' : ''}`}
      style={style}
    >
      <div 
        className={`dropdown-trigger ${isOpen ? 'open' : ''}`}
        onClick={handleToggle}
        onKeyDown={handleKeyDown}
        tabIndex={disabled ? -1 : 0}
        role="button"
        aria-expanded={isOpen}
        aria-haspopup="listbox"
      >
        <div className="selected-value">
          {selectedOption ? (
            <div className="selected-option">
              {imageKey && selectedOption[imageKey] && (
                <img 
                  src={selectedOption[imageKey]} 
                  alt={selectedOption[displayKey]}
                  className="option-image"
                  onError={(e) => { e.target.src = '/images/book-placeholder.jpg'; }}
                />
              )}
              <span className="option-text">{formatOptionText(selectedOption)}</span>
            </div>
          ) : (
            <span className="placeholder">{placeholder}</span>
          )}
        </div>
        <div className="dropdown-arrow">
          <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
            <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
        </div>
      </div>

      {isOpen && (
        <div className="dropdown-menu">
          <div className="search-container">
            <input
              ref={searchInputRef}
              type="text"
              className="search-input"
              placeholder={searchPlaceholder}
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              onKeyDown={handleKeyDown}
            />
            <div className="search-icon">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M7 14C10.866 14 14 10.866 14 7C14 3.13401 10.866 0 7 0C3.13401 0 0 3.13401 0 7C0 10.866 3.13401 14 7 14Z" stroke="currentColor" strokeWidth="1.5"/>
                <path d="M13 13L10.1 10.1" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </div>
          </div>

          <div className="options-container">
            {loading ? (
              <div className="loading-option">
                <div className="loading-spinner"></div>
                <span>جاري التحميل...</span>
              </div>
            ) : filteredOptions.length === 0 ? (
              <div className="no-options">
                <span>لا توجد نتائج</span>
              </div>
            ) : (
              filteredOptions.map((option, index) => (
                <div
                  key={option[valueKey]}
                  className={`option ${highlightedIndex === index ? 'highlighted' : ''} ${value == option[valueKey] ? 'selected' : ''}`}
                  onClick={() => handleSelect(option)}
                  onMouseEnter={() => setHighlightedIndex(index)}
                >
                  {imageKey && option[imageKey] && (
                    <img 
                      src={option[imageKey]} 
                      alt={option[displayKey]}
                      className="option-image"
                      onError={(e) => { e.target.src = '/images/book-placeholder.jpg'; }}
                    />
                  )}
                  <div className="option-content">
                    <div className="option-title">{option[displayKey]}</div>
                    {option[secondaryKey] && (
                      <div className="option-author">{option[secondaryKey]}</div>
                    )}
                    {option[priceKey] && (
                      <div className="option-price">{option[priceKey]} ريال</div>
                    )}
                  </div>
                  {value == option[valueKey] && (
                    <div className="checkmark">
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                      </svg>
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default SearchableDropdown;
