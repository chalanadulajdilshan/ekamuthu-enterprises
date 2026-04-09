const db = require('../server/config/db');

async function migrate() {
    console.log('--- Starting Database Migration: UNIQUE Code Constraint ---');
    const conn = await db.getConnection();
    try {
        // Add unique index to code column
        // We use IGNORE if duplicates exist (but MySQL doesn't support ALTER IGNORE TABLE anymore in recent versions)
        // So we'll check for duplicates first to be safe
        const [duplicates] = await conn.query(`
            SELECT code, COUNT(*) as count 
            FROM item_master 
            GROUP BY code 
            HAVING count > 1
        `);

        if (duplicates.length > 0) {
            console.error('❌ Migration Aborted: Duplicate codes found in database.');
            console.table(duplicates);
            process.exit(1);
        }

        console.log('   Adding UNIQUE index to item_master.code...');
        await conn.query('ALTER TABLE item_master ADD UNIQUE INDEX idx_unique_code (code)');
        
        console.log('✅ Migration successful: item_master.code is now unique.');
    } catch (err) {
        if (err.code === 'ER_DUP_KEYNAME') {
            console.log('ℹ️  UNIQUE index already exists.');
        } else {
            console.error('❌ Migration failed:', err.message);
        }
    } finally {
        conn.release();
        process.exit(0);
    }
}

migrate();
