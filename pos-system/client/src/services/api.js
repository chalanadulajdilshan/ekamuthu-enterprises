import axios from 'axios';

const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const API_BASE = isLocalhost 
    ? 'http://localhost:3001/api' 
    : 'https://pos-ekamuthu.sourcecode.lk/api';

const api = axios.create({
    baseURL: API_BASE,
    timeout: 60000,
    headers: {
        'Content-Type': 'application/json',
    }
});

// Visible diagnostics: log every request/response, surface failures.
api.interceptors.request.use((config) => {
    console.log('[API →]', (config.method || 'get').toUpperCase(), (config.baseURL || '') + (config.url || ''), config.params || '');
    return config;
});
api.interceptors.response.use(
    (res) => {
        console.log('[API ←]', res.status, res.config?.url, 'data keys:', res.data && typeof res.data === 'object' ? Object.keys(res.data) : typeof res.data);
        return res;
    },
    (err) => {
        const url = err.config?.url || '(no url)';
        const status = err.response?.status || 'NO_RESPONSE';
        const msg = err.response?.data?.message || err.message || 'unknown error';
        console.error('[API ✗]', status, url, msg, err);
        // Tag the error so callers / global handlers display something useful.
        err.userMessage = `API ${status} ${url}: ${msg}`;
        return Promise.reject(err);
    }
);

// Products
export const getProducts = (params = {}) => api.get('/products', { params, timeout: 120000 });
export const createProduct = (data) => api.post('/products', data);
export const updateProduct = (id, data) => api.put(`/products/${id}`, data);
export const getCategories = () => api.get('/categories');
export const deleteProduct = (id) => api.delete(`/products/${id}`);
export const getBrands = () => api.get('/brands');
export const getBrandCategories = () => api.get('/brand-categories');
export const createBrand = (data) => api.post('/brands', data);
export const updateBrand = (id, data) => api.put(`/brands/${id}`, data);

// Customers / Suppliers
export const getCustomers = (search = '') => api.get('/customers', { params: { search } });
export const getSuppliers = (search = '') => api.get('/suppliers', { params: { search } });
export const createSupplier = (data) => api.post('/suppliers', data);
export const updateSupplier = (id, data) => api.put(`/suppliers/${id}`, data);
export const deleteSupplier = (id) => api.delete(`/suppliers/${id}`);

// Company
export const getCompany = () => api.get('/company');

// Departments
export const getDepartments = () => api.get('/departments');

// Payment Types
export const getPaymentTypes = () => api.get('/payment-types');

// Sales
export const createSale = (data) => api.post('/sales', data);
export const getRecentSales = () => api.get('/sales/recent');
export const getSaleDetails = (id) => api.get(`/sales/${id}`);

// Dashboard
export const getDashboardStats = () => api.get('/dashboard-stats');

// GRN (ARN)
export const getGrns = () => api.get('/grn');
export const createGrn = (data) => api.post('/grn', data);
export const getNextGrnNo = () => api.get('/grn/next-no');
export const getGrnDetails = (id) => api.get(`/grn/${id}`);

export default api;
