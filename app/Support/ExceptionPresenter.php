<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionPresenter
{
    /**
     * @return array{status:int, code:string, title:string, message:string, details:?string, reference:string}
     */
    public function present(Throwable $exception): array
    {
        $error = $exception instanceof QueryException
            ? $this->presentQueryException($exception)
            : [
                'status' => 500,
                'code' => 'SYS-ERROR',
                'title' => __('web.errors.generic.system_title'),
                'message' => __('web.errors.generic.system_message'),
                'details' => config('app.debug') ? $exception->getMessage() : null,
            ];

        $error['reference'] = $error['code'].'-'.Str::upper(Str::random(6));

        $this->logException($exception, $error);

        return $error;
    }

    protected function logException(Throwable $exception, array $error): void
    {
        Log::channel('filament_error')->error('['.$error['reference'].'] '.$exception->getMessage(), [
            'reference' => $error['reference'],
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => request()?->fullUrl(),
            'user_id' => auth()->id(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function toResponse(Throwable $exception, Request $request): Response
    {
        $error = $this->present($exception);

        if ($request->expectsJson() || $request->is('livewire/*') || filled($request->header('X-Livewire'))) {
            return response()->json([
                'message' => $error['details'] ?? $error['message'],
                'title' => $error['title'],
                'error_code' => $error['code'],
                'reference' => $error['reference'],
                'details' => $error['details'],
                'log' => config('app.debug') ? [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile().':'.$exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ] : null,
            ], $error['status']);
        }

        return response()->view('errors.application', [
            'error' => $error,
        ], $error['status']);
    }

    /**
     * @return array{status:int, code:string, title:string, message:string, details:?string}
     */
    protected function presentQueryException(QueryException $exception): array
    {
        $message = $exception->getMessage();

        if ($this->isDuplicateConstraint($message)) {
            return [
                'status' => 422,
                'code' => 'DB-DUPLICATE',
                'title' => __('web.errors.generic.validation_title'),
                'message' => __('web.errors.generic.duplicate_message'),
                'details' => config('app.debug') ? $message : null,
            ];
        }

        if ($this->isForeignKeyConstraint($message)) {
            return [
                'status' => 422,
                'code' => 'DB-CONSTRAINT',
                'title' => __('web.errors.generic.validation_title'),
                'message' => __('web.errors.generic.constraint_message'),
                'details' => config('app.debug') ? $message : null,
            ];
        }

        return [
            'status' => 500,
            'code' => 'DB-QUERY',
            'title' => __('web.errors.generic.database_title'),
            'message' => __('web.errors.generic.database_message'),
            'details' => config('app.debug') ? $message : null,
        ];
    }

    protected function isDuplicateConstraint(string $message): bool
    {
        return str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'duplicate key value violates unique constraint');
    }

    protected function isForeignKeyConstraint(string $message): bool
    {
        return str_contains($message, 'foreign key constraint fails')
            || str_contains($message, 'a foreign key constraint fails')
            || str_contains($message, 'FOREIGN KEY constraint failed')
            || str_contains($message, 'violates foreign key constraint');
    }
}
