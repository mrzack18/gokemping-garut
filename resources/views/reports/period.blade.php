<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $business->name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 24px;
        }

        .kop {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .kop h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
        }

        .kop p {
            margin: 1px 0;
            color: #374151;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
        }

        .angka {
            text-align: right;
        }

        .ringkasan td:first-child {
            width: 60%;
            color: #374151;
        }

        .muted {
            color: #6b7280;
        }

        .footer {
            margin-top: 24px;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ $business->name }}</h1>
        <p>{{ $business->address }}</p>
        <p>
            WhatsApp: {{ $business->whatsapp }}
            @if ($business->email)
                &middot; Email: {{ $business->email }}
            @endif
        </p>
    </div>

    <h2>Laporan Periode {{ $report['period']['label'] }}</h2>

    <h2>Ringkasan</h2>
    <table class="ringkasan">
        <tr>
            <td>Jumlah Booking</td>
            <td class="angka">{{ $report['stats']['bookings'] }}</td>
        </tr>
        <tr>
            <td>Booking Selesai</td>
            <td class="angka">{{ $report['stats']['finished'] }}</td>
        </tr>
        <tr>
            <td>Booking Dibatalkan</td>
            <td class="angka">{{ $report['stats']['cancelled'] }}</td>
        </tr>
        <tr>
            <td>Jumlah Penyewa</td>
            <td class="angka">{{ $report['stats']['customers'] }}</td>
        </tr>
        <tr>
            <td>Total Pendapatan</td>
            <td class="angka">Rp {{ $report['stats']['revenue_label'] }}</td>
        </tr>
    </table>

    <h2>Produk Paling Banyak Disewa</h2>
    @if ($report['top_products'] === [])
        <p class="muted">Tidak ada produk tersewa pada periode ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="angka">Unit</th>
                    <th class="angka">Nilai Sewa</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['top_products'] as $product)
                    <tr>
                        <td>{{ $product['product_name'] }}</td>
                        <td class="angka">{{ $product['quantity'] }}</td>
                        <td class="angka">Rp {{ $product['revenue_label'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Rekap Metode Pembayaran</h2>
    <table>
        <thead>
            <tr>
                <th>Metode</th>
                <th class="angka">Transaksi</th>
                <th class="angka">Nominal</th>
                <th class="angka">Lunas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['payment_methods'] as $method)
                <tr>
                    <td>{{ $method['method_label'] }}</td>
                    <td class="angka">{{ $method['transactions'] }}</td>
                    <td class="angka">Rp {{ $method['amount_label'] }}</td>
                    <td class="angka">Rp {{ $method['paid_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Pendapatan per {{ $report['revenue_chart']['granularity'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th class="angka">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['revenue_chart']['points'] as $point)
                <tr>
                    <td>{{ $point['full_label'] }}</td>
                    <td class="angka">Rp {{ $point['amount_label'] }}</td>
                </tr>
            @endforeach
            <tr>
                <th>Total</th>
                <th class="angka">Rp {{ $report['revenue_chart']['total_label'] }}</th>
            </tr>
        </tbody>
    </table>

    <p class="footer">Dicetak {{ $generatedAt }}.</p>
</body>
</html>
