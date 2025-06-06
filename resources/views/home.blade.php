@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 mb-3">

            @include('notifications')

            <form method="get">
            <div class="row">
                <div class="col-md-2">
                    <h5 class="mt-2">Dashboard</h5>
                </div>
                <div class="col-md-2">
                    <select class="form-select bg-white" aria-label="Default select example" name="machine">
                        <option value="">All Machines</option>
                        @foreach($data['machines'] as $machine)
                            <option value="{{ $machine->id }}" @selected(request()->get('machine') == $machine->id)>{{ $machine->location }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select bg-white" name="status" aria-label="Default select example">
                        <option value="">Status</option>
                        <option value="1" {{ request()->get('status') == 1 ? "selected" : "" }}>Successful</option>
                        <option value="2" {{ request()->get('status') == 2 ? "selected" : "" }}>Failed</option>
                        <option value="3" {{ request()->get('status') == 3 ? "selected" : "" }}>Insufficient Balance</option>
                        <option value="4" {{ request()->get('status') == 4 ? "selected" : "" }}>Payment Error</option>
                        <option value="5" {{ request()->get('status') == 5 ? "selected" : "" }}>Dormant/Blocked</option>
                        <option value="6" {{ request()->get('status') == 6 ? "selected" : "" }}>Unregistered</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 6px 10px; border: 1px solid #dee2e6; width: 100%;border-radius: 0.375rem">
                        <i class="fa-solid fa-calendar-days"></i>&nbsp;
                        <span></span> <b class="caret"></b>
                    </div>
                    <input type="hidden" name="datefilter" value="{{ $data['dateFilter'][0] . ' to ' . $data['dateFilter'][1] }}" />
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>
            </div>
            </form>
        </div>
        <div class="col-md-12">
            <div class="row">
                <div class="col-sm-3 mb-4">
                    <div class="card bg-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text">
                                {{ number_format($data['sales_amount_sum']) }} Rwf <br>
                                Products: {{ number_format($data['sales_products_count']) }}&nbsp;&nbsp;
                                Qty : {{ number_format($data['sales_products_quantity']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 mb-4">
                    <div class="card bg-white">
                        <div class="card-body">
                            <h5 class="card-title">Orders</h5>
                            <p class="card-text">
                                Total: {{ number_format($data['orders_count']) }} <br>
                                Customers: {{ number_format($data['customers']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 mb-4">
                    <div class="card bg-white">
                        <div class="card-body">
                            <h5 class="card-title">Snacks</h5>
                            <p class="card-text">
                                {{ number_format($data['snacks_amount_sum']) }} Rwf <br>
                                Products: {{ number_format($data['snacks_products_count']) }}&nbsp;&nbsp;
                                Qty : {{ number_format($data['snacks_products_quantity']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 mb-4">
                    <div class="card bg-white">
                        <div class="card-body">
                            <h5 class="card-title">Drinks</h5>
                            <p class="card-text">
                                {{ number_format($data['drinks_amount_sum']) }} Rwf <br>
                                Products: {{ number_format($data['drinks_products_count']) }}&nbsp;&nbsp;
                                Qty : {{ number_format($data['drinks_products_quantity']) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h5 class="align-middle">{{ __('Recent Orders') }}</h5>
                </div>
                <div class="col-md-6">
                    <form action="/export" method="post">
                        @csrf
                        <input type="hidden" name="dateFrom" value="{{ $data['dateFilter'][0] }}">
                        <input type="hidden" name="dateTo" value="{{ $data['dateFilter'][1] }}">
                        <input type="hidden" name="status" value="{{ request()->get('status') }}">
                        <button type="submit" class="btn btn-success btn-sm float-end">Export Full Report</button>
                    </form>
                </div>
            </div>

            <table class="table table-striped mt-3">
                <thead>
                <tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Machine</th>
                    <th scope="col">Products</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Phone Number</th>
                    <th scope="col">Status</th>
                    <th scope="col">Time</th>
                    @if(auth()->user()->role == 'SUPER_ADMIN')
                    <th scope="col">Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($data['payments'] as $payment)
                    <tr>
                        <td class="align-middle">
                            <span data-bs-toggle="tooltip" title="{{ $payment->reference_number }}">
                                {{ $payment->id }}
                            </span>
                        </td>
                        <td class="align-middle">
                            {{ $payment->machine ? $payment->machine->location : '-' }}
                        </td>
                        <td class="align-middle">
                            <ul style="margin-bottom: 0">
                                @foreach($payment->products as $product)
                                    <li>
                                        {{ $product->product ? $product->product->name : '-' }} : {{ $product->quantity }}
                                        {!! getProductDeliveryStatusBadges($product->deliveryStatus, $payment->status == 1, $payment->created_at) !!}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="align-middle">{{ number_format($payment->amount) }}</td>
                        <td class="align-middle">{{ substr($payment->phone_number, 2) }}</td>
                        <td class="align-middle">
                            @if($payment->status == 0)
                                <span class="badge text-bg-info">Pending</span>
                            @elseif($payment->status == 1)
                                <span class="badge text-bg-success">Successful</span>
                            @elseif($payment->status == 3)
                                <span class="badge text-bg-warning">Insufficient Balance</span>
                            @elseif($payment->status == 4)
                                <span class="badge text-bg-dark">Payment Error</span>
                            @elseif($payment->status == 5)
                                <span class="badge text-bg-secondary">Dormant/Blocked</span>
                            @elseif($payment->status == 6)
                                <span class="badge text-bg-secondary">Unregistered</span>
                            @else
                                <span class="badge text-bg-danger">Failed</span>
                            @endif

                            @if($payment->callback_at != null && $payment->status != 2)
                                @php
                                    $totalDelayDurationInMinutes = $payment->created_at->diffInMinutes($payment->callback_at);
                                @endphp
                                @if($totalDelayDurationInMinutes >= 1)
                                    <span class="badge text-bg-dark">{{ number_format($totalDelayDurationInMinutes, 1) }} min</span>
                                @endif
                            @endif
                        </td>
                        <td class="align-middle">
                            <span data-bs-toggle="tooltip" title="{{ $payment->created_at->format('Y-m-d H:i:s') }}">
                                {{ $payment->created_at->diffForHumans() }}
                            </span>
                        </td>
                        @if(auth()->user()->role == 'SUPER_ADMIN')
                        <td class="align-middle">
                            <a href="{{ route('transaction.details', $payment->id) }}" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye"></i> Details
                            </a>
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                @if($data['payments']->hasPages())
                    <nav>
                        <ul class="pagination">
                            {{-- Previous Button --}}
                            @if($data['payments']->currentPage() > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $data['payments']->appends(request()->query())->url($data['payments']->currentPage() - 1) }}">Previous</a>
                                </li>
                            @endif

                            @php
                                $currentPage = $data['payments']->currentPage();
                                $lastPage = $data['payments']->lastPage();
                                $start = max(4, $currentPage - 1);
                                $end = min($lastPage - 3, $currentPage + 1);
                            @endphp

                            {{-- First three pages --}}
                            @for($i = 1; $i <= 3; $i++)
                                <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $data['payments']->appends(request()->query())->url($i) }}">{{ $i }}</a>
                                </li>
                            @endfor

                            {{-- Ellipsis if needed --}}
                            @if($start > 4)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif

                            {{-- Current page range --}}
                            @for($i = $start; $i <= $end; $i++)
                                @if($i > 3 && $i < $lastPage - 2)
                                    <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $data['payments']->appends(request()->query())->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Ellipsis if needed --}}
                            @if($end < $lastPage - 3)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif

                            {{-- Last three pages --}}
                            @for($i = $lastPage - 2; $i <= $lastPage; $i++)
                                @if($i > 3)
                                    <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $data['payments']->appends(request()->query())->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Next Button --}}
                            @if($data['payments']->currentPage() < $data['payments']->lastPage())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $data['payments']->appends(request()->query())->url($data['payments']->currentPage() + 1) }}">Next</a>
                                </li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script>
        $(function() {
            // var start = moment().subtract(7, 'days');
            var start = moment(new Date('{{ $data['dateFilter'][0] }}'));
            // var end = moment();
            var end = moment(new Date('{{ $data['dateFilter'][1] }}'));

            function cb(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY HH:mm') + ' - ' + end.format('MMMM D, YYYY HH:mm'));
            }

            $('#reportrange').daterangepicker({
                "timePicker": true,
                "timePicker24Hour": true,
                startDate: start,
                endDate: end,
                "locale": {
                    "firstDay": 1
                },
                ranges: {
                    'Today': [moment().set({hour:0,minute:0,second:0}), moment()],
                    'Yesterday': [moment().subtract(1, 'days').set({hour:0,minute:0,second:0}), moment().subtract(1, 'days').set({hour:23,minute:59,second:59})],
                    'Last 7 Days': [moment().subtract(6, 'days').set({hour:0,minute:0,second:0}), moment().set({hour:23,minute:59,second:59})],
                    'Last 30 Days': [moment().subtract(1, 'months').set({hour:0,minute:0,second:0}), moment().set({hour:23,minute:59,second:59})],
                    'This Month': [moment().startOf('month').set({hour:0,minute:0,second:0}), moment().endOf('month').set({hour:23,minute:59,second:59})],
                    'Last Month': [moment().subtract(1, 'month').startOf('month').set({hour:0,minute:0,second:0}), moment().subtract(1, 'month').endOf('month').set({hour:23,minute:59,second:59})]
                }
            }, cb);

            cb(start, end);

            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
                $('input[name="datefilter"]').val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' to ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
            });

            $('#reportrange').on('cancel.daterangepicker', function(ev, picker) {
                $('input[name="datefilter"]').val('');
            });
        });
    </script>
@endpush
