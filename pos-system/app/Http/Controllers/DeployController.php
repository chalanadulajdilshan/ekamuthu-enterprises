<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * POST /_deploy, called by GitHub Actions right after the FTP upload, because the
 * host is FTP-only (no SSH to run artisan). Header: X-Deploy-Token: <POS_DEPLOY_TOKEN>.
 *
 *  1. migrate --force   creates/updates the POS tables (its own database)
 *  2. db:seed --force   idempotent: first admin user, branch, category tabs
 *  3. optimize          cache config / routes / views for speed
 *
 * The response is deliberately terse: deploy logs of a public repo are public.
 */
class DeployController extends Controller
{
    /** Every table the POS migrations create (child tables first). */
    private const POS_TABLES = [
        'supplier_payments', 'customer_payments', 'stock_movements', 'purchase_items', 'purchases',
        'held_sales', 'sale_items', 'sales', 'suppliers', 'customers', 'product_stocks', 'products',
        'categories', 'doc_sequences', 'settings', 'branches',
        'failed_jobs', 'job_batches', 'jobs', 'cache_locks', 'cache',
        'sessions', 'password_reset_tokens', 'users',
    ];

    public function __invoke(Request $request)
    {
        $token = (string) config('pos.deploy_token');
        abort_if($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token')), 404);

        @set_time_limit(900);
        @ignore_user_abort(true);

        $steps = [];
        try {
            Artisan::call('optimize:clear');
            $steps['recover'] = $this->recoverHalfInstalledSchema();
            $steps['migrate'] = Artisan::call('migrate', ['--force' => true]);
            $steps['seed'] = Artisan::call('db:seed', ['--force' => true]);

            $steps['optimize'] = Artisan::call('optimize');
        } catch (Throwable $e) {
            Log::error('Deploy hook failed', ['exception' => $e]);

            // First line only, SQL text cut off: enough to diagnose (e.g. "Access denied
            // for user ..." / "Unknown database ...") without leaking queries. PDO never
            // includes the password in its messages.
            $msg = strtok($e->getMessage(), "\n");
            $msg = preg_replace('/\s*\(Connection: .*$/s', '', (string) $msg);

            try {
                $db = DB::selectOne('SELECT VERSION() AS v')->v;
            } catch (Throwable) {
                $db = 'unknown';
            }

            return response()->json([
                'ok' => false, 'steps' => $steps, 'db_version' => $db,
                'error' => class_basename($e).': '.mb_substr($msg, 0, 300),
            ], 500);
        }

        $failed = array_filter($steps, fn ($code) => is_int($code) && $code !== 0);

        return response()->json(['ok' => ! $failed, 'steps' => $steps], $failed ? 500 : 200);
    }

    /**
     * MySQL DDL is not transactional: if the very first install dies half-way
     * (e.g. an index too long for an old MySQL), it leaves empty tables behind and
     * every retry fails with "table already exists".
     *
     * Only when NO migration has ever completed, drop leftover POS tables that are
     * EMPTY. Tables holding any row are never touched, so this can't lose data even
     * if the hook pointed at the wrong database (the migration would then just fail).
     */
    private function recoverHalfInstalledSchema(): string
    {
        if (Schema::hasTable('migrations') && DB::table('migrations')->exists()) {
            return 'not needed';
        }

        $dropped = [];
        Schema::disableForeignKeyConstraints();
        try {
            foreach (self::POS_TABLES as $table) {
                if (Schema::hasTable($table) && ! DB::table($table)->exists()) {
                    Schema::drop($table);
                    $dropped[] = $table;
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $dropped ? 'dropped empty: '.implode(',', $dropped) : 'clean';
    }
}
