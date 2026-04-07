const db = require('./config/db');

async function test() {
    try {
        const today = new Date().toISOString().slice(0, 10);
        console.log('Today ISO:', today);

        const [todaySales] = await db.query(`
            SELECT COUNT(*) as count, IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice
            WHERE DATE(invoice_date) = ? AND invoice_no LIKE 'POS-%' AND status = 1
        `, [today]);
        console.log('Today Sales:', todaySales);

        const [dailySales] = await db.query(`
            SELECT 
                DATE(invoice_date) as date,
                IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice
            WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND status = 1
            GROUP BY DATE(invoice_date)
            ORDER BY DATE(invoice_date) ASC
        `);
        console.log('Daily Sales:', dailySales);

        const [deptSales] = await db.query(`
            SELECT 
                dm.name,
                IFNULL(SUM(si.grand_total), 0) as total
            FROM department_master dm
            LEFT JOIN sales_invoice si ON dm.id = si.department_id AND si.status = 1
            GROUP BY dm.id, dm.name
            HAVING total > 0
            ORDER BY total DESC
        `);
        console.log('Dept Sales:', deptSales);

        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

test();
