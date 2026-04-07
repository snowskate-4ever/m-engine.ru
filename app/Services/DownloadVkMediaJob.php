<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VkPostMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadVkMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $vkPostMediaId
    ) {
        $this->onQueue('vk');
    }

    public function handle(): void
    {
        $media = VkPostMedia::find($this->vkPostMediaId);
        if (!$media || !$media->vk_url) {
            Log::warning('DownloadVkMediaJob: media not found or no vk_url', ['id' => $this->vkPostMediaId]);
            return;
        }

        if ($media->path) {
            return;
        }

        $post = $media->vkPost;
        if (!$post) {
            return;
        }

        $ext = $media->type === 'photo' ? '.jpg' : '.mp3';
        $relPath = sprintf(
            '%d/%d/%s_%d%s',
            $post->vk_tracking_id,
            $post->id,
            $media->type,
            $media->sort_order,
            $ext
        );

        try {
            $response = Http::timeout(30)->get($media->vk_url);
            if (!$response->successful()) {
                Log::warning('DownloadVkMediaJob: download failed', [
                    'id' => $this->vkPostMediaId,
                    'status' => $response->status(),
                ]);
                return;
            }
            Storage::disk('vk_posts')->put($relPath, $response->body());
            $media->update(['path' => $relPath]);
        } catch (\Throwable $e) {
            Log::error('DownloadVkMediaJob: exception', [
                'id' => $this->vkPostMediaId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
