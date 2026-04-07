const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const apiRoutes = require('./routes/index');

const app = express();
const PORT = process.env.PORT || 3001;

// Global Error Logger Utility
const logErrorToFile = (error, context = '') => {
    const timestamp = new Date().toISOString();
    const logMessage = `\n[${timestamp}] ${context}\n${error.stack || error.message || error}\n`;
    
    // Also output to console
    console.error(logMessage);
    
    // Write to error.log
    fs.appendFile(path.join(__dirname, 'error.log'), logMessage, (err) => {
        if (err) console.error('Failed to write to error.log:', err);
    });
};

// Catch Uncaught Exceptions & Unhandled Promise Rejections (Process level)
process.on('uncaughtException', (error) => {
    logErrorToFile(error, 'UNCAUGHT EXCEPTION');
});
process.on('unhandledRejection', (reason, promise) => {
    logErrorToFile(reason, 'UNHANDLED REJECTION');
});

// Middleware
app.use(cors({
    origin: [
        'http://localhost:5173', 
        'http://localhost:3000', 
        'http://127.0.0.1:5173',
        'https://ekamuthu-enterprises.sourcecode.lk'
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

// Global Error Handling Middleware (Express level)
app.use((err, req, res, next) => {
    logErrorToFile(err, `EXPRESS ROUTE ERROR: ${req.method} ${req.originalUrl}`);
    res.status(500).json({ success: false, message: 'Internal Server Error' });
});

// Start server
app.listen(PORT, () => {
    console.log(`\n🚀 POS Server running on http://localhost:${PORT}`);
    console.log(`📦 API available at http://localhost:${PORT}/api`);
    console.log(`💚 Health check: http://localhost:${PORT}/health\n`);
});
