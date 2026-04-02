import { memo } from 'react';
import { FiPackage } from 'react-icons/fi';

const ProductGrid = memo(({ products, loading, onAddToCart }) => {
  if (loading) {
    return (
      <div className="pos-products">
        <div className="pos-loading">
          <div className="pos-spinner"></div>
          <span>Loading products...</span>
        </div>
      </div>
    );
  }

  if (products.length === 0) {
    return (
      <div className="pos-products">
        <div className="pos-empty">
          <FiPackage className="pos-empty-icon" />
          <div className="pos-empty-text">No products found</div>
          <div className="pos-empty-sub">Try a different search or category</div>
        </div>
      </div>
    );
  }

  return (
    <div className="pos-products">
      <div className="pos-products-grid">
        {products.map(product => (
          <div
            key={product.id}
            className="pos-product-card"
            onClick={() => onAddToCart(product)}
          >
            {product.brand_name && (
              <div className="pos-product-brand">{product.brand_name}</div>
            )}
            <div className="pos-product-code">{product.code}</div>
            <div className="pos-product-name">{product.name}</div>
            <div className="pos-product-meta">
              <div className="pos-product-price">
                Rs. {parseFloat(product.list_price).toLocaleString('en-US', { minimumFractionDigits: 2 })}
              </div>
              <div className={`pos-product-stock ${product.available_qty <= 5 ? 'low-stock' : 'in-stock'}`}>
                {product.available_qty} pcs
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
});

ProductGrid.displayName = 'ProductGrid';

export default ProductGrid;
