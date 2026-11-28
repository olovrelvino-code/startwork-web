<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\SubscriptionPurchases;
use App\WebpImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionPurchaseController extends Controller
{
    public function history(Request $request, SubscriptionPurchases $purchases): View
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        abort_if($company === null, 404);
        $purchases->activateForCompany($company->id);

        $purchases = DB::table('payment_transactions')
            ->join('company_subscriptions', 'company_subscriptions.id', '=', 'payment_transactions.subscription_id')
            ->join('packages', 'packages.id', '=', 'company_subscriptions.package_id')
            ->where('company_subscriptions.company_id', $company->id)
            ->select(
                'payment_transactions.*',
                'packages.name as package_name',
                'company_subscriptions.status as subscription_status',
                'company_subscriptions.quota_total',
                'company_subscriptions.duration_days'
            )
            ->orderByDesc('payment_transactions.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('employer.subscription-history', compact('company', 'purchases'));
    }

    public function store(Request $request, SubscriptionPurchases $purchases): JsonResponse
    {
        $data = $request->validate(['package_id' => ['required', 'uuid'], 'idempotency_key' => ['required', 'uuid']]);
        $transaction = $purchases->create($request->user()->id, $data['package_id'], $data['idempotency_key']);
        $details = DB::table('company_subscriptions')->join('companies', 'companies.id', '=', 'company_subscriptions.company_id')
            ->join('packages', 'packages.id', '=', 'company_subscriptions.package_id')
            ->where('company_subscriptions.id', $transaction->subscription_id)
            ->select('companies.name as company', 'packages.name as package')->first();
        $message = "Halo StartWork, saya ingin membeli paket {$details->package} untuk {$details->company}. Nominal Rp ".number_format($transaction->amount, 0, ',', '.').". ID transaksi: {$transaction->id}. Mohon instruksi pembayaran.";

        return response()->json(['transaction_id' => $transaction->id, 'whatsapp_url' => 'https://wa.me/628977772099?text='.rawurlencode($message)]);
    }

    public function availability(Request $request, SubscriptionPurchases $purchases): JsonResponse
    {
        $companyId = DB::table('companies')->where('owner_user_id', $request->user()->id)->value('id');
        abort_if($companyId === null, 404);
        $message = $purchases->purchaseBlockMessage($companyId);

        return response()->json(['allowed' => $message === null, 'message' => $message]);
    }

    public function proof(Request $request, SubscriptionPurchases $purchases, string $id): JsonResponse|RedirectResponse
    {
        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480']]);
        $file = $request->file('proof');
        $mime = $file->getMimeType();

        if ($mime === 'application/pdf') {
            if ($file->getSize() > 2 * 1024 * 1024) {
                throw ValidationException::withMessages(['proof' => 'Bukti PDF maksimal 2 MB.']);
            }
            $path = $file->store('payment-proofs', 'local');
        } else {
            $path = WebpImage::store($file, 'payment-proofs', 'proof', 'local', 2048, 2 * 1024 * 1024);
        }

        try {
            $purchases->proof($request->user()->id, $id, $path);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Bukti pembayaran dikirim untuk verifikasi.']);
        }

        return redirect()->back()->with('success', 'Bukti pembayaran berhasil dikirim untuk verifikasi admin.');
    }

    public function cancel(Request $request, SubscriptionPurchases $purchases, string $id): RedirectResponse
    {
        $purchases->cancel($request->user()->id, $id);

        return redirect()->back()->with('success', 'Permintaan pembelian dibatalkan.');
    }
}
