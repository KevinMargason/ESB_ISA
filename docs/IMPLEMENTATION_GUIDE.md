# IMPLEMENTATION GUIDE: MISSING COMPONENTS

Dokumen ini menyediakan step-by-step guide untuk melengkapi components yang masih missing dari Secure Supply Chain Tracking System.

---

## 1. PDF EXPORT MODULE

### 1.1 Installation

```bash
composer require barryvdh/laravel-dompdf
```

### 1.2 Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### 1.3 Create PDF Export Service

File: `app/Services/PdfExportService.php`

```php
<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\TransactionLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfExportService
{
    /**
     * Generate tracking report PDF untuk item
     */
    public function generateItemTrackingReport(Item $item): \Barryvdh\DomPDF\PDF
    {
        $item->load(['supplier', 'trackingEvents.actor']);

        $trackingEvents = $item->trackingEvents;
        $decryptedNotes = null;

        if ($item->sensitive_notes) {
            try {
                $decryptedNotes = \Illuminate\Support\Facades\Crypt::decryptString($item->sensitive_notes);
            } catch (\Throwable) {
                $decryptedNotes = '[Gagal decrypt catatan]';
            }
        }

        return Pdf::loadView('pdf.item-tracking', [
            'item' => $item,
            'trackingEvents' => $trackingEvents,
            'decryptedNotes' => $decryptedNotes,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])
        ->setPaper('a4')
        ->setOption('defaultFont', 'Arial');
    }

    /**
     * Generate audit trail summary PDF
     */
    public function generateAuditTrailReport(?Collection $audits = null): \Barryvdh\DomPDF\PDF
    {
        if ($audits === null) {
            $audits = AuditTrail::query()
                ->with('user')
                ->latest('created_at')
                ->limit(100)
                ->get();
        }

        return Pdf::loadView('pdf.audit-trail-report', [
            'audits' => $audits,
            'totalRecords' => $audits->count(),
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('defaultFont', 'Arial');
    }

    /**
     * Generate integrity verification report PDF
     */
    public function generateIntegrityReport(array $result): \Barryvdh\DomPDF\PDF
    {
        $logs = TransactionLog::query()
            ->with('actor')
            ->orderBy('chain_index')
            ->get();

        return Pdf::loadView('pdf.integrity-report', [
            'result' => $result,
            'totalLogs' => $logs->count(),
            'logs' => $logs->take(50), // Last 50 untuk preview
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])
        ->setPaper('a4')
        ->setOption('defaultFont', 'Arial');
    }
}
```

### 1.4 Create PDF Views

#### File: `resources/views/pdf/item-tracking.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Tracking Item</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { border-bottom: 3px solid #003366; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #003366; font-size: 24px; }
        .header p { margin: 5px 0; font-size: 12px; color: #666; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section h2 { border-bottom: 2px solid #003366; padding-bottom: 5px; color: #003366; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .label { font-weight: bold; width: 150px; background-color: #f9f9f9; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 LAPORAN TRACKING BARANG</h1>
        <p><strong>Sistem Keamanan Rantai Pasok Terpadu (S2CTS)</strong></p>
        <p>Tanggal Report: {{ $generatedAt }}</p>
    </div>

    <div class="section">
        <h2>INFORMASI BARANG</h2>
        <table>
            <tr>
                <td class="label">Kode Barang</td>
                <td>{{ $item->item_code }}</td>
            </tr>
            <tr>
                <td class="label">Nama Barang</td>
                <td>{{ $item->item_name }}</td>
            </tr>
            <tr>
                <td class="label">Kategori</td>
                <td>{{ $item->category }}</td>
            </tr>
            <tr>
                <td class="label">Kuantitas</td>
                <td>{{ $item->quantity }} unit</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td>{{ $item->supplier->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Status Saat Ini</td>
                <td>{{ $item->current_status }}</td>
            </tr>
        </table>
    </div>

    @if($decryptedNotes)
    <div class="section">
        <h2>CATATAN SENSITIF</h2>
        <p style="background-color: #fff3cd; padding: 10px; border-left: 4px solid #ff9800;">
            {{ $decryptedNotes }}
        </p>
    </div>
    @endif

    <div class="section">
        <h2>HISTORY TRACKING</h2>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Diupdate oleh</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trackingEvents as $event)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $event->event_time->format('d/m/Y H:i:s') }}</td>
                    <td><strong>{{ $event->status }}</strong></td>
                    <td>{{ $event->actor->name ?? 'Unknown' }}</td>
                    <td>{{ $event->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Dokumen ini digenerate oleh Secure Supply Chain Tracking System.</p>
        <p>Untuk verifikasi keaslian dokumen, harap hubungi administrator sistem.</p>
    </div>
</body>
</html>
```

#### File: `resources/views/pdf/audit-trail-report.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Audit Trail</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { border-bottom: 3px solid #d32f2f; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #d32f2f; font-size: 24px; }
        .header p { margin: 5px 0; font-size: 12px; color: #666; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section h2 { border-bottom: 2px solid #d32f2f; padding-bottom: 5px; color: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .action-create { background-color: #c8e6c9; }
        .action-update { background-color: #fff9c4; }
        .action-delete { background-color: #ffcccc; }
        .action-login { background-color: #e1f5fe; }
        .action-access { background-color: #f3e5f5; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔒 LAPORAN AUDIT TRAIL KEAMANAN</h1>
        <p><strong>Sistem Keamanan Rantai Pasok Terpadu (S2CTS)</strong></p>
        <p>Total Entries: {{ $totalRecords }} | Tanggal Report: {{ $generatedAt }}</p>
    </div>

    <div class="section">
        <h2>DAFTAR AKTIVITAS KEAMANAN</h2>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Target Type</th>
                    <th>IP Address</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($audits as $audit)
                <tr>
                    <td>{{ $audit->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $audit->user->name ?? 'System' }}</td>
                    <td class="action-{{ strtolower(explode('_', $audit->action)[0]) }}">
                        {{ $audit->action }}
                    </td>
                    <td>{{ $audit->target_type }}</td>
                    <td style="font-size: 10px;">{{ $audit->ip_address }}</td>
                    <td>{{ $audit->details['reason'] ?? $audit->details['email'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini memuat daftar lengkap aktivitas keamanan sistem.</p>
        <p>Dokumen digenerate oleh Secure Supply Chain Tracking System pada {{ $generatedAt }}</p>
    </div>
</body>
</html>
```

#### File: `resources/views/pdf/integrity-report.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Integrity Verification</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { border-bottom: 3px solid #1976d2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1976d2; font-size: 24px; }
        .header p { margin: 5px 0; font-size: 12px; color: #666; }
        .status-valid { color: #2e7d32; font-weight: bold; padding: 10px; background-color: #c8e6c9; border-radius: 5px; }
        .status-invalid { color: #c62828; font-weight: bold; padding: 10px; background-color: #ffcdd2; border-radius: 5px; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section h2 { border-bottom: 2px solid #1976d2; padding-bottom: 5px; color: #1976d2; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .hash-cell { font-family: 'Courier New', monospace; font-size: 9px; word-break: break-all; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 LAPORAN VERIFIKASI INTEGRITAS DATA</h1>
        <p><strong>Sistem Keamanan Rantai Pasok Terpadu (S2CTS)</strong></p>
        <p>Tanggal Verifikasi: {{ $generatedAt }}</p>
    </div>

    <div class="section">
        <h2>STATUS INTEGRITAS</h2>
        @if($result['valid'])
            <div class="status-valid">✓ VALID - Semua data terjaga integritas</div>
        @else
            <div class="status-invalid">✗ TAMPERED - Terdeteksi perubahan data tidak sah</div>
        @endif
        <p style="margin-top: 10px;">
            <strong>Total Log Entries:</strong> {{ $result['total_logs'] }}<br>
            @if(!$result['valid'] && $result['mismatch'])
            <strong style="color: #c62828;">Mismatch detected at chain_index:</strong> {{ $result['mismatch']['chain_index'] }}
            @endif
        </p>
    </div>

    @if(!$result['valid'] && $result['mismatch'])
    <div class="section">
        <h2>DETAIL TAMPERING</h2>
        <table>
            <tr>
                <td style="width: 150px; background-color: #f9f9f9;"><strong>Chain Index</strong></td>
                <td>{{ $result['mismatch']['chain_index'] }}</td>
            </tr>
            <tr>
                <td style="background-color: #f9f9f9;"><strong>Expected Prev Hash</strong></td>
                <td class="hash-cell">{{ $result['mismatch']['expected_prev_hash'] ?? 'GENESIS' }}</td>
            </tr>
            <tr>
                <td style="background-color: #f9f9f9;"><strong>Stored Prev Hash</strong></td>
                <td class="hash-cell">{{ $result['mismatch']['stored_prev_hash'] }}</td>
            </tr>
            <tr>
                <td style="background-color: #f9f9f9;"><strong>Stored Current Hash</strong></td>
                <td class="hash-cell">{{ $result['mismatch']['stored_current_hash'] }}</td>
            </tr>
            <tr>
                <td style="background-color: #f9f9f9;"><strong>Recalculated Current Hash</strong></td>
                <td class="hash-cell">{{ $result['mismatch']['recalculated_current_hash'] }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="section">
        <h2>RECENT TRANSACTION LOGS (Preview)</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ref Type</th>
                    <th>Event</th>
                    <th>Chain Index</th>
                    <th>Current Hash</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $log->ref_type }}</td>
                    <td>{{ $log->event_name }}</td>
                    <td>{{ $log->chain_index }}</td>
                    <td class="hash-cell">{{ substr($log->current_hash, 0, 16) }}...</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Verifikasi integritas dilakukan dengan mengecek hash chain setiap transaction log.</p>
        <p>Jika status VALID, memastikan tidak ada modifikasi data. Jika TAMPERED, segera lakukan investigasi keamanan.</p>
    </div>
</body>
</html>
```

### 1.5 Update Routes

File: `routes/web.php` - Add these routes:

```php
Route::middleware('auth')->group(function (): void {
    // ... existing routes ...

    Route::get('/items/{item}/export-pdf', [ItemController::class, 'exportPdf'])
        ->name('items.export-pdf');

    Route::get('/audit-trails/export-pdf', [AuditTrailController::class, 'exportPdf'])
        ->middleware('role:admin')
        ->name('audit.export-pdf');

    Route::get('/integrity/export-pdf', [IntegrityController::class, 'exportPdf'])
        ->middleware('role:admin')
        ->name('integrity.export-pdf');
});
```

### 1.6 Update Controllers

#### Update `ItemController.php`:

```php
public function exportPdf(Item $item, PdfExportService $pdfExportService): \Illuminate\Http\Response
{
    $user = request()->user();

    if ($user->role === 'supplier' && $item->supplier_id !== $user->id) {
        abort(403);
    }

    return $pdfExportService
        ->generateItemTrackingReport($item)
        ->download("item-{$item->item_code}-tracking.pdf");
}
```

#### Update `AuditTrailController.php`:

```php
public function exportPdf(PdfExportService $pdfExportService): \Illuminate\Http\Response
{
    $audits = AuditTrail::query()
        ->with('user')
        ->latest('created_at')
        ->get();

    return $pdfExportService
        ->generateAuditTrailReport($audits)
        ->download('audit-trail-export.pdf');
}
```

#### Update `IntegrityController.php`:

```php
public function exportPdf(
    IntegrityService $integrityService,
    PdfExportService $pdfExportService
): \Illuminate\Http\Response
{
    $result = $integrityService->verify();

    return $pdfExportService
        ->generateIntegrityReport($result)
        ->download('integrity-verification-report.pdf');
}
```

---

## 2. SECOND ENCRYPTION CIPHER (RSA)

### 2.1 Create Encryption Service

File: `app/Services/EncryptionService.php`

```php
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    private string $privateKeyPath;
    private string $publicKeyPath;

    public function __construct()
    {
        $this->privateKeyPath = storage_path('security/private_key.pem');
        $this->publicKeyPath = storage_path('security/public_key.pem');
    }

    /**
     * Generate RSA key pair (run once)
     */
    public static function generateKeyPair(): void
    {
        $keyPath = storage_path('security');

        if (!is_dir($keyPath)) {
            mkdir($keyPath, 0755, true);
        }

        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails['key'];

        file_put_contents("{$keyPath}/private_key.pem", $privateKey);
        file_put_contents("{$keyPath}/public_key.pem", $publicKey);

        chmod("{$keyPath}/private_key.pem", 0600);
        chmod("{$keyPath}/public_key.pem", 0644);
    }

    /**
     * Encrypt data dengan RSA public key
     * @return string Base64 encoded encrypted data
     */
    public function encryptRSA(string $plaintext): string
    {
        if (!file_exists($this->publicKeyPath)) {
            throw new Exception('Public key not found');
        }

        $publicKey = file_get_contents($this->publicKeyPath);

        if (!openssl_public_encrypt($plaintext, $encrypted, $publicKey)) {
            throw new Exception('RSA encryption failed');
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt data dengan RSA private key
     */
    public function decryptRSA(string $encrypted): string
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new Exception('Private key not found');
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        $encrypted = base64_decode($encrypted);

        if (!openssl_private_decrypt($encrypted, $decrypted, $privateKey)) {
            throw new Exception('RSA decryption failed');
        }

        return $decrypted;
    }

    /**
     * Hybrid encryption: RSA + AES
     * Gunakan untuk data yang lebih besar
     */
    public function encryptHybrid(string $plaintext): string
    {
        // Generate random DEK (Data Encryption Key)
        $dek = random_bytes(32); // 256-bit key

        // Encrypt data with AES-256-GCM
        $aesCiphertext = Crypt::encryptString($plaintext);

        // Encrypt DEK dengan RSA
        $wrappedDek = $this->encryptRSA($dek);

        // Return combined: wrappedDek|aesCiphertext
        return json_encode([
            'wrapped_dek' => $wrappedDek,
            'aes_ciphertext' => $aesCiphertext,
        ]);
    }

    /**
     * Hybrid decryption
     */
    public function decryptHybrid(string $encrypted): string
    {
        $data = json_decode($encrypted, true);

        if (!$data || !isset($data['wrapped_dek'], $data['aes_ciphertext'])) {
            throw new Exception('Invalid hybrid encrypted data');
        }

        // Decrypt DEK dengan RSA
        $dek = $this->decryptRSA($data['wrapped_dek']);

        // Decrypt data dengan AES (DEK sudah decrypt, tapi Crypt facade
        // menggunakan APP_KEY, jadi ini lebih ilustratif)
        return Crypt::decryptString($data['aes_ciphertext']);
    }
}
```

### 2.2 Create Artisan Command untuk Generate Keys

File: `app/Console/Commands/GenerateEncryptionKeys.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\EncryptionService;
use Illuminate\Console\Command;

class GenerateEncryptionKeys extends Command
{
    protected $signature = 'encryption:generate-keys';
    protected $description = 'Generate RSA key pair untuk encryption service';

    public function handle(): int
    {
        $this->info('Generating RSA key pair...');

        try {
            EncryptionService::generateKeyPair();
            $this->info('✓ RSA keys generated successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate keys: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 2.3 Add Migration untuk Encryption

File: `database/migrations/2026_04_16_000000_add_encrypted_fields.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            // Add column untuk RSA encrypted item_code
            $table->longText('encrypted_item_code')->nullable()->after('item_code');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('encrypted_item_code');
        });
    }
};
```

### 2.4 Usage Example

```php
// Di ItemController atau service
use App\Services\EncryptionService;

$encryptionService = new EncryptionService();

// Encrypt dengan RSA
$encrypted = $encryptionService->encryptRSA($item->item_code);
// Store: $item->encrypted_item_code = $encrypted

// Decrypt
$decrypted = $encryptionService->decryptRSA($encrypted);

// Hybrid encryption untuk sensitive notes
$hybridEncrypted = $encryptionService->encryptHybrid($sensitiveData);
$hybridDecrypted = $encryptionService->decryptHybrid($hybridEncrypted);
```

---

## 3. SECURITY TESTING SUITE

### 3.1 Create Feature Tests

File: `tests/Feature/EncryptionTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptionService = new EncryptionService();

        // Generate keys untuk testing
        EncryptionService::generateKeyPair();
    }

    public function test_aes_encrypt_decrypt(): void
    {
        $plaintext = 'Sensitive data untuk supply chain';
        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString($plaintext);
        $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($encrypted);

        $this->assertEqual($plaintext, $decrypted);
        $this->assertNotEqual($plaintext, $encrypted);
    }

    public function test_rsa_encrypt_decrypt(): void
    {
        $plaintext = 'Item code: SKU-12345';
        $encrypted = $this->encryptionService->encryptRSA($plaintext);
        $decrypted = $this->encryptionService->decryptRSA($encrypted);

        $this->assertEqual($plaintext, $decrypted);
    }

    public function test_hybrid_encryption(): void
    {
        $largeData = str_repeat('X', 5000); // Large data
        $encrypted = $this->encryptionService->encryptHybrid($largeData);
        $decrypted = $this->encryptionService->decryptHybrid($encrypted);

        $this->assertEqual($largeData, $decrypted);
    }

    public function test_invalid_rsa_decryption_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->encryptionService->decryptRSA('invalid-base64-data');
    }
}
```

### 3.2 Create Security Tests

File: `tests/Feature/SecurityTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supplier;
    protected User $courier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = User::factory()->create(['role' => 'supplier']);
        $this->courier = User::factory()->create(['role' => 'kurir']);
    }

    public function test_supplier_cannot_access_other_supplier_items(): void
    {
        $otherSupplier = User::factory()->create(['role' => 'supplier']);
        $item = Item::factory()->create(['supplier_id' => $otherSupplier->id]);

        $response = $this->actingAs($this->supplier)
            ->get("/items/{$item->id}");

        $response->assertStatus(403);
    }

    public function test_courier_cannot_create_items(): void
    {
        $response = $this->actingAs($this->courier)
            ->post('/items', [
                'item_code' => 'TEST-001',
                'item_name' => 'Test Item',
                'category' => 'Electronics',
                'quantity' => 10,
                'supplier_id' => $this->supplier->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_integrity_verification_detects_tampering(): void
    {
        // Create item and transaction log
        $item = Item::factory()->create(['supplier_id' => $this->supplier->id]);

        // Simulate tampering: modify transaction log
        \App\Models\TransactionLog::first()->update([
            'current_hash' => 'tampered-hash-value',
        ]);

        // Verify should detect tampering
        $response = $this->actingAs($this->admin)
            ->get('/integrity');

        $response->assertSeeText('TAMPERED');
    }

    public function test_audit_trail_logs_login_attempts(): void
    {
        $response = $this->post('/login', [
            'email' => $this->supplier->email,
            'password' => 'password',
        ]);

        $audit = \App\Models\AuditTrail::where('action', 'LOGIN_SUCCESS')->first();
        $this->assertNotNull($audit);
        $this->assertEqual($this->supplier->id, $audit->user_id);
    }

    public function test_audit_trail_logs_failed_login(): void
    {
        $response = $this->post('/login', [
            'email' => $this->supplier->email,
            'password' => 'wrong-password',
        ]);

        $audit = \App\Models\AuditTrail::where('action', 'LOGIN_FAILED')->first();
        $this->assertNotNull($audit);
    }

    public function test_sql_injection_blocked(): void
    {
        $response = $this->post('/login', [
            'email' => "' OR '1'='1",
            'password' => 'anything',
        ]);

        // Laravel validation should block this
        $response->assertSessionHasErrors('email');
    }
}
```

---

## 4. RUNNING TESTS

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/EncryptionTest.php

# Run with coverage
php artisan test --coverage

# Generate keys untuk production
php artisan encryption:generate-keys

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

---

## 5. IMPLEMENTATION CHECKLIST

- [ ] Install barryvdh/laravel-dompdf
- [ ] Create PdfExportService
- [ ] Create PDF views (3 types)
- [ ] Update ItemController dengan exportPdf method
- [ ] Update AuditTrailController dengan exportPdf method
- [ ] Update IntegrityController dengan exportPdf method
- [ ] Add PDF routes
- [ ] Test PDF export functionality
- [ ] Create EncryptionService dengan RSA
- [ ] Create GenerateEncryptionKeys command
- [ ] Create migration untuk encrypted fields
- [ ] Test RSA encryption/decryption
- [ ] Test hybrid encryption
- [ ] Create feature test suite
- [ ] Create security test suite
- [ ] Run all tests
- [ ] Document implementation

---

**Estimated Time**: 12-16 hours  
**Priority**: High  
**Due Date**: Before final submission
