<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __invoke(): View
    {
        $packages = DB::table('packages')->where('is_active', true)
            ->select('id', 'name', 'quota', 'duration_days', 'price', 'currency')
            ->orderBy('price')->orderBy('id')->paginate(10);

        return view('admin.packages.index', compact('packages'));
    }
}
