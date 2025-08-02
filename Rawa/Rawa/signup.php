<?php
// signup.php - Endpoint for guardian and foster family registration (MySQLi)

// Include the database connection file
require_once 'db_connect.php'; // This now uses MySQLi and provides $conn

// Allow CORS requests (important in development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "طريقة الطلب غير مسموح بها."]);
    exit();
}

// Read JSON data from the request body
$data = json_decode(file_get_contents("php://input"), true);

// Extract common data for all user types
$national_id = $data['national_id'] ?? null;
$password = $data['password'] ?? null;
$user_type = $data['user_type'] ?? null; // This is crucial for conditional validation

// Extract guardian-specific data
$full_name = $data['full_name'] ?? null;
$relationship_to_orphan = $data['relationship_to_orphan'] ?? null;
$has_guardianship_document = $data['has_guardianship_document'] ?? null;
$orphan_income_source = $data['orphan_income_source'] ?? null;
$has_financial_restriction = $data['has_financial_restriction'] ?? null;
$is_orphan_studying = $data['is_orphan_studying'] ?? null;

// Extract foster family-specific data
$foster_family_head_name = $data['foster_family_head_name'] ?? null;
$has_official_fostering_decision = $data['has_official_fostering_decision'] ?? null;
$fostering_type = $data['fostering_type'] ?? null;
$child_age_group = $data['child_age_group'] ?? null;
$is_child_studying_ff = $data['is_child_studying'] ?? null; // Renamed to avoid conflict
$child_study_level_ff = $data['child_study_level'] ?? null; // Renamed to avoid conflict

// 1. Validate common required fields
if (empty($national_id) || empty($password) || empty($user_type)) {
    http_response_code(400);
    echo json_encode(["message" => "الرجاء إدخال رقم الهوية الوطنية، الرمز السري، ونوع المستخدم."]);
    exit();
}

// Basic validation for national_id length
if (strlen($national_id) !== 10 || !ctype_digit($national_id)) {
    http_response_code(400);
    echo json_encode(["message" => "رقم الهوية الوطنية يجب أن يتكون من 10 أرقام."]);
    exit();
}

// Basic validation for password length (client-side has 6, backend usually stricter)
if (strlen($password) < 6) { // Keeping 6 to match frontend validation, adjust if DB requires more
    http_response_code(400);
    echo json_encode(["message" => "الرمز السري يجب أن لا يقل عن 6 أحرف."]);
    exit();
}

// 2. Validate user_type specific fields
if ($user_type === 'guardian') {
    if (empty($full_name) || empty($relationship_to_orphan) || $has_guardianship_document === null || $has_financial_restriction === null || $is_orphan_studying === null) {
        http_response_code(400);
        echo json_encode(["message" => "الرجاء تعبئة جميع معلومات الوصي المطلوبة."]);
        exit();
    }
} elseif ($user_type === 'foster_family') {
    if (empty($foster_family_head_name) || $has_official_fostering_decision === null || empty($fostering_type) || empty($child_age_group) || $is_child_studying_ff === null) {
        http_response_code(400);
        echo json_encode(["message" => "الرجاء تعبئة جميع معلومات الأسرة الكافلة المطلوبة."]);
        exit();
    }
    // If child is studying, child_study_level_ff should not be null
    if ($is_child_studying_ff == 1 && empty($child_study_level_ff)) {
        http_response_code(400);
        echo json_encode(["message" => "الرجاء اختيار المرحلة الدراسية للطفل المكفول."]);
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "نوع المستخدم غير صالح."]);
    exit();
}

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Start a transaction for atomicity
$conn->begin_transaction();

try {
    // 3. Check if national_id already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE national_id = ?');
    if (!$stmt) {
        throw new Exception("Failed to prepare statement for national ID check: " . $conn->error);
    }
    $stmt->bind_param('s', $national_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        http_response_code(409); // Conflict
        echo json_encode(["message" => "رقم الهوية الوطنية مسجل بالفعل."]);
        $conn->rollback(); // Rollback transaction
        $stmt->close();
        exit();
    }
    $stmt->close();

    // 4. Insert into users table
    $stmt = $conn->prepare('INSERT INTO users (national_id, password_hash, user_type) VALUES (?, ?, ?)');
    if (!$stmt) {
        throw new Exception("Failed to prepare statement for user insert: " . $conn->error);
    }
    $stmt->bind_param('sss', $national_id, $hashed_password, $user_type);
    $stmt->execute();
    $user_id = $conn->insert_id; // Get the ID of the newly inserted user
    $stmt->close();

    // 5. Insert into specific profile table based on user_type
    if ($user_type === 'guardian') {
        $stmt = $conn->prepare('INSERT INTO guardians_profiles (user_id, full_name, relationship_to_orphan, has_guardianship_document, orphan_income_source, has_financial_restriction, is_orphan_studying) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for guardian profile insert: " . $conn->error);
        }
        $stmt->bind_param('isssiis',
            $user_id,
            $full_name,
            $relationship_to_orphan,
            $has_guardianship_document,
            $orphan_income_source,
            $has_financial_restriction,
            $is_orphan_studying
        );
        $stmt->execute();
        $stmt->close();
    } elseif ($user_type === 'foster_family') {
        $stmt = $conn->prepare('INSERT INTO foster_families_profiles (user_id, foster_family_head_name, has_official_fostering_decision, fostering_type, child_age_group, is_child_studying, child_study_level) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for foster family profile insert: " . $conn->error);
        }
        $stmt->bind_param('issssis',
            $user_id,
            $foster_family_head_name,
            $has_official_fostering_decision,
            $fostering_type,
            $child_age_group,
            $is_child_studying_ff, // Use the foster family specific variable
            $child_study_level_ff  // Use the foster family specific variable
        );
        $stmt->execute();
        $stmt->close();
    }

    // Commit the transaction if all inserts are successful
    $conn->commit();

    http_response_code(201); // Created
    // تم التعديل هنا: توجيه كل من الوصي والأسرة الكافلة إلى Rawa.html بعد التسجيل
    echo json_encode(["message" => "تم تسجيل المستخدم بنجاح!", "userId" => $user_id, "userType" => $user_type, "redirect" => "Rawa.html"]);

} catch (Exception $e) { // Catch general Exception for prepared statement errors or other issues
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(["message" => "حدث خطأ في السيرفر عند التسجيل: " . $e->getMessage()]);
} finally {
    // Ensure the connection is closed
    if ($conn) {
        $conn->close();
    }
}
?>
