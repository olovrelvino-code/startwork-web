<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();

        return view('employer.company', compact('company'));
    }

    public function setup(Request $request): View
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();

        return view('employer.company', ['company' => $company, 'setup' => true]);
    }
}
