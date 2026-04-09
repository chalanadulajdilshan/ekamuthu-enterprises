const mysql = require('mysql2/promise');

const isProd = process.env.NODE_ENV === 'production';

const pool = mysql.createPool({
    host: 'localhost',
    user: isProd ? 'chalcepi_ekamuthu-enterprises' : 'root',
    password: isProd ? '!}}c~bOdZR#g' : '',
    database: isProd ? 'chalcepi_ekamuthu-enterprises' : 'ekamuthu-enterprises',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    charset: 'utf8mb4'
});

// Test connection on startup
pool.getConnection()
    .then(conn => {
        console.log('✅ Database connected successfully');
        conn.release();
    })
    .catch(err => {
        console.error('❌ Database connection failed:', err.message);
    });

module.exports = pool;
