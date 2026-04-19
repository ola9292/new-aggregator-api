<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveOldNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-old-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove news 30 days old or more';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $blogs_count = Blog::whereDate('created_at', '<=', now()->subDays(30))->count();

        if ($blogs_count > 0) {
            Blog::whereDate('created_at', '<=', now()->subDays(30))->delete();
            Log::info("News Sanitizer: Removed {$blogs_count} old records.");
        } else {
            Log::info('found no records.');
        }

    }
}
