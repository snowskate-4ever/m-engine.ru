<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MessengerAttachmentDownloadController extends Controller
{
    public function __invoke(MessageAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('message');
        if ($attachment->message === null) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk);
        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        $name = $attachment->original_name !== null && $attachment->original_name !== ''
            ? $attachment->original_name
            : basename($attachment->path);

        return $disk->response($attachment->path, $name, [
            'Content-Type' => $attachment->mime !== null && $attachment->mime !== ''
                ? $attachment->mime
                : 'application/octet-stream',
        ], 'attachment');
    }
}
