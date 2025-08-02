<?php
// signin.php - User login endpoint with session management (MySQLi)

// Start the session at the very beginning of the script
session_start();

// Include the database connection file
require_once 'db_connect.php'; // This now uses MySQLi and provides $conn

// Allow CORS requests (important in development environment)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests (sent by browsers for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "طريقة الطلب غير مسموح بها."]);
    exit();
}

// Read JSON data sent in the request body
$data = json_decode(file_get_contents("php://input"), true);

// Extract data
$national_id = $data['national_id'] ?? null;
$password = $data['password'] ?? null;

// 1. Check for missing national_id or password
if (empty($national_id) || empty($password)) {
    http_response_code(400);
    echo json_encode(["message" => "الرجاء إدخال رقم الهوية الوطنية والرمز السري."]);
    exit();
}

try {
    // 2. Search for the user in the database using prepared statement for security
    $stmt = $conn->prepare('SELECT id, national_id, password_hash, failed_attempts, account_locked_until, user_type FROM users WHERE national_id = ?');
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('s', $national_id); // 's' for string
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // 3. Check if the user exists
    if (!$user) {
        http_response_code(401); // Unauthorized
        echo json_encode(["message" => "رقم الهوية الوطنية أو الرمز السري غير صحيح."]);
        exit();
    }

    // 4. Check account lock status
    if ($user['account_locked_until'] && new DateTime($user['account_locked_until']) > new DateTime()) {
        $unlockTime = (new DateTime($user['account_locked_until']))->format('H:i'); // Format time
        http_response_code(403); // Forbidden
        echo json_encode(["message" => "الحساب مقفل مؤقتاً. الرجاء المحاولة بعد الساعة " . $unlockTime . "."]);
        exit();
    }

    // 5. Compare the provided password with the stored hashed password
    $isPasswordValid = password_verify($password, $user['password_hash']);

    if (!$isPasswordValid) {
        // 6. Handle failed login attempts
        $newFailedAttempts = $user['failed_attempts'] + 1;
        $accountLockedUntil = null;
        $lockMessage = '';

        $MAX_FAILED_ATTEMPTS = 3; // Number of allowed attempts before locking
        $LOCK_DURATION_MINUTES = 5; // Lock duration in minutes

        if ($newFailedAttempts >= $MAX_FAILED_ATTEMPTS) {
            $accountLockedUntil = (new DateTime())->modify('+' . $LOCK_DURATION_MINUTES . ' minutes')->format('Y-m-d H:i:s');
            $lockMessage = " تم قفل حسابك لمدة " . $LOCK_DURATION_MINUTES . " دقائق بسبب كثرة المحاولات الفاشلة.";
        }

        // Update failed attempts count and lock time in the database
        $stmt = $conn->prepare('UPDATE users SET failed_attempts = ?, account_locked_until = ? WHERE id = ?');
        if (!$stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        $stmt->bind_param('isi', $newFailedAttempts, $accountLockedUntil, $user['id']); // 'i' for int, 's' for string
        $stmt->execute();
        $stmt->close();

        http_response_code(401); // Unauthorized
        echo json_encode(["message" => "رقم الهوية الوطنية أو الرمز السري غير صحيح." . $lockMessage]);
        exit();

    } else {
        // 7. Successful login: Reset failed attempts and store user data in session
        $stmt = $conn->prepare('UPDATE users SET failed_attempts = 0, account_locked_until = NULL WHERE id = ?');
        if (!$stmt) {
            throw new Exception("Failed to prepare reset statement: " . $conn->error);
        }
        $stmt->bind_param('i', $user['id']); // 'i' for int
        $stmt->execute();
        $stmt->close();

        // Store essential user data in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['national_id'] = $user['national_id']; // Optional, but useful

        // Determine redirect URL based on user type
        // تم التعديل هنا: توجيه كل من الوصي والأسرة الكافلة إلى Rawa.html
        $redirectUrl = 'Rawa.html';

        http_response_code(200); // OK
        echo json_encode([
            "message" => "تم تسجيل الدخول بنجاح!",
            "userId" => $user['id'],
            "userType" => $user['user_type'],
            "redirect" => $redirectUrl // Send the determined redirect URL
        ]);
        exit();
    }

} catch (Exception $e) { // Catch general Exception for prepared statement errors
    // Handle database errors or other exceptions
    http_response_code(500); // Internal Server Error
    echo json_encode(["message" => "حدث خطأ في السيرفر عند محاولة تسجيل الدخول: " . $e->getMessage()]);
    exit();
} finally {
    // Ensure the connection is closed
    if ($conn) {
        $conn->close();
    }
}
?>
