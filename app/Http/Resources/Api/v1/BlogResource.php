<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'image' => $this->image_url,
            'source' => $this->source_name,
            'link' => $this->source_url,
            'category' => $this->category,
            // Format the date for a better UX (e.g., "2 hours ago")
            'published_at' => $this->published_at->diffForHumans(),
            // Include comment count without loading all comments
            'comment_count' => $this->comments_count ?? 0,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
