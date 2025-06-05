@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 mb-2">

                @include('notifications')

                <div class="row">
                    <div class="col-md-3">
                        <h3>Adverts</h3>
                    </div>
                    <div class="col-md-9">
                        <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa-solid fa-circle-plus"></i> Add Advert
                        </button>


                        <!-- Modal -->
                        <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5"><i class="fa-solid fa-plus"></i> Add New Advert</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form enctype="multipart/form-data" action="{{ route('advert.create') }}" method="POST">
                                        @csrf
                                        <div class="modal-body">

                                            <div class="row mb-3">
                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Media Name</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="name" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-4 col-form-label">Media Content</label>
                                                <div class="col-sm-8">
                                                    <input class="form-control" type="file" accept="video/*,image/*" name="media" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-4 col-form-label">Duration (Seconds)</label>
                                                <div class="col-sm-8">
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <input type="number" name="duration" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-7  d-flex align-items-center">
                                                            Seconds
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Broadcasting Date</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="daterange" value="{{ date('Y-m-d') . ' - ' . date('Y-m-d') }}" required/>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Time Slot</label>
                                                <div class="col-sm-8">
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <input type="time" name="time_from" value="00:00" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="time" name="time_to" value="23:59" class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-4 col-form-label">Vending Machine</label>
                                                <div class="col-sm-8 mt-2">
                                                    @foreach($machines as $machine)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="{{ $machine->id }}" name="machines[]" id="vm{{ $machine->id }}">
                                                            <label class="form-check-label" for="vm{{ $machine->id }}">
                                                                {{ $machine->location }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>


                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save Advert</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="col-md-10 mt-3">

                        <table class="table table-striped" id="table-1">
                            <caption>Drag & drop rows to set order & priority</caption>
                            <thead class="table-success">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Type</th>
                                <th scope="col">Machine</th>
                                <th scope="col">Duration</th>
                                <th scope="col">Date Range</th>
                                <th scope="col">Time Slot</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($adverts as $ad)
                                <tr id="{{ $ad->id }}">
                                    <th class="align-middle" scope="row">{{ $loop->iteration }}</th>
                                    <td class="align-middle">{{ $ad->name }}</td>
                                    <td class="align-middle">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#adView{{ $ad->id }}">{{ ucwords(strtolower($ad->type)) }}</a>


                                        <!-- Modal -->
                                        <div class="modal fade" id="adView{{ $ad->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">View Advert</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @if($ad->type == "IMAGE")
                                                            <img src="{{ asset("assets/adverts/" . $ad->media_path) }}" class="img-fluid">
                                                        @else

                                                            <video width="100%" controls>
                                                                <source src="{{ asset('assets/adverts/' . $ad->media_path) }}" type="video/mp4">
                                                                Your browser does not support the video tag.
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>



                                    </td>

                                    <td class="align-middle">
                                        <ul class="pt-3">
                                            @foreach($ad->machines as $machine)
                                                <li>{{ $machine->location }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="align-middle">{{ $ad->duration }} Secs</td>
                                    <td class="align-middle"> {{ $ad->date_from->format('Y-m-d') }} to {{ $ad->date_to->format('Y-m-d') }}</td>
                                    <td class="align-middle"> {{ $ad->time_from }} - {{ $ad->time_to }}</td>
                                    <td class="align-middle">

                                        <i class="fas fa-edit" data-bs-toggle="modal" data-bs-target="#editAd{{ $ad->id }}"></i>&nbsp;&nbsp;&nbsp;
                                        <i class="fas fa-trash" data-bs-toggle="modal" data-bs-target="#deleteAd{{ $ad->id }}"></i>


                                        <!-- Modal -->
                                        <div class="modal fade" id="editAd{{ $ad->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5"><i class="fa-solid fa-edit"></i> Edit Advert</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form enctype="multipart/form-data" action="{{ route('advert.update', $ad->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">

                                                            <div class="row mb-3">
                                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Media Name</label>
                                                                <div class="col-sm-8">
                                                                    <input type="text" name="name" class="form-control" value="{{ $ad->name }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Media Content</label>
                                                                <div class="col-sm-8">
                                                                    <input class="form-control" type="file" accept="video/*,image/*" name="media">
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Duration (Seconds)</label>
                                                                <div class="col-sm-8">
                                                                    <div class="row">
                                                                        <div class="col-md-5">
                                                                            <input type="number" name="duration" class="form-control" value="{{ $ad->duration }}" required>
                                                                        </div>
                                                                        <div class="col-md-7  d-flex align-items-center">
                                                                            Seconds
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Broadcasting Date</label>
                                                                <div class="col-sm-8">
                                                                    <input type="text" class="form-control" name="daterange" value="{{ $ad->date_from . ' - ' . $ad->date_to }}" required/>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="inputEmail3" class="col-sm-4 col-form-label">Time Slot</label>
                                                                <div class="col-sm-8">
                                                                    <div class="row">
                                                                        <div class="col-md-5">
                                                                            <input type="time" name="time_from" value="{{ $ad->time_from }}" class="form-control" required>
                                                                        </div>
                                                                        <div class="col-md-5">
                                                                            <input type="time" name="time_to"  value="{{ $ad->time_to }}" class="form-control" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Vending Machine</label>
                                                                <div class="col-sm-8 mt-2">
                                                                    @foreach($machines as $machine)
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox" value="{{ $machine->id }}" name="machines[]" {{ $ad->machines->contains($machine->id) ? "checked" : null }} id="vme{{ $machine->id }}">
                                                                            <label class="form-check-label" for="vme{{ $machine->id }}">
                                                                                {{ $machine->location }}
                                                                            </label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>


                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Advert</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal -->
                                        <div class="modal fade" id="deleteAd{{ $ad->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Are you sure?</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('advert.delete', $ad->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <div class="modal-body text-center">
                                                            Are you sure you want to delete this advert? <br><br>
                                                            <b>{{ $ad->name }}</b>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Close</button>
                                                            <button type="submit" class="btn btn-danger">Delete Advert</button>
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

                <form action="{{ route('advert.order') }}" method="POST" style="display: none" id="orderForm">
                    @csrf
                    <input type="hidden" name="order" id="adOrder">
                    <button class="btn btn-secondary"><i class="fa-solid fa-arrow-up-wide-short"></i> Save new order</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')

    <script type="text/javascript">
        $(function() {
            $('input[name="daterange"]').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
            });
        });

        $(document).ready(function() {
            // Initialise the table
            $("#table-1").tableDnD({
                onDragClass: "table-secondary",
                onDrop: function(table, row) {
                    var rows = table.tBodies[0].rows;
                    var newOrder = "";
                    var debugStr = "Row dropped was "+row.id+". New order: ";
                    for (var i=0; i<rows.length; i++) {
                        debugStr += rows[i].id+" ";
                        newOrder += rows[i].id+",";
                    }
                    console.log(debugStr);
                    $('#orderForm').show();
                    $('#adOrder').val(newOrder);
                    // $('#debugArea').html(debugStr);
                },
            });
        });
    </script>
@endpush
