<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function ShowUserInfo()
    {
        $user = User::where('role', 'salesman')->select('id', 'name', 'Cash')->get();

        return response()->json($user);
    }
    public function Pick(Request $request)
    {
        $user = auth()->user();
        // Validate the incoming request data
        $request->validate([
            'AccID' => 'required',
            'Amount' => 'required|numeric|min:0',
        ]);
        try {
            // Start a database transaction
            DB::beginTransaction();
            $USER = User::find($request->input('AccID'));
            $USER->Cash -= $request->input('Amount');
            $newCashBalance = $user->Cash + $request->input('Amount');
            $USER->save();
            $user->update(['Cash' => $newCashBalance]);
            DB::commit();
            return response()->json(['message' => 'Balance Pickup successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the database transaction in case of an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to Pick up Balance'], 500);
        }

    }
}
