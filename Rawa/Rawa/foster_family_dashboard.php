<?php
// foster_family_dashboard.php - لوحة تحكم الأسرة الكافلة

session_start(); // بدء الجلسة

// تحقق مما إذا كان المستخدم مسجلاً للدخول ونوعه "foster_family"
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'foster_family') {
    // إذا لم يكن مسجلاً للدخول أو لم يكن أسرة كافلة، قم بتوجيهه إلى صفحة تسجيل الدخول
    header('Location: foster_family_login.html');
    exit();
}

// يمكنك جلب المزيد من البيانات الخاصة بالأسرة الكافلة من قاعدة البيانات هنا
// باستخدام $_SESSION['user_id']
$loggedInUserId = $_SESSION['user_id'];
$loggedInNationalId = $_SESSION['national_id']; // إذا كنت تخزنها في الجلسة

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db_connect.php'; // هذا الملف يوفر الآن المتغير $conn لاتصال MySQLi

$fosterFamilyName = "الأسرة الكافلة"; // اسم افتراضي
$errorMessage = '';

try {
    // جلب اسم رب الأسرة الكافلة من قاعدة البيانات
    $stmt = $conn->prepare("SELECT foster_family_head_name FROM foster_families_profiles WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();

    if ($profile) {
        $fosterFamilyName = $profile['foster_family_head_name'];
    } else {
        $errorMessage = "لم يتم العثور على ملف تعريف الأسرة الكافلة.";
    }

} catch (Exception $e) {
    $errorMessage = "خطأ في جلب البيانات: " . $e->getMessage();
} finally {
    if ($conn) {
        $conn->close();
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأسرة الكافلة</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="info_guardian.css"> <!-- يمكن استخدام نفس الستايل العام أو إنشاء واحد جديد -->
    <style>
        /* أنماط بسيطة للوحة التحكم */
        .dashboard-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: var(--info-card-bg); /* لون خلفية البطاقة الداكنة */
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-align: center;
            color: var(--info-text-light);
        }
        .dashboard-container h1 {
            color: var(--info-active-color); /* لون مميز */
            margin-bottom: 20px;
        }
        .dashboard-container p {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .dashboard-container .user-id-display {
            font-size: 14px;
            color: #BBBBBB;
            margin-top: 20px;
            word-break: break-all; /* لكسر الكلمات الطويلة مثل الـ ID */
        }
        .logout-btn-dashboard {
            background-color: var(--info-button-color);
            color: var(--white);
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Almarai', sans-serif;
            margin-top: 30px;
        }
        .logout-btn-dashboard:hover {
            background-color: var(--info-button-hover);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- الهيدر - يمكن إعادة استخدام نفس الهيدر من info_guardian.php -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-right">
                <img src="images/Rowa2.png" alt="شعار الموقع" class="logo"> <!-- تأكدي من مسار الصورة -->
                <nav class="header-links">
                    <a href="#" class="header-link active">لوحة التحكم</a>
                    <a href="#" class="header-link">الرئيسية</a>
                </nav>
            </div>

            <div class="header-left">
                <a href="#" class="lang-link">English</a>
                <button class="logout-btn" onclick="window.location.href='logout.php'">تسجيل خروج</button>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <h1>مرحباً بك، <?php echo htmlspecialchars($fosterFamilyName); ?>!</h1>
        <?php if ($errorMessage): ?>
            <p style='color: red;'><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php else: ?>
            <p>هذه لوحة تحكم الأسرة الكافلة الخاصة بك.</p>
            <p>يمكنك هنا إدارة معلومات الكفالة، وتتبع حالة اليتيم، والمزيد.</p>
            <p class="user-id-display">رقم هويتك الوطنية: <?php echo htmlspecialchars($loggedInNationalId); ?></p>
        <?php endif; ?>
        <button class="logout-btn-dashboard" onclick="window.location.href='logout.php'">تسجيل خروج</button>
    </div>

</body>
</html>
