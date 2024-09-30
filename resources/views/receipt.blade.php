@extends('layouts.master')

@section('title', 'Receipt')

@section('content')
<div class="container mx-auto mt-8">
    <div class="w-full p-8 bg-white shadow-lg rounded-lg">
        <h1 class="text-center text-2xl font-bold mb-4">Bon Transaksi</h1>

        <!-- Printable Area -->
        <div id="receiptContent" class="printable-area"> 
            <div class="mb-4">
                <p>ID Transaksi: {{ $checkout->id }}</p>
                <p>Tanggal Transaksi: {{ $checkout->created_at->format('d M Y, H:i:s') }}</p>
                <p>Jenis Pembayaran: {{ $checkout->paymentType->name }}</p>
                <p>Total Produk: {{ $checkout->checkoutItems->sum('quantity') }}</p>
                <p>Harga Total: Rp.{{ number_format($checkout->checkoutItems->sum(function($item) { return $item->quantity * $item->price; }), 2) }}</p>
            </div>
            <table class="min-w-full leading-normal mb-4">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">Nama Barang</th>
                        <th class="py-2 px-4 border-b">Jumlah</th>
                        <th class="py-2 px-4 border-b">Harga Satuan</th>
                        <th class="py-2 px-4 border-b">Harga Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checkout->checkoutItems as $item)
                        <tr>
                            <td class="py-2 px-4 border-b">{{ $item->product_name }}</td>
                            <td class="py-2 px-4 border-b">{{ $item->quantity }}</td>
                            <td class="py-2 px-4 border-b">Rp.{{ number_format($item->price, 2) }}</td>
                            <td class="py-2 px-4 border-b">Rp.{{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Print Button --> 
        <button onclick="printReceipt()" class="btn btn-primary mt-4 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cetak Bon</button>
        <a href="{{ route('checkoutsDetails', $checkout->id)  }}" class="py-2.5 px-5 mt-8 ml-3 bottom-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 align-middle">Kembali</a>
    </div>
</div>

<script>
    function printReceipt() {
        // Store the original content of the document
        const originalContent = document.body.innerHTML;

        // Get the receipt content from the div
        const receiptContent = document.getElementById('receiptContent').outerHTML;

        // Replace the body content with the receipt content for printing
        document.body.innerHTML = receiptContent;

        // Trigger the print function
        window.print();

        // Restore the original content after printing
        document.body.innerHTML = originalContent;

        // Optionally, reload the page to re-apply any JavaScript functions or event listeners
        location.reload();
    }
</script>
@endsection
