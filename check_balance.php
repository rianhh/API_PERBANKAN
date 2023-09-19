<?php
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Koneksi ke database
    $conn = new mysqli("localhost", "root", "", "api_bank");

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Ambil parameter akun dari URL
    $accountNumber = $_GET['account_number'];

    // Periksa apakah akun ada
    $checkAccount = "SELECT * FROM accounts WHERE account_number = '$accountNumber'";
    $result = $conn->query($checkAccount);

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $balance = $row['balance'];

        // Mengirim respons dalam format JSON
        $response = ["status" => "success", "balance" => $balance];
        echo json_encode($response);
    } else {
        // Jika akun tidak ditemukan
        $response = ["status" => "error", "message" => "Akun tidak ditemukan"];
        echo json_encode($response);
    }

    // Tutup koneksi ke database
    $conn->close();
} else {
    // Jika metode permintaan tidak valid
    $response = ["status" => "error", "message" => "Metode permintaan tidak valid"];
    echo json_encode($response);
}
?>
