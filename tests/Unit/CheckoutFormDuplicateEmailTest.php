<?php

namespace Tests\Unit;

use App\Livewire\Checkout\CheckoutForm;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class CheckoutFormDuplicateEmailTest extends TestCase
{
    #[DataProvider('duplicateMessages')]
    public function test_it_recognizes_duplicate_key_violations_across_drivers(string $message, bool $expected): void
    {
        $exception = new QueryException('mysql', 'insert into `users` ...', [], new \Exception($message));

        $form = new CheckoutForm;
        $method = new ReflectionMethod($form, 'isDuplicateEmailViolation');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($form, $exception));
    }

    public static function duplicateMessages(): array
    {
        return [
            'mysql' => ["Duplicate entry 'someone@example.com' for key 'users_email_unique'", true],
            'sqlite' => ['UNIQUE constraint failed: users.email', true],
            'postgres' => ['duplicate key value violates unique constraint "users_email_unique"', true],
            'unrelated error' => ['SQLSTATE[42S02]: Base table or view not found', false],
        ];
    }
}
