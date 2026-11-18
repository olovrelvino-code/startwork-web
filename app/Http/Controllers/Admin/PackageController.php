<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageController extends Controller
{
    private const LEGACY_BENEFITS = [
        'Basic' => ['Tayang di website (15 hari)', '1x Instagram Story', '1x Feed (Simple Design)'],
        'Standard' => ['Tayang di website (30 hari)', '1x Feed (Excellent Design)', '2x Instagram Story', 'Featured Lowongan di Website (1 hari)'],
        'Premium' => ['Tayang di website (45 hari)', '1x Reels (Video)', '1x Feed', '3x Instagram Story', 'Featured Lowongan di Website (3 hari)'],
    ];

    public function __invoke(Request $request): View
    {
        $status = $request->query('status') === 'draft' ? 'draft' : 'active';
        $packages = DB::table('packages')->where('is_active', $status === 'active')
            ->select('id', 'name', 'quota', 'duration_days', 'price', 'currency', 'is_active', 'benefits')
            ->orderBy('price')->orderBy('id')->paginate(10)->withQueryString();
        $usedPackageIds = DB::table('company_subscriptions')->whereIn('package_id', $packages->pluck('id'))
            ->distinct()->pluck('package_id');

        foreach ($packages as $package) {
            $package->has_subscriptions = $usedPackageIds->contains($package->id);
            $package->benefit_list = $package->benefits
                ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $package->benefits))))
                : (self::LEGACY_BENEFITS[$package->name] ?? []);
        }

        return view('admin.packages.index', compact('packages', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPackage($request);
        DB::table('packages')->insert($data + ['id' => (string) Str::uuid(), 'currency' => 'IDR', 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('admin.packages.index', ['status' => $data['is_active'] ? 'active' : 'draft'])
            ->with('success', 'Paket berhasil ditambahkan.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $package = DB::table('packages')->where('id', $id)->first();
        abort_if($package === null, 404);
        $data = $this->validatedPackage($request, $id);
        abort_if($package->name === 'Basic' && $data['name'] !== 'Basic', 422, 'Nama paket Basic diperlukan untuk akses trial.');
        abort_if($data['name'] !== $package->name && DB::table('company_subscriptions')->where('package_id', $id)->exists(), 409, 'Nama paket dengan riwayat subscription tidak dapat diubah.');
        DB::table('packages')->where('id', $id)->update($data + ['updated_at' => now()]);

        return redirect()->route('admin.packages.index', ['status' => $data['is_active'] ? 'active' : 'draft'])
            ->with('success', 'Paket berhasil diperbarui.');
    }

    public function toggle(string $id): RedirectResponse
    {
        $package = DB::table('packages')->where('id', $id)->first();
        abort_if($package === null, 404);
        $isActive = ! $package->is_active;
        DB::table('packages')->where('id', $id)->update(['is_active' => $isActive, 'updated_at' => now()]);

        return redirect()->route('admin.packages.index', ['status' => $isActive ? 'active' : 'draft'])
            ->with('success', $isActive ? 'Paket diterbitkan.' : 'Paket dinonaktifkan.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $package = DB::table('packages')->where('id', $id)->first();
        abort_if($package === null, 404);
        abort_if(in_array($package->name, ['Basic', 'Standard', 'Premium'], true)
            || DB::table('company_subscriptions')->where('package_id', $id)->exists(), 409, 'Paket bawaan atau yang memiliki riwayat subscription tidak dapat dihapus.');
        DB::table('packages')->where('id', $id)->delete();

        return redirect()->route('admin.packages.index', ['status' => $package->is_active ? 'active' : 'draft'])
            ->with('success', 'Paket berhasil dihapus.');
    }

    private function validatedPackage(Request $request, ?string $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('packages', 'name')->ignore($id)],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'benefits' => ['required', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['name'] = trim($data['name']);
        $data['benefits'] = trim($data['benefits']);

        return $data;
    }
}
