<?php
include '../includes/config.php';

// Check if receipt data exists
if (!isset($_SESSION['receipt_data'])) {
    header("Location: transaksi.php");
    exit();
}

$receipt_data = $_SESSION['receipt_data'];
unset($_SESSION['receipt_data']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4c1d95;
            --secondary-color: #7c3aed;
            --accent-color: #a78bfa;
            --text-color: #1f2937;
            --light-gray: #f3f4f6;
            --medium-gray: #e5e7eb;
            --dark-gray: #6b7280;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }
        
        .receipt-container {
            max-width: 420px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border-radius: 12px;
            border: 1px solid var(--medium-gray);
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px dashed var(--medium-gray);
            position: relative;
        }
        
        .receipt-header::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            right: 0;
            height: 2px;
            background: repeating-linear-gradient(to right, transparent, transparent 10px, var(--medium-gray) 10px, var(--medium-gray) 20px);
        }
        
        .receipt-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .receipt-subtitle {
            font-size: 14px;
            color: var(--dark-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .receipt-info {
            margin-bottom: 25px;
            background: var(--light-gray);
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .info-value {
            font-weight: 500;
        }
        
        .receipt-items {
            margin-bottom: 20px;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .item-name {
            flex: 2;
            font-weight: 500;
        }
        
        .item-qty {
            flex: 1;
            text-align: center;
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        .item-price {
            flex: 1;
            text-align: right;
            font-weight: 500;
        }
        
        .receipt-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 18px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed var(--medium-gray);
            position: relative;
        }
        
        .receipt-total::before {
            content: "";
            position: absolute;
            top: -6px;
            left: 0;
            right: 0;
            height: 2px;
            background: repeating-linear-gradient(to right, transparent, transparent 10px, var(--medium-gray) 10px, var(--medium-gray) 20px);
        }
        
        .btn-print {
            width: 100%;
            margin-top: 25px;
            background-color: var(--primary-color);
            border: none;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        .btn-back {
            width: 100%;
            margin-top: 10px;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background-color: var(--light-gray);
        }
        
        .thank-you {
            text-align: center;
            margin-top: 25px;
            font-style: italic;
            color: var(--dark-gray);
            font-size: 15px;
            position: relative;
            padding-top: 15px;
        }
        
        .thank-you::before {
            content: "";
            position: absolute;
            top: 0;
            left: 25%;
            right: 25%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--medium-gray), transparent);
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: none;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt-container">
            <div class="receipt-header">
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="receipt-title"><?= $receipt_data['jurusan'] ?> KANTIN</div>
                <div class="receipt-subtitle">STRUK TRANSAKSI</div>
            </div>
            
            <div class="receipt-info">
                <div class="info-row">
                    <span class="info-label">ID TRANSAKSI:</span>
                    <span class="info-value">#<?= $receipt_data['transaction_id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">TANGGAL TRANSAKSI:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($receipt_data['tanggal'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PELANGGAN:</span>
                    <span class="info-value"><?= htmlspecialchars($receipt_data['nama_pembeli']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">METODE PEMBAYARAN:</span>
                    <span class="info-value"><?= ucfirst($receipt_data['metode_bayar']) ?></span>
                </div>
            </div>
            
            <div class="receipt-items">
                <?php foreach ($receipt_data['items'] as $item): ?>
                    <div class="receipt-item">
                        <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
                        <div class="item-qty"><?= $item['qty'] ?> × Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                        <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="receipt-total">
                <span>TOTAL</span>
                <span>Rp <?= number_format($receipt_data['total'], 0, ',', '.') ?></span>
            </div>
            
            <div class="thank-you">
                Thank you for your purchase!
            </div>
            
            <div class="no-print">
                <button class="btn btn-print" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> PRINT SEKARANG
                </button>
                <a href="transaksi.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i> KEMBALI KE TRANSAKSI
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto print if needed (optional)
        window.onload = function() {
            // Uncomment below to auto-print the receipt
            // setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>