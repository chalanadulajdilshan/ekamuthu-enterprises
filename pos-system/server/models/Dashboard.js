const db = require('../config/db');

class Dashboard {
    static async getStats() {
        const today = new Date().toISOString().slice(0, 10);

        const [todaySales] = await db.query(`
            SELECT COUNT(*) as count, IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice
            WHERE DATE(invoice_date) = ? AND invoice_no LIKE 'POS-%' AND status = 1
        `, [today]);

        const [totalProducts] = await db.query(`
            SELECT COUNT(*) as count FROM item_master WHERE is_active = 1
        `);

        const [lowStock] = await db.query(`
            SELECT COUNT(*) as count FROM (
                SELECT im.id, IFNULL(SUM(ib.qty_remaining), 0) as qty, im.re_order_level
                FROM item_master im
                LEFT JOIN item_batches ib ON im.id = ib.item_id
                WHERE im.is_active = 1
                GROUP BY im.id
                HAVING qty <= im.re_order_level AND qty > 0
            ) low_items
        `);

        // Last 7 days sales trend (using DATE_FORMAT for consistent JSON serialization)
        const [dailySales] = await db.query(`
            SELECT 
                DATE_FORMAT(invoice_date, '%Y-%m-%d') as date,
                IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice
            WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND status = 1
            GROUP BY DATE(invoice_date)
            ORDER BY DATE(invoice_date) ASC
        `);

        // Sales by department (including uncategorized sales)
        const [deptSales] = await db.query(`
            SELECT 
                COALESCE(dm.name, 'General/Other') as name,
                IFNULL(SUM(si.grand_total), 0) as total
            FROM sales_invoice si
            LEFT JOIN department_master dm ON si.department_id = dm.id
            WHERE si.status = 1
            GROUP BY si.department_id
            HAVING total > 0
            ORDER BY total DESC
        `);


        return {
            today_sales_count: todaySales[0].count,
            today_sales_total: todaySales[0].total,
            total_products: totalProducts[0].count,
            low_stock_count: lowStock[0].count,
            daily_sales: dailySales,
            department_sales: deptSales
        };
    }
}

module.exports = Dashboard;
