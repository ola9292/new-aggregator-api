<?php

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/news', function () {
    // Define your sources
    $sources = [
        ['name' => 'Punch', 'url' => 'https://rss.punchng.com/v1/category/latest_news'],
        ['name' => 'Vanguard', 'url' => 'https://www.vanguardngr.com/feed/'],
    ];

    foreach ($sources as $source) {
        $response = Http::get($source['url']);
        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

        foreach ($xml->channel->item as $item) {
            $content = (string) $item->children('content', true)->encoded ?: (string) $item->description;

            // Clean footer based on source
            if ($source['name'] === 'Vanguard') {
                $content = preg_replace('/<p>The post.*?<\/p>/is', '', $content);
                $content = preg_replace('/<img.*?>/i', '', $content); // Remove embedded img
            } else {
                $imageUrl = (string) $item->enclosure['url'];
                $content = preg_replace('/Read More: https?:\/\/.*$/i', '', $content);
            }

            Blog::updateOrCreate(
                ['source_url' => (string) $item->link],
                [
                    'title' => html_entity_decode((string) $item->title),
                    'slug' => Str::slug((string) $item->title).'-'.Str::random(5),
                    'content' => trim($content),
                    'image_url' => $source['name'] === 'Vanguard' ? $imageUrl : (string) $item->enclosure['url'],
                    'source_name' => $source['name'],
                    'category' => (string) $item->category ?: 'General',
                    'published_at' => Carbon::parse((string) $item->pubDate),
                    // 'user_id' => 1,
                ]
            );
        }
    }

    return 'done';
});
