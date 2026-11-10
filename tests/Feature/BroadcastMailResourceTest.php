<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\BroadcastMails\Pages\CreateBroadcastMail;
use App\Jobs\SendAdminBroadcastMailJob;
use App\Models\BroadcastMail;
use App\Models\BroadcastMailRecipient;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BroadcastMailResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithSendMailPermission(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        $permissions = collect(['send_mail.viewAny', 'send_mail.create', 'send_mail.view'])
            ->map(fn (string $name): Permission => Permission::query()->create(['name' => $name, 'guard_name' => 'admin']));
        $role->givePermissionTo($permissions);

        $admin = User::factory()->create();
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_creating_a_broadcast_mail_creates_a_recipient_row_and_dispatches_a_job_per_recipient(): void
    {
        $admin = $this->actingAsAdminWithSendMailPermission();

        $recipients = User::factory()->count(3)->create(['status' => User::STATUS_ACTIVE]);

        Queue::fake();

        Livewire::test(CreateBroadcastMail::class)
            ->fillForm([
                'user_ids' => $recipients->pluck('id')->all(),
                'subject' => 'Prototype broadcast',
                'body' => '<p>Hello everyone</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('broadcast_mails', 1);

        $broadcastMail = BroadcastMail::query()->sole();
        $this->assertSame('Prototype broadcast', $broadcastMail->subject);
        $this->assertSame('<p>Hello everyone</p>', $broadcastMail->body);
        $this->assertSame($admin->id, $broadcastMail->created_by);
        $this->assertSame(BroadcastMail::STATUS_SENDING, $broadcastMail->status);

        $this->assertSame($recipients->count(), $broadcastMail->recipients()->count());
        $this->assertTrue($broadcastMail->recipients()->pluck('status')->every(
            fn (string $status): bool => $status === BroadcastMailRecipient::STATUS_PENDING
        ));

        Queue::assertPushed(SendAdminBroadcastMailJob::class, $recipients->count());

        foreach ($broadcastMail->recipients as $recipient) {
            Queue::assertPushed(SendAdminBroadcastMailJob::class, fn (SendAdminBroadcastMailJob $job): bool => $job->recipientId === $recipient->id);
        }
    }

    public function test_broadcast_mails_page_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin');

        $this->get('/admin/broadcast-mails')->assertForbidden();
    }
}
