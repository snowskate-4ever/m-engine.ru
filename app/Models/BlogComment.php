<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogComment extends Model
{
    protected $fillable = [
        'blog_post_id',
        'author_user_id',
        'parent_id',
        'body',
        'moderation_status',
    ];

    protected function casts(): array
    {
        return [
            'moderation_status' => ModerationStatus::class,
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogComment::class, 'parent_id');
    }
}
