<?php

namespace App\Observers;

use App\Models\Grade;
use App\Models\GradeHistory;
use Illuminate\Support\Facades\Auth;

class GradeObserver
{
    /**
     * Handle the Grade "updated" event.
     */
    public function updated(Grade $grade): void
    {
        $original = $grade->getOriginal('score');
        $current = $grade->score;

        if ((string) $original === (string) $current) {
            return;
        }

        $userId = Auth::id() ?: null;

        GradeHistory::create([
            'grade_id' => $grade->id,
            'user_id' => $userId,
            'old_score' => $original !== null ? (float) $original : null,
            'new_score' => (float) $current,
            'reason' => 'Automatic update (no reason provided)',
        ]);
    }
}
