<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $companies = DB::table('companies')
            ->leftJoin('users as owners', 'companies.owner_user_id', '=', 'owners.id')
            ->select('companies.id', 'companies.name', 'companies.location', 'companies.owner_user_id', 'companies.created_by', 'companies.logo', 'companies.created_at', 'owners.role as owner_role')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('companies.name', 'like', '%'.$search.'%'))
            ->orderBy('companies.name')->paginate(15)->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function show(Request $request, string $id): View
    {
        $company = DB::table('companies')->leftJoin('users as owners', 'companies.owner_user_id', '=', 'owners.id')
            ->where('companies.id', $id)->select('companies.*', 'owners.role as owner_role')->first();
        abort_if($company === null, 404);

        $jobs = DB::table('job_posts')->where('company_id', $id)
            ->select('id', 'title', 'status', 'posted_by', 'created_at', 'expires_at')
            ->orderByDesc('created_at')->paginate(10);
        $isOwned = $company->owner_user_id === $request->user()->id;

        return view('admin.companies.show', compact('company', 'jobs', 'isOwned'));
    }
}
