<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('events');
        
        // Search functionality
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('physical_location', 'like', "%{$search}%");
            });
        }
        
        $perPage = $request->get('per_page', 10);
        $customers = $query->paginate($perPage);
        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:customers,phone_number|max:20',
            'title' => 'nullable|string|max:255',
            'physical_location' => 'nullable|string'
        ]);

        // Validate and normalize phone number
        $phoneValidation = PhoneNumberService::validateAndNormalize($validated['phone_number']);
        if (!$phoneValidation['is_valid']) {
            return response()->json([
                'message' => 'Invalid phone number format',
                'errors' => ['phone_number' => [$phoneValidation['error']]]
            ], 422);
        }

        // Use normalized phone number
        $validated['phone_number'] = $phoneValidation['normalized'];

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('events.eventType', 'events.cardClass', 'events.package');
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|unique:customers,phone_number,' . $customer->id . '|max:20',
            'title' => 'nullable|string|max:255',
            'physical_location' => 'nullable|string'
        ]);

        // Validate and normalize phone number if provided
        if (isset($validated['phone_number'])) {
            $phoneValidation = PhoneNumberService::validateAndNormalize($validated['phone_number']);
            if (!$phoneValidation['is_valid']) {
                return response()->json([
                    'message' => 'Invalid phone number format',
                    'errors' => ['phone_number' => [$phoneValidation['error']]]
                ], 422);
            }

            // Use normalized phone number
            $validated['phone_number'] = $phoneValidation['normalized'];
        }

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function toggleStatus(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $customer->status = $customer->status === 'Active' ? 'Inactive' : 'Active';
        $customer->save();
        return response()->json($customer);
    }

    public function activate(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $customer->status = 'Active';
        $customer->save();
        return response()->json($customer);
    }

    public function deactivate(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        $customer->status = 'Inactive';
        $customer->save();
        return response()->json($customer);
    }
} 