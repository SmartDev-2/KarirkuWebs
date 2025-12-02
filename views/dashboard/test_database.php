<?php
require_once 'supabase.php';

echo "<h3>Testing Supabase Connection</h3>";

// Test 1: Query semua perusahaan
$result = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan,nama_perusahaan,status_persetujuan,created_at',
    'order' => 'created_at.desc'
]);

echo "<h4>Test 1: All Perusahaan</h4>";
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
echo "Status Code: " . $result['status'] . "<br>";
echo "Data Count: " . count($result['data']) . "<br>";

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nama</th><th>Status</th><th>Created</th></tr>";
foreach ($result['data'] as $row) {
    echo "<tr>";
    echo "<td>" . $row['id_perusahaan'] . "</td>";
    echo "<td>" . $row['nama_perusahaan'] . "</td>";
    echo "<td>" . $row['status_persetujuan'] . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: Query hanya yang status menunggu
$result2 = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan,nama_perusahaan,status_persetujuan',
    'status_persetujuan' => 'eq.menunggu'
]);

echo "<h4>Test 2: Hanya Status 'menunggu'</h4>";
echo "Success: " . ($result2['success'] ? 'YES' : 'NO') . "<br>";
echo "Pending Count: " . count($result2['data']) . "<br>";

foreach ($result2['data'] as $row) {
    echo "- " . $row['nama_perusahaan'] . " (" . $row['status_persetujuan'] . ")<br>";
}
?>