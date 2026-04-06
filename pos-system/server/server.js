const express = require('express');
const cors = require('cors');
const path = require('path');
const apiRoutes = require('./routes/index');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors({
    origin: [
        'http://localhost:5173', 
        'http://localhost:3000', 
        'http://127.0.0.1:5173',
        'https://ekamuthu.sourcecode.lk'
    ],
    credentials: true
}));
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));
app.use('/uploads', express.static(path.join(__dirname, '../../uploads')));

// API Routes
app.use('/api', apiRoutes);

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// SERVE FRONTEND (PRODUCTION)
// The dist folder will be uploaded to the same directory as the server files in production
const frontendPath = path.join(__dirname, 'dist');
app.use(express.static(frontendPath));

// Catch-all route for React SPA navigation
app.get('*', (req, res) => {
    // If it starts with /api, we should have caught it by now. 
    // Otherwise, send the React index.html.
    if (!req.url.startsWith('/api')) {
        res.sendFile(path.join(frontendPath, 'index.html'));
    }
});

// Start server
app.listen(PORT, () => {
    console.log(`\n🚀 POS Server running on http://localhost:${PORT}`);
    console.log(`📦 API available at http://localhost:${PORT}/api`);
    console.log(`💚 Health check: http://localhost:${PORT}/health\n`);
});
