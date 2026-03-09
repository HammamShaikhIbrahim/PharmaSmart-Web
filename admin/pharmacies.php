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

    // حذف نهائي أو رفض الطلب من البداية

    elseif ($_GET['action'] == 'delete' || $_GET['action'] == 'reject') {



        // 💡 الحل هنا: نحذف جميع رسائل الصيدلية أولاً لمنع تعارض قاعدة البيانات

        mysqli_query($conn, "DELETE FROM Chat WHERE SenderID = $id OR ReceiverID = $id");



        // ثم نحذف المستخدم (الصيدلية) بأمان

        mysqli_query($conn, "DELETE FROM User WHERE UserID = $id");
    }



    // بعد ما نخلص الأكشن، بنعمل تحديث (Refresh) للصفحة عشان الجدول يتحدث وتختفي المتغيرات من الرابط

    header("Location: pharmacies.php");

    exit();
}



// ==========================================

// 3. نظام البحث المخصص وجلب البيانات

// ==========================================

$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');



// 💡 أضفنا u.Email في السطر الأول من الاستعلام ليتم جلبه من الداتابيز

$query = "SELECT u.UserID, u.Fname, u.Lname, u.Phone, u.Email, u.CreatedAt, p.*

          FROM User u

          JOIN Pharmacist p ON u.UserID = p.PharmacistID

          WHERE p.PharmacyName LIKE '%$search%' OR u.Fname LIKE '%$search%'

          ORDER BY p.IsApproved ASC";



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

        <!-- 💡 أيقونة مستشفى بلون أزرق (#048AC1) -->

        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">

            <i data-lucide="hospital" class="text-[#048AC1]"></i> <?php echo $lang['pharmacies']; ?>

        </h1>



        <!-- فورم البحث مع فوكس أزرق -->

        <form method="GET" class="w-full md:w-96">

            <div class="relative group">

                <input type="text" name="search" placeholder="<?php echo $lang['search_pharmacy']; ?>" value="<?php echo htmlspecialchars($search); ?>"

                    class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#048AC1] focus:border-[#048AC1] outline-none transition-all">

                <!-- 💡 تغيير لون الأيقونة عند التركيز -->

                <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#048AC1] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?>"></i>

            </div>

        </form>

    </div>



    <!-- 💡 تحسين الحواف والظلال بألوان موحدة -->

    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden transition-colors">

        <table class="w-full border-collapse">

            <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700 ">

                <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">

                    <th class="p-6 font-bold"><?php echo $lang['pharmacy_name']; ?></th>

                    <th class="p-6 font-bold"><?php echo $lang['owner']; ?></th>

                    <th class="p-6 font-bold"><?php echo isset($lang['contact_info']) ? $lang['contact_info'] : 'الاتصال'; ?></th>

                    <th class="p-6 font-bold"><?php echo $lang['location_work']; ?></th>

                    <th class="p-6 font-bold text-center"><?php echo $lang['status']; ?></th>

                    <th class="p-6 font-bold text-center"><?php echo $lang['actions']; ?></th>

                </tr>

            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <!-- 💡 هوفر أزرق خفيف (#048AC1 مع opacity) -->

                    <tr class="transition-all duration-300 hover:bg-blue-50 dark:hover:bg-blue-900/20">



                        <!-- 1. اسم الصيدلية والشعار -->

                        <td class="p-6">

                            <div class="flex items-center gap-3">

                                <img src="../uploads/<?php echo $row['Logo'] ? $row['Logo'] : 'default.png'; ?>" class="w-12 h-12 rounded-xl border border-gray-200 dark:border-slate-600 object-cover shadow-sm bg-white">

                                <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['PharmacyName']); ?></span>

                            </div>

                        </td>



                        <!-- 2. اسم المالك (الاسم الأول + الأخير) -->

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">

                            <?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?>

                        </td>



                        <!-- 3. بيانات الاتصال (إيميل وتلفون) -->

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">

                            <!-- 💡 توحيد الأيقونات بلون أزرق (#048AC1) -->

                            <div class="flex items-center gap-2 mb-1">

                                <i data-lucide="mail" class="w-4 h-4 text-[#048AC1]"></i>

                                <span><?php echo htmlspecialchars($row['Email']); ?></span>

                            </div>

                            <div class="flex items-center gap-2">

                                <i data-lucide="phone" class="w-4 h-4 text-[#048AC1]"></i>

                                <span dir="ltr"><?php echo htmlspecialchars($row['Phone']); ?></span>

                            </div>

                        </td>



                        <!-- 4. الموقع وساعات العمل -->

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300">

                            <div class="flex items-center gap-2 mb-1">

                                <i data-lucide="map-pin" class="w-4 h-4 text-[#048AC1]"></i>

                                <span><?php echo htmlspecialchars($row['Location']); ?></span>

                            </div>

                            <div class="flex items-center gap-2">

                                <i data-lucide="clock" class="w-4 h-4 text-[#048AC1]"></i>

                                <span><?php echo htmlspecialchars($row['WorkingHours']); ?></span>

                            </div>

                        </td>



                        <!-- 5. حالة الصيدلية (مفعلة ولا معلقة) -->

                        <td class="p-6 text-center">

                            <?php if ($row['IsApproved'] == 0): ?>

                                <!-- معلق: برتقالي -->

                                <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 px-4 py-1.5 rounded-full text-xs font-bold min-w-[80px] inline-block shadow-sm"><?php echo $lang['pending']; ?></span>

                            <?php else: ?>

                                <!-- مفعل: أخضر -->

                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-4 py-1.5 rounded-full text-xs font-bold min-w-[80px] inline-block shadow-sm"><?php echo $lang['active']; ?></span>

                            <?php endif; ?>

                        </td>



                        <!-- 6. الإجراءات (أزرار التحكم) -->

                        <td class="p-6 text-center">

                            <div class="flex justify-center items-center gap-2">

                                <?php if ($row['IsApproved'] == 0): ?>

                                    <!-- زر القبول: أخضر -->

                                    <a href="pharmacies.php?action=approve&id=<?php echo $row['PharmacistID']; ?>" class="text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 transition p-2.5 rounded-xl" title="Approve">

                                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>

                                    </a>

                                    <!-- زر الرفض: أحمر -->

                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'reject')" class="text-rose-500 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 transition p-2.5 rounded-xl" title="Reject">

                                        <i data-lucide="trash" class="w-5 h-5"></i>

                                    </button>

                                <?php else: ?>

                                    <!-- زر التعليق: برتقالي -->

                                    <button onclick="confirmAction(<?php echo $row['PharmacistID']; ?>, 'suspend')" class="text-amber-600 bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-800/50 transition p-2.5 rounded-xl" title="Suspend">

                                        <i data-lucide="pause-circle" class="w-5 h-5"></i>

                                    </button>

                                    <!-- زر الحذف: أحمر -->

                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'delete')" class="text-rose-500 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 transition p-2.5 rounded-xl" title="Delete">

                                        <i data-lucide="trash" class="w-5 h-5"></i>

                                    </button>

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