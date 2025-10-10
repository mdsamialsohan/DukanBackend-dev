<?php

namespace App\Http\Controllers;

use App\Models\CustomerList;
use App\Models\SellMemo;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = CustomerList::all(); // Fetch data from the CustomerList model
        return response()->json($customers);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'mobile' => 'nullable|string',
            'address' => 'required|string',
            'due'=> 'required|string',
            'national_id' => 'nullable|string',
        ]);

        $customer = CustomerList::create($validatedData);

        return response()->json(['message' => 'Customer added successfully', 'customer' => $customer]);
    }
    public function TotalDue()
    {
        $customers = CustomerList::all();

        $due = $customers->sum('due');

        return response()->json(['due' => $due]);
    }
    public function ledger($customerId)
    {
        $sellMemos = SellMemo::where('c_id', $customerId)->with('sellDtls')->get();
        return response()->json(['sellMemos' => $sellMemos]);
    }
    public function CustomerById($customerId)
    {
        try {
            $customer = CustomerList::findOrFail($customerId);
            return response()->json(['customer' => $customer]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }
    public function UpdateCustomer(Request $request, $customerId)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'mobile' => 'nullable|string',
            'address' => 'required|string',
            'national_id' => 'nullable|string',
            'due' => 'required|string'
        ]);

        try {
            $customer = CustomerList::findOrFail($customerId);

            $customer->update($validatedData);

            return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }
}
