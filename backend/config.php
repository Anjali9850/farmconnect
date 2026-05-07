<?php
// ============================================================
//  FarmConnect – config.php
//  Compatible: PHP 7.4+, XAMPP Windows, Apache
// ============================================================

// ── Error reporting (turn OFF for production) ────────────────
error_reporting(E_ALL);
ini_set('display_errors', 0);          // Never show errors to browser
ini_set('log_errors', 1);             // Log them instead

// ── Database ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');                 // XAMPP default = blank
define('DB_NAME', 'farmconnect');

// ── Paths ─────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('BASE_URL',   'http://localhost/farmconnect');

// ── Session (must happen before any output) ──────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Use simple params for max XAMPP compatibility
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ── CORS headers ──────────────────────────────────────────────
// Allow requests from the same localhost origin
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');
}

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Database connection ───────────────────────────────────────
function getDB() {
    static $db = null;
    if ($db !== null) return $db;

    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($db->connect_error) {
        jsonError('Database connection failed. Check DB_HOST/USER/PASS in config.php. Error: ' . $db->connect_error, 500);
    }

    $db->set_charset('utf8mb4');
    return $db;
}

// ── JSON response helpers ─────────────────────────────────────
function jsonSuccess($data = [], $message = 'Success', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

function jsonError($message = 'An error occurred', $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data'    => null
    ]);
    exit;
}

// ── Auth helpers ──────────────────────────────────────────────
function requireLogin() {
    if (empty($_SESSION['user'])) {
        jsonError('Please log in to continue.', 401);
    }
    return $_SESSION['user'];
}

function requireRole() {
    $roles = func_get_args();           // e.g. requireRole('farmer','admin')
    $user  = requireLogin();
    if (!in_array($user['role'], $roles, true)) {
        jsonError('Access denied. You do not have permission for this action.', 403);
    }
    return $user;
}

// ── Read JSON body ────────────────────────────────────────────
function getBody() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ── Sanitize input ────────────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim((string)$val)), ENT_QUOTES, 'UTF-8');
}

// ── Send notification helper ───────────────────────────────────
function sendNotification($user_id, $type, $title, $message, $related_user_id = null, $related_order_id = null, $related_product_id = null) {
    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO notifications (user_id, type, title, message, related_user_id, related_order_id, related_product_id, is_read)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ');
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param(
        'isssiii',
        $user_id, $type, $title, $message, $related_user_id, $related_order_id, $related_product_id
    );
    
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

