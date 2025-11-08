<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        return response()->json(EventType::orderBy('name', 'asc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_types,name',
            'status' => 'in:Active,Inactive',
            'sms_template' => 'nullable|string',
            'whatsapp_template' => 'nullable|string',
            'sms_invitation_template' => 'nullable|string',
            'whatsapp_invitation_template' => 'nullable|string',
            'sms_donation_template' => 'nullable|string',
            'whatsapp_donation_template' => 'nullable|string',
        ]);
        $eventType = EventType::create(array_merge(['status' => 'Active'], $validated));
        return response()->json($eventType, 201);
    }

    public function update(Request $request, EventType $eventType): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:event_types,name,' . $eventType->id,
            'status' => 'in:Active,Inactive',
            'sms_template' => 'nullable|string',
            'whatsapp_template' => 'nullable|string',
            'sms_invitation_template' => 'nullable|string',
            'whatsapp_invitation_template' => 'nullable|string',
            'sms_donation_template' => 'nullable|string',
            'whatsapp_donation_template' => 'nullable|string',
        ]);
        $eventType->update($validated);
        return response()->json($eventType);
    }

    public function toggleStatus(Request $request, EventType $eventType): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $eventType->status = $eventType->status === 'Active' ? 'Inactive' : 'Active';
        $eventType->save();
        return response()->json($eventType);
    }
} 