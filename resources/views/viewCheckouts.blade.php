@extends('layouts.master')

@section('title', 'Checkout History')

@section('content')
<div class="container mx-auto ">
    <h1 class="text-center font-semibold  text-3xl">Riwayat Checkout</h1>


    <div class="flex justify-center mb-4 py-1">
        <form id="filterForm" action="{{ route('viewCheckouts') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
            <select name="year" id="yearFilter" class="px-4 py-2 border rounded-lg">
                <option value="">Tahun</option>
                @for ($i = now()->year; $i >= 2000; $i--)
                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
            <select name="month" id="monthFilter" class="px-4 py-2 border rounded-lg">
                <option value="">Bulan</option>
                @foreach (range(1, 12) as $month)
                    <option value="{{ $month }}" {{ request('month') == $month ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $month)->format('F') }}</option>
                @endforeach
            </select>
              <!-- Payment Type Filter -->
              <select name="payment_type" id="paymentTypeFilter" class="px-4 py-2 border rounded-lg">
                <option value="">Jenis Pembayaran</option>
                @foreach ($paymentTypes as $paymentType)
                    <option value="{{ $paymentType->id }}" {{ request('payment_type') == $paymentType->id ? 'selected' : '' }}>
                        {{ $paymentType->name }}
                    </option>
                @endforeach
            </select>
			<!-- Search by ID -->
            <input type="text" name="search_id" value="{{ request('search_id') }}" placeholder="Pencarian ID Transaksi" class="px-4 py-2 border rounded">
        </form>
    </div>

    @if ($checkouts->isNotEmpty())
        <div class="-mx-4 sm:-mx-8 sm:px-8 py-4 overflow-x-auto flex justify-center">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                ID Checkout
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Tanggal Checkout
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Total Produk
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Harga Total
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($checkouts as $checkout)
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $checkout->id }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="flex items-center">
                                        <div class="ml-3">
                                            <p class="text-gray-900 whitespace-no-wrap">
                                                {{ $checkout->created_at->format('d M Y, H:i:s') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                    <p class="text-gray-900 whitespace-no-wrap">{{ $checkout->checkoutItems->sum('quantity') }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                    <p class="text-gray-900 whitespace-no-wrap">Rp.{{ $checkout->checkoutItems->sum(function($item) { return $item->quantity * $item->price; }) }}</p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                        <a href="{{ route('checkoutsDetails', $checkout->id) }}" class="btn btn-primary mx-2 my-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Detail barang</a>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="text-center text-3xl font-medium">Belum ada transaksi</p>
    @endif


    <div class="flex justify-center mt-4">
        {{ $checkouts->links() }}
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('yearFilter').addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });
        document.getElementById('monthFilter').addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });
        document.getElementById('paymentTypeFilter').addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });
    });
</script>
@endsection


