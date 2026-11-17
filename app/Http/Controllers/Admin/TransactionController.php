<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SubscriptionPurchases;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    private function query(): Builder
    {
        return DB::table('payment_transactions as transactions')
            ->join('company_subscriptions as subscriptions', 'subscriptions.id', '=', 'transactions.subscription_id')
            ->join('companies', 'companies.id', '=', 'subscriptions.company_id')
            ->join('packages', 'packages.id', '=', 'subscriptions.package_id')
            ->select('transactions.*', 'subscriptions.status as subscription_status', 'subscriptions.company_id',
                'companies.name as company_name', 'packages.name as package_name');
    }

    public function dashboard(): View
    {
        $counts = DB::table('payment_transactions')->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $subscriptions = DB::table('company_subscriptions')->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $recent = $this->query()->orderByDesc('transactions.created_at')->limit(8)->get();

        return view('admin.transactions.dashboard', compact('counts', 'subscriptions', 'recent'));
    }

    public function requests(Request $request): View
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;
        $transactions = $this->query()->where('transactions.status', 'waiting_verification')
            ->when($search, fn ($query) => $query->where('companies.name', 'like', '%'.$search.'%'))
            ->orderBy('transactions.created_at')->paginate(20)->withQueryString();

        return view('admin.transactions.history', ['transactions' => $transactions, 'filters' => ['search' => $search]]);
    }

    public function history(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:pending_payment,waiting_verification,paid,rejected,failed,cancelled']]);
        $transactions = $this->query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('transactions.status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($match) => $match->where('companies.name', 'like', '%'.$search.'%')->orWhere('packages.name', 'like', '%'.$search.'%')->orWhere('transactions.id', 'like', '%'.$search.'%')))
            ->orderByDesc('transactions.created_at')->paginate(20)->withQueryString();

        return view('admin.transactions.history', compact('transactions', 'filters'));
    }

    public function show(string $id): View
    {
        $transaction = $this->query()->where('transactions.id', $id)->first();
        abort_if($transaction === null, 404);
        $events = DB::table('payment_transaction_events')->leftJoin('users', 'users.id', '=', 'payment_transaction_events.actor_id')
            ->where('transaction_id', $id)->select('payment_transaction_events.*', 'users.full_name as actor_name')->orderBy('created_at')->get();

        return view('admin.transactions.show', compact('transaction', 'events'));
    }

    public function proof(string $id): BinaryFileResponse
    {
        $path = DB::table('payment_transactions')->where('id', $id)->value('proof_path');
        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }

    public function decide(Request $request, SubscriptionPurchases $purchases, string $id): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:500']]);
        $purchases->decide($request->user()->id, $id, $data['decision'] === 'approve', $data['note'] ?? null);

        return redirect()->route('admin.transactions.show', $id)->with('success', 'Keputusan pembayaran tersimpan.');
    }

    public function subscriptions(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,pending_activation,expired,cancelled,revoked'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        $subscriptions = DB::table('company_subscriptions as subscriptions')
            ->join('companies', 'companies.id', '=', 'subscriptions.company_id')
            ->join('packages', 'packages.id', '=', 'subscriptions.package_id')
            ->select('subscriptions.*', 'companies.name as company_name', 'packages.name as package_name')
            ->when($status, fn ($query) => $query->where('subscriptions.status', $status))
            ->when($search, fn ($query) => $query->where('companies.name', 'like', '%'.$search.'%'))
            ->orderByDesc('subscriptions.created_at')->paginate(20)->withQueryString();

        return view('admin.transactions.subscriptions', compact('subscriptions', 'status', 'search'));
    }
}
