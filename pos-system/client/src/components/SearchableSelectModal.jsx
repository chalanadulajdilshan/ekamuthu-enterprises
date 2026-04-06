import React, { useState, useEffect, useRef } from 'react';
import { FiSearch, FiX, FiCheck } from 'react-icons/fi';

/**
 * SearchableSelectModal
 * @param {boolean} isOpen - Whether the modal is visible
 * @param {function} onClose - Function to close the modal
 * @param {function} onSelect - Function called when an item is selected
 * @param {Array} data - Array of objects to be selected from
 * @param {string} title - Modal title
 * @param {string} searchPlaceholder - Placeholder for the search input
 * @param {string|function} renderItem - How to display each item (either a key name or a render function)
 * @param {string} keyExtractor - Field to use as unique key (default 'id')
 */
const SearchableSelectModal = ({ 
  isOpen, 
  onClose, 
  onSelect, 
  data = [], 
  title = "Select Item", 
  searchPlaceholder = "Search...",
  renderItem,
  keyExtractor = 'id'
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const searchInputRef = useRef(null);

  // Focus search input when modal opens
  useEffect(() => {
    if (isOpen && searchInputRef.current) {
      setTimeout(() => searchInputRef.current.focus(), 100);
    }
    if (!isOpen) {
      setSearchTerm('');
    }
  }, [isOpen]);

  // Handle escape key
  useEffect(() => {
    const handleEsc = (e) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', handleEsc);
    return () => window.removeEventListener('keydown', handleEsc);
  }, [onClose]);

  if (!isOpen) return null;

  const safeData = Array.isArray(data) ? data : [];

  const filteredData = safeData.filter(item => {
    const searchStr = searchTerm.toLowerCase();
    // Search across all string/number fields of the object
    return Object.values(item).some(val => 
      String(val).toLowerCase().includes(searchStr)
    );
  });

  const handleSelectItem = (item) => {
    onSelect(item);
    onClose();
  };

  return (
    <div className="selection-modal-overlay" onClick={onClose}>
      <div className="selection-modal" onClick={e => e.stopPropagation()}>
        <div className="selection-modal-header">
          <h3 className="selection-modal-title">{title}</h3>
          <button className="selection-modal-close" onClick={onClose}>
            <FiX />
          </button>
        </div>
        
        <div className="selection-modal-search">
          <FiSearch className="search-icon" />
          <input 
            ref={searchInputRef}
            type="text" 
            className="search-input" 
            placeholder={searchPlaceholder}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <div className="selection-modal-list">
          {filteredData.length > 0 ? (
            filteredData.map((item, index) => (
              <div 
                key={item[keyExtractor] || index} 
                className="selection-list-item"
                onClick={() => handleSelectItem(item)}
              >
                <div className="item-content">
                  {typeof renderItem === 'function' ? (
                    renderItem(item)
                  ) : (
                    <>
                      {item.code && <span className="item-code">{item.code}</span>}
                      <span className="item-name">{item[renderItem] || item.name}</span>
                    </>
                  )}
                </div>
                <FiCheck className="select-icon" />
              </div>
            ))
          ) : (
            <div className="selection-empty">
              <p>No results found for "{searchTerm}"</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default SearchableSelectModal;
