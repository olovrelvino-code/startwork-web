<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();

        return view('employer.company', compact('company'));
    }

    public function setup(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->google_id !== null && (! filled(trim((string) $user->full_name))
            || ! filled(trim((string) $user->phone)) || ! filled(trim((string) $user->email)))) {
            return view('employer.register', ['completingAccount' => true, 'account' => $user]);
        }

        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();

        if ($company !== null && filled(trim((string) $company->name)) && filled(trim((string) $company->location))) {
            return redirect()->route('employer.dashboard');
        }

        return view('employer.company', ['company' => $company, 'setup' => true]);
    }

    public function completeAccount(Request $request): JsonResponse
    {
        abort_unless($request->user()->google_id !== null, 403);
        $input = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::in([$request->user()->email])],
        ]);

        $request->user()->update(['full_name' => $input['full_name'], 'phone' => $input['phone']]);

        return response()->json(['message' => 'Data akun berhasil dilengkapi.']);
    }
}
