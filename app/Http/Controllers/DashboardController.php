<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        return DashboardService::dashboard($request);
    }
}
