<?php
header("Content-Type: application/json");

// Untuk koneksi ke database 
$conn = new mysqli("localhost", "root", "", "api_bank");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Data dari permintaan POST
$data = json_decode(file_get_contents("php://input"), true);

$fromAccount = $data['from'];
$toAccount = $data['to'];
$amount = $data['amount'];

// Periksa apakah akun pengirim dan penerima ada
$checkAccounts = "SELECT * FROM accounts WHERE account_number IN ('$fromAccount', '$toAccount')";
$result = $conn->query($checkAccounts);

if ($result->num_rows !== 2) {
    http_response_code(404);
    echo json_encode(["message" => "Akun pengirim atau penerima tidak ditemukan"]);
    exit();
}

// Periksa apakah saldo mencukupi
$row = $result->fetch_assoc();
if ($row['account_number'] === $fromAccount && $row['balance'] < $amount) {
    http_response_code(400);
    echo json_encode(["message" => "Saldo tidak mencukupi"]);
    exit();
}

// Memulai transaksi
$conn->autocommit(FALSE);

// Transfer uang
$updateSenderBalance = "UPDATE accounts SET balance = balance - $amount WHERE account_number = '$fromAccount'";
$updateReceiverBalance = "UPDATE accounts SET balance = balance + $amount WHERE account_number = '$toAccount'";

if ($conn->query($updateSenderBalance) === TRUE && $conn->query($updateReceiverBalance) === TRUE) {
    // Simpan transaksi ke tabel transaksi
    $insertTransaction = "INSERT INTO transactions (from_account_number, to_account_number, amount) VALUES ('$fromAccount', '$toAccount', $amount)";
    if ($conn->query($insertTransaction) === TRUE) {
        // Perbarui riwayat transaksi pada akun pengirim
        $transactionData = "Transfer $amount ke akun $toAccount";
        $updateSenderTransactions = "UPDATE accounts SET transactions = CONCAT(transactions, '$transactionData\n') WHERE account_number = '$fromAccount'";
        $conn->query($updateSenderTransactions);

        // Perbarui riwayat transaksi pada akun penerima
        $transactionData = "Terima $amount dari akun $fromAccount";
        $updateReceiverTransactions = "UPDATE accounts SET transactions = CONCAT(transactions, '$transactionData\n') WHERE account_number = '$toAccount'";
        $conn->query($updateReceiverTransactions);

        $conn->commit();
        http_response_code(200);
        echo json_encode(["message" => "Transfer berhasil"]);
    } else {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["message" => "Gagal menyimpan transaksi"]);
    }
} else {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["message" => "Gagal melakukan transfer"]);
}
$conn->close();
?>
