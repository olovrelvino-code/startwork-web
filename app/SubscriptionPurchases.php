<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPurchases
{
    public function create(string $employerId, string $packageId, string $key): object
    {
        return DB::transaction(function () use ($employerId, $packageId, $key): object {
            $company = DB::table('companies')->where('owner_user_id', $employerId)->lockForUpdate()->first();
            abort_if($company === null, 404);
            $existing = DB::table('payment_transactions')->where('idempotency_key', $key)->first();
            if ($existing !== null) {
                abort_unless(DB::table('company_subscriptions')->where('id', $existing->subscription_id)->where('company_id', $company->id)->exists(), 409);

                return $existing;
            }
            $package = DB::table('packages')->where('id', $packageId)->where('is_active', true)->first();
            abort_if($package === null, 422, 'Paket tidak tersedia');
            $blockMessage = $this->purchaseBlockMessage($company->id);
            abort_if($blockMessage !== null, 409, $blockMessage);
            $previous = DB::table('company_subscriptions')->where('company_id', $company->id)->whereIn('status', ['active', 'expired', 'revoked'])->orderByDesc('created_at')->first();
            $type = $previous === null ? 'new' : ($package->price > $previous->price_snapshot ? 'upgrade' : ($package->price < $previous->price_snapshot ? 'downgrade' : 'renewal'));
            $subscriptionId = (string) Str::uuid();
            $transactionId = (string) Str::uuid();
            DB::table('company_subscriptions')->insert(['id' => $subscriptionId, 'company_id' => $company->id, 'package_id' => $packageId,
                'previous_subscription_id' => $previous?->id, 'source' => 'purchase', 'change_type' => $type,
                'status' => 'pending', 'quota_total' => $package->quota, 'quota_used' => 0, 'duration_days' => $package->duration_days,
                'price_snapshot' => $package->price, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('payment_transactions')->insert(['id' => $transactionId, 'subscription_id' => $subscriptionId,
                'type' => $type === 'new' ? 'purchase' : $type, 'amount' => $package->price, 'credit_applied' => 0,
                'currency' => 'IDR', 'status' => 'pending_payment', 'provider' => 'whatsapp', 'idempotency_key' => $key,
                'created_at' => now(), 'updated_at' => now()]);
            $this->event($transactionId, $employerId, null, 'pending_payment');

            return DB::table('payment_transactions')->where('id', $transactionId)->first();
        }, attempts: 3);
    }

    public function proof(string $employerId, string $transactionId, string $path): void
    {
        DB::transaction(function () use ($employerId, $transactionId, $path): void {
            $company = DB::table('companies')->where('owner_user_id', $employerId)->lockForUpdate()->first();
            abort_if($company === null, 404);
            $transaction = DB::table('payment_transactions')->join('company_subscriptions', 'company_subscriptions.id', '=', 'payment_transactions.subscription_id')
                ->where('payment_transactions.id', $transactionId)->where('company_subscriptions.company_id', $company->id)
                ->select('payment_transactions.*')->lockForUpdate()->first();
            abort_if($transaction === null, 404);
            abort_unless($transaction->status === 'pending_payment', 409, 'Bukti sudah dikirim');
            DB::table('payment_transactions')->where('id', $transactionId)->update(['status' => 'waiting_verification', 'proof_path' => $path, 'updated_at' => now()]);
            $this->event($transactionId, $employerId, 'pending_payment', 'waiting_verification');
        }, attempts: 3);
    }

    public function cancel(string $employerId, string $transactionId): void
    {
        DB::transaction(function () use ($employerId, $transactionId): void {
            $company = DB::table('companies')->where('owner_user_id', $employerId)->lockForUpdate()->first();
            abort_if($company === null, 404);
            $transaction = DB::table('payment_transactions')->join('company_subscriptions', 'company_subscriptions.id', '=', 'payment_transactions.subscription_id')
                ->where('payment_transactions.id', $transactionId)->where('company_subscriptions.company_id', $company->id)
                ->select('payment_transactions.*')->lockForUpdate()->first();
            abort_if($transaction === null, 404);
            abort_unless($transaction->status === 'pending_payment', 409, 'Transaksi tidak dapat dibatalkan');
            DB::table('payment_transactions')->where('id', $transactionId)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('company_subscriptions')->where('id', $transaction->subscription_id)->update(['status' => 'cancelled', 'ended_at' => now(), 'updated_at' => now()]);
            $this->event($transactionId, $employerId, 'pending_payment', 'cancelled');
        }, attempts: 3);
    }

    public function decide(string $adminId, string $transactionId, bool $approve, ?string $note): void
    {
        DB::transaction(function () use ($adminId, $transactionId, $approve, $note): void {
            $lookup = DB::table('payment_transactions')->join('company_subscriptions', 'company_subscriptions.id', '=', 'payment_transactions.subscription_id')
                ->where('payment_transactions.id', $transactionId)->select('company_subscriptions.company_id')->first();
            abort_if($lookup === null, 404);
            DB::table('companies')->where('id', $lookup->company_id)->lockForUpdate()->first();
            $transaction = DB::table('payment_transactions')->where('id', $transactionId)->lockForUpdate()->first();
            abort_unless($transaction->status === 'waiting_verification', 409, 'Transaksi sudah diproses');
            $subscription = DB::table('company_subscriptions')->where('id', $transaction->subscription_id)->lockForUpdate()->first();
            abort_unless($subscription->status === 'pending', 409);
            $active = DB::table('company_subscriptions')->where('company_id', $subscription->company_id)->where('status', 'active')->where('expires_at', '>', now())->exists();
            if ($approve) {
                abort_if(DB::table('company_subscriptions')->where('company_id', $subscription->company_id)->whereIn('status', ['scheduled', 'pending_activation'])->exists(), 409, 'Antrean sudah terisi');
            }
            $status = $approve ? 'paid' : 'rejected';
            DB::table('payment_transactions')->where('id', $transactionId)->update(['status' => $status, 'paid_at' => $approve ? now() : null,
                'verified_by' => $adminId, 'verified_at' => now(), 'verification_note' => $note, 'updated_at' => now()]);
            DB::table('company_subscriptions')->where('id', $subscription->id)->update($approve
                ? ['status' => $active ? 'pending_activation' : 'active', 'starts_at' => $active ? null : now(),
                    'expires_at' => $active ? null : now()->addDays($subscription->duration_days), 'updated_at' => now()]
                : ['status' => 'cancelled', 'ended_at' => now(), 'updated_at' => now()]);
            $this->event($transactionId, $adminId, 'waiting_verification', $status, $note);
        }, attempts: 3);
    }

    public function activateDue(): int
    {
        $ids = DB::table('company_subscriptions')->whereIn('status', ['active', 'scheduled', 'pending_activation'])->distinct()->pluck('company_id');
        $count = 0;
        foreach ($ids as $id) {
            $count += $this->activateForCompany($id);
        }

        return $count;
    }

    public function activateForCompany(string $companyId): int
    {
        return DB::transaction(function () use ($companyId): int {
            DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();
            DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'active')->where('expires_at', '<=', now())
                ->update(['status' => 'expired', 'ended_at' => now(), 'updated_at' => now()]);
            if (DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'active')->where('expires_at', '>', now())->exists()) {
                return 0;
            }
            $next = DB::table('company_subscriptions')->where('company_id', $companyId)->whereIn('status', ['scheduled', 'pending_activation'])->orderBy('created_at')->lockForUpdate()->first();
            $transactionId = $next ? DB::table('payment_transactions')->where('subscription_id', $next->id)->where('status', 'paid')->value('id') : null;
            if ($transactionId === null) {
                return 0;
            }
            DB::table('company_subscriptions')->where('id', $next->id)->update(['status' => 'active', 'starts_at' => now(),
                'expires_at' => now()->addDays($next->duration_days ?: DB::table('packages')->where('id', $next->package_id)->value('duration_days')),
                'updated_at' => now()]);
            $this->event($transactionId, null, 'paid', 'paid', 'Subscription diaktifkan dari antrean');

            return 1;
        }, attempts: 3);
    }

    public function purchaseBlockMessage(string $companyId): ?string
    {
        $queue = DB::table('company_subscriptions')->join('packages', 'packages.id', '=', 'company_subscriptions.package_id')
            ->where('company_subscriptions.company_id', $companyId)
            ->whereIn('company_subscriptions.status', ['pending', 'scheduled', 'pending_activation'])
            ->get(['company_subscriptions.status', 'packages.name as package_name']);
        $pendingActivation = $queue->firstWhere('status', 'pending_activation');
        if ($pendingActivation !== null) {
            $trialActive = DB::table('company_subscriptions')->where('company_id', $companyId)->where('source', 'admin_trial')
                ->where('status', 'active')->where('starts_at', '<=', now())->where('expires_at', '>', now())->exists();

            return $trialActive
                ? "Trial Anda masih aktif dan paket {$pendingActivation->package_name} sedang menunggu aktivasi. Pembelian berikutnya dapat dilakukan setelah trial berakhir dan paket {$pendingActivation->package_name} aktif."
                : 'Anda masih memiliki antrean subscription yang menunggu aktivasi. Selesaikan antrean tersebut sebelum membeli paket berikutnya.';
        }

        return $queue->contains('status', 'scheduled')
            ? 'Anda masih memiliki antrean subscription yang menunggu aktivasi. Selesaikan antrean tersebut sebelum membeli paket berikutnya.'
            : ($queue->contains('status', 'pending') ? 'Selesaikan pembelian sebelumnya sebelum membeli paket berikutnya.' : null);
    }

    private function event(string $transactionId, ?string $actorId, ?string $from, string $to, ?string $note = null): void
    {
        DB::table('payment_transaction_events')->insert(['transaction_id' => $transactionId, 'actor_id' => $actorId,
            'from_status' => $from, 'to_status' => $to, 'note' => $note, 'created_at' => now()]);
    }
}
