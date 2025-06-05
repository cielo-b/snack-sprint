@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-8 mb-2">

                @include('notifications')


                <div class="row">
                    <div class="col-md-3">
                        <h3>Products</h3>
                    </div>
                    <div class="col-md-9">
                        <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa-solid fa-circle-plus"></i> Add Product
                        </button>


                        <!-- Modal -->
                        <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5"><i class="fa-solid fa-plus"></i> Add New Product</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="{{ route('product.create') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <label for="inputEmail3" class="col-sm-3 col-form-label">Name</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" maxlength="20" name="name" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label for="inputProductCode" class="col-sm-3 col-form-label">Product Code</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" maxlength="20" name="product_code" required>
                                                    <div class="form-text">Unique identifier for the product</div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label for="inputEmail3" class="col-sm-3 col-form-label">Category</label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="category" required>
                                                        <option>Choose Category</option>
                                                        <option value="SNACK">Snack</option>
                                                        <option value="DRINK">Drink</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-3 col-form-label">Price</label>
                                                <div class="col-sm-8">
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <input type="number" class="form-control" name="price" required>
                                                        </div>
                                                        <div class="col-md-7  d-flex align-items-center">
                                                            Rwf
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-3 col-form-label">Image</label>
                                                <div class="col-sm-9">
                                                    <input class="form-control" type="file" accept="image/*" name="image" required>
                                                    <div class="form-text">Dimensions 180x154</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save Product</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-8 mt-3">
                        <table class="table table-striped ">
                            <thead class="table-success">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Image</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Code</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Price <small>- Rwf</small></th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <th class="align-middle" scope="row">{{ $loop->iteration }}</th>
                                        <td class="align-middle">
                                            <img src="assets/products/{{ $product->image_path }}" class="img-thumbnail" style="width: 50px; height: 50px;">
                                        </td>
                                        <td class="align-middle">{{ $product->name }}</td>
                                        <td class="align-middle">{{ $product->product_code ?: 'N/A' }}</td>
                                        <td class="align-middle">{{ ucwords(strtolower($product->category)) }}</td>
                                        <td class="align-middle">{{ number_format($product->price) }}</td>
                                        <td class="align-middle">
                                            <i class="fas fa-edit" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}"></i>&nbsp;&nbsp;&nbsp;
                                            <i class="fas fa-trash" data-bs-toggle="modal" data-bs-target="#deleteProductModal{{ $product->id }}"></i>



                                            <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h1 class="modal-title fs-5"><i class="fa-solid fa-edit"></i> Edit Product</h1>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('product.update', $product->id) }}" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <label for="inputEmail3" class="col-sm-3 col-form-label">Name</label>
                                                                    <div class="col-sm-9">
                                                                        <input type="text" class="form-control" maxlength="20" name="name" value="{{ $product->name }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <label for="inputProductCode" class="col-sm-3 col-form-label">Product Code</label>
                                                                    <div class="col-sm-9">
                                                                        <input type="text" class="form-control" maxlength="20" name="product_code" value="{{ $product->product_code }}" required>
                                                                        <div class="form-text">Unique identifier for the product</div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <label for="inputEmail3" class="col-sm-3 col-form-label">Category</label>
                                                                    <div class="col-sm-9">
                                                                        <select class="form-select" name="category" required>
                                                                            <option>Choose Category</option>
                                                                            <option value="SNACK" {{ $product->category == "SNACK" ? "selected" : "" }}>Snack</option>
                                                                            <option value="DRINK" {{ $product->category == "DRINK" ? "selected" : "" }}>Drink</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <label class="col-sm-3 col-form-label">Price</label>
                                                                    <div class="col-sm-8">
                                                                        <div class="row">
                                                                            <div class="col-md-5">
                                                                                <input type="number" class="form-control" name="price" value="{{ $product->price }}" required>
                                                                            </div>
                                                                            <div class="col-md-7  d-flex align-items-center">
                                                                                Rwf
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <label class="col-sm-3 col-form-label">Image</label>
                                                                    <div class="col-sm-9">
                                                                        <input class="form-control" type="file" accept="image/*" name="image">
                                                                        <div class="form-text">Dimensions 180x154</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Product</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal -->
                                            <div class="modal fade" id="deleteProductModal{{ $product->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Are you sure?</h1>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('product.delete', $product->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                        <div class="modal-body text-center">
                                                            Are you sure you want to delete this product? <br><br>
                                                            <img src="assets/products/{{ $product->image_path }}" class="img-thumbnail" style="width: 50px; height: 50px;">
                                                            <br><br>
                                                            {{ $product->name }}
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Close</button>
                                                            <button type="submit" class="btn btn-danger">Delete Product</button>
                                                        </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>

                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center">Start adding products </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>

            </div>
        </div>
    </div>
@endsection
