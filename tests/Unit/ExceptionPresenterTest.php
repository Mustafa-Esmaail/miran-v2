<?php

namespace Tests\Unit;

use App\Support\ExceptionPresenter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExceptionPresenterTest extends TestCase
{
    public function test_it_maps_duplicate_database_errors_to_descriptive_message(): void
    {
        config()->set('app.debug', false);

        $presenter = new ExceptionPresenter;
        $error = $presenter->present(new QueryException(
            'mysql',
            'insert into users (email) values (?)',
            ['taken@example.com'],
            new \Exception('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry')
        ));

        $this->assertSame(422, $error['status']);
        $this->assertSame('DB-DUPLICATE', $error['code']);
        $this->assertSame(__('web.errors.generic.validation_title'), $error['title']);
        $this->assertSame(__('web.errors.generic.duplicate_message'), $error['message']);
        $this->assertNull($error['details']);
    }

    public function test_it_maps_foreign_key_errors_to_descriptive_message(): void
    {
        config()->set('app.debug', false);

        $presenter = new ExceptionPresenter;
        $error = $presenter->present(new QueryException(
            'mysql',
            'delete from categories where id = ?',
            [1],
            new \Exception('SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails')
        ));

        $this->assertSame(422, $error['status']);
        $this->assertSame('DB-CONSTRAINT', $error['code']);
        $this->assertSame(__('web.errors.generic.constraint_message'), $error['message']);
    }

    public function test_it_builds_json_response_for_async_requests(): void
    {
        config()->set('app.debug', true);

        $presenter = new ExceptionPresenter;
        $response = $presenter->toResponse(
            new \RuntimeException('Detailed exception message'),
            Request::create('/livewire/update', 'POST', server: ['HTTP_X-LIVEWIRE' => 'true', 'HTTP_ACCEPT' => 'application/json'])
        );

        $payload = $response->getData(true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('SYS-ERROR', $payload['error_code']);
        $this->assertSame(__('web.errors.generic.system_title'), $payload['title']);
        $this->assertSame('Detailed exception message', $payload['details']);
    }

    public function test_bootstrap_registers_global_exception_renderer(): void
    {
        $bootstrapContents = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertIsString($bootstrapContents);
        $this->assertStringContainsString('return app(ExceptionPresenter::class)->toResponse($exception, $request);', $bootstrapContents);
        $this->assertStringContainsString('ValidationException', $bootstrapContents);
        $this->assertStringContainsString('HttpExceptionInterface', $bootstrapContents);
    }
}
