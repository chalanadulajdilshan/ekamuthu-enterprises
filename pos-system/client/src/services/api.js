import axios from 'axios';

const API_BASE = '/api';

const api = axios.create({
    baseURL: API_BASE,
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
    }
});

// Products
export const getProducts = (params = {}) => api.get('/products', { params });
export const createProduct = (data) => api.post('/products', data);
export const updateProduct = (id, data) => api.put(`/products/${id}`, data);
export const getCategories = () => api.get('/categories');
export const getBrands = () => api.get('/brands');

// Customers
export const getCustomers = (search = '') => api.get('/customers', { params: { search } });

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

export default api;
