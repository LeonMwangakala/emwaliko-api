<?php

namespace App\Http\Controllers;

use App\Models\CardType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CardTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admin
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        return response()->json(CardType::orderBy('name', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:card_types,name',
            'status' => 'in:Active,Inactive',
            'show_card_class' => 'boolean',
            'show_guest_name' => 'boolean',
            'show_qr_code' => 'boolean',
        ]);
        $cardType = CardType::create(array_merge(['status' => 'Active'], $validated));
        return response()->json($cardType, 201);
    }

    public function update(Request $request, CardType $cardType): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:card_types,name,' . $cardType->id,
            'status' => 'in:Active,Inactive',
            'show_card_class' => 'boolean',
            'show_guest_name' => 'boolean',
            'show_qr_code' => 'boolean',
        ]);
        $cardType->update($validated);
        return response()->json($cardType);
    }

    public function toggleStatus(Request $request, CardType $cardType): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $cardType->status = $cardType->status === 'Active' ? 'Inactive' : 'Active';
        $cardType->save();
        return response()->json($cardType);
    }
} 