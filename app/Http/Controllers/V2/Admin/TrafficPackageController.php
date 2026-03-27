<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrafficPackage;
use Illuminate\Http\Request;

class TrafficPackageController extends Controller
{
    public function fetch()
    {
        $packages = TrafficPackage::orderBy('sort', 'asc')->get();
        return $this->success($packages);
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'traffic_bytes' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'validity_days' => 'nullable|integer|min:0',
            'type' => 'nullable|in:time-limited,permanent',
        ]);

        $package = TrafficPackage::create($request->only([
            'name', 'description', 'traffic_bytes', 'price',
            'validity_days', 'group_id', 'speed_limit', 'show', 'sell', 'sort', 'type'
        ]));

        return $this->success($package);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:v2_traffic_package,id',
            'type' => 'nullable|in:time-limited,permanent',
        ]);

        $package = TrafficPackage::findOrFail($request->input('id'));
        $package->update($request->only([
            'name', 'description', 'traffic_bytes', 'price',
            'validity_days', 'group_id', 'speed_limit', 'show', 'sell', 'sort', 'type'
        ]));

        return $this->success(true);
    }

    public function drop(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:v2_traffic_package,id',
        ]);

        TrafficPackage::findOrFail($request->input('id'))->delete();
        return $this->success(true);
    }
}
