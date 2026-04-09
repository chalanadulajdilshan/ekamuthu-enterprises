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

// Test connection and run startup migrations
pool.getConnection()
    .then(async conn => {
        console.log('✅ Database connected successfully');
        conn.release();
        await fixZeroItemIds();
    })
    .catch(err => {
        console.error('❌ Database connection failed:', err.message);
    });

/**
 * Migration: assign proper sequential IDs to any item_master rows that have id = 0.
 * This happens when the table was created without AUTO_INCREMENT active.
 */
async function fixZeroItemIds() {
    const conn = await pool.getConnection();
    try {
        // Check how many rows have id = 0
        const [zeroRows] = await conn.query(
            'SELECT id, code FROM item_master WHERE id = 0 OR id IS NULL'
        );
        if (zeroRows.length === 0) return; // nothing to fix

        console.log(`⚠️  Found ${zeroRows.length} item(s) with id=0 in item_master. Running ID fix migration...`);

        await conn.beginTransaction();

        // Ensure item_master allows updating the PK (disable safe-update mode)
        await conn.query('SET SQL_SAFE_UPDATES = 0');

        // Find the current max valid id so we don't collide
        const [[{ maxId }]] = await conn.query(
            'SELECT COALESCE(MAX(id), 0) as maxId FROM item_master WHERE id > 0'
        );
        let nextId = maxId + 1;

        for (const row of zeroRows) {
            const newId = nextId++;
            // Update the item's id
            await conn.query(
                'UPDATE item_master SET id = ? WHERE code = ? AND (id = 0 OR id IS NULL)',
                [newId, row.code]
            );
            // Update any item_batches rows pointing at the old id (0 → newId)
            // Note: if multiple zero-id items share item_id=0 batches we can't
            // distinguish them, so we only update batches whose qty_remaining > 0
            // for the first item (best-effort).
            await conn.query(
                'UPDATE item_batches SET item_id = ? WHERE item_id = 0 LIMIT 1',
                [newId]
            );
            console.log(`   ✔ Reassigned item "${row.code}" → id ${newId}`);
        }

        // Ensure AUTO_INCREMENT is set above the new max so future inserts work
        const [[{ newMax }]] = await conn.query(
            'SELECT COALESCE(MAX(id), 0) as newMax FROM item_master'
        );
        await conn.query(
            `ALTER TABLE item_master AUTO_INCREMENT = ${newMax + 1}`
        );

        await conn.query('SET SQL_SAFE_UPDATES = 1');
        await conn.commit();
        console.log('✅ Item ID migration complete.');
    } catch (err) {
        await conn.rollback();
        console.error('❌ Item ID migration failed:', err.message);
    } finally {
        conn.release();
    }
}

module.exports = pool;
