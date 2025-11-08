<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        return response()->json(Package::orderBy('name', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'amount' => 'required|numeric|min:0',
            'currency' => 'string|max:10',
            'status' => 'in:Active,Inactive',
        ]);
        $package = Package::create(array_merge([
            'currency' => 'TZS',
            'status' => 'Active'
        ], $validated));
        return response()->json($package, 201);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name,' . $package->id,
            'amount' => 'required|numeric|min:0',
            'currency' => 'string|max:10',
            'status' => 'in:Active,Inactive',
        ]);
        $package->update($validated);
        return response()->json($package);
    }

    public function toggleStatus(Request $request, Package $package): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $package->status = $package->status === 'Active' ? 'Inactive' : 'Active';
        $package->save();
        return response()->json($package);
    }
} 