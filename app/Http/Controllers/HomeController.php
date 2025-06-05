<?php

namespace App\Http\Controllers;

use App\Exports\ExcelExport;
use App\Models\Machine;
use App\Models\Payment;
use App\Models\PaymentProducts;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
         $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if ($request->has('datefilter') && $request->datefilter != null) {
            $dateFilter = $request->datefilter;
            $dateFilter = explode(' to ', $dateFilter);

        } else {
            $dateFilter = [ now()->format('Y-m-d 00:00'), now()->format('Y-m-d H:i') ];
        }
        $payments = Payment::query()->with('products.product')->orderBy('created_at', 'desc');

        $payments = $payments->whereDate('created_at', '>=', $dateFilter[0])
            ->whereDate('created_at', '<=', $dateFilter[1]);

        if ($request->has('status') && $request->status != null) {
            $payments = $payments->where('status', $request->status);
        }

        if ($request->has('machine') && $request->machine != null) {
            $payments = $payments->where('machine_id', $request->machine);
        }

        $payments = $payments->get();

        $successfulPayments = $payments->where('status', 1);


        $data['sales_amount_sum'] = $successfulPayments->sum('amount');
        $data['sales_products_count'] = $successfulPayments->pluck('products')->flatten()->pluck('product')->flatten()->unique()->count();
        $data['sales_products_quantity'] = $successfulPayments->pluck('products')->flatten()->sum(function ($item) {
            return $item->quantity;
        });

        $data['orders_count'] = $successfulPayments->count();
        $data['customers'] = $successfulPayments->pluck('phone_number')->flatten()->unique()->count();

        $data['snacks_amount_sum'] = $successfulPayments->pluck('products')->flatten()->sum(function ($item) {
            if ($item->product != null && $item->product->category == 'SNACK') {
                return $item->unit_price * $item->quantity;
            }
            return 0;
        });

        $data['snacks_products_count'] = $successfulPayments
            ->pluck('products')
            ->flatten()
            ->filter(function ($item) {
                return $item->product != null && $item->product->category == 'SNACK';
            })
            ->unique('product.id') // Make the products unique based on their ID
            ->count(); // Count the unique items

        $data['snacks_products_quantity'] = $successfulPayments->pluck('products')->flatten()->sum(function ($item) {
            if ($item->product != null && $item->product->category == 'SNACK') {
                return $item->quantity;
            }

            return 0;
        });

        $data['drinks_amount_sum'] = $successfulPayments->pluck('products')->flatten()->sum(function ($item) {
            if ($item->product != null && $item->product->category == 'DRINK') {
                return $item->unit_price * $item->quantity;
            }
            return 0;
        });

        $data['drinks_products_count'] = $successfulPayments
            ->pluck('products')
            ->flatten()
            ->filter(function ($item) {
                return $item->product != null && $item->product->category == 'DRINK';
            })
            ->unique('product.id') // Ensure uniqueness based on the product's ID
            ->count(); // Count the unique items

        $data['drinks_products_quantity'] = $successfulPayments->pluck('products')->flatten()->sum(function ($item) {
            if ($item->product != null && $item->product->category == 'DRINK') {
                return $item->quantity;
            }
            return 0;
        });
        $data['payments'] = $payments->slice(0, 100);
        $data['machines'] = Machine::all();
        $data['dateFilter'] = $dateFilter;

        return view('home', compact('data'));
    }

    /**
     * Show transaction details for Super Admins.
     *
     * @param Request $request
     * @param int $paymentId
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function transactionDetails(Request $request, $paymentId)
    {
        // Only allow super admins to access this information
        if (auth()->user()->role !== 'SUPER_ADMIN') {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
        
        $payment = Payment::with('products.product', 'machine')
            ->findOrFail($paymentId);
        
        // Get the payment gateway type
        $paymentGateway = !empty($payment->invoice_number) ? 'IremboPay' : 'MoPay';
        
        // Format response body as JSON if it exists
        $responseBody = null;
        if (!empty($payment->response_body)) {
            try {
                // If it's already JSON, decode it for better display
                $decodedResponse = json_decode($payment->response_body, true);
                if ($decodedResponse) {
                    // Mask any sensitive data if present
                    if (isset($decodedResponse['secretKey'])) {
                        $decodedResponse['secretKey'] = '********';
                    }
                    
                    // Re-encode with pretty print for display
                    $responseBody = json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                } else {
                    // If it's not valid JSON, just use as is
                    $responseBody = $payment->response_body;
                }
            } catch (\Exception $e) {
                $responseBody = $payment->response_body;
            }
        }
        
        // Format invoice_response as JSON if it exists
        $invoiceResponse = null;
        if (!empty($payment->invoice_response)) {
            try {
                $decodedInvoice = json_decode($payment->invoice_response, true);
                if ($decodedInvoice) {
                    // Mask any sensitive data
                    if (isset($decodedInvoice['secretKey'])) {
                        $decodedInvoice['secretKey'] = '********';
                    }
                    
                    $invoiceResponse = json_encode($decodedInvoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                } else {
                    $invoiceResponse = $payment->invoice_response;
                }
            } catch (\Exception $e) {
                $invoiceResponse = $payment->invoice_response;
            }
        }
        
        return view('transaction-details', compact('payment', 'paymentGateway', 'responseBody', 'invoiceResponse'));
    }

    public function export(Request $request)
    {
        if ($request->has("dateFrom")) {
            $dateFrom = $request->dateFrom;
            $dateTo = $request->dateTo;
        } else {
            $dateFrom = now()->format('Y-m-d 00:00');
            $dateTo = now()->format('Y-m-d H:i');
        }

        $payments = Payment::query()->with('products.product')->orderBy('created_at', 'desc');

        $payments = $payments->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $prefix = "";
        if ($request->has('status') && $request->status != null) {
            $payments = $payments->where('status', $request->status);
            $prefix = getStatusWord($request->status) . ' ';
        }

        $payments = $payments->get();

        $paymentsInDetails = PaymentProducts::whereIn('payment_id', $payments->pluck('id'))
                                    ->with('product')
                                    ->with('payment');

        $paymentsInDetails = $paymentsInDetails->get()->map(function($paymentProduct) {
            $i = new \stdClass();
            $i->machine = $paymentProduct->payment->machine ? $paymentProduct->payment->machine->name . ' / ' . $paymentProduct->payment->machine->location : '-';
            $i->id = $paymentProduct->payment->id;
            $i->product = $paymentProduct->product ? $paymentProduct->product->name : '-';
            $i->quantity = $paymentProduct->quantity;
            $i->amount = $paymentProduct->quantity * $paymentProduct->unit_price;
            $i->phone = substr($paymentProduct->payment->phone_number, 2);
            $i->status = getStatusWord($paymentProduct->payment->status);
            $i->date = $paymentProduct->payment->created_at->format('Y-m-d H:i:s');
            return $i;
        });

        return (Excel::download(new ExcelExport(["Machine", "Transaction", "Product", "Quantity", "Amount", "Phone Number", "Status","Date"], $paymentsInDetails), $prefix . "Sales From " . $dateFrom . "  To " . $dateTo . ".xlsx"));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = auth()->user();

        if (Hash::check($request->old_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect()->back()->with('success', 'Password changed successfully');
        }

        return redirect()->back()->withErrors(['error', 'Old password is incorrect']);

    }

    public function users(Request $request)
    {
        $users = User::all();
        return view('users', compact('users'));
    }

    public function deleteUser(Request $request, $userId)
    {
        User::query()->where('id', $userId)->delete();
        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function changeUserRole(Request $request, $userId, $newRole)
    {
        User::query()->where('id', $userId)->update([
            'role' => $newRole
        ]);

        return redirect()->back()->with('success', 'Role assigned successfully');
    }
}
