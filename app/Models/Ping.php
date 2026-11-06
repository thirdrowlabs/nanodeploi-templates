<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One queued job: which container dispatched it and which one ran it.
 */
class Ping extends Model
{
    protected $fillable = ['dispatched_by', 'processed_by', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    /** True when the job ran in a different container than the web request (a real queue worker). */
    public function ranOnWorker(): bool
    {
        return $this->processed_by !== null && $this->processed_by !== $this->dispatched_by;
    }
}
