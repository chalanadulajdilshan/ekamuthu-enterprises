const axios = require('axios');

async function test() {
    try {
        const res = await axios.get('http://localhost:3001/api/products?all=true');
        console.log('Products structure:', Object.keys(res.data));
        console.log('Products data type:', Array.isArray(res.data.data) ? 'Array' : typeof res.data.data);
        
        const res2 = await axios.get('http://localhost:3001/api/suppliers');
        console.log('Suppliers structure:', Object.keys(res2.data));
        
        const res3 = await axios.get('http://localhost:3001/api/departments');
        console.log('Departments structure:', Object.keys(res3.data));
    } catch (e) {
        console.error('API Error:', e.message);
    }
}

test();
