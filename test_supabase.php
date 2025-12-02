<?php
require __DIR__ . '/config/supabase.php';

$response = $supabase->from('jobs')->select('*')->execute();

print_r($response->getResult());
