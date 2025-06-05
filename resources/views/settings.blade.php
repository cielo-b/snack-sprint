@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('System Settings') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf

                        <div class="row mb-4">
                            <label for="payment_gateway" class="col-md-4 col-form-label text-md-end">{{ __('Payment Gateway') }}</label>

                            <div class="col-md-6">
                                <select id="payment_gateway" class="form-select @error('payment_gateway') is-invalid @enderror" name="payment_gateway" required>
                                    <option value="mopay" {{ $settings['payment_gateway'] == 'mopay' ? 'selected' : '' }}>MoPay</option>
                                    <option value="irembopay" {{ $settings['payment_gateway'] == 'irembopay' ? 'selected' : '' }}>IremboPay</option>
                                </select>

                                @error('payment_gateway')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="payment_expiry" class="col-md-4 col-form-label text-md-end">{{ __('Payment Expiry (minutes)') }}</label>

                            <div class="col-md-6">
                                <input id="payment_expiry" type="number" class="form-control @error('payment_expiry') is-invalid @enderror" name="payment_expiry" value="{{ $settings['payment_expiry'] }}" required min="1" max="60">

                                @error('payment_expiry')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Settings') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
