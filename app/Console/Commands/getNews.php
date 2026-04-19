<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class getNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'pull news from punch and vanguard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        try {
            $sources = [
                ['name' => 'Punch', 'url' => 'https://rss.punchng.com/v1/category/latest_news'],
                ['name' => 'Vanguard', 'url' => 'https://www.vanguardngr.com/feed/'],
            ];
            foreach ($sources as $source) {
                $response = Http::get($source['url']);

                if ($response->failed()) {
                    Log::warning("Skipping {$source['name']}: Source is unreachable or returned an error.");

                    continue;
                }
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if (! $xml->channel->item) {
                    Log::info(['message', 'no news item found!']);
                }

                foreach ($xml->channel->item as $item) {

                    $imageUrl = null;
                    $content = (string) $item->children('content', true)->encoded ?: (string) $item->description;

                    // 1. ALWAYS try to find an <img> tag first (Works for Vanguard & some Punch)
                    if (preg_match('/<img.+src=["\']([^"\']+)["\']/', $content, $matches)) {
                        $imageUrl = $matches[1];
                    }

                    // 2. If no <img> found, check the enclosure (Punch fallback)
                    if (! $imageUrl && isset($item->enclosure)) {
                        $imageUrl = (string) $item->enclosure['url'];
                    }

                    // 3. IF the image is still the Punch logo, maybe set it to null
                    // so your Vue app can show a nice category-based placeholder instead
                    if (str_contains($imageUrl, 'punch-logo')) {
                        $imageUrl = null;
                    }

                    // --- Clean the content as usual ---
                    if ($source['name'] === 'Vanguard') {
                        $content = preg_replace('/<p>The post.*?<\/p>/is', '', $content);
                        $content = preg_replace('/<img.*?>/i', '', $content);
                    } else {
                        $content = preg_replace('/Read More: https?:\/\/.*$/i', '', $content);
                    }

                    Blog::updateOrCreate(
                        ['source_url' => (string) $item->link],
                        [
                            'title' => html_entity_decode((string) $item->title),
                            'content' => trim(strip_tags($content, '<p><br><b><strong>')),
                            'image_url' => $imageUrl,
                            'source_name' => $source['name'],
                            'category' => (string) $item->category ?: 'General',
                            'published_at' => Carbon::parse((string) $item->pubDate),
                            // 'user_id' => 1, // Uncommented since it's likely required
                        ]
                    );
                }
            }
            Cache::flush();
            $this->comment('Cache cleared.');
            Log::info('News Sync Successful', [
                'started_at' => $startTime->toDateTimeString(),
                'finished_at' => now()->toDateTimeString(),
                'duration' => now()->diffInSeconds($startTime).' seconds',
            ]);
        } catch (Throwable $e) {
            Log::error('News Sync Failed: '.$e->getMessage());
        }

    }
}
