<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AiRequestLogCsvExportService
{
    public const MAX_ROWS = 50_000;

    /**
     * @return list<string>
     */
    private function headerRow(): array
    {
        return [
            'id',
            'created_at',
            'user_id',
            'user_email',
            'conversation_id',
            'source',
            'status',
            'duration_ms',
            'http_status',
            'tokens_prompt',
            'tokens_completion',
            'estimated_internal_cost',
            'ai_server_model_id',
            'user_ai_connection_id',
            'provider_request_id',
            'provider_error_code',
            'error_message',
            'prompt_excerpt',
            'response_excerpt',
        ];
    }

    public function streamResponse(): StreamedResponse
    {
        $filename = 'ai-request-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $this->headerRow());

            $query = AiRequestLog::query()
                ->with(['user:id,email'])
                ->orderByDesc('id')
                ->limit(self::MAX_ROWS);

            foreach ($query->cursor() as $log) {
                /** @var AiRequestLog $log */
                fputcsv($out, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    $log->user_id,
                    $log->user?->email,
                    $log->conversation_id,
                    $log->source->value,
                    $log->status->value,
                    $log->duration_ms,
                    $log->http_status,
                    $log->tokens_prompt,
                    $log->tokens_completion,
                    $log->estimated_internal_cost,
                    $log->ai_server_model_id,
                    $log->user_ai_connection_id,
                    $log->provider_request_id,
                    $log->provider_error_code,
                    $log->error_message,
                    $log->prompt_excerpt,
                    $log->response_excerpt,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
