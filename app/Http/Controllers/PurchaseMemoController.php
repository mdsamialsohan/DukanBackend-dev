<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseList;
use App\Models\Product;
use App\Models\PurchaseDtls;
use App\Models\PurchaseMemo;
use App\Models\VendorList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseMemoController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        // Validate the incoming request data
        $request->validate([
            'Date' => 'required|date',
            'VendorID' => 'required|exists:vendor,VendorID',
            'Pay' =>'required|numeric|min:0',
            'TransportCost' => 'nullable|numeric|min:0',
            'LabourCost' => 'nullable|numeric|min:0',
            'products' => 'required|array',
            'products.*.productID' => 'required|exists:products,ProductID',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.rate' => 'required|numeric|min:0',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new PurchaseMemo
            $purchaseMemo = PurchaseMemo::create([
                'Date' => $request->input('Date'),
                'VendorID' => $request->input('VendorID'),
                'Debt' => 0,
                'TotalBill' => 0,
                'PrevDebt' => 0,
                'Paid' => $request->input('Pay'),
            ]);
            $totalBill = 0;
            // Fetch products with their unit weights
            $products = collect($request->input('products'))->map(function ($productData) {
                $product = Product::with('unit')->find($productData['productID']);
                $unitWeight = $product->unit->Unit2KG ?? 1; // fallback to 1 if not set
                return [
                    'productID' => $productData['productID'],
                    'quantity' => $productData['quantity'],
                    'rate' => $productData['rate'],
                    'unitWeight' => $unitWeight,
                    'totalWeight' => $productData['quantity'] * $unitWeight,
                ];
            });
// Calculate total weight
            $totalWeight = $products->sum('totalWeight');
// Calculate per-kg costs
            $transportPerKg = $request->input('TransportCost', 0) / max($totalWeight, 1);
            $labourPerKg = $request->input('LabourCost', 0) / max($totalWeight, 1);

            // Iterate through the products and update Product, MemoDtls, and calculate totalBill
            foreach ($products as $productData) {
                $productID = $productData['productID'];
                $quantity = $productData['quantity'];
                $rate = $productData['rate'];
                $finalRate = $productData['rate'] + ($transportPerKg + $labourPerKg) * $productData['unitWeight'];

                // Update Product quantity
                $product = Product::find($productID);
                $product->Rate  = (($product->Rate*$product->ProductUnit)+($quantity*$finalRate))/($product->ProductUnit+$quantity);
                $product->ExpPerunit = (($product->ExpPerUnit*$product->ProductUnit) + ((($transportPerKg + $labourPerKg) * $productData['unitWeight'])*$quantity))/ ($product->ProductUnit + $quantity);
                $product->ProductUnit += $quantity;
                $product->save();



                // Create MemoDtls
                PurchaseDtls::create([
                    'ProductID' => $productID,
                    'Quantity' => $quantity,
                    'Rate' => $rate,
                    'SubTotal' => $quantity * $rate,
                    'PurMemoID' => $purchaseMemo->PurMemoID,
                ]);

                // Calculate totalBill
                $totalBill += $quantity * $rate;
            }

            // Update totalBill in PurchaseMemo
            $purchaseMemo->update(['TotalBill' => $totalBill]);

            // Update Vendor debt
            $vendor = VendorList::find($request->input('VendorID'));
            $purchaseMemo->update(['PrevDebt' => $vendor->Debt]);
            $vendor->Debt += $totalBill; // Assuming totalBill is the amount of the purchase
            $vendor->Debt -= $request->input('Pay');
            $newCashBalance = $user->Cash - $request->input('Pay');
            $newCashBalance = $newCashBalance - $request->input('TransportCost', 0) - $request->input('LabourCost', 0);
            $vendor->save();
            $purchaseMemo->update(['Debt' => $vendor->Debt]);
            $user->update(['Cash' => $newCashBalance]);

            if ($request->input('TransportCost', 0) > 0) {
                $transportExpense = Expense::firstOrCreate(['ExpName' => 'Transport']);
                ExpenseList::create([
                    'ExpID' => $transportExpense->ExpID,
                    'Date' => $request->input('Date'),
                    'Amount' => $request->input('TransportCost'),
                    'Ref' => 'PurchaseMemoID: ' . $purchaseMemo->PurMemoID,
                ]);
            }
            if ($request->input('LabourCost', 0) > 0) {
                $labourExpense = Expense::firstOrCreate(['ExpName' => 'Labour']);
                ExpenseList::create([
                    'ExpID' => $labourExpense->ExpID,
                    'Date' => $request->input('Date'),
                    'Amount' => $request->input('LabourCost'),
                    'Ref' => 'PurchaseMemoID: ' . $purchaseMemo->PurMemoID,
                ]);
            }
            // Commit the database transaction

            DB::commit();
            return response()->json(['message' => 'Purchase successfully added'], 201);
        } catch (\Exception $e) {
            // Rollback the database transaction in case of an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to add purchase'], 500);
        }
    }
    public function Debt(Request $request)
    {
        $user = auth()->user();
        // Validate the incoming request data
        $request->validate([
            'Date' => 'required|date',
            'VendorID' => 'required|exists:vendor,VendorID',
            'Pay' =>'required|numeric',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new PurchaseMemo
            $purchaseMemo = PurchaseMemo::create([
                'Date' => $request->input('Date'),
                'VendorID' => $request->input('VendorID'),
                'Debt' => 0,
                'TotalBill' => 0,
                'PrevDebt' => 0,
                'Paid' => $request->input('Pay'),
            ]);
            // Update Vendor debt
            $vendor = VendorList::find($request->input('VendorID'));
            $purchaseMemo->update(['PrevDebt' => $vendor->Debt]);
            $vendor->Debt -= $request->input('Pay');
            $newCashBalance = $user->Cash - $request->input('Pay');
            $vendor->save();
            $user->update(['Cash' => $newCashBalance]);
            $purchaseMemo->update(['Debt' => $vendor->Debt]);
            // Commit the database transaction
            DB::commit();

            return response()->json(['message' => 'Debt paid successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the database transaction in case of an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to add debt'], 500);
        }
    }
    public function getPurMemoDetails($PurchaseMemoID)
    {
        $purMemo = PurchaseMemo::with(['Vendor', 'purchaseDtls', 'purchaseDtls.product','purchaseDtls.product.brand', 'purchaseDtls.product.category', 'purchaseDtls.product.unit'])
            ->find($PurchaseMemoID);

        if (!$purMemo) {
            return response()->json(['error' => 'purchase Memo not found'], 404);
        }

        return response()->json(['purMemo' => $purMemo]);
    }
    public function getPurMemo($Date)
    {
        $purMemo = PurchaseMemo::with(['Vendor', 'purchaseDtls', 'purchaseDtls.product','purchaseDtls.product.brand', 'purchaseDtls.product.category', 'purchaseDtls.product.unit'])
            ->whereDate('Date', $Date)
            ->get();

        if (!$purMemo) {
            return response()->json(['error' => 'Purchase Memo not found'], 404);
        }

        return response()->json(['purMemo' => $purMemo]);
    }
    public function getPurPaid($Date)
    {
        $purMemo = PurchaseMemo::with(['Vendor'])
            ->whereDate('Date', $Date)
            ->where('Paid', '!=', 0)
            ->get();

        if (!$purMemo) {
            return response()->json(['error' => 'Purchase Memo not found'], 404);
        }

        return response()->json(['purMemo' => $purMemo]);
    }
}
