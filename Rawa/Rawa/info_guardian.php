<?php
// info_guardian.php - صفحة عرض المعلومات الشخصية للوصي مع PHP مدمج

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db_connect.php'; // هذا الملف يوفر الآن المتغير $conn لاتصال MySQLi

// ***************************************************************
// ملاحظة هامة: في تطبيق حقيقي، يجب الحصول على user_id من جلسة المستخدم (session)
// بعد تسجيل الدخول بنجاح لضمان الأمان.
// لأغراض الاختبار، سنفترض user_id مؤقتاً.
// ***************************************************************
// افترض أن الوصي الذي سجل الدخول لديه user_id = 1 (يمكنك تغييره للاختبار)
$loggedInUserId = 1; // هذا هو ID الوصي الذي نريد عرض معلوماته
// في تطبيق حقيقي:
// session_start(); // يجب بدء الجلسة هنا
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guardian') {
//     // توجيه المستخدم لصفحة تسجيل الدخول إذا لم يكن مصادقاً أو لم يكن وصياً
//     header('Location: guardian_login.html'); // تم تحديث المسار
//     exit();
// }
// $loggedInUserId = $_SESSION['user_id'];
// ***************************************************************

$guardianInfo = null;
$errorMessage = '';
$selectedStudyLevel = ''; // لتحديد الخيار المختار في القائمة المنسدلة

try {
    // جلب معلومات الوصي من جدول users وجدول guardians_profiles باستخدام MySQLi
    $stmt = $conn->prepare("
        SELECT
            u.national_id,
            gp.full_name,
            gp.relationship_to_orphan,
            gp.is_orphan_studying,
            gp.orphan_income_source
        FROM users u
        JOIN guardians_profiles gp ON u.id = gp.user_id
        WHERE u.id = ? AND u.user_type = 'guardian'
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('i', $loggedInUserId); // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result();
    $guardianInfo = $result->fetch_assoc();
    $stmt->close();

    if (!$guardianInfo) {
        $errorMessage = "عذراً، لم يتم العثور على معلومات الوصي لهذا المستخدم.";
    } else {
        // تحديد المرحلة الدراسية للقائمة المنسدلة
        // بناءً على is_orphan_studying. هذا افتراضي حيث لا يوجد حقل للمرحلة في جدول الوصي.
        if ($guardianInfo['is_orphan_studying'] == 1) {
            // إذا كان اليتيم يدرس، يمكن تعيين قيمة افتراضية هنا
            // أو جلب القيمة الفعلية من جدول Orphans إذا كان هناك يتيم محدد
            $selectedStudyLevel = 'ابتدائي'; // افتراضي: إذا كان يدرس، افترض أنه ابتدائي
        } else {
            $selectedStudyLevel = 'لا يدرس'; // إذا كان لا يدرس
        }
    }

} catch (Exception $e) { // Catch general Exception for prepared statement errors or other issues
    $errorMessage = "خطأ في جلب البيانات: " . $e->getMessage();
} finally {
    // Ensure the connection is closed if it was opened in this script
    // Note: If db_connect.php is always included, you might not close it here
    // if other scripts rely on the open connection.
    // For this specific page, it's fine to close it.
    if ($conn) {
        $conn->close();
    }
}

// تحويل القيم المخزنة في قاعدة البيانات إلى نص عربي للعرض
$relationshipMap = [
    'uncle' => 'عم',
    'grandfather' => 'جد',
    'court_guardian' => 'وصي من المحكمة',
    'official_entity' => 'جهة رسمية'
];

$incomeSourceMap = [
    'inheritance' => 'إرث',
    'social_security' => 'ضمان',
    'government_support' => 'دعم حكومي'
];

// حالة الحساب (افتراضياً نشط، يمكن ربطها بحالة الحساب البنكي لليتيم)
$accountStatus = "نشط"; // هذه القيمة يمكن جلبها ديناميكياً من جدول accounts المرتبط باليتيم
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المعلومات الشخصية للوصي</title>
    <link rel="stylesheet" href="info_guardian.css">
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- الهيدر -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-right">
                <img src="images/Rowa2.png" alt="شعار الموقع" class="logo"> <!-- تأكدي من مسار الصورة ووجودها في مجلد 'images' -->
                <nav class="header-links">
                    <a href="#" class="header-link active">المعلومات الشخصية</a>
                    <a href="#" class="header-link">الرئيسية</a>
                </nav>
            </div>

            <div class="header-left">
                <a href="#" class="lang-link">English</a>
                <button class="logout-btn">تسجيل خروج</button>
            </div>
        </div>
    </header>

   

        <!-- Main content area -->
        <main class="main-content-area">
            <div class="info-container">
                <h1 class="info-title">المعلومات الشخصية</h1>

                <?php if ($errorMessage): ?>
                    <p style='text-align: center; color: red; margin-top: 20px;'><?php echo htmlspecialchars($errorMessage); ?></p>
                <?php else: ?>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">الاسم الرباعي:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($guardianInfo['full_name']); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">رقم الهوية الوطنية:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($guardianInfo['national_id']); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">صفتك بالنسبة للطفل:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($relationshipMap[$guardianInfo['relationship_to_orphan']] ?? $guardianInfo['relationship_to_orphan']); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">المرحلة الدراسية:</span>
                            <select class="info-value-editable" id="studyLevelSelect" data-user-id="<?php echo htmlspecialchars($loggedInUserId); ?>">
                                <option value="">اختر المرحلة</option>
                                <option value="ابتدائي" <?php echo ($selectedStudyLevel == 'ابتدائي') ? 'selected' : ''; ?>>ابتدائي</option>
                                <option value="متوسط" <?php echo ($selectedStudyLevel == 'متوسط') ? 'selected' : ''; ?>>متوسط</option>
                                <option value="ثانوي" <?php echo ($selectedStudyLevel == 'ثانوي') ? 'selected' : ''; ?>>ثانوي</option>
                                <option value="لا يدرس" <?php echo ($selectedStudyLevel == 'لا يدرس') ? 'selected' : ''; ?>>لا يدرس حالياً</option>
                            </select>
                        </div>

                        <div class="info-item">
                            <span class="info-label">دخل الطفل:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($incomeSourceMap[$guardianInfo['orphan_income_source']] ?? 'غير محدد'); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">حالة الحساب:</span>
                            <span class="info-value status-active"><?php echo htmlspecialchars($accountStatus); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <!-- تم إزالة استدعاء script.js لأنه لم يعد ضرورياً -->
    <!-- <script src="script.js"></script> -->
</body>
</html>
