@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Transaction Details</h3>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
            </div>

            @include('notifications')

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Transaction Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>ID:</th>
                                    <td>{{ $payment->id }}</td>
                                </tr>
                                <tr>
                                    <th>Reference Number:</th>
                                    <td>{{ $payment->reference_number }}</td>
                                </tr>
                                <tr>
                                    <th>Amount:</th>
                                    <td>{{ number_format($payment->amount) }} RWF</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($payment->status == 0)
                                            <span class="badge bg-info">Pending</span>
                                        @elseif($payment->status == 1)
                                            <span class="badge bg-success">Successful</span>
                                        @elseif($payment->status == 3)
                                            <span class="badge bg-warning text-dark">Insufficient Balance</span>
                                        @elseif($payment->status == 4)
                                            <span class="badge bg-dark">Payment Error</span>
                                        @elseif($payment->status == 5)
                                            <span class="badge bg-secondary">Dormant/Blocked</span>
                                        @elseif($payment->status == 6)
                                            <span class="badge bg-secondary">Unregistered</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone Number:</th>
                                    <td>{{ $payment->phone_number }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Callback At:</th>
                                    <td>{{ $payment->callback_at ? $payment->callback_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Gateway:</th>
                                    <td>{{ $paymentGateway }}</td>
                                </tr>
                                @if($payment->invoice_number)
                                <tr>
                                    <th>Invoice Number:</th>
                                    <td>{{ $payment->invoice_number }}</td>
                                </tr>
                                @endif
                                @if($payment->transaction_reference)
                                <tr>
                                    <th>Transaction Reference:</th>
                                    <td>{{ $payment->transaction_reference }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->products as $product)
                            <tr>
                                <td>{{ $product->product ? $product->product->name : 'Unknown Product' }}</td>
                                <td>{{ $product->product ? $product->product->category : 'N/A' }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ number_format($product->unit_price) }} RWF</td>
                                <td>{{ number_format($product->quantity * $product->unit_price) }} RWF</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th>{{ number_format($payment->amount) }} RWF</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Machine Information -->
            @if($payment->machine)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Machine Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 150px;">Machine ID:</th>
                            <td>{{ $payment->machine->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $payment->machine->name }}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>{{ $payment->machine->location }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            <!-- Response Body (if available) -->
            @if($responseBody)
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Response Information</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 mb-0" style="max-height: 300px; overflow-y: auto;">{{ $responseBody }}</pre>
                </div>
            </div>
            @endif

            <!-- Invoice Response (if available) -->
            @if($invoiceResponse)
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Invoice Information</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 mb-0" style="max-height: 300px; overflow-y: auto;">{{ $invoiceResponse }}</pre>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection