# 🏗️ Arsitektur Sistem Pembayaran Kos

Dokumentasi lengkap tentang arsitektur, design patterns, dan bagaimana komponenkomponen saling berinteraksi.

## 📐 Diagram Alur Pembayaran

```
┌─────────────────────────────────────────────────────────────┐
│                    FILAMENT ADMIN PANEL                      │
│                   (PaymentResource.php)                      │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
    [ CREATE ]   [ READ ]   [ UPDATE ]
        │             │             │
        └─────────────┼─────────────┘
                      │
        ┌─────────────▼─────────────┐
        │  PaymentService Layer     │
        │  (Business Logic)         │
        │  - createPayment()        │
        │  - addPayment()           │
        │  - markAsPaid()           │
        │  - getReports()           │
        └─────────────┬─────────────┘
                      │
        ┌─────────────▼─────────────┐
        │   Payment Model Layer     │
        │   (Data & Relationships)  │
        │  - tenant()               │
        │  - room()                 │
        │  - isPaid()               │
        │  - isOverdue()            │
        └─────────────┬─────────────┘
                      │
        ┌─────────────▼─────────────┐
        │     Events & Listeners    │
        │  - PaymentMarkedAsPaid    │
        │  - PaymentReceived        │
        │  - LogPaymentPaid         │
        │  - LogPaymentReceived     │
        └─────────────┬─────────────┘
                      │
        ┌─────────────▼─────────────┐
        │      Database Layer       │
        │  - rooms table            │
        │  - tenants table          │
        │  - payments table         │
        └───────────────────────────┘
```

## 🔄 Data Flow - Membuat Pembayaran Baru

```
User Filament UI
      ↓
  Form Input
  (Pilih Penyewa, Isi Jumlah)
      ↓
  PaymentResource (Store)
      ↓
  Validation
      ↓
  PaymentService::createPayment()
      ↓
  Payment Model Create
      ↓
  Database Insert
      ↓
  Events Dispatched
      ↓
  Listeners Execute (Logging)
      ↓
  Redirect to List
      ↓
  Table Updated
```

## 🔄 Data Flow - Update Pembayaran (Cicilan)

```
User klik Edit pembayaran
      ↓
  Form Load dengan data existing
      ↓
  User ubah "Jumlah Dibayar"
      ↓
  Form Fields Reactive Update:
      ├─ Calculate: remaining = due - paid
      ├─ Update: status (unpaid/partial/paid)
      └─ Update: paid_date (jika lunas)
      ↓
  User klik Simpan
      ↓
  PaymentResource (Update)
      ↓
  Validation
      ↓
  Payment Model Update
      ↓
  Database Update
      ↓
  Events Dispatched (PaymentReceived)
      ↓
  Listeners Execute
      ↓
  Redirect to Show
      ↓
  Detail Updated
```

## 📊 Database Relationships

```
ROOMS (1)
  │
  ├──── HAS MANY ──────→ TENANTS (Many)
  │                          │
  │                          ├──── HAS MANY ──→ PAYMENTS (Many)
  │                          │
  │                          └──── Methods:
  │                                  - getTotalRemainingAmount()
  │                                  - getTotalAmountDue()
  │                                  - hasUnpaidPayments()
  │
  └──── HAS MANY ──────→ PAYMENTS (Many)
                            │
                            ├──── BELONGS TO ──→ TENANT (1)
                            ├──── BELONGS TO ──→ ROOM (1)
                            │
                            └──── Methods:
                                  - isPaid()
                                  - isPartial()
                                  - isUnpaid()
                                  - isOverdue()
                                  - markAsPaid()
                                  - addPayment()
```

## 🎯 Core Classes & Responsibilities

### 1. Room Model

**Responsibility:** Merepresentasikan kamar kos fisik

```php
Properties:
- room_number: string
- room_type: enum (standard, deluxe)
- monthly_rate: decimal
- status: enum (available, occupied, maintenance)
- capacity: integer

Methods:
- tenants()              // Get semua penyewa yang pernah menginap
- activeTenant()        // Get penyewa aktif saat ini
- payments()            // Get semua pembayaran untuk kamar ini
- unpaidPayments()      // Get pembayaran yang belum lunas
- getTotalOutstandingBalance() // Total sisa pembayaran
```

### 2. Tenant Model

**Responsibility:** Merepresentasikan penyewa/penghuni kamar

```php
Properties:
- name: string
- email: string
- phone: string
- room_id: foreign key
- check_in_date: date
- check_out_date: date (nullable)
- status: enum (active, inactive, moved_out)

Methods:
- room()                       // Relasi ke kamar
- payments()                   // Get semua pembayaran
- unpaidPayments()             // Get pembayaran unpaid
- getTotalAmountDue()          // Total tagihan
- getTotalRemainingAmount()    // Total sisa
- getTotalPaidAmount()         // Total sudah dibayar
- hasUnpaidPayments()          // Check jika ada unpaid
```

### 3. Payment Model

**Responsibility:** Merepresentasikan catatan pembayaran

```php
Properties:
- tenant_id: foreign key
- room_id: foreign key
- amount_due: decimal
- amount_paid: decimal
- remaining_amount: decimal ⭐
- due_date: date
- paid_date: date (nullable)
- status: enum (unpaid, partial, paid) ⭐
- payment_method: string
- notes: text

Key Methods:
- tenant()           // Relasi ke penyewa
- room()             // Relasi ke kamar
- isPaid()           // Status check
- isPartial()        // Status check
- isUnpaid()         // Status check
- isOverdue()        // Due date check
- markAsPaid()       // Mark sebagai lunas + dispatch event
- addPayment()       // Tambah pembayaran + auto-update remaining
- getPaymentPercentage() // Get % pembayaran (dari trait)
- getDaysUntilDue()      // Get hari sampai jatuh tempo (dari trait)
- getDaysOverdue()       // Get hari sudah overdue (dari trait)
```

### 4. PaymentService

**Responsibility:** Menangani business logic pembayaran

```php
Key Methods:
- createPayment(Tenant $tenant)
  └─ Buat catatan pembayaran baru

- addPayment(Payment $payment, float $amount)
  └─ Tambah cicilan (update remaining & status otomatis)

- markAsPaid(Payment $payment)
  └─ Tandai sebagai lunas (dispatch event)

- getPaymentsByStatus(string $status)
  └─ Filter pembayaran berdasarkan status

- getUnpaidPaymentsForTenant(Tenant $tenant)
  └─ Get pembayaran unpaid per penyewa

- getOverduePayments()
  └─ Get pembayaran yang sudah melewati due date

- getRoomSummary(Room $room)
  └─ Get ringkasan pembayaran per kamar

- getTenantSummary(Tenant $tenant)
  └─ Get ringkasan pembayaran per penyewa

- getOverallReport()
  └─ Get laporan keseluruhan pembayaran
```

### 5. PaymentResource (Filament)

**Responsibility:** UI untuk manajemen pembayaran

```php
Key Features:
- Form dengan section yang rapi
- Auto-fill kamar & tarif saat pilih penyewa
- Reactive form (auto-calculate sisa & status)
- Color-coded status badges
- Filter & search
- Action "Tandai Lunas"
- View, Edit, Delete actions
```

### 6. PaymentService (Events & Listeners)

**Responsibility:** Track dan log pembayaran

```php
Events:
- PaymentMarkedAsPaid    // Event saat pembayaran ditandai lunas
- PaymentReceived        // Event saat ada pembayaran masuk

Listeners:
- LogPaymentPaid         // Catat di log saat pembayaran lunas
- LogPaymentReceived     // Catat di log saat ada pembayaran
```

## 🔐 Data Integrity & Validation

### Database Level

```sql
-- Foreign Keys
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON CASCADE DELETE
FOREIGN KEY (room_id) REFERENCES rooms(id) ON CASCADE DELETE

-- Indexes untuk performa
INDEX idx_status ON payments(status)
INDEX idx_due_date ON payments(due_date)
INDEX idx_tenant_status ON payments(tenant_id, status)
INDEX idx_room_status ON payments(room_id, status)
```

### Application Level

```php
// Casting untuk type safety
protected $casts = [
    'amount_due' => 'decimal:2',
    'amount_paid' => 'decimal:2',
    'remaining_amount' => 'decimal:2',
    'due_date' => 'date',
];

// Enum validation
'status' => ['in:unpaid,partial,paid']

// Numeric validation
'amount_paid' => ['numeric', 'min:0']
```

## 🎨 Design Patterns Used

### 1. Service Pattern

```
Controller/Resource → Service → Model → Database
```

Memisahkan business logic dari presentation

### 2. Trait Pattern

```php
PaymentStatusTrait
├─ Reusable untuk multiple models
├─ Status label & color methods
└─ Helper methods (getPaymentPercentage, getDaysUntilDue)
```

### 3. Event-Listener Pattern

```
Model Update → Event Dispatched → Listeners Execute
```

Untuk logging, notifications, dan audit trail

### 4. Factory Pattern

```
Factory::create() → Model Instance dengan dummy data
```

Untuk testing dan seeding

### 5. Repository Pattern (Optional - untuk fase 2)

```
Resource → Service → Repository → Model → Database
```

Untuk query abstraction

## 📈 Scalability Considerations

### Current Architecture

-   ✓ Handles up to 1000+ payments per tenant
-   ✓ Efficient queries dengan relationships
-   ✓ Database indexes untuk fast lookups
-   ✓ Async logging dengan ShouldQueue

### Future Improvements

```
[ ] Add Repository pattern untuk complex queries
[ ] Add Caching (Redis) untuk frequently accessed data
[ ] Add Pagination untuk large datasets
[ ] Add Background Jobs untuk batch operations
[ ] Add API Rate Limiting
[ ] Add Real-time Updates (WebSocket)
```

## 🧪 Testability Architecture

### Unit Tests

```php
PaymentServiceTest
├─ test_can_create_payment
├─ test_can_add_partial_payment
├─ test_can_mark_payment_as_paid
├─ test_can_get_unpaid_payments_for_tenant
├─ test_can_get_overdue_payments
├─ test_can_get_room_summary
└─ test_can_get_tenant_summary
```

### Testable Components

-   ✓ Service layer (no dependencies on UI)
-   ✓ Model methods (stateless logic)
-   ✓ Factory for test data creation
-   ✓ Events dispatched predictably

### Future Tests

-   [ ] Feature tests untuk Filament Resource
-   [ ] API endpoint tests
-   [ ] Integration tests
-   [ ] Performance tests

## 🔄 API Architecture (Optional)

```php
// Exposed endpoints (untuk mobile app / external integrations)

GET /api/payments              // List payments
GET /api/payments/{id}         // Get detail payment
GET /api/payments/status/{status} // Filter by status

GET /api/tenants/{id}/payments // Get tenant's payments
GET /api/rooms/{id}/summary    // Get room payment summary

GET /api/payments/report       // Get overall report
GET /api/payments/overdue      // Get overdue payments
```

## 📦 Folder Structure & Dependency

```
app/
├── Events/                    ← Event dispatched from Model
│   ├── PaymentMarkedAsPaid
│   └── PaymentReceived
│
├── Filament/
│   ├── Resources/
│   │   ├── RoomResource       ← Uses Room Model
│   │   ├── TenantResource     ← Uses Tenant Model
│   │   └── PaymentResource ⭐ ← Uses Payment Model & PaymentService
│   │
│   └── Widgets/
│       ├── PaymentStatsWidget ← Queries Payment Model
│       └── LatestPaymentsWidget
│
├── Http/
│   └── Controllers/
│       └── Api/
│           └── PaymentController ← Uses PaymentService
│
├── Listeners/                 ← Listens to Events
│   ├── LogPaymentPaid
│   └── LogPaymentReceived
│
├── Models/
│   ├── Room ⭐                ← Core data model
│   ├── Tenant ⭐              ← Core data model
│   └── Payment ⭐             ← Core data model with logic
│
├── Services/
│   └── PaymentService ⭐      ← Business logic (reusable)
│
└── Traits/
    └── PaymentStatusTrait    ← Shared functionality

database/
├── factories/
│   ├── RoomFactory
│   ├── TenantFactory
│   └── PaymentFactory
│
├── migrations/
│   ├── create_rooms_table
│   ├── create_tenants_table
│   └── create_payments_table
│
└── seeders/
    └── DatabaseSeeder
```

## 🚀 Execution Flow Example

### Scenario: Penyewa membayar cicilan

```
1. User buka Filament → Menu Pembayaran
   └─ PaymentResource::index() called
      └─ Query Payment with relationships
         └─ Display in table

2. User klik Edit pembayaran yang sudah partial
   └─ PaymentResource::edit() called
      └─ Form loads dengan data existing

3. User ubah "Amount Paid" dari 500000 menjadi 1000000
   └─ Form reactive field triggered
      └─ afterStateUpdated() callback
         ├─ Calculate: remaining = 1000000 - 1000000 = 0
         ├─ Update: status = 'paid'
         └─ Update UI field preview

4. User klik "Simpan"
   └─ Form validation
      └─ PaymentResource::update() called
         └─ Payment::update() called
            ├─ Save to database
            ├─ Trigger eloquent event 'updated'
            └─ Events dispatched:
               ├─ PaymentMarkedAsPaid event? (NO, because partial→paid)
               └─ PaymentReceived event? (YES, addPayment() called)
                  └─ Listeners executed:
                     └─ LogPaymentReceived::handle()
                        └─ Write to log file
         └─ Flash message "Pembayaran berhasil diupdate"
         └─ Redirect to list
         └─ Table shows updated status: ✓ LUNAS

5. System maintains data integrity:
   ├─ Database: amount_paid, remaining_amount, status updated
   ├─ Relationships: Tenant & Room still properly linked
   ├─ Indices: Query still efficient
   └─ Audit log: Event logged for future reference
```

---

## 📚 Design Philosophy

```
KISS (Keep It Simple, Stupid)
- Model buat hal sederhana
- Service buat hal kompleks
- Resource buat tampilan

DRY (Don't Repeat Yourself)
- Trait untuk reusable logic
- Service untuk shared business logic
- Factory untuk test data

SOLID Principles
- Single Responsibility: Setiap class punya 1 job
- Open/Closed: Open untuk extension, closed untuk modification
- Liskov: Contracts/Interfaces respected
- Interface Segregation: No fat classes
- Dependency Inversion: Depend on abstraction
```

---

**Dengan arsitektur ini, sistem pembayaran kos Anda clean, maintainable, dan siap untuk scale!** 🚀
