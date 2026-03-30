<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\TrackingEvent;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $itemsQuery = Item::query();
        if ($user->role === 'supplier') {
            $itemsQuery->where('supplier_id', $user->id);
        }

        return view('dashboard', [
            'user' => $user,
            'itemCount' => (clone $itemsQuery)->count(),
            'eventCount' => TrackingEvent::query()->count(),
            'logCount' => TransactionLog::query()->count(),
            'recentItems' => (clone $itemsQuery)
                ->with('supplier')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
