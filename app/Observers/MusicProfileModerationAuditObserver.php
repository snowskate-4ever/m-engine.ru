<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ModerationStatus;
use App\Support\Moderation\ModerationAuditRecorder;
use Illuminate\Database\Eloquent\Model;

final class MusicProfileModerationAuditObserver
{
    /** @var list<string> */
    private const TRACK = ['moderation_hidden_at', 'moderation_reason', 'moderation_review_requested_at', 'moderation_status'];

    public function created(Model $model): void
    {
        $snapshot = $this->onlyTrack($model, self::TRACK);
        if ($this->allEmptyModeration($snapshot)) {
            return;
        }
        ModerationAuditRecorder::record($model, 'music_profile.moderation_initial', [], $snapshot);
    }

    public function updated(Model $model): void
    {
        $old = [];
        $new = [];
        foreach (self::TRACK as $key) {
            if ($model->wasChanged($key)) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $model->getAttribute($key);
            }
        }

        if ($old !== [] || $new !== []) {
            ModerationAuditRecorder::record($model, 'music_profile.moderation_updated', $old, $new);
        }
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function onlyTrack(Model $model, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $model->getAttribute($key);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function allEmptyModeration(array $snapshot): bool
    {
        foreach ($snapshot as $key => $v) {
            if ($key === 'moderation_status') {
                $status = $v instanceof ModerationStatus ? $v : ModerationStatus::tryFrom((string) $v);
                if ($status !== null && $status !== ModerationStatus::Approved) {
                    return false;
                }

                continue;
            }
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }
}
