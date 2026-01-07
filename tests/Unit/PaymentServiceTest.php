<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\PaymentService;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    /**
     * Test creating a new payment
     * @test
     */
    public function test_can_create_payment(): void
    {
        // Arrange
        $room = Room::factory()->create([
            'monthly_rate' => 1000000,
        ]);
        
        $tenant = Tenant::factory()->create([
            'room_id' => $room->id,
        ]);

        // Act
        $payment = $this->paymentService->createPayment($tenant);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($tenant->id, $payment->tenant_id);
        $this->assertEquals($room->id, $payment->room_id);
        $this->assertEquals(1000000, $payment->amount_due);
        $this->assertEquals('unpaid', $payment->status);
    }

    /**
     * Test adding partial payment
     * @test
     */
    public function test_can_add_partial_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount_due' => 1000000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Act
        $this->paymentService->addPayment($payment, 500000, 'cash', 'Cicilan pertama');

        // Assert
        $payment->refresh();
        $this->assertEquals(500000, $payment->amount_paid);
        $this->assertEquals(500000, $payment->remaining_amount);
        $this->assertEquals('partial', $payment->status);
    }

    /**
     * Test marking payment as paid
     * @test
     */
    public function test_can_mark_payment_as_paid(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'unpaid',
            'amount_paid' => 0,
        ]);

        // Act
        $this->paymentService->markAsPaid($payment, 'bank_transfer');

        // Assert
        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('bank_transfer', $payment->payment_method);
        $this->assertEquals(0, $payment->remaining_amount);
    }

    /**
     * Test getting unpaid payments for tenant
     * @test
     */
    public function test_can_get_unpaid_payments_for_tenant(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Payment::factory()->paid()->create(['tenant_id' => $tenant->id]);
        Payment::factory()->create(['tenant_id' => $tenant->id, 'status' => 'unpaid']);
        Payment::factory()->partial()->create(['tenant_id' => $tenant->id]);

        // Act
        $unpaidPayments = $this->paymentService->getUnpaidPaymentsForTenant($tenant);

        // Assert
        $this->assertEquals(2, $unpaidPayments->count());
        $this->assertTrue($unpaidPayments->every(fn ($p) => $p->status !== 'paid'));
    }

    /**
     * Test getting overdue payments
     * @test
     */
    public function test_can_get_overdue_payments(): void
    {
        // Arrange
        Payment::factory()->create([
            'due_date' => now()->subDays(5),
            'status' => 'unpaid',
        ]);
        
        Payment::factory()->paid()->create([
            'due_date' => now()->subDays(5),
        ]);

        // Act
        $overduePayments = $this->paymentService->getOverduePayments();

        // Assert
        $this->assertTrue($overduePayments->every(fn ($p) => $p->isOverdue()));
        $this->assertTrue($overduePayments->every(fn ($p) => $p->status !== 'paid'));
    }

    /**
     * Test getting room summary
     * @test
     */
    public function test_can_get_room_summary(): void
    {
        // Arrange
        $room = Room::factory()->create();
        $tenant = Tenant::factory()->create(['room_id' => $room->id]);
        
        Payment::factory()->count(3)->paid()->create([
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
        ]);

        // Act
        $summary = $this->paymentService->getRoomSummary($room);

        // Assert
        $this->assertEquals($room->room_number, $summary['room_number']);
        $this->assertEquals(3, $summary['total_payments']);
        $this->assertEquals(3, $summary['paid_payments']);
    }

    /**
     * Test getting tenant summary
     * @test
     */
    public function test_can_get_tenant_summary(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Payment::factory()->paid()->create(['tenant_id' => $tenant->id]);
        Payment::factory()->partial()->create(['tenant_id' => $tenant->id]);

        // Act
        $summary = $this->paymentService->getTenantSummary($tenant);

        // Assert
        $this->assertEquals($tenant->name, $summary['tenant_name']);
        $this->assertTrue($summary['has_unpaid']);
        $this->assertGreaterThan(0, $summary['total_remaining']);
    }
}
