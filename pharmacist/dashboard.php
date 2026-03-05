<?php
include('../config/database.php');
session_start();

// التحقق من الصلاحيات (Role 2 للصيدلي)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pId = $_SESSION['user_id'];

// جلب الإحصائيات (عدد الأدوية الخاصة بهذه الصيدلية)
$medCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Medicine WHERE PharmacistID = $pId"))['c'];

// جلب عدد الطلبات الجديدة (Pending) الخاصة بهذه الصيدلية
// ملاحظة: سنحتاج لربط الطلبات بالصيدلية لاحقاً، حالياً سنفترض أنها مرتبطة عبر Medicine
$orderCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE Status = 'Pending'"))['c'];

include('../includes/header.php');
// ملاحظة: نحتاج مستقبلاً لـ sidebar خاص بالصيدلي (يمكننا نسخه وتعديله)
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">لوحة تحكم الصيدلية</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- كرت الأدوية -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 font-medium">عدد الأدوية المضافة</h3>
            <p class="text-4xl font-black text-blue-600 mt-2"><?php echo $medCount; ?></p>
        </div>

        <!-- كرت الطلبات -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 font-medium">طلبات جديدة</h3>
            <p class="text-4xl font-black text-amber-600 mt-2"><?php echo $orderCount; ?></p>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>