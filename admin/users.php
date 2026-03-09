<?php
// ==========================================
// 1. الإعدادات الأساسية والحماية
// ==========================================
include('../config/database.php'); // الاتصال بقاعدة البيانات
session_start(); // تشغيل الجلسة

// حماية الصفحة: إذا مش مسجل دخول أو مش أدمن (RoleID=1)، اطرده لصفحة الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

// ==========================================
// 2. معالجة عملية الحذف (Delete Patient)
// ==========================================
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // 💡 الحل هنا: نقوم بحذف أي رسائل (Chat) أرسلها أو استقبلها هذا المريض أولاً
    // حتى لا تمنعنا قاعدة البيانات من حذفه
    mysqli_query($conn, "DELETE FROM Chat WHERE SenderID = $id OR ReceiverID = $id");

    // بعد تنظيف رسائله، نقوم بحذفه من جدول المستخدمين بأمان
    mysqli_query($conn, "DELETE FROM User WHERE UserID=$id AND RoleID=3");

    header("Location: users.php?msg=deleted");
    exit();
}

// ==========================================
// 3. نظام البحث وجلب بيانات المرضى (مدمج وذكي)
// ==========================================
// تنظيف نص البحث من أي أكواد خبيثة
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

// 💡 الاستعلام الذكي (بنجيب كل المرضى أو بنفلتر حسب البحث):
// بنجيب بيانات المستخدم (u) وبنجيب عنوانه من جدول المرضى (p)
// وبنحسب عمره برمجياً من تاريخ ميلاده (DOB)
$query = "SELECT u.*, p.Address, 
          TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS Age 
          FROM User u 
          LEFT JOIN Patient p ON u.UserID = p.PatientID 
          WHERE u.RoleID = 3 AND (u.Fname LIKE '%$search%' OR u.Lname LIKE '%$search%')
          ORDER BY u.CreatedAt DESC"; // الترتيب: أحدث مريض سجل بيظهر أول واحد
$result = mysqli_query($conn, $query);

// استدعاء ملفات التصميم
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- ==========================================
     تصميم الصفحة (الـ HTML و Tailwind)
=========================================== -->
<main class="flex-1 p-8 bg-blue-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <?php include('../includes/topbar.php'); ?>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <!-- 💡 أيقونة مستخدمون بلون أزرق (#048AC1) -->
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="users" class="text-[#048AC1]"></i> <?php echo $lang['patients']; ?>
        </h1>

        <!-- فورم البحث مع فوكس أزرق -->
        <form method="GET" class="w-full md:w-96">
            <div class="relative group">
                <!-- 💡 تحسين وضوح مربع البحث بحدود زرقاء عند التركيز -->
                <input type="text" name="search" placeholder="<?php echo $lang['search_patient']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#048AC1] focus:border-[#048AC1] outline-none transition-all">
                <!-- 💡 تغيير لون الأيقونة عند التركيز -->
                <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#048AC1] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?>"></i>
            </div>
        </form>
    </div>

    <!-- 💡 تحسين تباين الحواف والظل ليكون واضحاً للعين في الوضع النهاري -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden transition-colors">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <table class="w-full border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700">
                    <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-6 font-bold"><?php echo $lang['patient_name']; ?></th>
                        <th class="p-6 font-bold"><?php echo $lang['contact_info']; ?></th>
                        <th class="p-6 font-bold"><?php echo $lang['address']; ?></th>
                        <th class="p-6 font-bold text-center"><?php echo $lang['join_date']; ?></th>
                        <th class="p-6 font-bold text-center"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <!-- 💡 تم تغيير لون الهوفر ليصبح أزرق خفيف متناسق مع الإدارة -->
                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-150">
                            <td class="p-6">
                                <div class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo $lang['age']; ?>:
                                    <!-- 💡 تم تغيير لون العمر إلى أزرق (#048AC1) ليتطابق مع ثيم الإدمن -->
                                    <span class="font-bold text-[#048AC1] dark:text-blue-400">
                                        <?php echo ($row['Age'] !== null) ? $row['Age'] . ' ' . $lang['years'] : '-'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                                <!-- 💡 توحيد لون الأيقونات للأزرق (#048AC1) -->
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="mail" class="w-4 h-4 text-[#048AC1]"></i>
                                    <span><?php echo htmlspecialchars($row['Email']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="w-4 h-4 text-[#048AC1]"></i>
                                    <span dir="ltr"><?php echo htmlspecialchars($row['Phone'] ?? $lang['not_specified']); ?></span>
                                </div>
                            </td>
                            <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-[#048AC1]"></i>
                                    <span><?php echo htmlspecialchars($row['Address'] ?? $lang['not_specified']); ?></span>
                                </div>
                            </td>
                            <td class="p-6 text-sm text-gray-500 dark:text-gray-400 text-center">
                                <span dir="ltr"><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <!-- 💡 زر الحذف بلون أحمر مع هوفر -->
                                <button onclick="confirmDelete(<?php echo $row['UserID']; ?>)" class="text-rose-500 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 transition p-2.5 rounded-xl" title="Delete">
                                    <i data-lucide="trash" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- حالة عدم وجود بيانات (Empty State) -->
            <div class="p-16 text-center text-gray-500 flex flex-col items-center">
                <!-- 💡 أيقونة مستخدمون برتقالية فاتحة للدلالة على عدم وجود بيانات -->
                <i data-lucide="users-round" class="w-16 h-16 mb-4 opacity-50 text-[#048AC1]"></i>
                <p class="text-lg font-bold"><?php echo isset($lang['no_data']) ? $lang['no_data'] : 'لا يوجد بيانات'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- ==========================================
     الجافاسكربت للتنبيهات والأيقونات
=========================================== -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: Lang.title, // مأخوذة من متغيرات الـ Footer للترجمة
            text: Lang.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.confirm,
            cancelButtonText: Lang.cancel,
            // تنسيق الوضع الليلي المضمون
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                // توجيه لصفحة الحذف مع إرسال الـ ID
                window.location.href = 'users.php?delete_id=' + id;
            }
        });
    }

    lucide.createIcons(); // تفعيل أيقونات Lucide
</script>

<?php include('../includes/footer.php'); ?>