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
// 2. معالجة الإجراءات (Actions: قبول، رفض، تعليق، حذف)
// ==========================================
// بنفحص إذا الرابط فيه كلمة action و id (مثال: pharmacies.php?action=approve&id=5)
if (isset($_GET['action']) && isset($_GET['id'])) {

    // intval: بتحول الـ ID لرقم صحيح بس (حماية ممتازة عشان ما حد يبعت نصوص خبيثة بالرابط)
    $id = intval($_GET['id']);

    // قبول: بنعدل حالة الصيدلية لـ 1 (مفعل)
    if ($_GET['action'] == 'approve') {
        mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 1 WHERE PharmacistID = $id");
    }
    // تعليق (توقيف مؤقت): بنرجع حالة الصيدلية لـ 0 (معلق)
    elseif ($_GET['action'] == 'suspend') {
        mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 0 WHERE PharmacistID = $id");
    }
    // حذف نهائي أو رفض الطلب من البداية: بنحذف "المستخدم" كله من الداتابيز
    elseif ($_GET['action'] == 'delete' || $_GET['action'] == 'reject') {
        mysqli_query($conn, "DELETE FROM User WHERE UserID = $id");
    }

    // بعد ما نخلص الأكشن، بنعمل تحديث (Refresh) للصفحة عشان الجدول يتحدث وتختفي المتغيرات من الرابط
    header("Location: pharmacies.php");
    exit();
}

// ==========================================
// 3. نظام البحث المخصص وجلب البيانات
// ==========================================
// mysqli_real_escape_string: تنظيف النص اللي كتبه اليوزر في مربع البحث عشان نمنع الاختراق (SQL Injection)
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

// الاستعلام: بنربط (JOIN) جدول المستخدمين بجدول الصيادلة عشان نجيب الاسم ورقم التلفون وتفاصيل الصيدلية
// وبنبحث باستخدام كلمة LIKE إذا الاسم بيشبه الكلمة اللي انكتبت بالبحث
$query = "SELECT u.UserID, u.Fname, u.Lname, u.Phone, u.CreatedAt, p.* 
          FROM User u 
          JOIN Pharmacist p ON u.UserID = p.PharmacistID 
          WHERE p.PharmacyName LIKE '%$search%' OR u.Fname LIKE '%$search%'
          ORDER BY p.IsApproved ASC"; // الترتيب: الصيدليات المعلقة بتظهر أول (عشان الأدمن ينتبه لها)
$result = mysqli_query($conn, $query);

// استدعاء ملفات التصميم
include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- ==========================================
     تصميم الصفحة (الـ HTML و Tailwind)
=========================================== -->
<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <!-- الترويسة مع شريط البحث -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="hospital" class="text-blue-500"></i> <?php echo $lang['pharmacies']; ?>
        </h1>

        <!-- فورم البحث (بيرسل البيانات بطريقة GET عشان تنطبع بالرابط ونقدر نستخدمها فوق) -->
        <form method="GET" class="w-full md:w-96">
            <div class="relative">
                <!-- htmlspecialchars: عشان لو اليوزر بحث بكود جافاسكربت خبيث، المتصفح يعتبره نص عادي وما ينفذه -->
                <input type="text" name="search" placeholder="ابحث عن صيدلية بالاسم..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <i data-lucide="search" class="absolute left-3 top-3.5 text-gray-400"></i>
            </div>
        </form>
    </div>

    <!-- جدول عرض الصيدليات -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors">
        <table class="w-full border-collapse">
            <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700 ">
                <!-- بنغير اتجاه النص بالجدول حسب لغة الموقع (يمين للعربي، يسار للإنجليزي) -->
                <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <!-- أسماء الأعمدة (مربوطة بملف الترجمة lang.php) -->
                    <th class="p-6 font-bold"><?php echo $lang['pharmacy_name']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['owner']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['phone']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['location_work']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['join_date']; ?></th>
                    <th class="p-6 font-bold text-center"><?php echo $lang['status']; ?></th>
                    <th class="p-6 font-bold text-center"><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">

                <!-- حلقة التكرار (While): بنلف على نتيجة البحث من الداتابيز وبنرسم صف لكل صيدلية -->
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr id="row_<?php echo $row['UserID']; ?>" class="transition-all duration-500 hover:bg-gray-100 dark:hover:bg-slate-700/50 transition">

                        <!-- 1. الشعار واسم الصيدلية -->
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <!-- لو الصيدلية ما الها صورة، بنعرض الصورة الافتراضية default.png -->
                                <img src="../uploads/<?php echo $row['Logo'] ? $row['Logo'] : 'default.png'; ?>"
                                    class="w-12 h-12 rounded-full border dark:border-slate-600 object-cover shadow-sm">
                                <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['PharmacyName']); ?></span>
                            </div>
                        </td>

                        <!-- 2. اسم المالك (الاسم الأول + الأخير) -->
                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">
                            <?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?>
                        </td>

                        <!-- 3. رقم الهاتف -->
                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="phone" class="w-4 h-4 text-emerald-500"></i>
                                <?php echo htmlspecialchars($row['Phone']); ?>
                            </div>
                        </td>

                        <!-- 4. الموقع وساعات العمل -->
                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                <span><?php echo htmlspecialchars($row['Location']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="clock" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                <span><?php echo htmlspecialchars($row['WorkingHours']); ?></span>
                            </div>
                        </td>

                        <!-- 5. تاريخ الانضمام -->
                        <td class="p-6 text-sm text-gray-500 dark:text-gray-400 text-center">
                            <!-- بنحول صيغة التاريخ الطويلة لشكل مرتب (سنة-شهر-يوم) -->
                            <span dir="ltr"><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?></span>
                        </td>

                        <!-- 6. حالة الصيدلية (مفعلة ولا معلقة) -->
                        <td class="p-6">
                            <!-- بنفحص قيمة IsApproved -->
                            <?php if ($row['IsApproved'] == 0): ?>
                                <!-- تصميم لون برتقالي للمعلق -->
                                <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 px-4 py-1.5 rounded-full text-xs font-bold border border-amber-200 dark:border-amber-700 inline-block text-center min-w-[80px]">
                                    <?php echo $lang['pending']; ?>
                                </span>
                            <?php else: ?>
                                <!-- تصميم لون أخضر للمفعل -->
                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-4 py-1.5 rounded-full text-xs font-bold border border-emerald-200 dark:border-emerald-700 inline-block text-center min-w-[80px]">
                                    <?php echo $lang['active']; ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- 7. الإجراءات (أزرار التحكم) -->
                        <td class="p-6 text-center">
                            <div class="flex justify-center items-center gap-2">
                                <!-- إذا الصيدلية معلقة (بنعرض زر "قبول" وزر "رفض") -->
                                <?php if ($row['IsApproved'] == 0): ?>
                                    <a href="pharmacies.php?action=approve&id=<?php echo $row['PharmacistID']; ?>" class="bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 p-2.5 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-800/50 transition"><i data-lucide="check"></i></a>
                                    <!-- دالة الجافاسكربت confirmAction بتفتح رسالة تأكيد (SweetAlert) قبل ما تعمل رفض -->
                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'reject')" class="bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-2.5 rounded-xl hover:bg-red-100 dark:hover:bg-red-800/50 transition"><i data-lucide="trash-2"></i></button>
                                <?php else: ?>
                                    <!-- إذا الصيدلية مفعلة (بنعرض زر "تعليق" وزر "حذف") -->
                                    <button onclick="confirmAction(<?php echo $row['PharmacistID']; ?>, 'suspend')" class="bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 p-2.5 rounded-xl hover:bg-amber-100 dark:hover:bg-amber-800/50 transition"><i data-lucide="pause"></i></button>
                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'delete')" class="bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-2.5 rounded-xl hover:bg-red-100 dark:hover:bg-red-800/50 transition"><i data-lucide="trash-2"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<!-- ==========================================
     أكواد الجافاسكربت (لرسائل التأكيد)
=========================================== -->
<script>
    // هذي الدالة بتستقبل الـ ID ونوع الأكشن (حذف، رفض، تعليق) وبناء عليه بتظهر التنبيه المناسب
    function confirmAction(id, type) {
        // قيم افتراضية (للحذف والرفض) جايبينها من متغير Lang اللي جهزناه بملف footer
        let modalTitle = Lang.title;
        let modalText = Lang.text;
        let modalBtn = Lang.confirm;
        let btnColor = '#ef4444'; // لون أحمر
        let iconType = 'warning';

        // إذا الأكشن "تعليق" (suspend)، بنغير النصوص واللون للبرتقالي
        if (type === 'suspend') {
            modalTitle = Lang.suspendTitle;
            modalText = Lang.suspendText;
            modalBtn = Lang.suspendConfirm;
            btnColor = '#f59e0b';
            iconType = 'question';
        }

        // إظهار التنبيه باستخدام مكتبة SweetAlert2
        Swal.fire({
            title: modalTitle,
            text: modalText,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: modalBtn,
            cancelButtonText: Lang.cancel,
            // تحديد لون خلفية التنبيه حسب وضع الموقع (Dark Mode أو Light)
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((res) => {
            // إذا ضغط "تأكيد"، بنحوله لرابط الأكشن وينفذ العملية بالـ PHP فوق
            if (res.isConfirmed) window.location.href = `pharmacies.php?action=${type}&id=${id}`;
        });
    }
    // تفعيل الأيقونات
    lucide.createIcons();
</script>
<?php include('../includes/footer.php'); ?>