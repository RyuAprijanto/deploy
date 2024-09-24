<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\History;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\TransactionType;
use function PHPSTORM_META\type;

class ItemController extends Controller


{

    public function createProduct()
    {
        // Fetch types from the 'types' table with id and name
        $types = DB::table('types')->pluck('name', 'id');
        return view('addItem', compact('types'));
    }

    protected function createNewType($typeName)
    {
        $type = DB::table('types')->where('name', $typeName)->first();
        if (!$type) {
            $typeId = DB::table('types')->insertGetId(['name' => $typeName]);
        } else {
            $typeId = $type->id;
        }

        return $typeId;
    }

    public function insertProduct(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'type_id' => 'nullable|exists:types,id',  // Validate type_id to exist in types table
        'new_type' => 'nullable|string|max:255',
        'stock' => 'required|integer|min:1',
        'description' => 'nullable|string|max:30',
        'buy_price' => 'required|numeric|min:0',
        'sell_price' => 'required|numeric|min:0',
        'expired_date' => 'nullable|date',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Determine type_id
    $type_id = $request->new_type ? $this->createNewType($request->new_type) : $request->type_id;

    if (is_null($type_id)) {
        return redirect()->back()->withErrors(['type_id' => 'Type cannot be null.']);
    }

    // Handle file upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imagePath = $image->hashName();
        $image->storeAs('public/images', $imagePath);
    }

    // If the expired date checkbox is not checked, set expired_date to null
    if (!$request->has('has_expiry_date')) {
        $request->merge(['expired_date' => null]);
    }

    // Insert product
    $productID = DB::table('products')->insertGetId([
        'user_id' => Auth::id(),
        'name' => $request->name,
        'type_id' => $type_id,  // Use type_id
        'stock' => $request->stock,
        'description' => $request->description,
        'buy_price' => $request->buy_price,
        'sell_price' => $request->sell_price,
        'expired_date' => $request->expired_date,
        'image' => $imagePath,
    ]);

    // Log action
    History::create([
        'user_id' => Auth::id(),
        'product_id' => $productID,
        'action' => 'Produk Baru',
        'quantity' => $request->stock,
        'details'=> 'Membuat Produk Baru Dengan Nama ' . $request->name,
        'action_time' => now(),
    ]);

    // Fetch all unique types after inserting the product
    $allTypes = DB::table('products')
        ->join('types', 'products.type_id', '=', 'types.id')
        ->where('products.user_id', '=', Auth::user()->id)
        ->select('types.name')
        ->distinct()
        ->pluck('name');

    return redirect()->route('index_home')->with(['success' => 'Item added successfully', 'allTypes' => $allTypes]);
}

 public function viewProductDetail($id)
 {
     $product = DB::table('products')
         ->join('types', 'products.type_id', '=', 'types.id')
         ->select('products.*', 'types.name as type_name')
         ->where('products.id', $id)
         ->first();

     if (!$product) {
         return redirect()->route('index_home')->with('error', 'Product not found.');
     }

     return view('showProductDetail', compact('product'));
 }


 public function editProduct($id)
 {
     $product = Product::findOrFail($id);
     $types = DB::table('types')->pluck('name', 'id');
     return view('editProduct', compact('product', 'types'));
 }
 public function updateProduct(Request $request, $id)
 {
    $request->validate([
        'name' => 'required|string|max:255',
        'type_id' => 'nullable|exists:types,id', // Validate type_id to exist in types table
        'new_type' => 'nullable|string|max:255',
        'stock' => 'required|integer|min:1',
        'description' => 'nullable|string|max:255',
        'buy_price' => 'required|numeric|min:0',
        'sell_price' => 'required|numeric|min:0',
        'expired_date' => 'nullable|date',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);


    $product = Product::findOrFail($id);

    // Determine the type ID
    $type_id = $request->new_type ? $this->createNewType($request->new_type) : $request->type_id;

    if (is_null($type_id)) {
        return redirect()->back()->withErrors(['type_id' => 'Type cannot be null.']);
    }

    $product->name = $request->input('name');
    $product->type_id = $type_id; // Correctly assign the type ID
    $product->description = $request->input('description');
    $product->stock = $request->input('stock');
    $product->buy_price = $request->input('buy_price');
    $product->sell_price = $request->input('sell_price');
    $product->expired_date = $request->input('expired_date');

    if ($request->hasFile('image')) {
        // Delete old image
        if ($product->image) {
            Storage::delete('public/images/' . $product->image);
        }

        // Store new image
        $image = $request->file('image');
        $imageName = $image->hashName();
        $image->storeAs('public/images', $imageName);
        $product->image = $imageName;
    }

    if ($product->isDirty()) {
        History::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'action' => 'Ubah Produk',
            'quantity' => $product->stock,
            'details' => "Berhasil melakukan perubahan pada produk ".$product->name,
            'action_time' => now(),
        ]);
    }
    $product->save();
     return redirect()->route('productDetail', ['id' => $product->id])->with('success', 'Product updated successfully');
 }


    public function deleteProduct($id)
    {
    $product = Product::findOrFail($id);
    $typeId = $product->type_id;
    History::create([
        'user_id' => Auth::id(),
        'product_id' => $product->id,
        'action' => 'Hapus Produk',
        'quantity' => 0,
        'details' => "Berhasil menghapus produk ".$product->name,
        'action_time' => now(),
    ]);
    $product->delete();

    //cek produk lain dengan tipe sama, klo gaada delete tipenya
    $otherProductsWithType = Product::where('type_id', $typeId)->exists();
    if (!$otherProductsWithType) {
        Type::find($typeId)->delete();
    }

    $allTypes = DB::table('products')
        ->join('types', 'products.type_id', '=', 'types.id')
        ->where('products.user_id', '=', Auth::user()->id)
        ->select('types.name')
        ->distinct()
        ->pluck('name');

    return redirect()->route('index_home')->with(['success' => 'Product deleted successfully', 'allTypes' => $allTypes]);
}



    public function showCheckoutPage()
    {
        $products = DB::table('products')
        ->join('users', 'products.user_id', '=', 'users.id')
        ->where('user_id','=',Auth::user()->id)
        ->select('products.*')
        ->paginate(5);
        return view('checkout', compact('products'));
    }



    public function addStockPage()
    {
        $products = DB::table('products')
        ->join('users', 'products.user_id', '=', 'users.id')
        ->where('user_id','=',Auth::user()->id)
        ->select('products.*')
        ->paginate(5);
        return view('addStock', compact('products'));
    }

    public function processAddStock(Request $request)
    {

        $changes = [];
        foreach ($request->products as $productData) {
            $product = Product::findOrFail($productData['id']);
            $product->stock += $productData['quantity'];
            $product->save();
            if($productData['quantity'] > 0){
                $changes[] = 'Stok ' . $product->name . ' ditambahkan ' . $productData['quantity'];
            }
        }
        $details = implode('; ', $changes);
        if($details){
            History::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'action' => 'Penambahan Stok',
            'quantity' => 0,
            'details' => $details,
            'action_time' => now(),
        ]);
        }
        return redirect()->route('index_home')->with('success', 'Checkout completed successfully!');
    }
    public function updateStock(Request $request, $id)
{
    $product = Product::findOrFail($id);
    $action = $request->input('action');

    if ($action === 'increase') {
        $product->stock += 1;
        History::create([
            'user_id' => Auth::id(),
            'action' => 'Tambah Stok',
            'product_id' => $product->id,
            'details' => 'Menambahkan 1 Stok untuk '. $product->name,
            'quantity' => 1,
            'action_time' => now(),
        ]);
    } elseif ($action === 'decrease') {
        $product->stock = max(0, $product->stock - 1); // Prevent stock from going below 0
        History::create([
            'user_id' => Auth::id(),
            'action' => 'Kurang Stok',
            'product_id' => $product->id,
            'details' => 'Mengurangkan 1 Stok untuk '. $product->name,
            'quantity' => 1,
            'action_time' => now(),
        ]);
    }
    $product->save();
    return redirect()->route('index_home')->with('success', 'Stock updated successfully!');
}

public function showRestockPage(Request $request)
{
    // Get search term and filter type from the request
    $searchTerm = $request->input('search', '');
    $selectedType = $request->input('type', '');

    // Build the query for products
    $query = Product::where('user_id', Auth::id());

    // Apply the search filter
    if ($searchTerm) {
        $query->where('name', 'like', "%{$searchTerm}%");
    }

    // Apply the type filter
    if ($selectedType) {
        $query->whereHas('type', function($q) use ($selectedType) {
            $q->where('name', $selectedType);
        });
    }

    // Get the list of product types for the filter options
    $allTypes = DB::table('products')
        ->join('types', 'products.type_id', '=', 'types.id')
        ->where('products.user_id', '=', Auth::user()->id)
        ->select('types.name')
        ->distinct()
        ->pluck('name');

    // Paginate the products
    $products = $query->orderBy('stock', 'asc')->paginate(8);

    return view('restockPage', compact('products', 'allTypes', 'searchTerm', 'selectedType'));
}

public function processRestock(Request $request)
{
    $selectedProducts = $request->input('products', []);
    $restockQuantities = $request->input('restock_qty', []);
    $restockExpiryDates = $request->input('restock_expiry', []);
    $temp = 0;
    $flag = 0;
    foreach ($selectedProducts as $productId) {
        $product = Product::find($productId);

        if ($product) {
            $newProduct = $product->replicate();
            $newProduct->stock = $restockQuantities[$productId] ?? 0;
            $newProduct->expired_date = $restockExpiryDates[$productId] ?? null;
            $temp+=$temp+$newProduct->stock*$newProduct->buy_price;
            $newProduct->save();
            $flag = 1;
        }
    }
    $transactionTypeId = DB::table('transaction_types')
            ->where('name', 'Pembelian')
            ->value('id');
    if($flag !=0){
        Transaction::create([
            'user_id' => Auth::id(),
            'date' => now(),
            'transaction_type_id' => $transactionTypeId,
            'price' => $temp,
            'description' => 'Restock Produk',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    return redirect()->route('index_home')->with('success', 'Products restocked successfully!');
}


}
