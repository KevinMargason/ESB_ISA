<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\HashChainService;
use App\Services\HybridCryptoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Item::query()
            ->with('supplier')
            ->latest();

        if ($user->role === 'supplier') {
            $query->where('supplier_id', $user->id);
        }

        return view('items.index', [
            'items' => $query->paginate(10),
            'user' => $user,
        ]);
    }

    public function create(Request $request): View
    {
        return view('items.create', [
            'suppliers' => User::query()->where('role', 'supplier')->orderBy('name')->get(),
            'user' => $request->user(),
        ]);
    }

    public function store(
        Request $request,
        HashChainService $hashChainService,
        AuditTrailService $auditTrailService,
        HybridCryptoService $hybridCryptoService
    ): RedirectResponse {
        $user = $request->user();

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:64', 'unique:items,item_code'],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'supplier_id' => ['nullable', Rule::exists('users', 'id')],
            'sensitive_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $supplierId = $user->role === 'supplier'
            ? $user->id
            : (int) ($validated['supplier_id'] ?? 0);

        if ($supplierId <= 0) {
            return back()->withErrors([
                'supplier_id' => 'Supplier harus dipilih.',
            ])->withInput();
        }

        $supplier = User::query()->find($supplierId);
        if (! $supplier || $supplier->role !== 'supplier') {
            return back()->withErrors([
                'supplier_id' => 'User yang dipilih bukan supplier.',
            ])->withInput();
        }

        $item = Item::query()->create([
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'category' => $validated['category'],
            'quantity' => $validated['quantity'],
            'supplier_id' => $supplierId,
            'current_status' => Item::STATUS_WAREHOUSE,
            'sensitive_notes' => $validated['sensitive_notes']
                ? $hybridCryptoService->encryptSensitive($validated['sensitive_notes'])
                : null,
        ]);

        TrackingEvent::query()->create([
            'item_id' => $item->id,
            'actor_id' => $user->id,
            'status' => Item::STATUS_WAREHOUSE,
            'notes' => 'Item dibuat dan masuk gudang.',
            'event_time' => now(),
        ]);

        $hashChainService->append('item', $item->id, $user->id, 'ITEM_CREATED', [
            'item_code' => $item->item_code,
            'status' => $item->current_status,
            'quantity' => $item->quantity,
        ]);

        $auditTrailService->record(
            $request,
            'ITEM_CREATED',
            'item',
            $item->id,
            ['item_code' => $item->item_code]
        );

        return redirect()->route('items.show', $item)->with('success', 'Item berhasil dibuat.');
    }

    public function show(
        Request $request,
        Item $item,
        AuditTrailService $auditTrailService,
        HybridCryptoService $hybridCryptoService
    ): View {
        $user = $request->user();

        if ($user->role === 'supplier' && $item->supplier_id !== $user->id) {
            $auditTrailService->record(
                $request,
                'SUPPLIER_ITEM_ACCESS_BLOCKED',
                'item',
                $item->id,
                ['owner_supplier_id' => $item->supplier_id]
            );

            abort(403, 'Anda tidak bisa melihat item ini.');
        }

        $item->load(['supplier', 'trackingEvents.actor']);

        $canViewSensitive = $user->role === 'admin' || $user->id === $item->supplier_id;
        $decryptedNotes = null;

        if ($item->sensitive_notes && $canViewSensitive) {
            try {
                $decryptedNotes = $hybridCryptoService->decryptSensitive($item->sensitive_notes);
            } catch (\Throwable) {
                $decryptedNotes = '[Gagal decrypt catatan]';
            }

            $auditTrailService->record(
                $request,
                'SENSITIVE_NOTES_VIEWED',
                'item',
                $item->id,
                [
                    'item_code' => $item->item_code,
                    'decrypt_success' => $decryptedNotes !== '[Gagal decrypt catatan]',
                ]
            );
        } elseif ($item->sensitive_notes && ! $canViewSensitive) {
            $auditTrailService->record(
                $request,
                'SENSITIVE_NOTES_ACCESS_BLOCKED',
                'item',
                $item->id,
                [
                    'item_code' => $item->item_code,
                    'viewer_role' => $user->role,
                ]
            );
        } else {
            $auditTrailService->record(
                $request,
                'ITEM_VIEWED',
                'item',
                $item->id,
                ['item_code' => $item->item_code]
            );
        }

        return view('items.show', [
            'item' => $item,
            'decryptedNotes' => $decryptedNotes,
            'canViewSensitive' => $canViewSensitive,
            'statuses' => Item::statuses(),
            'canUpdateStatus' => in_array($user->role, ['admin', 'kurir'], true),
        ]);
    }
}
