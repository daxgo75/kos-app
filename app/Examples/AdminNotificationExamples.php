<?php

/**
 * CONTOH IMPLEMENTASI ADMIN NOTIFICATION SYSTEM
 * 
 * File ini menunjukkan contoh penggunaan sebenarnya dari admin notification system
 * dalam berbagai scenario.
 */

namespace App\Examples;

use App\Models\AdminNotification;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Utils\AdminNotificationManager;

class AdminNotificationExamples
{
    /**
     * SCENARIO 1: Penyewa Melakukan Pembayaran Penuh
     * 
     * Ketika penyewa melakukan pembayaran untuk melunasi tagihan,
     * sistem secara otomatis membuat notifikasi untuk semua admin.
     */
    public static function scenario1_TenantPaysFullAmount()
    {
        // 1. Ambil payment dari database
        $payment = Payment::find(1);
        
        // 2. Penyewa membayar (biasanya dari mobile app)
        // Ini akan trigger PaymentMarkedAsPaid event
        $payment->markAsPaid('transfer_bank');
        
        // 3. Event dispatcher menjalankan listeners otomatis
        // NotifyAdminPaymentMarkedAsPaid listener akan:
        // - Mengambil semua admin users
        // - Membuat record di admin_notifications untuk setiap admin
        // - Data yang disimpan:
        //   * tenant_id, room_id, amount, payment_method
        
        // 4. Admin bisa mengakses notifikasi via:
        $admin = User::where('role', 'admin')->first();
        
        // Via API
        // GET /api/admin/notifications
        
        // Via Model
        $notifications = AdminNotification::where('user_id', $admin->id)
            ->where('type', 'payment_marked_paid')
            ->recent()
            ->get();
        
        // Via Service
        $service = app(AdminNotificationService::class);
        $unreadCount = $service->getUnreadCount($admin);
        
        // Via Trait
        $unreadNotifications = $admin->getUnreadNotifications();
    }

    /**
     * SCENARIO 2: Penyewa Melakukan Pembayaran Cicilan
     * 
     * Ketika penyewa melakukan pembayaran cicilan (sebagian saja),
     * sistem membuat notifikasi dengan informasi berapa yang sudah dibayar
     * dan berapa sisa yang perlu dibayar.
     */
    public static function scenario2_TenantPaysPartial()
    {
        $payment = Payment::find(2);
        
        // Penyewa membayar Rp 500.000 dari Rp 1.000.000
        // Ini akan trigger PaymentReceived event
        $payment->addPayment(500000, 'e_wallet');
        
        // Listener NotifyAdminPaymentReceived akan:
        // - Ambil semua admin
        // - Buat notifikasi dengan tipe 'payment_received'
        // - Sertakan data: amount_received, remaining_amount, status
        
        // Admin bisa melihat detail:
        $admin = User::where('role', 'admin')->first();
        $notification = AdminNotification::where('user_id', $admin->id)
            ->where('type', 'payment_received')
            ->latest()
            ->first();
        
        // Output data:
        echo "Notifikasi: {$notification->title}";
        echo "Pesan: {$notification->message}";
        echo "Data: " . json_encode($notification->data);
        // Data berisi:
        // - tenant_id: ID penyewa
        // - room_id: ID kamar
        // - amount_received: 500000
        // - remaining_amount: 500000
        // - status: 'partial'
    }

    /**
     * SCENARIO 3: Admin Mengecek Notifikasi via API
     * 
     * Contoh request/response API untuk admin notification
     */
    public static function scenario3_AdminChecksNotificationsViaAPI()
    {
        $admin = User::where('role', 'admin')->first();
        
        // 1. GET /api/admin/notifications
        // Response:
        // {
        //   "success": true,
        //   "count": 15,
        //   "unread_count": 3,
        //   "data": [
        //     {
        //       "id": 1,
        //       "type": "payment_marked_paid",
        //       "title": "Pembayaran Lunas",
        //       "message": "John Doe (Kamar 101) telah menyelesaikan pembayaran...",
        //       "data": {...},
        //       "is_read": false,
        //       "read_at": null,
        //       "created_at": "2025-01-07T10:30:00Z",
        //       "payment": {...}
        //     },
        //     ...
        //   ]
        // }
        
        // 2. GET /api/admin/notifications/unread
        // Hanya tampilkan notifikasi yang belum dibaca
        
        // 3. GET /api/admin/notifications/unread-count
        // Response:
        // {
        //   "success": true,
        //   "unread_count": 3
        // }
        
        // 4. PATCH /api/admin/notifications/1/read
        // Tandai notifikasi dengan ID 1 sebagai sudah dibaca
        
        // 5. PATCH /api/admin/notifications/mark-all-read
        // Tandai semua notifikasi sebagai sudah dibaca
        
        // 6. GET /api/admin/notifications?type=payment_marked_paid
        // Filter hanya notifikasi pembayaran lunas
    }

    /**
     * SCENARIO 4: Admin Menggunakan Fitur via Model/Service
     * 
     * Contoh menggunakan notifikasi secara programmatic
     */
    public static function scenario4_AdminUsesNotificationsProgrammatically()
    {
        $admin = User::where('role', 'admin')->first();
        $service = app(AdminNotificationService::class);
        
        // 1. Ambil semua notifikasi
        $allNotifications = $service->getAdminNotifications($admin);
        
        // 2. Ambil notifikasi belum dibaca
        $unreadNotifications = AdminNotification::where('user_id', $admin->id)
            ->unread()
            ->get();
        
        // 3. Hitung notifikasi belum dibaca
        $unreadCount = $service->getUnreadCount($admin);
        echo "Admin memiliki $unreadCount notifikasi belum dibaca";
        
        // 4. Filter berdasarkan tipe
        $paymentPaidNotifications = $service->getAdminNotifications($admin, 'payment_marked_paid');
        
        // 5. Tandai sebagai dibaca
        $notification = $unreadNotifications->first();
        $service->markAsRead($notification);
        
        // 6. Tandai semua sebagai dibaca
        $service->markAllAsRead($admin);
        
        // 7. Hapus notifikasi
        $service->deleteNotification($notification);
        
        // 8. Cleanup notifikasi lama (> 30 hari)
        $deletedCount = $service->deleteOldNotifications(30);
        echo "Berhasil menghapus $deletedCount notifikasi lama";
    }

    /**
     * SCENARIO 5: Dashboard Admin dengan Ringkasan Notifikasi
     * 
     * Admin dashboard yang menampilkan ringkasan notifikasi
     */
    public static function scenario5_AdminDashboardSummary()
    {
        $admin = User::where('role', 'admin')->first();
        
        // Gunakan AdminNotificationManager untuk mendapatkan ringkasan
        $summary = AdminNotificationManager::getNotificationsSummary($admin);
        
        // Output:
        // {
        //   "total": 50,
        //   "unread": 5,
        //   "by_type": {
        //     "payment_marked_paid": 20,
        //     "payment_received": 30
        //   },
        //   "recent_unread": [...]
        // }
        
        echo "Total Notifikasi: {$summary['total']}";
        echo "Belum Dibaca: {$summary['unread']}";
        echo "Pembayaran Lunas: {$summary['by_type']['payment_marked_paid']}";
        echo "Pembayaran Diterima: {$summary['by_type']['payment_received']}";
        
        // Kelompok berdasarkan tipe
        $grouped = AdminNotificationManager::groupNotificationsByType($admin);
        foreach ($grouped as $type => $notifications) {
            echo "Tipe: $type, Jumlah: {$notifications->count()}";
        }
        
        // Notifikasi dalam range tanggal tertentu
        $startDate = now()->subDays(7);
        $endDate = now();
        $weekNotifications = AdminNotificationManager::getNotificationsForDateRange(
            $admin, 
            $startDate, 
            $endDate
        );
        echo "Notifikasi minggu ini: {$weekNotifications->count()}";
        
        // Notifikasi belum dibaca berdasarkan tipe
        $unreadPaid = AdminNotificationManager::getUnreadByType($admin, 'payment_marked_paid');
        echo "Pembayaran Lunas (belum dibaca): {$unreadPaid->count()}";
    }

    /**
     * SCENARIO 6: Menggunakan HasAdminNotifications Trait
     * 
     * Trait yang ditambahkan ke User model untuk kemudahan akses
     */
    public static function scenario6_UseHasAdminNotificationsTrait()
    {
        $admin = User::where('role', 'admin')->first();
        
        // 1. Akses relasi
        $notifications = $admin->adminNotifications; // Eloquent Collection
        
        // 2. Hitung notifikasi belum dibaca
        $unreadCount = $admin->getUnreadNotificationsCount();
        echo "Notifikasi belum dibaca: $unreadCount";
        
        // 3. Ambil notifikasi belum dibaca
        $unreadNotifications = $admin->getUnreadNotifications();
        foreach ($unreadNotifications as $notification) {
            echo "{$notification->title} - {$notification->message}";
        }
    }

    /**
     * SCENARIO 7: Testing dengan PHPUnit
     * 
     * Contoh testing untuk notification system
     */
    public static function scenario7_TestingNotifications()
    {
        // Lihat tests/Feature/AdminNotificationTest.php untuk test lengkap
        
        // Contoh test:
        // 
        // public function test_admin_receives_notification_on_payment_marked_paid()
        // {
        //     $payment = Payment::factory()->create();
        //     $service = app(AdminNotificationService::class);
        //     
        //     $service->notifyAdminPaymentMarkedAsPaid($payment);
        //     
        //     $this->assertDatabaseHas('admin_notifications', [
        //         'payment_id' => $payment->id,
        //         'type' => 'payment_marked_paid',
        //     ]);
        // }
        //
        // public function test_only_own_notifications_visible()
        // {
        //     $admin1 = User::factory()->create(['role' => 'admin']);
        //     $admin2 = User::factory()->create(['role' => 'admin']);
        //     
        //     $notification = AdminNotification::factory()->for($admin1)->create();
        //     
        //     $this->actingAs($admin2)
        //         ->getJson("/api/admin/notifications/{$notification->id}")
        //         ->assertStatus(403);
        // }
    }

    /**
     * SCENARIO 8: Console Command untuk Cleanup
     * 
     * Menjalankan cleanup otomatis untuk notifikasi lama
     */
    public static function scenario8_CleanupOldNotifications()
    {
        // 1. Manual cleanup
        // php artisan notifications:cleanup
        // php artisan notifications:cleanup --days=60
        
        // 2. Atau via kode
        $service = app(AdminNotificationService::class);
        $deleted = $service->deleteOldNotifications(30);
        echo "Berhasil menghapus $deleted notifikasi yang lebih dari 30 hari";
        
        // 3. Schedule in app/Console/Kernel.php
        // $schedule->command('notifications:cleanup --days=30')
        //     ->daily()
        //     ->at('02:00');
    }

    /**
     * SCENARIO 9: Query Optimization
     * 
     * Contoh query yang optimal untuk performa
     */
    public static function scenario9_OptimizedQueries()
    {
        $admin = User::where('role', 'admin')->first();
        
        // ❌ N+1 Problem - HINDARI
        // foreach ($notifications as $notification) {
        //     echo $notification->payment->tenant->name;
        // }
        
        // ✅ Eager Loading - LAKUKAN INI
        $notifications = AdminNotification::where('user_id', $admin->id)
            ->with(['payment.tenant', 'payment.room'])
            ->recent()
            ->get();
        
        foreach ($notifications as $notification) {
            echo $notification->payment->tenant->name;
        }
        
        // ✅ Paginate untuk data besar
        $paginated = AdminNotification::where('user_id', $admin->id)
            ->with('payment')
            ->latest()
            ->paginate(20);
        
        echo "Total: {$paginated->total()}";
        echo "Current Page: {$paginated->currentPage()}";
    }

    /**
     * SCENARIO 10: Frontend Integration (JavaScript)
     * 
     * Contoh integrasi di frontend dengan JavaScript/React/Vue
     */
    public static function scenario10_FrontendIntegration()
    {
        // JAVASCRIPT/REACT EXAMPLE:
        
        // const getUnreadCount = async () => {
        //   const response = await fetch('/api/admin/notifications/unread-count', {
        //     headers: { 'Authorization': `Bearer ${token}` }
        //   });
        //   const data = await response.json();
        //   return data.unread_count;
        // };
        
        // const getUnreadNotifications = async () => {
        //   const response = await fetch('/api/admin/notifications/unread', {
        //     headers: { 'Authorization': `Bearer ${token}` }
        //   });
        //   const data = await response.json();
        //   return data.data;
        // };
        
        // const markAsRead = async (notificationId) => {
        //   const response = await fetch(
        //     `/api/admin/notifications/${notificationId}/read`,
        //     { 
        //       method: 'PATCH',
        //       headers: { 'Authorization': `Bearer ${token}` }
        //     }
        //   );
        //   return response.json();
        // };
        
        // // Usage
        // useEffect(() => {
        //   const count = await getUnreadCount();
        //   setNotificationBadge(count);
        // }, []);
    }
}

/**
 * FLOW DIAGRAM
 * 
 * Penyewa Pembayaran
 *     ↓
 * Payment::markAsPaid() atau Payment::addPayment()
 *     ↓
 * PaymentMarkedAsPaid atau PaymentReceived Event
 *     ↓
 * EventServiceProvider Router
 *     ↓
 * NotifyAdminPaymentMarkedAsPaid atau NotifyAdminPaymentReceived Listener
 *     ↓
 * AdminNotificationService::notifyAdmin*()
 *     ↓
 * Query Get Admin Users
 *     ↓
 * Create AdminNotification Record (per admin)
 *     ↓
 * Database Storage
 *     ↓
 * Admin API Access
 *     ↓
 * Frontend Display / Badge Update
 */
