<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TrafficPackageService;
use Illuminate\Http\Request;

class TrafficPackageController extends Controller
{
    protected TrafficPackageService $packageService;

    public function __construct(TrafficPackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function fetch()
    {
        $packages = $this->packageService->getAvailablePackages();
        return $this->success($packages);
    }

    public function purchase(Request $request)
    {
        $request->validate([
            'package_id' => 'required|integer|exists:v2_traffic_package,id',
            'auto_renew' => 'nullable|boolean',
            'consumption_priority' => 'nullable|integer|min:0',
        ]);

        $user = User::findOrFail($request->user()->id);
        $userPackage = $this->packageService->purchase(
            $user,
            $request->input('package_id'),
            (bool) $request->input('auto_renew', false),
            (int) $request->input('consumption_priority', 0)
        );

        return $this->success($userPackage);
    }

    public function active(Request $request)
    {
        $packages = $this->packageService->getActivePackages($request->user()->id);
        return $this->success($packages);
    }

    public function toggleAutoRenew(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'auto_renew' => 'required|boolean',
        ]);

        $this->packageService->toggleAutoRenew(
            $request->user()->id,
            $request->input('id'),
            $request->input('auto_renew')
        );

        return $this->success(true);
    }

    public function updatePriority(Request $request)
    {
        $request->validate([
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|integer',
            'priorities.*.priority' => 'required|integer|min:0',
        ]);

        $this->packageService->updatePriority(
            $request->user()->id,
            $request->input('priorities')
        );

        return $this->success(true);
    }
}
