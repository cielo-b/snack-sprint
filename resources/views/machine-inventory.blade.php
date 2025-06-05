@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-10">

                @include('notifications')

                <h3>Stock Levels : {{ $machine->location }}</h3>

                <div class="row">
{{--                    <div class="col-md-5 mt-3">--}}
{{--                        <h5>Per Product</h5>--}}
{{--                        <table class="table table-striped">--}}
{{--                            <thead>--}}
{{--                            <tr>--}}
{{--                                <th scope="col">Product</th>--}}
{{--                                <th scope="col">Quantity</th>--}}
{{--                            </tr>--}}
{{--                            </thead>--}}
{{--                            <tbody>--}}
{{--                            @foreach($machine->inventoryState->unique('product_id')->values()->all() as $productState)--}}
{{--                                <tr>--}}
{{--                                    <td>{{ $productState->product ? $productState->product->name : '-' }}</td>--}}
{{--                                    <td>{{ $machine->inventoryState->where('product_id', $productState->product_id)->sum('quantity') }}</td>--}}
{{--                                </tr>--}}
{{--                            @endforeach--}}
{{--                            </tbody>--}}
{{--                        </table>--}}
{{--                    </div>--}}
                    <div class="col-md-7 mt-3">
{{--                        <h5>Per Lane</h5>--}}
                        <table class="table">
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col">Lane</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $total = 0;
                                $totalMax = 0;
                            @endphp
                                @foreach($inventory as $state)
                                    @php

                                        $total += $state->quantity;
                                        $totalMax += $state->max_quantity;

                                        if ($state->product == null) {
                                            $color = 'table-secondary';
                                        } else {
                                            $perc = number_format((intval($state->quantity) * 100) / intval($state->max_quantity));

                                            if ($perc < 40) {
                                                $color = 'table-danger';
                                            } elseif ($perc < 60) {
                                                $color = 'table-warning';
                                            } else {
                                                $color = '';
                                            }
                                        }
                                    @endphp
                                    <tr class="{{ $color }}">
                                        <th scope="row">{{ str_replace('.', '-', $state->lane_id) }}</th>
                                        <td>{{ $state->product ? $state->product->name : '-' }}</td>
                                        <td>{{ $state->quantity . ' / ' . $state->max_quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr>
                                    <td></td>
                                    <td>Total</td>
                                    <td>{{ $total . ' / ' . $totalMax }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection
