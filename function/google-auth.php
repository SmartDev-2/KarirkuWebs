<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('IN_APP', true);

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/supabase.php';
} catch (Exception $e) {
    die("Error loading system files: " . $e->getMessage());
}

// ✅ GANTI DENGAN CLIENT ID & SECRET ANDA
$googleClientId = 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com';
$googleClientSecret = 'YOUR_ACTUAL_CLIENT_SECRET';
$redirectUri = SITE_URL . '/function/google-auth.php';

// Log file
$logFile = __DIR__ . '/google-auth-debug.log';

function debug_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

debug_log("=== GOOGLE AUTH PROCESS STARTED ===");
debug_log("Client ID: " . substr($googleClientId, 0, 10) . "...");
debug_log("Redirect URI: " . $redirectUri);

// Step 1: Redirect to Google OAuth
if (!isset($_GET['code'])) {
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $googleClientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    
    debug_log("Redirecting to Google OAuth");
    debug_log("Auth URL: " . $authUrl);
    header('Location: ' . $authUrl);
    exit;
}

// Step 2: Handle Google callback with code
$code = $_GET['code'];
debug_log("Received authorization code: " . substr($code, 0, 20) . "...");

// Step 3: Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'client_id' => $googleClientId,
    'client_secret' => $googleClientSecret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri
];

debug_log("Exchanging code for token...");
debug_log("Token URL: " . $tokenUrl);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $tokenUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

debug_log("Token exchange - HTTP Code: " . $httpCode);
debug_log("Token exchange - Response: " . $response);

if ($httpCode !== 200) {
    debug_log("Token exchange failed: " . $error);
    $_SESSION['error'] = "Google authentication failed (Token Error). Please try again.";
    header('Location: ../views/register.php');
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'];

debug_log("Successfully obtained access token");

// Step 4: Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $userInfoUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$userResponse = curl_exec($ch);
$userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

debug_log("User info - HTTP Code: " . $userHttpCode);
debug_log("User info - Response: " . $userResponse);

if ($userHttpCode !== 200) {
    debug_log("Failed to get user info");
    $_SESSION['error'] = "Failed to get user information from Google.";
    header('Location: ../views/register.php');
    exit;
}

$userData = json_decode($userResponse, true);
debug_log("User data received - Email: " . ($userData['email'] ?? 'No email'));

// Step 5: Process user
$email = $userData['email'] ?? '';
$nama = $userData['name'] ?? $userData['email'] ?? 'User';

if (empty($email)) {
    $_SESSION['error'] = "No email received from Google.";
    header('Location: ../views/register.php');
    exit;
}

// Check if user exists
$result = supabaseQuery('pengguna', [
    'select' => '*',
    'email' => 'eq.' . $email
]);

debug_log("User check - Success: " . ($result['success'] ? 'YES' : 'NO'));
debug_log("User check - Count: " . count($result['data']));

if ($result['success'] && count($result['data']) > 0) {
    // Login existing user
    $user = $result['data'][0];
    loginUser($user);
} else {
    // Register new user
    registerNewUser($nama, $email);
}

function loginUser($user) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = $user['nama_lengkap'];
    $_SESSION['user_id'] = $user['id_pengguna'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'] ?? 'pencaker';
    $_SESSION['success'] = "Login berhasil! Selamat datang kembali, " . $user['nama_lengkap'] . "!";
    
    debug_log("User logged in: " . $user['email']);
    header('Location: ../index.php');
    exit;
}

function registerNewUser($nama, $email) {
    $randomPassword = bin2hex(random_bytes(8));
    
    $userData = [
        'nama_lengkap' => $nama,
        'email' => $email,
        'password' => $randomPassword,
        'role' => 'pencaker',
        'dibuat_pada' => date('Y-m-d H:i:s'),
        'email_verified' => true,
        'auth_provider' => 'google'
    ];
    
    debug_log("Registering new user: " . $email);
    
    $newUser = supabaseInsert('pengguna', $userData);
    
    debug_log("Registration result - Success: " . ($newUser['success'] ? 'YES' : 'NO'));
    debug_log("Registration result - HTTP Code: " . ($newUser['http_code'] ?? 'N/A'));
    
    if ($newUser['success'] && isset($newUser['data'][0]['id_pengguna'])) {
        $userData = $newUser['data'][0];
        
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = $nama;
        $_SESSION['user_id'] = $userData['id_pengguna'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'pencaker';
        $_SESSION['success'] = "Registrasi berhasil! Selamat datang di Karirku.";
        
        debug_log("User registered successfully: " . $email);
        header('Location: ../index.php');
        exit;
    } else {
        $errorMsg = $newUser['error'] ?? 'Unknown error';
        debug_log("Registration failed: " . $errorMsg);
        debug_log("Full response: " . print_r($newUser, true));
        
        $_SESSION['error'] = "Registration failed: " . $errorMsg;
        header('Location: ../views/register.php');
        exit;
    }
}