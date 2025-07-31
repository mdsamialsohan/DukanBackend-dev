<?php

namespace App\Http\Controllers;

use App\Models\CustomerList;
use App\Models\Product;
use App\Models\SellDtls;
use App\Models\SellMemo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellDtlsController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        // Validate the incoming request data
        $request->validate([
            'Date' => 'required|date',
            'c_id' => 'required|exists:customer_list,c_id',
            'Pay' => 'required|numeric|min:0',
            'products' => 'required|array',
            'products.*.productID' => 'required|exists:products,ProductID',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.rate' => 'required|numeric|min:0',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();
            // Create a new PurchaseMemo

            $sellMemo = SellMemo::create([
                'Date' => $request->input('Date'),
                'c_id' => $request->input('c_id'),
                'Due' => 0,
                'TotalBill' => 0,
                'PrevDue' => 0,
                'Paid' => $request->input('Pay'),
                'isApproved' => $user->role === 'admin', // auto-approve if admin
                'approved_by' => $user->role === 'admin' ? $user->id : null,
                'created_by' => $user->id,
            ]);
            $totalBill = 0;

            // Iterate through the products and update Product, MemoDtls, and calculate totalBill
            foreach ($request->input('products') as $productData) {
                $productID = $productData['productID'];
                $quantity = $productData['quantity'];
                $rate = $productData['rate'];
                $subtotal = $quantity * $rate;

                // Update Product quantity
                if ($user->role === 'admin') {
                    $product = Product::find($productID);
                    $product->ProductUnit -= $quantity;
                    $product->save();
                }


                // Create MemoDtls
                SellDtls::create([
                    'ProductID' => $productID,
                    'Quantity' => $quantity,
                    'Rate' => $rate,
                    'SubTotal' => $subtotal,
                    'SellMemoID' => $sellMemo->SellMemoID,
                    'isApproved' => $sellMemo->isApproved,
                    'approved_by' => $sellMemo->approved_by,
                    'created_by' => $user->id,
                ]);


                // Calculate totalBill
                $totalBill += $quantity * $rate;
            }

            // Update totalBill in PurchaseMemo
            $sellMemo->update(['TotalBill' => $totalBill]);

            // Update Vendor debt

            if ($user->role === 'admin') {
                $customer = CustomerList::find($request->input('c_id'));

                $sellMemo->update(['PrevDue' => $customer->due]);

                $customer->due += $totalBill;
                $customer->due -= $request->input('Pay');
                $customer->save();

                $sellMemo->update(['Due' => $customer->due]);

                $user->update(['Cash' => $user->Cash + $request->input('Pay')]);
            }

            // Commit the database transaction
            DB::commit();

            return response()->json(['message' => 'Sell successfully added', 'memoId' => $sellMemo->SellMemoID], 201);
        } catch (\Exception $e) {
            // Rollback the database transaction in case of an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to add Sell'], 500);
        }
    }

    public function approve(Request $request, $memoId)
    {
        $user = auth()->user();
        //Log::info('ApproveMemo POST data:', $request->all());
        $request->validate([
            'date' => 'required|date',
            'customerID' => 'required|exists:customer_list,c_id',
            'paid' => 'required|numeric|min:0',
            'sell_dtls' => 'nullable|array',
            'sell_dtls.*.SellDtID' => 'required|exists:sell_dtls,SellDtID',
            'sell_dtls.*.product_id' => 'required|exists:products,ProductID',
            'sell_dtls.*.Quantity' => 'required|integer|min:1',
            'sell_dtls.*.Rate' => 'required|numeric|min:0',
            'sell_dtls.*.SubTotal' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $sellMemo = SellMemo::findOrFail($memoId);

            $sellMemo->Date = $request->input('date');
            $sellMemo->c_id = $request->input('customerID');
            $sellMemo->Paid = $request->input('paid');
            $sellMemo->isApproved = 1;
            $sellMemo->approved_by =  $user->id ;

            $totalBill = 0;
            if ($request->has('sell_dtls')) {
                foreach ($request->input('sell_dtls') as $item) {
                    $sellDtl = SellDtls::findOrFail($item['SellDtID']);
                    $sellDtl->ProductID = $item['product_id'];
                    $sellDtl->Quantity = $item['Quantity'];
                    $sellDtl->Rate = $item['Rate'];
                    $sellDtl->SubTotal = $item['SubTotal'];
                    $sellDtl->isApproved = 1;
                    $sellDtl->approved_by = $user->id;
                    $sellDtl->save();

                    $totalBill += $item['SubTotal'];

                    $product = Product::find($sellDtl->ProductID);
                    $product->ProductUnit -= $sellDtl->Quantity;
                    $product->save();
                }
            }

            $sellMemo->TotalBill = $totalBill;

            $customer = $sellMemo->customer;
            $sellMemo->PrevDue = $customer->due;

            $customer->due = $customer->due - $sellMemo->Paid + $totalBill;
            $customer->save();

            $sellMemo->Due = $customer->due;

            $sellMemo->save();

            if ($sellMemo->created_by) {
                $salesman = User::find($sellMemo->created_by);
                if ($salesman) {
                    $salesman->Cash = $salesman->Cash + $request->input('paid');
                    $salesman->save();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Memo approved successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function Due(Request $request)
    {
        $user = auth()->user();
        // Validate the incoming request data
        $request->validate([
            'Date' => 'required|date',
            'c_id' => 'required|exists:customer_list,c_id',
            'Pay' => 'required|numeric',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new PurchaseMemo
            $sellMemo = SellMemo::create([
                'Date' => $request->input('Date'),
                'c_id' => $request->input('c_id'),
                'Due' => 0,
                'TotalBill' => 0,
                'PrevDue' => 0,
                'Paid' => $request->input('Pay'),
                'created_by' => $user->id,
            ]);

            // Update Vendor debt
            if ($user->role === 'admin') {
                $customer = CustomerList::find($request->input('c_id'));
                $sellMemo->update(['PrevDue' => $customer->due]);
                $customer->due -= $request->input('Pay');
                $sellMemo->update(['Due' => $customer->due]);
                $newCashBalance = $user->Cash + $request->input('Pay');
                $customer->save();
                $user->update(['Cash' => $newCashBalance]);
            }
            // Commit the database transaction
            DB::commit();
           // $this->sms_send($customer->mobile, $request->input('Pay').'টাকা জমা হয়ে বর্তমান হিসাব:'. $customer->due.'টাকা । - মেসার্স মোঃ রজব আলী' );

            return response()->json(['message' => 'Due paid successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the database transaction in case of an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to add debt'], 500);
        }
    }
    function sms_send($numbers, $message) {
        $url = "http://bulksmsbd.net/api/smsapi";
        $api_key = "45TfstQ0tcyju6uapWEz";
        $senderid = "8809617614525";
        $data = [
            "api_key" => $api_key,
            "senderid" => $senderid,
            "number" => $numbers,
            "message" => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    public function getSellMemoDetails($sellMemoID)
    {
        // Fetch detailed information about the Sell Memo with SellDtls
        $sellMemo = SellMemo::with(['customer', 'sellDtls', 'sellDtls.product','sellDtls.product.brand', 'sellDtls.product.category', 'sellDtls.product.unit'])
            ->find($sellMemoID);

        if (!$sellMemo) {
            return response()->json(['error' => 'Sell Memo not found'], 404);
        }

        return response()->json(['sellMemo' => $sellMemo]);
    }
    public function getSellMemo($Date)
    {
        $sellMemo = SellMemo::with(['customer', 'sellDtls', 'sellDtls.product','sellDtls.product.brand', 'sellDtls.product.category', 'sellDtls.product.unit'])
            ->whereDate('Date', $Date)
            ->where('Paid', '=', 0)
            ->get();

        if (!$sellMemo) {
            return response()->json(['error' => 'Sell Memo not found'], 404);
        }

        return response()->json(['sellMemo' => $sellMemo]);
    }
    public function getSellPaid($Date)
    {
        $sellMemo = SellMemo::with(['customer'])
            ->whereDate('Date', $Date)
            ->where('Paid', '!=', 0)
            ->get();

        if (!$sellMemo) {
            return response()->json(['error' => 'Sell Memo not found'], 404);
        }

        return response()->json(['sellMemo' => $sellMemo]);
    }
    public function TotalBillByDate($date)
    {
        // Filter SellMemo records for a specific date
        $sellMemos = SellMemo::whereDate('Date', $date)->get();

        // Calculate the total of TotalBill for the filtered records
        $totalBill = $sellMemos->sum('TotalBill');

        return response()->json(['total_bill' => $totalBill]);
    }
    public function TotalPayByDate($date)
    {
        // Filter SellMemo records for a specific date
        $sellMemos = SellMemo::whereDate('Date', $date)->get();

        // Calculate the total of TotalBill for the filtered records
        $Paid = $sellMemos->sum('Paid');

        return response()->json(['Paid' => $Paid]);
    }
    public function soldProductsByDate($Date)
    {
        $soldProducts = SellDtls::whereHas('sellMemo', function ($query) use ($Date) {
            $query->where('Date', $Date);
        })
            ->with(['product.brand', 'product.category', 'product.unit'])
            ->select('ProductID', DB::raw('SUM(Quantity) as totalQuantity'))
            ->groupBy('ProductID')
            ->get();

        return response()->json(['soldProducts' => $soldProducts]);
    }
    public function SoldProductAPI()
    {
        $soldProducts = SellDtls::all()
            ->with(['sellMemo.Date','product.brand.BrandName', 'product.category.ProductCat', 'product.unit.UnitName','Quantity'])
            ->get();
        return response()->json(['soldProduct' => $soldProducts]);
    }
    public function pendingMemos()
    {
        $pending = SellMemo::with('customer')
            ->where('isApproved', 0)
            ->orderBy('Date', 'desc')
            ->get();

        return response()->json([
            'pendingMemos' => $pending,
        ]);
    }

}
