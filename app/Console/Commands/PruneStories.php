<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Support\StoryDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneStories extends Command
{
    protected $signature = 'stories:prune';

    protected $description = 'Prune expired story data on the dedicated stories connection';

    public function handle(): int
    {
        if (! config('stories.enabled')) {
            $this->info('Stories disabled; no records touched.');

            return self::SUCCESS;
        }
        StoryDatabase::ensureConfigured();
        $cutoff = now()->subDays(max(1, (int) config('stories.retention_days')));
        $ids = Story::withTrashed()->where('created_at', '<', $cutoff)->orderBy('id')->limit(1000)->pluck('id');
        DB::connection('stories')->table('stories')->whereIn('id', $ids)->delete();
        DB::connection('stories')->table('story_limits')->where('expires_at', '<', now()->subDay())->delete();
        DB::connection('stories')->table('story_reactions')->where('created_at', '<', $cutoff)->delete();
        $this->info('Pruned '.$ids->count().' expired story records (maximum 1000 per run).');

        return self::SUCCESS;
    }
}
