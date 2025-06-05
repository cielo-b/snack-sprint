<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $products = Product::all();
        return view('products', compact('products'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imageName = "product_" . time() . '.' . $request->image->extension();
        $request->image->move(public_path('assets/products'), $imageName);

        $product = new Product();
        $product->name = $request->name;
        $product->product_code = $request->product_code;
        $product->category = $request->category;
        $product->image_path = $imageName;
        $product->price = $request->price;
        $product->save();

        return redirect()->route('product.index')->with('success', 'Product created successfully!');
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        $product->name = $request->name;
        $product->product_code = $request->product_code;
        $product->category = $request->category;
        $product->price = $request->price;

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $imageName = "product_" . time() . '.' . $request->image->extension();
            $request->image->move(public_path('assets/products'), $imageName);
            $product->image_path = $imageName;
        }

        $product->touch();
        $product->save();

        return redirect()->route('product.index')->with('success', 'Product updated successfully!');
    }

    public function delete(Request $request, $id)
    {
        $product = Product::find($id);
        $product->delete();

        return redirect()->route('product.index')->with('success', 'Product deleted successfully!');
    }
}
