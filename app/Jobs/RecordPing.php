<?php

namespace App\Jobs;

use App\Models\Ping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Marks the ping as processed and records which container ran it.
 */
class RecordPing implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $pingId) {}

    public function handle(): void
    {
        Ping::whereKey($this->pingId)->update([
            'processed_by' => gethostname(),
            'processed_at' => now(),
        ]);
    }
}
