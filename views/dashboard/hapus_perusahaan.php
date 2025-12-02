<?php
// 1. LOAD LIBRARY
require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client;

// 2. KONFIGURASI
$supabaseUrl = 'https://tkjnbelcgfwpbhppsnrl.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRram5iZWxjZ2Z3cGJocHBzbnJsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTc0MDc2MiwiZXhwIjoyMDc3MzE2NzYyfQ.vZoNXxMWtoG4ktg7K6Whqv8EFzCv7qbS3OAHEfxVoR0';

// 3. KONEKSI
try {
    $client = new Client([
        'base_uri' => $supabaseUrl . '/rest/v1/',
        'headers' => [
            'apikey'        => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type'  => 'application/json',
        ],
        'http_errors' => false
    ]);
} catch (Exception $e) {
    die("Gagal inisialisasi Client: " . $e->getMessage());
}

// 4. PROSES HAPUS
if (isset($_GET['id'])) {
    $id_dari_url = $_GET['id'];

    try {
        // PERBAIKAN DISINI: Mengganti 'id' menjadi 'id_perusahaan'
        $response = $client->delete('perusahaan', [
            'query' => ['id_perusahaan' => 'eq.' . $id_dari_url]
        ]);

        $statusCode = $response->getStatusCode();

        // Cek Hasil
        if ($statusCode >= 200 && $statusCode < 300) {
            header("Location: perusahaan.php?pesan=hapus_sukses");
            exit;
        } else {
            $body = $response->getBody()->getContents();
            echo "<h3>Gagal Menghapus</h3>";
            echo "Status Code: " . $statusCode . "<br>";
            echo "Pesan Server: " . $body;
        }

    } catch (Exception $e) {
        echo "Error Sistem: " . $e->getMessage();
    }

} else {
    header("Location: perusahaan.php");
    exit;
}
?>