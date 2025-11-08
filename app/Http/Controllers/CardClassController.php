<?php

namespace App\Http\Controllers;

use App\Models\CardClass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CardClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admin
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        return response()->json(CardClass::orderBy('name', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:card_classes,name',
            'max_guests' => 'required|integer|min:1',
            'status' => 'in:Active,Inactive',
        ]);
        $cardClass = CardClass::create(array_merge(['status' => 'Active'], $validated));
        return response()->json($cardClass, 201);
    }

    public function update(Request $request, CardClass $cardClass): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:card_classes,name,' . $cardClass->id,
            'max_guests' => 'required|integer|min:1',
            'status' => 'in:Active,Inactive',
        ]);
        $cardClass->update($validated);
        return response()->json($cardClass);
    }

    public function toggleStatus(Request $request, CardClass $cardClass): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $cardClass->status = $cardClass->status === 'Active' ? 'Inactive' : 'Active';
        $cardClass->save();
        return response()->json($cardClass);
    }
} 