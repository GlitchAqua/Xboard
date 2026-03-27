<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function logs(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $logs = $this->balanceService->getLogs($request->user()->id, $page, $limit);
        return $this->success($logs);
    }
}
