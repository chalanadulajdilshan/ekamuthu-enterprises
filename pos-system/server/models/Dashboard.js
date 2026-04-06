const db = require('../config/db');

class Dashboard {
    static async getStats() {
        const today = new Date().toISOString().slice(0, 10);

        const [todaySales] = await db.query(`
            SELECT COUNT(*) as count, IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice
            WHERE DATE(invoice_date) = ? AND invoice_no LIKE 'POS-%' AND is_cancel = 0
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

        return {
            today_sales_count: todaySales[0].count,
            today_sales_total: todaySales[0].total,
            total_products: totalProducts[0].count,
            low_stock_count: lowStock[0].count
        };
    }
}

module.exports = Dashboard;
