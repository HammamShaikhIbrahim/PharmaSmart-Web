<?php
// ==========================================
// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// ==========================================
// 2. معالجة تحديث حالة الطلب (Actions)
// ==========================================
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];

    $valid_actions = ['Accepted', 'Rejected', 'Delivered'];

    if (in_array($action, $valid_actions)) {
        mysqli_query($conn, "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id");

        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }

        header("Location: orders.php");
        exit();
    }
}

// ==========================================
// 3. معالجة البحث والفلترة 
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
$search_query  = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// حساب الأعداد لكل فلتر
$counts_sql = "SELECT o.Status, COUNT(DISTINCT o.OrderID) as count 
                 FROM `Order` o 
                 JOIN OrderItems oi ON o.OrderID = oi.OrderID 
                 JOIN Medicine m ON oi.MedicineID = m.MedicineID 
                 WHERE m.PharmacistID = $pharmacist_id 
                 GROUP BY o.Status";
$counts_result = mysqli_query($conn, $counts_sql);

$status_counts = ['Pending' => 0, 'Accepted' => 0, 'Delivered' => 0, 'Rejected' => 0];
$total_all = 0;
while ($row = mysqli_fetch_assoc($counts_result)) {
    $status_counts[$row['Status']] = $row['count'];
    $total_all += $row['count'];
}
$status_counts['All'] = $total_all;

// شروط الاستعلام
$status_condition = ($filter_status !== 'All') ? "AND o.Status = '$filter_status'" : "";
$search_condition = "";

if (!empty($search_query)) {
    $search_condition = " AND (o.OrderID = '$search_query' OR u.Fname LIKE '%$search_query%' OR u.Lname LIKE '%$search_query%')";
}

// جلب الطلبات
$orders_query = "
    SELECT DISTINCT 
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress,
        u.UserID, u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE m.PharmacistID = $pharmacist_id $status_condition $search_condition
    ORDER BY 
        FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), 
        o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// جلب تفاصيل الأدوية
$order_items_data = [];
$items_query = "
    SELECT oi.OrderID, m.Name, oi.Quantity, oi.SoldPrice, m.IsControlled
    FROM OrderItems oi
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    WHERE m.PharmacistID = $pharmacist_id
";
$items_result = mysqli_query($conn, $items_query);
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items_data[$item['OrderID']][] = $item;
}

// تجميع الطلبات حسب المريض
$grouped_orders = [];
$has_orders = mysqli_num_rows($orders_result) > 0;
if ($has_orders) {
    while ($order = mysqli_fetch_assoc($orders_result)) {
        $grouped_orders[$order['UserID']][] = $order;
    }
}

// ==========================================
// 🚀 4. هندسة الـ AJAX (رسم الجدول برمجياً)
// ==========================================
ob_start();
if ($has_orders) {
    foreach ($grouped_orders as $user_id => $user_orders) {
        $has_multiple = count($user_orders) > 1;
        $group_id = 'user_group_' . $user_id;

        foreach ($user_orders as $index => $order) {
            $is_main_row = ($index === 0);

            $statusColor = 'bg-transparent border border-gray-400 text-gray-400';
            $statusIcon = 'circle';

            if ($order['Status'] == 'Pending') {
                $statusColor = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
                $statusIcon = 'clock-3';
            } elseif ($order['Status'] == 'Accepted') {
                $statusColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800';
                $statusIcon = 'package-open';
            } elseif ($order['Status'] == 'Delivered') {
                $statusColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
                $statusIcon = 'check-circle';
            } elseif ($order['Status'] == 'Rejected') {
                $statusColor = 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800';
                $statusIcon = 'x-circle';
            }

            $current_items = $order_items_data[$order['OrderID']] ?? [];
            $has_controlled = array_reduce($current_items, fn($carry, $item) => $carry || $item['IsControlled'] == 1, false);

            $order_json = htmlspecialchars(json_encode([
                'id' => $order['OrderID'],
                'date' => date('d M Y, h:i A', strtotime($order['OrderDate'])),
                'status' => $order['Status'],
                'total' => $order['TotalAmount'],
                'patient' => $order['Fname'] . ' ' . $order['Lname'],
                'phone' => $order['Phone'],
                'address' => $order['DeliveryAddress'],
                'items' => $current_items,
                'prescription' => $order['PrescriptionImage'],
                'has_controlled' => $has_controlled
            ]));

            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($order['Fname'] . ' ' . $order['Lname']) . '&background=e2e8f0&color=475569&rounded=true';

            // تدرجات الأخضر بدلاً من الأزرق
            $row_classes = $is_main_row
                ? "hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 group cursor-pointer border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700"
                : "sub-row-{$group_id} hidden bg-gray-50/50 dark:bg-slate-800/40 hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 cursor-pointer transition-all border-b border-transparent";

            $sub_indicator = !$is_main_row ? '<i data-lucide="corner-down-left" class="w-4 h-4 text-gray-500 inline-block mr-1 ml-1"></i>' : '';
?>
            <tr class="<?php echo $row_classes; ?>" onclick="viewOrderDetails('<?php echo $order_json; ?>')">

                <!-- عمود رقم الطلب -->
                <td class="p-5 whitespace-nowrap">
                    <div class="font-black text-gray-800 dark:text-white flex items-center gap-1.5" dir="ltr">
                        <span class="text-[#0A7A48] dark:text-[#4ADE80] text-xs font-black">#</span>ORD-<?php echo $order['OrderID']; ?>
                        <?php if (!$is_main_row) echo $sub_indicator; ?>
                    </div>
                </td>

                <!-- عمود التاريخ والوقت (مستقل) -->
                <td class="p-5 whitespace-nowrap">
                    <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">
                        <i data-lucide="calendar" class="w-4 h-4 text-[#0A7A48] shrink-0"></i>
                        <span><?php echo date('d M Y', strtotime($order['OrderDate'])); ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-[#0A7A48]/60 shrink-0"></i>
                        <span dir="ltr"><?php echo date('h:i A', strtotime($order['OrderDate'])); ?></span>
                    </div>
                </td>

                <!-- عمود العميل -->
                <td class="p-5 whitespace-nowrap">
                    <div class="flex items-center gap-3 mb-1.5">
                        <img src="<?php echo $avatarUrl; ?>" class="w-9 h-9 rounded-xl border border-gray-200 dark:border-slate-600 object-cover shadow-sm bg-white shrink-0">
                        <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4 text-[#0A7A48] shrink-0"></i>
                        <span dir="ltr"><?php echo htmlspecialchars($order['Phone'] ?? 'لا يوجد رقم'); ?></span>
                    </div>
                </td>

                <!-- عمود العنوان -->
                <td class="p-5">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 font-medium">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#0A7A48] shrink-0"></i>
                        <span class="leading-relaxed font-medium line-clamp-2"><?php echo htmlspecialchars($order['DeliveryAddress'] ?? 'الاستلام من الصيدلية'); ?></span>
                    </div>
                </td>

                <!-- عمود الأصناف مع مؤشر Rx -->
                <td class="p-5 text-center whitespace-nowrap">
                    <div class="flex flex-col items-center gap-1.5">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#0A7A48]/10 dark:bg-[#4ADE80]/10 text-[#0A7A48] dark:text-[#4ADE80] font-black text-[13px] shadow-sm border border-[#0A7A48]/15">
                            <?php echo count($current_items); ?>
                        </span>
                        <?php if ($has_controlled): ?>
                            <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 text-[9px] px-1.5 py-0.5 rounded-full font-black border border-amber-200 dark:border-amber-800 uppercase tracking-wide">Rx</span>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- عمود الإجمالي -->
                <td class="p-5 whitespace-nowrap text-center">
                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-[15px] mb-1.5" dir="ltr"><?php echo number_format($order['TotalAmount'], 2); ?> ₪</div>
                    <?php if ($order['PaymentMethod'] == 'COD'): ?>
                        <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 text-[10px] px-2 py-0.5 rounded-full font-bold border border-gray-200 dark:border-slate-600">
                            <i data-lucide="banknote" class="w-3 h-3"></i> COD
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] px-2 py-0.5 rounded-full font-bold border border-blue-100 dark:border-blue-800">
                            <i data-lucide="credit-card" class="w-3 h-3"></i> Card
                        </span>
                    <?php endif; ?>
                </td>

                <!-- عمود الحالة -->
                <td class="p-5 text-center whitespace-nowrap">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold inline-flex items-center justify-center gap-1.5 shadow-sm <?php echo $statusColor; ?>">
                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5"></i>
                        <?php
                        $statusLabels = [
                            'Pending'   => 'قيد الانتظار',
                            'Accepted'  => 'جاري التجهيز',
                            'Delivered' => 'مكتمل',
                            'Rejected'  => 'مرفوض',
                        ];
                        echo $statusLabels[$order['Status']] ?? $order['Status'];
                        ?>
                    </span>
                </td>

                <!-- عمود الإجراءات -->
                <td class="p-5 text-center">
                    <div class="flex justify-center items-center gap-1">
                        <?php if ($is_main_row && $has_multiple): ?>
                            <button class="px-3 py-1.5 bg-[#0A7A48]/10 dark:bg-[#4ADE80]/10 rounded-lg text-[#0A7A48] dark:text-[#4ADE80] hover:bg-[#0A7A48]/20 dark:hover:bg-[#4ADE80]/20 transition-colors flex items-center justify-center gap-1 mx-auto shadow-sm" onclick="event.stopPropagation(); toggleSubOrders('<?php echo $group_id; ?>', this)">
                                <span class="text-[12px] font-black"><?php echo count($user_orders) - 1; ?>+</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300 toggle-icon"></i>
                            </button>
                        <?php else: ?>
                            <button class="p-2 bg-[#0A7A48]/8 dark:bg-[#334155] rounded-lg text-[#0A7A48] dark:text-[#4ADE80] hover:bg-[#0A7A48] hover:text-white dark:hover:bg-[#0A7A48] dark:hover:text-white transition-colors shadow-sm border border-[#0A7A48]/20 dark:border-[#4ADE80]/10" onclick="event.stopPropagation(); viewOrderDetails('<?php echo $order_json; ?>')">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
    <?php
        }
    }
} else {
    ?>
    <!-- Empty State بنفس مقاسات الأنيميشن لصفحة الأدمن مع اللون الأخضر -->
    <tr>
        <td colspan="8" class="p-20">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="relative w-24 h-24 mb-6">
                    <!-- دائرة تنبض في الخلفية (أخضر) -->
                    <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                    <!-- الأيقونة في المقدمة -->
                    <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/20">
                        <i data-lucide="package-x" class="w-10 h-10 text-[#0A7A48]"></i>
                    </div>
                </div>
                <h3 class="text-lg font-black text-gray-800 dark:text-white mb-2"><?php echo isset($lang['no_orders_desc']) ? $lang['no_orders_desc'] : 'لا يوجد طلبات مطابقة للبحث أو الفلتر'; ?></h3>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">حاول تغيير كلمة البحث أو اختيار فلتر آخر.</p>
            </div>
        </td>
    </tr>
<?php
}
$rows_html = ob_get_clean();

// AJAX Response
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $rows_html,
        'counts' => $status_counts,
        'has_orders' => $has_orders
    ]);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- نفس أكواد CSS للفلاتر المخصصة -->
<style>
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
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 2px 8px rgba(0, 0, 0, 0.1);
        border-color: #1e293b;
    }

    .glass-radio-group input {
        display: none;
    }

    .glass-radio-group label {
        flex: 1 1 0%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 110px;
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
        gap: 6px;
    }

    .dark .glass-radio-group label {
        color: #94a3b8;
    }

    /* ألوان الهوفر لكل فلتر */
    label[for="filter-All"]:hover {
        color: #0A7A48;
    }

    label[for="filter-Pending"]:hover {
        color: #d97706;
    }

    label[for="filter-Accepted"]:hover {
        color: #2563eb;
    }

    label[for="filter-Delivered"]:hover {
        color: #059669;
    }

    /* النص يصبح أبيض دائماً عند التحديد (كما في pharmacies) */
    .glass-radio-group input:checked+label {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    /* عداد الحالة: يظهر فقط على المحدد */
    .status-count {
        display: none;
        background: rgba(255, 255, 255, 0.25);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 900;
        backdrop-filter: blur(4px);
        color: #fff;
    }

    .glass-radio-group input:checked+label .status-count {
        display: inline-flex;
    }

    /* شريط الانزلاق */
    .glass-glider {
        position: absolute;
        top: 4px;
        bottom: 4px;
        width: calc((100% - 8px) / 4);
        border-radius: 0.7rem;
        z-index: 1;
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), background 0.4s ease, box-shadow 0.4s ease;
    }

    /* ألوان الـ glider — solid وواضحة مثل pharmacies */
    #filter-All:checked~.glass-glider {
        transform: translateX(0%);
        background: #0A7A48;
        box-shadow: 0 4px 12px rgba(10, 122, 72, 0.35);
    }

    #filter-Pending:checked~.glass-glider {
        transform: translateX(100%);
        background: #f59e0b;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
    }

    #filter-Accepted:checked~.glass-glider {
        transform: translateX(200%);
        background: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }

    #filter-Delivered:checked~.glass-glider {
        transform: translateX(300%);
        background: #10b981;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }

    /* دعم RTL */
    html[dir="rtl"] #filter-Pending:checked~.glass-glider {
        transform: translateX(-100%);
    }

    html[dir="rtl"] #filter-Accepted:checked~.glass-glider {
        transform: translateX(-200%);
    }

    html[dir="rtl"] #filter-Delivered:checked~.glass-glider {
        transform: translateX(-300%);
    }

    /* سكرول المودال */
    .modal-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .modal-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(10, 122, 72, 0.3);
        border-radius: 10px;
    }

    .dark .modal-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(74, 222, 128, 0.3);
    }
</style>

<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-[#0B1120] h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <!-- الترويسة والفلاتر -->
    <div class="mb-6 flex flex-col xl:flex-row justify-between items-center gap-6">

        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 shrink-0 w-full xl:w-auto">
            <i data-lucide="shopping-bag" class="text-[#0A7A48]"></i> <?php echo isset($lang['orders']) ? $lang['orders'] : 'الطلبات'; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto justify-end">
            <!-- شريط البحث السريع -->
            <div class="w-full md:w-80">
                <div class="relative group">
                    <input type="text" id="searchInput" placeholder="<?php echo isset($lang['search_order_patient']) ? $lang['search_order_patient'] : 'ابحث برقم الطلب أو المريض...'; ?>" value="<?php echo htmlspecialchars($search_query); ?>"
                        oninput="fetchData(document.querySelector('input[name=\'order-filter\']:checked').value, this.value)"
                        class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] outline-none transition-all text-sm">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-4' : 'absolute right-4'; ?> w-5 h-5"></i>
                </div>
            </div>

            <!-- الفلتر الزجاجي -->
            <div class="w-full xl:w-auto overflow-x-auto custom-scrollbar pb-2 -mb-2">
                <div class="glass-radio-group shrink-0 mx-auto md:mx-0 min-w-max">
                    <?php
                    $tabs = [
                        'All'       => isset($lang['all_orders'])        ? $lang['all_orders']        : 'الكل',
                        'Pending'   => isset($lang['pending_orders'])     ? $lang['pending_orders']     : 'قيد الانتظار',
                        'Accepted'  => isset($lang['filter_processing'])  ? $lang['filter_processing']  : 'جاري التجهيز',
                        'Delivered' => isset($lang['filter_delivered'])   ? $lang['filter_delivered']   : 'مكتملة'
                    ];
                    foreach ($tabs as $key => $title):
                        $isChecked = ($filter_status == $key) ? 'checked' : '';
                    ?>
                        <input type="radio" name="order-filter" id="filter-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $isChecked; ?>
                            onchange="fetchData(this.value, document.getElementById('searchInput').value)" />
                        <label for="filter-<?php echo $key; ?>">
                            <?php echo $title; ?>
                            <span class="status-count" id="count-<?php echo $key; ?>"><?php echo $status_counts[$key]; ?></span>
                        </label>
                    <?php endforeach; ?>
                    <div class="glass-glider"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- الجدول -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden mb-6">
        <div class="overflow-x-auto" id="ordersTableContainer" style="transition: opacity 0.3s ease;">
            <table class="w-full border-collapse min-w-[1050px]">
                <thead id="tableHeader" class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700 text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>" <?php if (!$has_orders) echo 'style="display:none;"'; ?>>
                    <tr>
                        <th class="p-5 font-bold whitespace-nowrap"><?php echo isset($lang['order_number']) ? $lang['order_number'] : 'رقم الطلب'; ?></th>
                        <th class="p-5 font-bold whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#0A7A48]"></i>
                                <?php echo isset($lang['order_date']) ? $lang['order_date'] : 'تاريخ الطلب'; ?>
                            </div>
                        </th>
                        <th class="p-5 font-bold whitespace-nowrap"><?php echo isset($lang['customer_contact']) ? $lang['customer_contact'] : 'العميل / الاتصال'; ?></th>
                        <th class="p-5 font-bold min-w-[180px]"><?php echo isset($lang['address_col']) ? $lang['address_col'] : 'العنوان'; ?></th>
                        <th class="p-5 font-bold whitespace-nowrap text-center"><?php echo isset($lang['items_col']) ? $lang['items_col'] : 'الأصناف'; ?></th>
                        <th class="p-5 font-bold whitespace-nowrap text-center"><?php echo isset($lang['total_amount']) ? $lang['total_amount'] : 'الإجمالي'; ?></th>
                        <th class="p-5 font-bold whitespace-nowrap text-center"><?php echo isset($lang['status']) ? $lang['status'] : 'الحالة'; ?></th>
                        <th class="p-5 font-bold text-center"><?php echo isset($lang['actions']) ? $lang['actions'] : 'الإجراءات'; ?></th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody" class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php echo $rows_html; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<!-- البطاقة المنبثقة للطلب (المودال) -->
<div id="orderModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-[100] hidden flex justify-center items-center transition-opacity p-4">
    <div class="bg-gray-50 dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col md:flex-row transform transition-all border border-gray-200 dark:border-slate-700">

        <!-- الجانب الأيمن: الفاتورة والمنتجات -->
        <div class="flex-1 flex flex-col bg-white dark:bg-slate-800 relative z-10 w-full md:w-1/2 md:max-w-[50%]">

            <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                <!-- 💡 نقل زر الـ X ليكون في الزاوية المقابلة للعنوان تماماً وبشكل متناسق -->
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-gray-800 dark:text-white flex items-center gap-2 m-0">
                            <?php echo isset($lang['order_summary']) ? $lang['order_summary'] : 'ملخص الطلب'; ?>
                            <span id="modalOrderId" class="text-[#0A7A48] dark:text-[#4ADE80]"></span>
                        </h2>
                        <p id="modalOrderDate" class="text-xs text-gray-500 font-bold mt-1" dir="ltr"></p>
                    </div>
                    <!-- زر الإغلاق الأحمر -->
                    <button onclick="closeOrderModal()" class="flex items-center justify-center w-8 h-8 rounded-full bg-rose-100 text-rose-600 hover:bg-rose-600 hover:text-white dark:bg-rose-900/40 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white transition-all shadow-sm">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="p-6 overflow-y-auto modal-scroll flex-1 space-y-6">
                <div class="bg-[#F2FBF5] dark:bg-[#044E29]/20 p-4 rounded-2xl border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm">
                            <i data-lucide="user" class="w-5 h-5 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        </div>
                        <div>
                            <h3 id="modalPatientName" class="font-bold text-gray-800 dark:text-white text-sm"></h3>
                            <p id="modalPatientPhone" class="text-xs text-gray-500 font-bold" dir="ltr"></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2 pt-3 border-t border-[#0A7A48]/10 dark:border-slate-700/50">
                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"></i>
                        <p id="modalPatientAddress" class="text-sm font-bold text-gray-600 dark:text-gray-300 leading-relaxed"></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wider"><?php echo isset($lang['purchases']) ? $lang['purchases'] : 'المشتريات'; ?></h3>
                    <div class="space-y-3" id="modalItemsList"></div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex justify-between items-end mb-4">
                    <span class="text-sm font-bold text-gray-500"><?php echo isset($lang['total_required']) ? $lang['total_required'] : 'الإجمالي المطلوب:'; ?></span>
                    <h2 id="modalTotalAmount" class="text-3xl font-black text-gray-900 dark:text-white" dir="ltr"></h2>
                </div>
                <div id="dynamicActionButtons" class="flex gap-2"></div>
            </div>
        </div>

        <!-- الجانب الأيسر: الوصفة الطبية -->
        <div id="prescriptionSection" class="hidden md:flex flex-1 flex-col bg-gray-50 dark:bg-slate-900 border-r border-gray-200 dark:border-slate-700 w-full md:w-1/2 md:max-w-[50%]">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg text-amber-600 dark:text-amber-400"><i data-lucide="file-check-2" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="font-black text-gray-800 dark:text-white"><?php echo isset($lang['prescription_rx']) ? $lang['prescription_rx'] : 'الوصفة الطبية (Rx)'; ?></h3>
                    <p class="text-xs text-amber-600 dark:text-amber-500 font-bold"><?php echo isset($lang['contains_controlled']) ? $lang['contains_controlled'] : 'الطلب يحتوي على أدوية مراقبة'; ?></p>
                </div>
            </div>

            <div class="flex-1 p-6 flex flex-col items-center justify-center overflow-y-auto modal-scroll">
                <a id="prescriptionImgLink" href="#" target="_blank" class="block w-full max-w-sm relative group rounded-2xl overflow-hidden border-4 border-white dark:border-slate-800 shadow-xl mb-6">
                    <img id="prescriptionImg" src="" alt="Prescription" class="w-full h-auto object-cover transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <i data-lucide="zoom-in" class="text-white w-10 h-10"></i>
                    </div>
                </a>

                <div class="w-full max-w-sm bg-white dark:bg-slate-800 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/30 shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <input type="checkbox" id="verifyPrescriptionCheck" class="w-5 h-5 text-[#0A7A48] rounded border-gray-300 focus:ring-[#0A7A48]">
                        <label for="verifyPrescriptionCheck" class="text-sm font-bold text-gray-800 dark:text-gray-200 cursor-pointer select-none">
                            <?php echo isset($lang['rx_verify_check']) ? $lang['rx_verify_check'] : 'أقر بأني راجعت الوصفة الطبية، وصحتها، ومطابقتها للأدوية المطلوبة.'; ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    // تمرير متغيرات الترجمة للجافاسكربت
    const txtNoPhone = "<?php echo isset($lang['no_phone']) ? $lang['no_phone'] : 'لا يوجد رقم'; ?>";
    const txtPickup = "<?php echo isset($lang['pickup_pharmacy']) ? $lang['pickup_pharmacy'] : 'استلام من الصيدلية'; ?>";
    const txtQty = "<?php echo isset($lang['quantity']) ? $lang['quantity'] : 'الكمية:'; ?>";
    const txtReject = "<?php echo isset($lang['reject']) ? $lang['reject'] : 'رفض'; ?>";
    const txtAccept = "<?php echo isset($lang['accept_prepare']) ? $lang['accept_prepare'] : 'قبول وتجهيز'; ?>";
    const txtDelivered = "<?php echo isset($lang['delivered_successfully']) ? $lang['delivered_successfully'] : 'تم تسليم الطلب بنجاح'; ?>";
    const txtActionTaken = "<?php echo isset($lang['action_taken']) ? $lang['action_taken'] : 'تم اتخاذ إجراء مسبقاً'; ?>";

    // AJAX Fetch
    let timeoutId;
    async function fetchData(status, searchQuery) {
        const container = document.getElementById('ordersTableContainer');
        const tbody = document.getElementById('ordersTableBody');
        const tableHeader = document.getElementById('tableHeader');

        // إخفاء الجدول تدريجياً أثناء التحميل
        if (container.querySelector('.bg-white')) {
            container.querySelector('.bg-white').style.opacity = '0.4';
            container.style.pointerEvents = 'none';
        }

        const newUrl = `?status=${status}&search=${encodeURIComponent(searchQuery)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        clearTimeout(timeoutId);
        timeoutId = setTimeout(async () => {
            try {
                const response = await fetch(`${newUrl}&ajax=1`);
                const data = await response.json();

                tbody.innerHTML = data.html;

                if (tableHeader) {
                    tableHeader.style.display = data.has_orders ? '' : 'none';
                }

                for (const [key, value] of Object.entries(data.counts)) {
                    const badge = document.getElementById(`count-${key}`);
                    if (badge) badge.innerText = value;
                }

                lucide.createIcons();

            } catch (error) {
                console.error("Error fetching orders:", error);
            } finally {
                if (container.querySelector('.bg-white')) {
                    container.querySelector('.bg-white').style.opacity = '1';
                }
                container.style.pointerEvents = 'auto';
            }
        }, 300);
    }

    function toggleSubOrders(groupId, btnElement) {
        const subRows = document.querySelectorAll('.sub-row-' + groupId);
        const icon = btnElement.querySelector('.toggle-icon');

        subRows.forEach(row => {
            row.classList.toggle('hidden');
        });

        if (icon) {
            if (icon.classList.contains('rotate-180')) {
                icon.classList.remove('rotate-180');
            } else {
                icon.classList.add('rotate-180');
            }
        }
    }

    let currentOrderData = null;

    function viewOrderDetails(jsonString) {
        const order = JSON.parse(jsonString);
        currentOrderData = order;

        document.getElementById('modalOrderId').innerText = `#${order.id}`;
        document.getElementById('modalOrderDate').innerText = order.date;
        document.getElementById('modalPatientName').innerText = order.patient;
        document.getElementById('modalPatientPhone').innerText = order.phone || txtNoPhone;
        document.getElementById('modalPatientAddress').innerText = order.address || txtPickup;
        document.getElementById('modalTotalAmount').innerText = parseFloat(order.total).toFixed(2) + ' ₪';

        const itemsList = document.getElementById('modalItemsList');
        itemsList.innerHTML = '';
        order.items.forEach(item => {
            const rxBadge = item.IsControlled == 1 ? '<span class="ml-2 bg-amber-100 text-amber-700 text-[10px] px-1.5 py-0.5 rounded font-black border border-amber-200">Rx</span>' : '';
            const itemTotal = parseFloat(item.Quantity * item.SoldPrice).toFixed(2);

            itemsList.innerHTML += `
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/30 rounded-xl border border-gray-100 dark:border-slate-700">
                    <div>
                        <div class="font-bold text-gray-800 dark:text-white text-sm mb-1">${item.Name} ${rxBadge}</div>
                        <div class="text-xs text-gray-500 font-bold">${txtQty} ${item.Quantity} × ${item.SoldPrice} ₪</div>
                    </div>
                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80]" dir="ltr">${itemTotal} ₪</div>
                </div>
            `;
        });

        const prescriptionSection = document.getElementById('prescriptionSection');
        const verifyCheckbox = document.getElementById('verifyPrescriptionCheck');

        if (order.has_controlled) {
            prescriptionSection.classList.remove('hidden');
            verifyCheckbox.checked = false;
            if (order.prescription) {
                const imgUrl = `../uploads/${order.prescription}`;
                document.getElementById('prescriptionImg').src = imgUrl;
                document.getElementById('prescriptionImgLink').href = imgUrl;
            }
        } else {
            prescriptionSection.classList.add('hidden');
            verifyCheckbox.checked = true;
        }

        const actionsContainer = document.getElementById('dynamicActionButtons');
        if (order.status === 'Pending') {
            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Rejected')" class="w-1/3 bg-gray-100 hover:bg-rose-100 text-gray-600 hover:text-rose-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-rose-900/30 py-3 rounded-xl font-bold transition">${txtReject}</button>
                <button onclick="attemptAcceptOrder()" class="w-2/3 bg-[#0A7A48] hover:bg-[#044E29] text-white py-3 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i> ${txtAccept}
                </button>
            `;
        } else if (order.status === 'Accepted') {
            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="w-full bg-[#0A7A48] hover:bg-[#044E29] text-white py-3 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2">
                    <i data-lucide="truck" class="w-5 h-5"></i> ${txtDelivered}
                </button>
            `;
        } else {
            actionsContainer.innerHTML = `<div class="w-full py-3 text-center text-sm font-bold text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-xl border border-gray-100 dark:border-slate-600">${txtActionTaken}</div>`;
        }

        lucide.createIcons();
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled && !document.getElementById('verifyPrescriptionCheck').checked) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه أمني',
                text: 'يجب مراجعة الوصفة الطبية وتأكيد الإقرار المهني قبل قبول الطلب.',
                confirmButtonColor: '#f43f5e',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
            return;
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }

    function confirmOrderStatus(orderId, action) {
        let title, text, btnText, btnColor;
        if (action === 'Accepted') {
            title = 'قبول الطلب؟';
            text = 'سيتم إشعار المريض بأنك تقوم بتجهيز الطلب حالياً.';
            btnText = 'نعم، أقبل الطلب';
            btnColor = '#0A7A48';
        } else if (action === 'Rejected') {
            title = 'رفض الطلب؟';
            text = 'هل أنت متأكد من رغبتك في إلغاء هذا الطلب؟';
            btnText = 'نعم، ارفض';
            btnColor = '#f43f5e';
        } else if (action === 'Delivered') {
            title = 'تأكيد التسليم؟';
            text = 'هل تم تسليم الطلب للعميل؟';
            btnText = 'نعم، تم التسليم';
            btnColor = '#0A7A48';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: (action === 'Rejected' ? 'warning' : 'question'),
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: btnText,
            cancelButtonText: 'إلغاء',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
        }).then((res) => {
            if (res.isConfirmed) window.location.href = `orders.php?action=${action}&order_id=${orderId}`;
        });
    }
</script>

<?php include('../includes/footer.php'); ?>