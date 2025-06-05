@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-8 ">

                @include('notifications')

                <h3>Users</h3>

                <table class="table table-striped mt-3">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Role</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <th scope="row">{{ $user->id }}</th>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucwords(strtolower($user->role)) }}</td>
                            <td>

                                @if($user->role == "SUPER_ADMIN")
                                <form action="{{ route('users.change.role', ['id' => $user->id, 'newRole' => 'ADMIN']) }}" method="POST" style="display: inline-block">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Make Admin</button>
                                </form>
                                @endif
                                
                                @if($user->role == "ADMIN")
                                <form action="{{ route('users.change.role', ['id' => $user->id, 'newRole' => 'OPERATOR']) }}" method="POST" style="display: inline-block">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Make Operator</button>
                                </form>
                                
                                @if(auth()->user()->role == "SUPER_ADMIN")
                                <form action="{{ route('users.change.role', ['id' => $user->id, 'newRole' => 'SUPER_ADMIN']) }}" method="POST" style="display: inline-block">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Make Super Admin</button>
                                </form>
                                @endif
                                @endif
                                
                                @if($user->role == "OPERATOR")
                                <form action="{{ route('users.change.role', ['id' => $user->id, 'newRole' => 'ADMIN']) }}" method="POST" style="display: inline-block">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Make Admin</button>
                                </form>
                                @endif
                                <form action="{{ route('users.delete', $user->id) }}" method="POST"  style="display: inline-block" onsubmit="return confirmUserDelete('{{ $user->email  }}');" >
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                    @endforeach

                    </tbody>
                </table>

                <p>New User Registration Link : <strong> {{ route('users.register', '9ef37638') }}</strong></p>

            </div>
        </div>
    </div>
    <script>
        function confirmUserDelete(email) {
            return confirm("Are you sure you want to delete this user : ?" + email);
        }
    </script>
@endsection
