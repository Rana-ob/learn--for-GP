<?php
// update_study_level.php - نقطة نهاية لتحديث المرحلة الدراسية للوصي

require_once 'db_connect.php'; // تضمين ملف الاتصال بقاعدة البيانات

// السماح بطلبات CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "طريقة الطلب غير مسموح بها."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data['user_id'] ?? null;
$isStudying = $data['is_studying'] ?? null; // 0 for not studying, 1 for studying

// 1. التحقق من البيانات المدخلة
if ($userId === null || $isStudying === null || !is_numeric($userId) || !in_array($isStudying, [0, 1])) {
    http_response_code(400);
    echo json_encode(["message" => "بيانات غير صالحة للتحديث."]);
    exit();
}

try {
    // 2. تحديث حقل is_orphan_studying في جدول guardians_profiles
    // ملاحظة: إذا أردتِ تخزين المرحلة الدراسية المحددة (ابتدائي، متوسط، ثانوي)،
    // يجب إضافة عمود جديد في جدول guardians_profiles (مثلاً: orphan_study_level)
    // وتحديثه هنا أيضاً. حالياً، يتم تحديث is_orphan_studying فقط.
    $stmt = $pdo->prepare("UPDATE guardians_profiles SET is_orphan_studying = ? WHERE user_id = ?");
    $stmt->execute([$isStudying, $userId]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["message" => "تم تحديث المرحلة الدراسية بنجاح."]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(["message" => "لم يتم العثور على الوصي لتحديث معلوماته، أو لم يتم تغيير القيمة."]);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["message" => "حدث خطأ في قاعدة البيانات أثناء التحديث: " . $e->getMessage()]);
}
?>
