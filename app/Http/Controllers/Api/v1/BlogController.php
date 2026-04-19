<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\BlogResource;
use App\Http\Resources\Api\v1\CommentResource;
use App\Models\Blog;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $categoryInput = $request->query('category', 'All');

        $category = ucfirst(strtolower($categoryInput));
        // used for caching
        $page = $request->query('page', 1);

        $featuredQuery = Blog::withCount('comments')
            ->whereNotNull('image_url')
            ->where('image_url', 'not like', '%punch-logo%');

        if ($category != 'All') {
            $featuredQuery->where('category', $category);
        }

        $featured = $featuredQuery->latest('published_at')->first();

        // cache params
        $params = [
            'category' => $category,
            'page' => $page,
            'featured_id' => $featured?->id,
        ];
        $cache_key = 'blogs_'.md5(json_encode($params));

        $blogs = Cache::remember($cache_key, 3600, function () use ($category, $featured) {
            $blogQuery = Blog::withCount('comments');

            if ($category != 'All') {
                $blogQuery->where('category', $category);
            }

            if ($featured) {
                $blogQuery->where('id', '!=', $featured->id);
            }

            return $blogQuery->latest('published_at')->paginate(10);
        });

        return response()->json([
            'featured' => $featured ? new BlogResource($featured) : null,
            'blogs' => BlogResource::collection($blogs)->response()->getData(true),
        ]);
    }

    public function show(Blog $blog)
    {
        $blog->load([
            'comments.user',
            'comments.likes' => function ($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                }
            },
        ])->loadCount('comments');

        $blog->comments->each->loadCount('likes');
        // dd([
        //     'auth_id' => auth()->id(),
        //     'auth_check' => auth()->check(),
        //     'comments' => $blog->comments->map(fn ($c) => [
        //         'id' => $c->id,
        //         'likes_loaded' => $c->relationLoaded('likes'),
        //         'likes_collection' => $c->likes,
        //     ]),
        // ]);

        return new BlogResource($blog);
    }

    public function comment(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2'],
        ]);

        $comment = $blog->comments()->create([
            'body' => $validated['body'],
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'comment created',
            'data' => new CommentResource($comment->load('user')),
        ], 201);

    }

    public function toggleLike(Comment $comment)
    {
        $user = auth()->user();

        // detach() removes the record, attach() adds it.
        // toggle() does both automatically!
        $status = $user->likedComments()->toggle($comment->id);

        return response()->json([
            'is_liked' => count($status['attached']) > 0,
            'likes_count' => $comment->likes()->count(),
        ]);
    }
}
