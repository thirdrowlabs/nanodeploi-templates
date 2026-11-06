<?php

use App\Jobs\RecordPing;
use App\Models\Ping;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pings', [
        'pings' => Ping::latest('id')->limit(10)->get(),
        'queue' => config('queue.default'),
        'web' => gethostname(),
    ]);
});

// Demo only: a GET so the page and the automated tests can trigger it without a form.
Route::get('/dispatch', function () {
    $ping = Ping::create(['dispatched_by' => gethostname()]);
    RecordPing::dispatch($ping->id);

    return redirect('/');
});

Route::get('/health', function (): JsonResponse {
    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        return response()->json(['app' => '10-laravel-queue', 'ok' => false, 'database' => $e->getMessage()], 503);
    }

    $queue = config('queue.default');
    $stuck = Ping::whereNull('processed_at')->where('created_at', '<', now()->subMinute())->count();
    $last = Ping::whereNotNull('processed_at')->latest('id')->first();
    $ok = $queue !== 'sync' && $stuck === 0;

    return response()->json([
        'app' => '10-laravel-queue',
        'ok' => $ok,
        'database' => 'connected',
        'queue' => $queue,
        'stuck_jobs' => $stuck,
        'last_job_ran_on_worker' => $last?->ranOnWorker(),
    ], $ok ? 200 : 503);
});
