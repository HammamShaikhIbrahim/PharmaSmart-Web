<?php

// ==========================================
// 1. الإعدادات الأساسية والحماية
// ==========================================
include('../config/database.php');
session_start();

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

// استدعاء ملف اللغة (ضروري جداً قبل أي عمليات)
require_once('../includes/lang.php');

// ==========================================
// 2. معالجة الإجراءات (Actions)
// ==========================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'approve') {
        mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 1 WHERE PharmacistID = $id");
    } elseif ($_GET['action'] == 'suspend') {
        mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 0 WHERE PharmacistID = $id");
    } elseif ($_GET['action'] == 'delete' || $_GET['action'] == 'reject') {
        mysqli_query($conn, "DELETE FROM Chat WHERE SenderID = $id OR ReceiverID = $id");
        mysqli_query($conn, "DELETE FROM User WHERE UserID = $id");
    }
    header("Location: pharmacies.php");
    exit();
}

// ==========================================
// 3. منطق جلب البيانات (لدعم AJAX والتحميل الأولي)
// ==========================================
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
// الفلتر الافتراضي هو "all"
$status_filter = $_GET['status'] ?? 'all';

// بناء شرط الحالة
$status_condition = "";
if ($status_filter == 'active') {
    $status_condition = "AND p.IsApproved = 1";
} elseif ($status_filter == 'pending') {
    $status_condition = "AND p.IsApproved = 0";
}

$query = "SELECT u.UserID, u.Fname, u.Lname, u.Phone, u.Email, u.CreatedAt, p.*
          FROM User u
          JOIN Pharmacist p ON u.UserID = p.PharmacistID
          WHERE (p.PharmacyName LIKE '%$search%' OR u.Fname LIKE '%$search%')
          $status_condition
          ORDER BY p.IsApproved ASC, u.CreatedAt DESC";

$result = mysqli_query($conn, $query);

// 🚀 معالجة طلب AJAX
if (isset($_GET['ajax'])) {
    ob_start();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr class="hover:bg-blue-50 dark:hover:bg-[#011C3B]/50 transition-colors duration-200 group cursor-pointer border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                <td class="p-6">
                    <div class="flex items-center gap-3">
                        <img src="../uploads/<?php echo $row['Logo'] ? $row['Logo'] : 'default.png'; ?>" class="w-12 h-12 rounded-xl border border-gray-200 dark:border-slate-600 object-cover shadow-sm bg-white">
                        <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['PharmacyName']); ?></span>
                    </div>
                </td>
                <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium"><?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?></td>
                <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">
                    <div class="flex items-center gap-2 mb-1"><i data-lucide="mail" class="w-4 h-4 text-[#048AC1]"></i><span><?php echo htmlspecialchars($row['Email']); ?></span></div>
                    <div class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 text-[#048AC1]"></i><span dir="ltr"><?php echo htmlspecialchars($row['Phone']); ?></span></div>
                </td>
                <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center gap-2 mb-1"><i data-lucide="map-pin" class="w-4 h-4 text-[#048AC1]"></i><span><?php echo htmlspecialchars($row['Location']); ?></span></div>
                    <div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-[#048AC1]"></i><span><?php echo htmlspecialchars($row['WorkingHours']); ?></span></div>
                </td>
                <td class="p-6 text-center">
                    <?php if ($row['IsApproved'] == 0): ?>
                        <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800 px-4 py-1.5 rounded-full text-xs font-bold min-w-[80px] inline-block shadow-sm">
                            <?php echo isset($lang['pending']) ? $lang['pending'] : 'معلق'; ?>
                        </span>
                    <?php else: ?>
                        <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 px-4 py-1.5 rounded-full text-xs font-bold min-w-[80px] inline-block shadow-sm">
                            <?php echo isset($lang['active']) ? $lang['active'] : 'نشط'; ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td class="p-6 text-center">
                    <div class="flex justify-center items-center gap-1">
                        <?php if ($row['IsApproved'] == 0): ?>
                            <a href="pharmacies.php?action=approve&id=<?php echo $row['PharmacistID']; ?>" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" title="<?php echo $lang['approve_activate']; ?>"><i data-lucide="check-circle-2" class="w-5 h-5"></i></a>
                            <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'reject')" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="<?php echo $lang['reject_request']; ?>">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        <?php else: ?>
                            <button onclick="confirmAction(<?php echo $row['PharmacistID']; ?>, 'suspend')" class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" title="<?php echo $lang['suspend_temp']; ?>"><i data-lucide="pause-circle" class="w-5 h-5"></i></button>
                            <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'delete')" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="<?php echo $lang['delete_permanently']; ?>">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php }
    } else { ?>
        <tr>
            <td colspan="6" class="p-20">
                <!-- 🚀 Empty State متحرك مستوحى من Uiverse (بالثيم الأزرق للأدمن) -->
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative w-24 h-24 mb-6">
                        <!-- دائرة تنبض في الخلفية -->
                        <div class="absolute inset-0 bg-[#048AC1] rounded-full opacity-20 animate-ping"></div>
                        <!-- الأيقونة في المقدمة -->
                        <div class="relative flex items-center justify-center w-full h-full bg-blue-50 dark:bg-blue-900/40 rounded-full shadow-inner border border-blue-100 dark:border-blue-800/50">
                            <i data-lucide="search-x" class="w-10 h-10 text-[#048AC1]"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white mb-2"><?php echo $lang['no_matching_pharmacies']; ?></h3>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400"><?php echo $lang['try_changing_search']; ?></p>
                </div>
            </td>
        </tr>
<?php }
    $content = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['html' => $content, 'has_data' => mysqli_num_rows($result) > 0]);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
    /* =========================================
       الفلتر الزجاجي بـ 3 خيارات
       ========================================= */
    .glass-radio-group {
        display: flex;
        position: relative;
        background-color: #ffffff;
        border-radius: 1rem;
        padding: 4px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.05);
        width: fit-content;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .dark .glass-radio-group {
        background-color: #0f172a;
        border-color: #1e293b;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
    }

    .glass-radio-group input {
        display: none;
    }

    .glass-radio-group label {
        flex: 1 1 0%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        font-size: 14px;
        padding: 0.6rem 1.2rem;
        cursor: pointer;
        font-weight: 800;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease-in-out;
        border-radius: 0.8rem;
        color: #64748b;
        white-space: nowrap;
    }

    .dark .glass-radio-group label {
        color: #94a3b8;
    }

    label[for="filter-all"]:hover { color: #048AC1; }
    label[for="filter-active"]:hover { color: #10b981; }
    label[for="filter-pending"]:hover { color: #f59e0b; }

    .glass-radio-group input:checked+label {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    .glass-glider {
        position: absolute;
        top: 4px;
        bottom: 4px;
        width: calc((100% - 8px) / 3);
        border-radius: 0.7rem;
        z-index: 1;
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), background 0.4s ease, box-shadow 0.4s ease;
    }

    #filter-all:checked~.glass-glider {
        transform: translateX(0%);
        background: #048AC1;
        box-shadow: 0 4px 10px rgba(4, 138, 193, 0.3);
    }

    #filter-active:checked~.glass-glider {
        transform: translateX(100%);
        background: #10b981;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }

    #filter-pending:checked~.glass-glider {
        transform: translateX(200%);
        background: #f59e0b;
        box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
    } 
    

    html[dir="rtl"] #filter-active:checked~.glass-glider { transform: translateX(-100%); }
    html[dir="rtl"] #filter-pending:checked~.glass-glider { transform: translateX(-200%); }
</style>

<main class="flex-1 p-8 bg-blue-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <div class="mb-8 flex flex-col xl:flex-row justify-between items-center gap-6">

        <!-- عنوان الصفحة -->
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 shrink-0 w-full xl:w-auto">
            <i data-lucide="hospital" class="text-[#048AC1]"></i> <?php echo $lang['pharmacies']; ?>
        </h1>

        <!-- أدوات التحكم (الفلتر + البحث المباشر) -->
        <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto justify-end">

            <!-- 🚀 البحث السريع (AJAX Live Search) -->
            <div class="w-full md:w-80">
                <div class="relative group">
                    <!-- 💡 استدعاء دالة fetchTableData() عند كل حرف يكتب (oninput) للبحث المباشر الحي -->
                    <input type="text" id="searchInput" oninput="fetchTableData()" placeholder="<?php echo $lang['search_pharmacy']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#048AC1] outline-none transition-all text-sm">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#048AC1] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-4' : 'absolute right-4'; ?> w-5 h-5"></i>
                </div>
            </div>

            <!-- الفلتر الزجاجي -->
            <div class="overflow-x-auto custom-scrollbar pb-2 -mb-2 w-full md:w-auto">
                <div class="glass-radio-group shrink-0 mx-auto md:mx-0">
                    <!-- 💡 يتم استدعاء دالة تحديث الجدول مع تغيير الحالة -->
                    <input type="radio" name="status" id="filter-all" value="all" onchange="fetchTableData()" <?php echo ($status_filter == 'all') ? 'checked' : ''; ?> />
                    <label for="filter-all"><?php echo isset($lang['filter_all']) ? $lang['filter_all'] : 'الكل'; ?></label>

                    <input type="radio" name="status" id="filter-active" value="active" onchange="fetchTableData()" <?php echo ($status_filter == 'active') ? 'checked' : ''; ?> />
                    <label for="filter-active"><?php echo $lang['active']; ?></label>

                    <input type="radio" name="status" id="filter-pending" value="pending" onchange="fetchTableData()" <?php echo ($status_filter == 'pending') ? 'checked' : ''; ?> />
                    <label for="filter-pending"><?php echo $lang['pending']; ?></label>

                    <div class="glass-glider"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- الجدول (ديناميكي الحجم يتناسب مع عدد النتائج) -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden mb-6">
        <div class="overflow-x-auto" id="tableContainer" style="transition: opacity 0.3s ease;">
            <table class="w-full border-collapse min-w-[800px]">
                <thead id="tableHeader" class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700 text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <tr>
                        <th class="p-6 font-bold whitespace-nowrap"><?php echo $lang['pharmacy_name']; ?></th>
                        <th class="p-6 font-bold whitespace-nowrap"><?php echo $lang['owner']; ?></th>
                        <th class="p-6 font-bold whitespace-nowrap"><?php echo isset($lang['contact_info']) ? $lang['contact_info'] : 'الاتصال'; ?></th>
                        <th class="p-6 font-bold min-w-[200px]"><?php echo $lang['location_work']; ?></th>
                        <th class="p-6 font-bold text-center whitespace-nowrap"><?php echo $lang['status']; ?></th>
                        <th class="p-6 font-bold text-center whitespace-nowrap"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <!-- 💡 محتوى الجدول الذي سيتم تحديثه عبر الـ AJAX -->
                <tbody id="pharmaciesBody" class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <!-- سيتم ملؤه بواسطة الجافاسكربت فور تحميل الصفحة -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // ==========================================
    // 💡 دالة الـ AJAX للبحث المباشر (Live Search) مع Debounce
    // ==========================================
    let fetchTimeoutId; // متغير لإيقاف الطلبات المتكررة أثناء الكتابة السريعة

    async function fetchTableData() {
        const body = document.getElementById('pharmaciesBody');
        const header = document.getElementById('tableHeader');
        const container = document.getElementById('tableContainer');
        const status = document.querySelector('input[name="status"]:checked').value;
        const search = document.getElementById('searchInput').value;

        // 1. تأثير تحميل ناعم (بهتان الجدول)
        container.style.opacity = '0.3';
        container.style.pointerEvents = 'none';

        // 2. تحديث الرابط في المتصفح لتبدو كصفحة احترافية
        const newUrl = `?status=${status}&search=${encodeURIComponent(search)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        // 3. تأخير الطلب قليلاً (Debounce) لمنع إرهاق السيرفر عند الكتابة بسرعة
        clearTimeout(fetchTimeoutId);
        fetchTimeoutId = setTimeout(async () => {
            try {
                // إرسال الطلب للسيرفر مع إضافة معامل ajax=1
                const response = await fetch(`pharmacies.php?ajax=1&status=${status}&search=${encodeURIComponent(search)}`);
                const data = await response.json();

                // تحديث HTML الجدول الداخلي فقط
                body.innerHTML = data.html;
                header.style.display = data.has_data ? '' : 'none'; // إخفاء رأس الجدول إن لم يكن هناك بيانات

                // إعادة تفعيل أيقونات Lucide للعناصر الجديدة
                lucide.createIcons();

            } catch (error) {
                console.error("Error fetching pharmacies:", error);
            } finally {
                // إعادة الجدول لوضعه الطبيعي بعد الانتهاء
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }, 300); // الانتظار 300 ملي ثانية
    }

    // جلب البيانات فور تحميل الصفحة لأول مرة (لضمان عمل الألوان والأيقونات بشكل صحيح)
    document.addEventListener('DOMContentLoaded', fetchTableData);

    // ==========================================
    // 💡 دوال أزرار الإجراءات (SweetAlert)
    // ==========================================
    function confirmAction(id, type) {
        let modalTitle = Lang.title;
        let modalText = Lang.text;
        let modalBtn = Lang.confirm;
        let btnColor = '#ef4444';

        if (type === 'suspend') {
            modalTitle = Lang.suspendTitle;
            modalText = Lang.suspendText;
            modalBtn = Lang.suspendConfirm;
            btnColor = '#f59e0b'; // لون برتقالي للتعليق
        }

        Swal.fire({
            title: modalTitle,
            text: modalText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: modalBtn,
            cancelButtonText: Lang.cancel,
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((res) => {
            if (res.isConfirmed) window.location.href = `pharmacies.php?action=${type}&id=${id}`;
        });
    }
</script>

<?php include('../includes/footer.php'); ?>