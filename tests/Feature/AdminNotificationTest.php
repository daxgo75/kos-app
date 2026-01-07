<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    private AdminNotificationService $notificationService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(AdminNotificationService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_receives_notification_on_payment_marked_paid(): void
    {
        $payment = Payment::factory()->create();

        $this->notificationService->notifyAdminPaymentMarkedAsPaid($payment);

        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'payment_id' => $payment->id,
            'type' => 'payment_marked_paid',
        ]);
    }

    public function test_admin_receives_notification_on_payment_received(): void
    {
        $payment = Payment::factory()->create();
        $amountReceived = 100000;

        $this->notificationService->notifyAdminPaymentReceived($payment, $amountReceived);

        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'payment_id' => $payment->id,
            'type' => 'payment_received',
        ]);
    }

    public function test_get_unread_notifications(): void
    {
        AdminNotification::factory()->for($this->admin)->create(['is_read' => false]);
        AdminNotification::factory()->for($this->admin)->create(['is_read' => true]);

        $unreadCount = $this->notificationService->getUnreadCount($this->admin);

        $this->assertEquals(1, $unreadCount);
    }

    public function test_mark_notification_as_read(): void
    {
        $notification = AdminNotification::factory()->for($this->admin)->create(['is_read' => false]);

        $this->notificationService->markAsRead($notification);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        AdminNotification::factory()->for($this->admin)->count(3)->create(['is_read' => false]);

        $this->notificationService->markAllAsRead($this->admin);

        $unreadCount = $this->notificationService->getUnreadCount($this->admin);
        $this->assertEquals(0, $unreadCount);
    }

    public function test_delete_notification(): void
    {
        $notification = AdminNotification::factory()->for($this->admin)->create();

        $this->notificationService->deleteNotification($notification);

        $this->assertDatabaseMissing('admin_notifications', ['id' => $notification->id]);
    }

    public function test_filter_notifications_by_type(): void
    {
        AdminNotification::factory()->for($this->admin)->create(['type' => 'payment_marked_paid']);
        AdminNotification::factory()->for($this->admin)->create(['type' => 'payment_received']);

        $notifications = $this->notificationService->getAdminNotifications($this->admin, 'payment_marked_paid');

        $this->assertEquals(1, $notifications->count());
        $this->assertEquals('payment_marked_paid', $notifications->first()->type);
    }

    public function test_get_admin_notifications_api(): void
    {
        AdminNotification::factory()->for($this->admin)->count(5)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/admin/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'count',
            'unread_count',
            'data',
        ]);
    }

    public function test_get_unread_notifications_api(): void
    {
        AdminNotification::factory()->for($this->admin)->create(['is_read' => false]);
        AdminNotification::factory()->for($this->admin)->create(['is_read' => true]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/notifications/unread');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_mark_notification_as_read_api(): void
    {
        $notification = AdminNotification::factory()->for($this->admin)->create(['is_read' => false]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_delete_notification_api(): void
    {
        $notification = AdminNotification::factory()->for($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('admin_notifications', ['id' => $notification->id]);
    }

    public function test_get_unread_count_api(): void
    {
        AdminNotification::factory()->for($this->admin)->count(3)->create(['is_read' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['unread_count' => 3]);
    }

    public function test_only_own_notifications_visible(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $notification = AdminNotification::factory()->for($this->admin)->create();

        $response = $this->actingAs($otherAdmin)->getJson("/api/admin/notifications/{$notification->id}");

        $response->assertStatus(403);
    }
}
