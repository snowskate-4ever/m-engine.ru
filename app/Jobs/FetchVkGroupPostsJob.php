<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\VkPost;
use App\Models\VkPostMedia;
use App\Models\VkSetting;
use App\Models\VkTracking;
use App\Services\api\VkApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchVkGroupPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $vkTrackingId,
        public int $userId,
        public int $count = 100,
        public ?string $startFrom = null
    ) {
        $this->onQueue('vk');
    }

    public function handle(): void
    {
        $tracking = VkTracking::find($this->vkTrackingId);
        if (!$tracking || !$tracking->group_id) {
            Log::warning('FetchVkGroupPostsJob: tracking not found or no group_id', ['id' => $this->vkTrackingId]);
            return;
        }

        $settings = VkSetting::instance();
        $token = $settings->vk_access_token ?? null;
        if (!$token) {
            Log::warning('FetchVkGroupPostsJob: vk_settings has no vk_access_token');
            return;
        }

        $service = new VkApiService();
        $result = $service->getWallPosts(
            (int) $tracking->group_id,
            $token,
            $this->count,
            $this->startFrom ? 0 : 0,
            $this->startFrom
        );

        if ($result['error'] ?? true) {
            Log::error('FetchVkGroupPostsJob: wall.get failed', [
                'vk_tracking_id' => $this->vkTrackingId,
                'error' => $result['error_msg'] ?? 'unknown',
            ]);
            return;
        }

        $items = $result['response']['items'] ?? [];
        $nextFrom = $result['response']['next_from'] ?? null;

        foreach ($items as $post) {
            $vkPostId = (int) ($post['id'] ?? 0);
            if ($vkPostId <= 0) {
                continue;
            }
            $exists = VkPost::where('vk_tracking_id', $this->vkTrackingId)
                ->where('vk_post_id', $vkPostId)
                ->exists();
            if ($exists) {
                continue;
            }

            $postedAt = isset($post['date']) ? \Carbon\Carbon::createFromTimestamp((int) $post['date']) : null;
            $rawJson = $post; // сохраняем сырой пост целиком

            $vkPost = VkPost::create([
                'vk_tracking_id' => $this->vkTrackingId,
                'vk_post_id' => $vkPostId,
                'from_id' => $post['from_id'] ?? null,
                'signer_id' => $post['signer_id'] ?? null,
                'text' => $post['text'] ?? null,
                'raw_json' => $rawJson,
                'posted_at' => $postedAt,
            ]);

            $this->processAttachments($vkPost, $post['attachments'] ?? []);
        }

        if ($nextFrom !== null) {
            $tracking->update(['next_from' => $nextFrom]);
        }
    }

    private function processAttachments(VkPost $vkPost, array $attachments): void
    {
        $order = 0;
        foreach ($attachments as $att) {
            $type = $att['type'] ?? '';
            $url = null;
            $mediaType = null;

            if ($type === 'photo' && isset($att['photo'])) {
                $photo = $att['photo'];
                $sizes = $photo['sizes'] ?? [];
                $url = $this->getLargestPhotoUrl($sizes);
                if (!$url && !empty($photo['url'])) {
                    $url = $photo['url'];
                }
                if ($url) {
                    $mediaType = 'photo';
                }
            } elseif ($type === 'audio' && isset($att['audio'])) {
                $audio = $att['audio'];
                $url = $audio['url'] ?? null;
                if ($url) {
                    $mediaType = 'audio';
                }
            }

            if ($url && $mediaType) {
                $media = VkPostMedia::create([
                    'vk_post_id' => $vkPost->id,
                    'type' => $mediaType,
                    'vk_url' => $url,
                    'path' => null,
                    'sort_order' => $order,
                ]);
                DownloadVkMediaJob::dispatch($media->id);
                $order++;
            }
        }
    }

    private function getLargestPhotoUrl(array $sizes): ?string
    {
        $prefer = ['w', 'z', 'y', 'r', 'q', 'p', 'o', 'x', 'm', 's'];
        foreach ($prefer as $t) {
            foreach ($sizes as $s) {
                if (($s['type'] ?? '') === $t && !empty($s['url'])) {
                    return $s['url'];
                }
            }
        }
        return !empty($sizes) && !empty($sizes[count($sizes) - 1]['url'])
            ? $sizes[count($sizes) - 1]['url']
            : null;
    }
}
