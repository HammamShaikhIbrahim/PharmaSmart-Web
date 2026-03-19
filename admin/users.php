<?php
// ==========================================
// 1. الإعدادات الأساسية والحماية
// ==========================================
include('../config/database.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

require_once('../includes/lang.php');

// ==========================================
// 2. معالجة عملية الحذف (Delete Patient)
// ==========================================
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM Chat WHERE SenderID = $id OR ReceiverID = $id");
    mysqli_query($conn, "DELETE FROM User WHERE UserID=$id AND RoleID=3");
    header("Location: users.php?msg=deleted");
    exit();
}

// ==========================================
// 3. نظام البحث وجلب بيانات المرضى
// ==========================================
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

// 💡 استخدمنا CONCAT لدمج الاسم الأول والأخير معاً ليتمكن الأدمن من البحث بالاسم الكامل
$query = "SELECT u.*, p.Address, 
          TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS Age 
          FROM User u 
          LEFT JOIN Patient p ON u.UserID = p.PatientID 
          WHERE u.RoleID = 3 AND (u.Fname LIKE '%$search%' OR u.Lname LIKE '%$search%' OR CONCAT(u.Fname, ' ', u.Lname) LIKE '%$search%')
          ORDER BY u.CreatedAt DESC";
$result = mysqli_query($conn, $query);

// 🚀 معالجة طلب AJAX للبحث المباشر
if (isset($_GET['ajax'])) {
    ob_start();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr class="hover:bg-[#F0F7FA] dark:hover:bg-[#011C3B]/50 transition-colors duration-200 border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                <td class="p-6">
                    <div class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <?php echo $lang['age']; ?>:
                        <span class="font-bold text-[#048AC1] dark:text-blue-400">
                            <?php echo ($row['Age'] !== null) ? $row['Age'] . ' ' . $lang['years'] : '-'; ?>
                        </span>
                    </div>
                </td>
                <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
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
                    <div class="flex justify-center">
                        <button onclick="confirmDelete(<?php echo $row['UserID']; ?>)" class="bin-button text-rose-500" title="حذف المريض">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M3 6h18" class="bin-top"></path>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" class="bin-top"></path>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" class="bin-bottom"></path>
                                <path d="M10 11v6" class="bin-bottom"></path>
                                <path d="M14 11v6" class="bin-bottom"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        <?php }
    } else { ?>
        <!-- 🚀 Empty State متحرك مستوحى من Uiverse (بالثيم الأزرق للأدمن) -->
        <tr>
            <td colspan="5" class="p-20">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative w-24 h-24 mb-6">
                        <!-- دائرة تنبض في الخلفية -->
                        <div class="absolute inset-0 bg-[#048AC1] rounded-full opacity-20 animate-ping"></div>
                        <!-- الأيقونة في المقدمة -->
                        <div class="relative flex items-center justify-center w-full h-full bg-blue-50 dark:bg-blue-900/40 rounded-full shadow-inner border border-blue-100 dark:border-blue-800/50">
                            <i data-lucide="users" class="w-10 h-10 text-[#048AC1]"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white mb-2">لا يوجد مرضى مطابقين</h3>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">تأكد من كتابة الاسم بشكل صحيح.</p>
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
    /* ستايل سلة المهملات المتحركة */
    .bin-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background-color: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0;
    }

    .bin-button:hover {
        background-color: rgba(225, 29, 72, 0.1);
    }

    .dark .bin-button:hover {
        background-color: rgba(225, 29, 72, 0.3);
    }

    .bin-bottom {
        transform-origin: bottom center;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bin-top {
        transform-origin: bottom right;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bin-button:hover .bin-top {
        transform: rotate(20deg) translateY(-2px);
    }
</style>

<!-- ==========================================
     بداية محتوى الصفحة (Main Content)
=========================================== -->
<main class="flex-1 p-8 bg-blue-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300 relative">

    <?php include('../includes/topbar.php'); ?>

    <!-- الترويسة وشريط البحث -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 shrink-0">
            <i data-lucide="users" class="text-[#048AC1]"></i> <?php echo $lang['patients']; ?>
        </h1>

        <!-- شريط البحث السريع (AJAX Live Search) -->
        <div class="w-full md:w-80">
            <div class="relative group">
                <!-- 💡 حدث oninput للبحث الحي -->
                <input type="text" id="searchInput" oninput="fetchTableData()" placeholder="<?php echo $lang['search_patient']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#048AC1] focus:border-[#048AC1] outline-none transition-all text-sm">
                <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#048AC1] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-4' : 'absolute right-4'; ?> w-5 h-5"></i>
            </div>
        </div>

    </div>

    <!-- 💡 الجدول ديناميكي الحجم (بدون min-h-[500px]) ليناسب النتائج تماماً -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden mb-6">
        <div class="overflow-x-auto" id="tableContainer" style="transition: opacity 0.3s ease;">
            <table class="w-full border-collapse min-w-[800px]">
                <thead id="tableHeader" class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700 text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <tr>
                        <th class="p-6 font-bold whitespace-nowrap"><?php echo $lang['patient_name']; ?></th>
                        <th class="p-6 font-bold whitespace-nowrap"><?php echo $lang['contact_info']; ?></th>
                        <th class="p-6 font-bold min-w-[200px]"><?php echo $lang['address']; ?></th>
                        <th class="p-6 font-bold text-center whitespace-nowrap"><?php echo $lang['join_date']; ?></th>
                        <th class="p-6 font-bold text-center whitespace-nowrap"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <!-- سيتم ملؤه بواسطة الـ AJAX -->
                <tbody id="patientsBody" class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <!-- الفراغ يعبأ عبر الجافاسكربت -->
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- ==========================================
     الجافاسكربت للتنبيهات والأجاكس
=========================================== -->
<script>
    let fetchTimeoutId;

    // 🚀 دالة جلب بيانات الجدول عبر AJAX بدون Reload
    async function fetchTableData() {
        const body = document.getElementById('patientsBody');
        const header = document.getElementById('tableHeader');
        const container = document.getElementById('tableContainer');
        const search = document.getElementById('searchInput').value;

        // 1. تأثير تحميل ناعم
        container.style.opacity = '0.3';
        container.style.pointerEvents = 'none';

        // 2. تحديث الرابط في المتصفح بسلاسة
        const newUrl = `?search=${encodeURIComponent(search)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        // 3. تأخير (Debounce) لمنع الضغط على السيرفر
        clearTimeout(fetchTimeoutId);
        fetchTimeoutId = setTimeout(async () => {
            try {
                const response = await fetch(`users.php?ajax=1&search=${encodeURIComponent(search)}`);
                const data = await response.json();

                body.innerHTML = data.html;
                header.style.display = data.has_data ? '' : 'none'; // إخفاء رأس الجدول إن لم يكن هناك بيانات

                lucide.createIcons();

            } catch (error) {
                console.error("Error fetching patients:", error);
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }, 300);
    }

    // جلب البيانات فور تحميل الصفحة لأول مرة 
    document.addEventListener('DOMContentLoaded', fetchTableData);


    // 🚀 دالة التأكيد (SweetAlert)
    function confirmDelete(id) {
        Swal.fire({
            title: Lang.title,
            text: Lang.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.confirm,
            cancelButtonText: Lang.cancel,
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'users.php?delete_id=' + id;
            }
        });
    }
</script>

<?php include('../includes/footer.php'); ?>