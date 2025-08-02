<?php
// info_foster_family.php - صفحة عرض المعلومات الشخصية للأسرة الكافلة

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db_connect.php'; // هذا الملف يوفر الآن المتغير $conn لاتصال MySQLi

// ***************************************************************
// ملاحظة هامة: في تطبيق حقيقي، يجب الحصول على user_id من جلسة المستخدم (session)
// بعد تسجيل الدخول بنجاح لضمان الأمان.
// لأغراض الاختبار، سنفترض user_id مؤقتاً.
// ***************************************************************
// افترض أن الأسرة الكافلة التي سجلت الدخول لديها user_id = 2 (يمكنك تغييره للاختبار)
$loggedInUserId = 2; // هذا هو ID الأسرة الكافلة التي نريد عرض معلوماتها
// في تطبيق حقيقي:
// session_start(); // يجب بدء الجلسة هنا
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'foster_family') {
//     // توجيه المستخدم لصفحة تسجيل الدخول إذا لم يكن مصادقاً أو لم يكن أسرة كافلة
//     header('Location: foster_family_login.html'); // تم تحديث المسار
//     exit();
// }
// $loggedInUserId = $_SESSION['user_id'];
// ***************************************************************

$fosterFamilyInfo = null;
$errorMessage = '';
$selectedStudyLevel = ''; // لتحديد الخيار المختار في القائمة المنسدلة

try {
    // جلب معلومات الأسرة الكافلة من جدول users وجدول foster_families_profiles باستخدام MySQLi
    $stmt = $conn->prepare("
        SELECT
            u.national_id,
            ffp.foster_family_head_name,
            ffp.fostering_type,
            ffp.child_age_group,
            ffp.is_child_studying,
            ffp.child_study_level
        FROM users u
        JOIN foster_families_profiles ffp ON u.id = ffp.user_id
        WHERE u.id = ? AND u.user_type = 'foster_family'
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('i', $loggedInUserId); // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result();
    $fosterFamilyInfo = $result->fetch_assoc();
    $stmt->close();

    if (!$fosterFamilyInfo) {
        $errorMessage = "عذراً، لم يتم العثور على معلومات الأسرة الكافلة لهذا المستخدم.";
    } else {
        // تحديد المرحلة الدراسية للقائمة المنسدلة بناءً على البيانات المسترجعة
        if ($fosterFamilyInfo['is_child_studying'] == 1) {
            $selectedStudyLevel = htmlspecialchars($fosterFamilyInfo['child_study_level'] ?? '');
        } else {
            $selectedStudyLevel = 'لا يدرس حالياً';
        }
    }

} catch (Exception $e) { // Catch general Exception for prepared statement errors or other issues
    $errorMessage = "خطأ في جلب البيانات: " . $e->getMessage();
} finally {
    // Ensure the connection is closed
    if ($conn) {
        $conn->close();
    }
}

// تحويل القيم المخزنة في قاعدة البيانات إلى نص عربي للعرض
$fosteringTypeMap = [
    'temporary' => 'مؤقتة',
    'permanent' => 'دائمة'
];

$childAgeGroupMap = [
    'under_6' => 'أقل من 6 سنوات',
    '6_or_more' => '6 سنوات أو أكثر'
];

// حالة الحساب (افتراضياً نشط، يمكن ربطها بحالة الحساب البنكي لليتيم)
$accountStatus = "نشط"; // هذه القيمة يمكن جلبها ديناميكياً من جدول accounts المرتبط باليتيم
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المعلومات الشخصية للأسرة الكافلة</title>
    <link rel="stylesheet" href="info_guardian.css">
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- الهيدر الجديد المتوافق مع التصميم العام -->
    <header class="dashboard-header">
        <div class="header-left">
            <a href="#" class="lang-link">English</a>
            <button class="logout-btn">تسجيل خروج</button>
        </div>
        <div class="header-right">
            <nav class="header-links">
                <a href="#" class="header-link active">المعلومات الشخصية</a>
                <a href="#" class="header-link">الرئيسية</a>
            </nav>
            <img src="images/Rowa2.png" alt="شعار الموقع" class="logo">
        </div>
    </header>

    <!-- New wrapper for sidebar and main content -->
    <div class="page-content-wrapper">
        <!-- Main content area -->
        <main class="main-content-area">
            <div class="info-container">
                <h1 class="info-title">المعلومات الشخصية للأسرة الكافلة</h1>

                <?php if ($errorMessage): ?>
                    <p style='text-align: center; color: red; margin-top: 20px;'><?php echo htmlspecialchars($errorMessage); ?></p>
                <?php else: ?>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">اسم رب الأسرة الكامل:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($fosterFamilyInfo['foster_family_head_name'] ?? ''); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">رقم الهوية الوطنية لرب الأسرة:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($fosterFamilyInfo['national_id'] ?? ''); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">نوع الكفالة:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($fosteringTypeMap[$fosterFamilyInfo['fostering_type']] ?? ''); ?></span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">الفئة العمرية للطفل:</span>
                            <span class="info-value fixed-value"><?php echo htmlspecialchars($childAgeGroupMap[$fosterFamilyInfo['child_age_group']] ?? ''); ?></span>
                        </div>
                   
                        <div class="info-item">
                            <span class="info-label">المرحلة الدراسية للطفل:</span>
                            <select class="info-value-editable" id="fosterChildStudyLevel">
                                <option value="">-- اختر --</option>
                                <option value="ابتدائي" <?php echo ($selectedStudyLevel == 'ابتدائي') ? 'selected' : ''; ?>>ابتدائي</option>
                                <option value="متوسط" <?php echo ($selectedStudyLevel == 'متوسط') ? 'selected' : ''; ?>>متوسط</option>
                                <option value="ثانوي" <?php echo ($selectedStudyLevel == 'ثانوي') ? 'selected' : ''; ?>>ثانوي</option>
                                <option value="جامعي" <?php echo ($selectedStudyLevel == 'جامعي') ? 'selected' : ''; ?>>جامعي</option>
                                <option value="لا يدرس حالياً" <?php echo ($selectedStudyLevel == 'لا يدرس حالياً') ? 'selected' : ''; ?>>لا يدرس حالياً</option>
                            </select>
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
</body>
</html>
