<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    /**
     * Get all payments with optional filters
     */
    public function index(): JsonResponse
    {
        $status = request('status');
        $tenantId = request('tenant_id');
        $roomId = request('room_id');

        $query = Payment::query()->with(['tenant', 'room']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Get single payment
     */
    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $payment->load(['tenant', 'room']),
        ]);
    }

    /**
     * Get unpaid payments for tenant
     */
    public function tenantUnpaidPayments(Tenant $tenant): JsonResponse
    {
        $unpaidPayments = $this->paymentService->getUnpaidPaymentsForTenant($tenant);

        return response()->json([
            'success' => true,
            'tenant_name' => $tenant->name,
            'total_outstanding' => $tenant->getTotalRemainingAmount(),
            'unpaid_count' => $unpaidPayments->count(),
            'data' => $unpaidPayments,
        ]);
    }

    /**
     * Get room payment summary
     */
    public function roomSummary(Room $room): JsonResponse
    {
        $summary = $this->paymentService->getRoomSummary($room);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get overall payment report
     */
    public function report(): JsonResponse
    {
        $report = $this->paymentService->getOverallReport();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get overdue payments
     */
    public function overduePayments(): JsonResponse
    {
        $overduePayments = $this->paymentService->getOverduePayments();

        return response()->json([
            'success' => true,
            'count' => $overduePayments->count(),
            'data' => $overduePayments,
        ]);
    }
}
