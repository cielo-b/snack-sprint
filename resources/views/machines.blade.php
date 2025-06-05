@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-8 ">

                @include('notifications')

                <h3>Vending Machines</h3>

                    <table class="table table-striped mt-3">
                        <thead>
                        <tr>
                            <th scope="col">Serial No</th>
                            <th scope="col">Name</th>
                            <th scope="col">Location</th>
                            <th scope="col">Support Contact</th>
                            <th scope="col">Last Checked-In</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($machines as $machine)
                            <tr>
                                <th scope="row">{{ $machine->id }}</th>
                                <td>{{ $machine->name }}</td>
                                <td>{{ $machine->location }}</td>
                                <td>{{ $machine->support_contact }}</td>
                                <td>{{ $machine->last_checked_in_at ? $machine->last_checked_in_at->diffForHumans() : '-' }}</td>
                                <td>
                                    <a href="/machine/{{ $machine->id }}" style="text-decoration: none">
{{--                                            <i class="fas fa-hamburger" data-bs-toggle="modal" data-bs-target="#editMachine{{ $machine->id }}"></i>--}}
                                        Stock Levels
                                    </a>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <i class="fas fa-edit" data-bs-toggle="modal" data-bs-target="#editMachine{{ $machine->id }}"></i>


                                    <!-- Modal -->
                                    <div class="modal fade" id="editMachine{{ $machine->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="exampleModalLabel"> <i class="fa-solid fa-edit"></i> Edit Vending Machine</h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('machines.update', $machine->id) }}" method="POST">
                                                    @csrf
                                                <div class="modal-body">

                                                        <div class="row mb-3">
                                                            <label for="inputEmail3" class="col-sm-4 col-form-label">Serial No</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" value="{{ $machine->id }}" class="form-control" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label for="inputEmail3" class="col-sm-4 col-form-label">Name</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" name="name" class="form-control" value="{{ $machine->name }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label for="inputEmail3" class="col-sm-4 col-form-label">Location</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" name="location" class="form-control" value="{{ $machine->location }}">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label for="inputEmail3" class="col-sm-4 col-form-label">Support Contact</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" name="support_contact" class="form-control" value="{{ $machine->support_contact }}">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label for="inputEmail3" class="col-sm-4 col-form-label">Inventory PIN</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" name="inventory_pin" class="form-control" value="{{ $machine->inventory_pin }}">
                                                            </div>
                                                        </div>

                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>

                        @endforeach

                        </tbody>
                    </table>

            </div>
        </div>
    </div>
@endsection
