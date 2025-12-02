<?php
require_once __DIR__ . '/../../function/supabase.php';

echo "<h1>Simple Test</h1>";

// Test 1: Cek apakah fungsi bekerja
$result = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan, nama_perusahaan, status_persetujuan',
    'limit' => 5
]);

echo "<h2>Result:</h2>";
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
echo "Status Code: " . $result['status'] . "<br>";
echo "Data Count: " . count($result['data']) . "<br>";

if ($result['success']) {
    echo "<h3>Data:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nama</th><th>Status</th></tr>";
    foreach ($result['data'] as $company) {
        echo "<tr>";
        echo "<td>" . $company['id_perusahaan'] . "</td>";
        echo "<td>" . htmlspecialchars($company['nama_perusahaan']) . "</td>";
        echo "<td>" . htmlspecialchars($company['status_persetujuan']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h3>Error:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}
?>