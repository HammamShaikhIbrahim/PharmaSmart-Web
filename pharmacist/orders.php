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
// 2. معالجة تحديث حالة الطلب (Actions) مع سبب الرفض وخصم المخزون
// ==========================================

if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = mysqli_real_escape_string($conn, $_GET['action']);

    // التقاط سبب الرفض إن وُجد
    $reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : '';

    $valid_actions =['Accepted', 'Rejected', 'Delivered'];

    if (in_array($action, $valid_actions)) {

        // التحقق مما إذا كان الإجراء هو "رفض" لإدخال السبب مع الحالة
        if ($action == 'Rejected') {
            mysqli_query($conn, "UPDATE `Order` SET Status = '$action', RejectionReason = '$reason' WHERE OrderID = $order_id");
        } else {
            // تحديث الحالة لباقي الإجراءات
            mysqli_query($conn, "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id");
        }

        // في حال القبول، يتم تأكيد الوصفة الطبية
        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }

        // خصم المخزون فقط عندما يتم التوصيل بنجاح
        if ($action == 'Delivered') {
            // جلب جميع الأدوية التابعة لهذا الطلب
            $items_query = mysqli_query($conn, "SELECT StockID, Quantity FROM OrderItems WHERE OrderID = $order_id");
            while ($item = mysqli_fetch_assoc($items_query)) {
                $stock_id = $item['StockID'];
                $quantity = $item['Quantity'];
                // خصم الكمية (مع التأكد أن المخزون لا يصبح بالسالب)
                mysqli_query($conn, "UPDATE PharmacyStock SET Stock = GREATEST(Stock - $quantity, 0) WHERE StockID = $stock_id");
            }
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
                 JOIN PharmacyStock ps ON oi.StockID = ps.StockID
                 WHERE ps.PharmacistID = $pharmacist_id
                 GROUP BY o.Status";
$counts_result = mysqli_query($conn, $counts_sql);

$status_counts =['Pending' => 0, 'Accepted' => 0, 'Delivered' => 0, 'Rejected' => 0];
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
        o.DeliveryLatitude, o.DeliveryLongitude, o.RejectionReason,
        u.UserID, u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage, pr.IsVerified
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN PharmacyStock ps ON oi.StockID = ps.StockID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE ps.PharmacistID = $pharmacist_id $status_condition $search_condition
    ORDER BY
        FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'),
        o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// جلب تفاصيل الأدوية (Products inside the order)
$order_items_data =[];
$items_query = "
    SELECT oi.OrderID, sm.Name, oi.Quantity, oi.SoldPrice, sm.IsControlled
    FROM OrderItems oi
    JOIN PharmacyStock ps ON oi.StockID = ps.StockID
    JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
    WHERE ps.PharmacistID = $pharmacist_id
";
$items_result = mysqli_query($conn, $items_query);
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items_data[$item['OrderID']][] = $item;
}

// تجميع الطلبات حسب المريض
$grouped_orders =[];
$has_orders = mysqli_num_rows($orders_result) > 0;
if ($has_orders) {
    while ($order = mysqli_fetch_assoc($orders_result)) {
        $grouped_orders[$order['UserID']][] = $order;
    }
}

// ==========================================
// 4. هندسة الـ AJAX (رسم الجدول برمجياً)
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

            $current_items = $order_items_data[$order['OrderID']] ??[];
            $has_controlled = array_reduce($current_items, fn($carry, $item) => $carry || $item['IsControlled'] == 1, false);

            // التعديل الهام هنا: الاعتماد على الجمع الحي للأدوية الموجودة فقط لحل مشكلة الأسعار القديمة
            $calculated_total = 0;
            foreach ($current_items as $c_item) {
                $calculated_total += ($c_item['Quantity'] * $c_item['SoldPrice']);
            }
            // في حال لم يتبق شيء في الطلب (حذفت كل الأدوية)، يكون المجموع 0، وإلا الإجمالي المحسوب
            $final_total = (count($current_items) > 0) ? $calculated_total : 0;

            $order_json = htmlspecialchars(json_encode([
                'id' => $order['OrderID'],
                'date' => date('d M Y, h:i A', strtotime($order['OrderDate'])),
                'status' => $order['Status'],
                'total' => $final_total, // إرسال الإجمالي المصحح بدلاً من الإجمالي القديم
                'patient' => $order['Fname'] . ' ' . $order['Lname'],
                'phone' => $order['Phone'],
                'address' => $order['DeliveryAddress'],
                'lat' => $order['DeliveryLatitude'],
                'lng' => $order['DeliveryLongitude'],
                'rejection_reason' => $order['RejectionReason'],
                'is_verified' => $order['IsVerified'],
                'items' => $current_items,
                'prescription' => $order['PrescriptionImage'],
                'has_controlled' => $has_controlled
            ]));


            $row_classes = $is_main_row
                ? "hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 group cursor-pointer border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700"
                : "sub-row-{$group_id} hidden bg-gray-50/50 dark:bg-slate-800/40 hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 cursor-pointer transition-all border-b border-transparent";

            $sub_indicator = !$is_main_row ? '<i data-lucide="corner-down-left" class="w-4 h-4 text-gray-500 inline-block mr-1 ml-1"></i>' : '';
?>
            <tr class="<?php echo $row_classes; ?>" onclick="viewOrderDetails('<?php echo $order_json; ?>')">
                <td class="p-5 whitespace-nowrap text-right">
                    <div class="font-black text-gray-800 dark:text-white flex items-center justify-end gap-1.5 w-full" dir="ltr">
                        <?php if (!$is_main_row) echo $sub_indicator; ?>
                        <span class="text-[#0A7A48] dark:text-[#4ADE80] text-xs font-black">#</span>ORD-<?php echo $order['OrderID']; ?>
                    </div>
                </td>

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

                <td class="p-5 whitespace-nowrap">
                    <div class="flex items-center gap-3 mb-1.5">
                        <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4 text-[#0A7A48] shrink-0"></i>
                        <span dir="ltr"><?php echo htmlspecialchars($order['Phone'] ?? 'لا يوجد رقم'); ?></span>
                    </div>
                </td>

                <td class="p-5">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 font-medium">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#0A7A48] shrink-0"></i>
                        <span class="leading-relaxed font-medium line-clamp-2"><?php echo htmlspecialchars($order['DeliveryAddress'] ?? 'الاستلام من الصيدلية'); ?></span>
                    </div>
                </td>

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

                <td class="p-5 whitespace-nowrap text-center">
                    <!-- 🚀 طباعة الإجمالي المصحح في الجدول أيضاً -->
                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-[15px] mb-1.5" dir="ltr"><?php echo number_format($final_total, 2); ?> ₪</div>
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

                <td class="p-5 text-center whitespace-nowrap">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold inline-flex items-center justify-center gap-1.5 shadow-sm <?php echo $statusColor; ?>">
                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5"></i>
                        <?php
                        $statusLabels =[
                            'Pending'   => 'قيد الانتظار',
                            'Accepted'  => 'جاري التجهيز',
                            'Delivered' => 'مكتمل',
                            'Rejected'  => 'مرفوض',
                        ];
                        echo $statusLabels[$order['Status']] ?? $order['Status'];
                        ?>
                    </span>
                </td>

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
    <tr>
        <td colspan="8" class="p-20">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="relative w-24 h-24 mb-6">
                    <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
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

<!-- استدعاء ملفات مكتبة الخرائط Leaflet.js (التصميم والسكربت) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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

    label[for="filter-All"]:hover { color: #0A7A48; }
    label[for="filter-Pending"]:hover { color: #d97706; }
    label[for="filter-Accepted"]:hover { color: #2563eb; }
    label[for="filter-Delivered"]:hover { color: #059669; }
    label[for="filter-Rejected"]:hover { color: #e11d48; }

    .glass-radio-group input:checked+label {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    .status-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(100, 116, 139, 0.15);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 900;
        color: #64748b;
        transition: all 0.4s ease;
    }

    .dark .status-count {
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
    }

    .glass-radio-group input:checked+label .status-count {
        background: rgba(255, 255, 255, 0.3);
        color: #ffffff;
        backdrop-filter: blur(4px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .glass-glider {
        position: absolute;
        top: 4px;
        bottom: 4px;
        width: calc((100% - 10px) / 5);
        border-radius: 0.7rem;
        z-index: 1;
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), background 0.4s ease, box-shadow 0.4s ease;
    }

    #filter-All:checked~.glass-glider { transform: translateX(0%); background: #0A7A48; box-shadow: 0 4px 12px rgba(10, 122, 72, 0.35); }
    #filter-Pending:checked~.glass-glider { transform: translateX(100%); background: #f59e0b; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35); }
    #filter-Accepted:checked~.glass-glider { transform: translateX(200%); background: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); }
    #filter-Delivered:checked~.glass-glider { transform: translateX(300%); background: #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35); }
    #filter-Rejected:checked~.glass-glider { transform: translateX(400%); background: #f43f5e; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.35); }

    html[dir="rtl"] #filter-Pending:checked~.glass-glider { transform: translateX(-100%); }
    html[dir="rtl"] #filter-Accepted:checked~.glass-glider { transform: translateX(-200%); }
    html[dir="rtl"] #filter-Delivered:checked~.glass-glider { transform: translateX(-300%); }
    html[dir="rtl"] #filter-Rejected:checked~.glass-glider { transform: translateX(-400%); }

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

    <div class="mb-6 flex flex-col xl:flex-row justify-between items-center gap-6">
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 shrink-0 w-full xl:w-auto">
            <i data-lucide="shopping-bag" class="text-[#0A7A48]"></i> <?php echo isset($lang['orders']) ? $lang['orders'] : 'الطلبات'; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto justify-end">
            <div class="w-full md:w-80">
                <div class="relative group">
                    <input type="text" id="searchInput" placeholder="<?php echo isset($lang['search_order_patient']) ? $lang['search_order_patient'] : 'ابحث برقم الطلب أو المريض...'; ?>" value="<?php echo htmlspecialchars($search_query); ?>"
                        oninput="fetchData(document.querySelector('input[name=\'order-filter\']:checked').value, this.value)"
                        class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] outline-none transition-all text-sm">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-4' : 'absolute right-4'; ?> w-5 h-5"></i>
                </div>
            </div>

            <div class="w-full xl:w-auto overflow-x-auto custom-scrollbar pb-2 -mb-2">
                <div class="glass-radio-group shrink-0 mx-auto md:mx-0 min-w-max">
                    <?php
                    $tabs =[
                        'All'       => isset($lang['all_orders'])        ? $lang['all_orders']        : 'الكل',
                        'Pending'   => isset($lang['pending_orders'])    ? $lang['pending_orders']    : 'قيد الانتظار',
                        'Accepted'  => isset($lang['filter_processing']) ? $lang['filter_processing'] : 'جاري التجهيز',
                        'Delivered' => isset($lang['filter_delivered'])  ? $lang['filter_delivered']  : 'مكتملة',
                        'Rejected'  => isset($lang['filter_rejected'])   ? $lang['filter_rejected']   : 'مرفوض'
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
                        <th class="p-5 font-bold whitespace-nowrap text-right"><?php echo isset($lang['order_number']) ? $lang['order_number'] : 'رقم الطلب'; ?></th>
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

    <!-- النافذة الثابتة كعمود واحد -->
    <div id="modalContentWrapper" class="bg-white dark:bg-slate-900 w-full max-w-xl max-h-[90vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col transform transition-all border border-gray-200 dark:border-slate-700 relative">

        <button onclick="closeOrderModal()" class="absolute top-5 rtl:left-5 ltr:right-5 flex items-center justify-center w-8 h-8 rounded-full bg-gray-100/80 text-gray-500 hover:bg-rose-500 hover:text-white dark:bg-slate-800/80 dark:text-gray-400 dark:hover:bg-rose-500 dark:hover:text-white backdrop-blur-sm transition-all shadow-sm z-50">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <!-- المحتوى الموحد -->
        <div id="invoiceSection" class="flex-1 flex flex-col relative z-10 w-full min-h-0 transition-all duration-300">

            <!-- الهيدر الثابت -->
            <div id="modalHeader" class="shrink-0 p-5 border-b border-gray-100 dark:border-slate-700 transition-colors relative overflow-hidden">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 dark:text-white flex items-center gap-2 m-0">
                            <?php echo isset($lang['order_summary']) ? $lang['order_summary'] : 'ملخص الطلب'; ?>
                            <span id="modalOrderId" class="text-[#0A7A48] dark:text-[#4ADE80]"></span>
                        </h2>
                        <p id="modalOrderDate" class="text-xs text-gray-500 font-bold mt-1" dir="ltr"></p>
                    </div>
                    <div id="statusStamp" class="hidden absolute top-0 left-1/2 transform -translate-x-1/2 rotate-[-10deg] border-4 font-black text-2xl px-5 py-1 rounded-xl opacity-20 select-none pointer-events-none uppercase tracking-widest"></div>
                </div>
            </div>

            <!-- جسم المودال القابل للسكرول -->
            <div class="p-5 overflow-y-auto modal-scroll flex-1 space-y-5 relative">
                
                <!-- تنبيه سبب الرفض -->
                <div id="rejectionAlert" class="hidden bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 p-4 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-black text-rose-800 dark:text-rose-400 mb-1">سبب إلغاء الطلب:</h4>
                            <p id="rejectionReasonText" class="text-xs font-bold text-rose-600 dark:text-rose-300"></p>
                        </div>
                    </div>
                </div>

                <!-- بيانات العميل والخريطة -->
                <div class="bg-gray-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-gray-100 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="user" class="w-5 h-5 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        </div>
                        <div class="flex flex-col gap-1">
                            <h3 id="modalPatientName" class="font-bold text-gray-800 dark:text-white text-sm"></h3>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="phone" class="w-3 h-3 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                                <span id="modalPatientPhone" class="text-xs text-gray-600 dark:text-gray-300 font-bold tracking-wide" dir="ltr"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-2 pt-3 border-t border-gray-200 dark:border-slate-700">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        <p id="modalPatientAddress" class="text-sm font-bold text-gray-600 dark:text-gray-300 leading-relaxed"></p>
                    </div>
                    <div id="mapWrapper" class="hidden mt-3 rounded-xl overflow-hidden border border-gray-200 dark:border-slate-600 h-32 w-full relative z-0 shadow-inner">
                        <div id="deliveryMap" class="absolute inset-0"></div>
                    </div>
                    <p id="noLocationMsg" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400 font-bold flex items-center gap-1">
                        <i data-lucide="info" class="w-3 h-3"></i> لم يقم المريض بتحديد موقعه على الخريطة
                    </p>
                </div>

                <!-- قسم المشتريات -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3"><?php echo isset($lang['purchases']) ? $lang['purchases'] : 'المشتريات'; ?></h3>
                    <div class="space-y-2" id="modalItemsList"></div>
                </div>

                <!-- قسم الوصفة الطبية -->
                <div id="unifiedPrescriptionContainer" class="hidden flex-col items-center justify-center bg-amber-50/30 dark:bg-amber-900/10 border border-amber-100 dark:border-slate-700/50 rounded-2xl p-4 relative">
                    <div id="rxHeader" class="w-full mb-3 flex items-center gap-3">
                        <!-- يُحقن بواسطة الجافاسكربت -->
                    </div>
                    
                    <a id="prescriptionImgLink" href="#" target="_blank" class="block w-full max-w-[250px] relative group rounded-xl overflow-hidden border-4 border-white dark:border-slate-800 shadow-lg mb-4 bg-gray-200 dark:bg-slate-800 ring-2 ring-transparent group-hover:ring-amber-400 transition-all duration-300">
                        <img id="prescriptionImg" src="" onerror="this.src='https://placehold.co/400x600/e2e8f0/475569?text=صورة+غير+متوفرة';" alt="Prescription" class="w-full h-auto object-cover transition-transform duration-500 group-hover:scale-110 min-h-[150px]">
                        <div class="absolute inset-0 bg-amber-900/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <i data-lucide="zoom-in" class="text-white w-8 h-8 drop-shadow-md"></i>
                        </div>
                    </a>

                    <div id="rxCheckboxContainer" class="w-full max-w-[250px] bg-white dark:bg-slate-800 p-3 rounded-xl border border-amber-100 dark:border-slate-700 shadow-sm transition-opacity duration-300">
                        <div class="flex items-start gap-2">
                            <input type="checkbox" id="verifyPrescriptionCheck" class="w-4 h-4 text-amber-500 rounded border-gray-300 focus:ring-amber-500 accent-amber-500 mt-0.5 shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                            <label for="verifyPrescriptionCheck" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer select-none leading-tight">
                                أقر بأني راجعت الوصفة الطبية وصحتها.
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- الفوتر (المبلغ والأزرار الثابتة في الأسفل) -->
            <div id="modalFooter" class="shrink-0 p-5 border-t border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-900/50">
                <div class="flex justify-between items-end mb-4">
                    <span class="text-sm font-bold text-gray-500"><?php echo isset($lang['total_required']) ? $lang['total_required'] : 'الإجمالي المطلوب:'; ?></span>
                    <h2 id="modalTotalAmount" class="text-2xl font-black text-gray-900 dark:text-white" dir="ltr"></h2>
                </div>
                <div id="dynamicActionButtons" class="flex gap-2"></div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    const txtNoPhone = "<?php echo isset($lang['no_phone']) ? $lang['no_phone'] : 'لا يوجد رقم'; ?>";
    const txtPickup = "<?php echo isset($lang['pickup_pharmacy']) ? $lang['pickup_pharmacy'] : 'استلام من الصيدلية'; ?>";
    const txtQty = "<?php echo isset($lang['quantity']) ? $lang['quantity'] : 'الكمية:'; ?>";
    const txtReject = "<?php echo isset($lang['reject']) ? $lang['reject'] : 'رفض'; ?>";
    const txtAccept = "<?php echo isset($lang['accept_prepare']) ? $lang['accept_prepare'] : 'قبول وتجهيز'; ?>";
    const txtDelivered = "<?php echo isset($lang['delivered_successfully']) ? $lang['delivered_successfully'] : 'تم تسليم الطلب بنجاح'; ?>";
    const txtActionTaken = "<?php echo isset($lang['action_taken']) ? $lang['action_taken'] : 'تم اتخاذ إجراء مسبقاً'; ?>";

    let timeoutId;
    async function fetchData(status, searchQuery) {
        const container = document.getElementById('ordersTableContainer');
        const tbody = document.getElementById('ordersTableBody');
        const tableHeader = document.getElementById('tableHeader');

        if (container.querySelector('.bg-white')) {
            container.querySelector('.bg-white').style.opacity = '0.4';
            container.style.pointerEvents = 'none';
        }

        const newUrl = `?status=${status}&search=${encodeURIComponent(searchQuery)}`;
        window.history.pushState({ path: newUrl }, '', newUrl);

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
            if (icon.classList.contains('rotate-180')) icon.classList.remove('rotate-180');
            else icon.classList.add('rotate-180');
        }
    }

    let currentOrderData = null;
    let deliveryMapInstance = null;

    const customMapIcon = L.divIcon({
        className: 'custom-leaflet-marker',
        html: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0A7A48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0px 3px 4px rgba(0,0,0,0.3));">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" fill="#fff"></path>
                <circle cx="12" cy="10" r="3" fill="#0A7A48"></circle>
               </svg>`,
        iconSize: [32, 32],
        iconAnchor:[16, 32],
    });

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
            const rxBadge = item.IsControlled == 1 ? '<span class="ml-2 bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-black border border-amber-200">Rx</span>' : '';
            const itemTotal = parseFloat(item.Quantity * item.SoldPrice).toFixed(2);

            itemsList.innerHTML += `
                <div class="flex justify-between items-center p-2.5 bg-gray-50 dark:bg-slate-700/30 rounded-xl border border-gray-100 dark:border-slate-700">
                    <div>
                        <div class="font-bold text-gray-800 dark:text-white text-xs mb-1">${item.Name} ${rxBadge}</div>
                        <div class="text-[10px] text-gray-500 font-bold">${txtQty} ${item.Quantity} × ${item.SoldPrice} ₪</div>
                    </div>
                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-sm" dir="ltr">${itemTotal} ₪</div>
                </div>
            `;
        });

        const modalWrapper = document.getElementById('modalContentWrapper');
        const unifiedPrescriptionContainer = document.getElementById('unifiedPrescriptionContainer');
        const rxHeader = document.getElementById('rxHeader');
        const verifyCheckbox = document.getElementById('verifyPrescriptionCheck');
        const rxCheckboxContainer = document.getElementById('rxCheckboxContainer');

        const modalHeader = document.getElementById('modalHeader');
        const statusStamp = document.getElementById('statusStamp');
        const modalFooter = document.getElementById('modalFooter');
        const rejectionAlert = document.getElementById('rejectionAlert');
        const actionsContainer = document.getElementById('dynamicActionButtons');

        modalHeader.className = 'shrink-0 p-5 border-b border-gray-100 dark:border-slate-700 transition-colors relative overflow-hidden';
        statusStamp.className = 'hidden absolute top-4 left-1/2 transform -translate-x-1/2 rotate-[-10deg] border-4 font-black text-2xl px-5 py-1 rounded-xl opacity-20 select-none pointer-events-none uppercase tracking-widest z-0';
        modalFooter.classList.remove('hidden');
        rejectionAlert.classList.add('hidden');
        verifyCheckbox.disabled = false;
        rxCheckboxContainer.classList.remove('opacity-50');

        if (order.status === 'Delivered') {
            modalHeader.classList.add('bg-emerald-50', 'dark:bg-emerald-900/20');
            statusStamp.classList.remove('hidden');
            statusStamp.classList.add('border-emerald-500', 'text-emerald-500');
            statusStamp.innerText = 'COMPLETED';
            modalFooter.classList.add('hidden');
            verifyCheckbox.disabled = true;
            rxCheckboxContainer.classList.add('opacity-50');

        } else if (order.status === 'Rejected') {
            modalHeader.classList.add('bg-rose-50', 'dark:bg-rose-900/20');
            statusStamp.classList.remove('hidden');
            statusStamp.classList.add('border-rose-500', 'text-rose-500');
            statusStamp.innerText = 'REJECTED';
            modalFooter.classList.add('hidden');
            verifyCheckbox.disabled = true;
            rxCheckboxContainer.classList.add('opacity-50');

            rejectionAlert.classList.remove('hidden');
            document.getElementById('rejectionReasonText').innerText = order.rejection_reason || 'تم الإلغاء بدون كتابة سبب';

        } else if (order.status === 'Accepted') {
            modalHeader.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
            statusStamp.classList.remove('hidden');
            statusStamp.classList.add('border-blue-500', 'text-blue-500');
            statusStamp.innerText = 'PROCESSING';
            verifyCheckbox.disabled = true;
            rxCheckboxContainer.classList.add('opacity-50');

            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="w-full bg-[#0A7A48] hover:bg-[#044E29] text-white py-2.5 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2 text-sm">
                    <i data-lucide="truck" class="w-4 h-4"></i> ${txtDelivered}
                </button>
            `;
        } else if (order.status === 'Pending') {
            modalHeader.classList.add('bg-amber-50', 'dark:bg-amber-900/20');

            statusStamp.classList.remove('hidden');
            statusStamp.classList.add('border-amber-500', 'text-amber-500');
            statusStamp.innerText = 'PENDING';

            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Rejected')" class="w-1/3 bg-gray-100 hover:bg-rose-100 text-gray-600 hover:text-rose-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-rose-900/30 py-2.5 rounded-xl font-bold transition text-sm shadow-sm">${txtReject}</button>
                <button onclick="attemptAcceptOrder()" class="w-2/3 bg-[#0A7A48] hover:bg-[#044E29] text-white py-2.5 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2 text-sm">
                    <i data-lucide="check" class="w-4 h-4"></i> ${txtAccept}
                </button>
            `;
        }

        if (order.prescription) {
            unifiedPrescriptionContainer.classList.remove('hidden');
            unifiedPrescriptionContainer.classList.add('flex');

            const imgUrl = `../${order.prescription}`;
            document.getElementById('prescriptionImg').src = imgUrl;
            document.getElementById('prescriptionImgLink').href = imgUrl;

            if (order.has_controlled) {
                rxHeader.innerHTML = `
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/40 rounded-lg text-amber-600 dark:text-amber-400">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-amber-800 dark:text-amber-400 text-sm">أدوية مراقبة (Rx)</h3>
                        <p class="text-[10px] text-amber-600/80 dark:text-amber-500/80 font-bold">يلزم التحقق من الوصفة</p>
                    </div>
                `;
                rxCheckboxContainer.classList.remove('hidden');

                if (order.status === 'Pending') {
                    verifyCheckbox.checked = false;
                } else {
                    verifyCheckbox.checked = true;
                }

            } else {
                rxHeader.innerHTML = `
                    <div class="p-2 bg-gray-100 dark:bg-slate-800 rounded-lg text-gray-600 dark:text-gray-400">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-800 dark:text-gray-200 text-sm">مرفق إضافي (وصفة)</h3>
                    </div>
                `;
                rxCheckboxContainer.classList.add('hidden');
                verifyCheckbox.checked = true;
            }
        } else {
            unifiedPrescriptionContainer.classList.add('hidden');
            unifiedPrescriptionContainer.classList.remove('flex');
            verifyCheckbox.checked = true;
        }

        const mapWrapper = document.getElementById('mapWrapper');
        const noLocationMsg = document.getElementById('noLocationMsg');

        if (order.lat && order.lng) {
            mapWrapper.classList.remove('hidden');
            noLocationMsg.classList.add('hidden');

            if (deliveryMapInstance !== null) {
                deliveryMapInstance.remove();
            }

            deliveryMapInstance = L.map('deliveryMap', {
                zoomControl: false,
                attributionControl: false
            }).setView([order.lat, order.lng], 14);

            const isDark = document.documentElement.classList.contains('dark');
            const tileUrl = isDark ?
                'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

            L.tileLayer(tileUrl, { maxZoom: 19 }).addTo(deliveryMapInstance);
            L.marker([order.lat, order.lng], { icon: customMapIcon }).addTo(deliveryMapInstance);

        } else {
            mapWrapper.classList.add('hidden');
            noLocationMsg.classList.remove('hidden');
        }

        lucide.createIcons();
        document.getElementById('orderModal').classList.remove('hidden');

        setTimeout(() => {
            if (deliveryMapInstance) {
                deliveryMapInstance.invalidateSize();
            }
        }, 150);
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled && !document.getElementById('verifyPrescriptionCheck').checked) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه أمني',
                text: 'يجب مراجعة الوصفة الطبية وإقرار صحتها قبل قبول الطلب.',
                confirmButtonColor: '#f43f5e',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
            return;
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }

    function confirmOrderStatus(orderId, action) {
        if (action === 'Rejected') {
            Swal.fire({
                title: 'رفض الطلب؟',
                text: 'يرجى كتابة سبب الرفض (سيظهر للمريض):',
                input: 'text',
                inputPlaceholder: 'مثال: الدواء غير متوفر حالياً...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'تأكيد الرفض',
                cancelButtonText: 'إلغاء',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                inputValidator: (value) => {
                    if (!value) return 'يجب كتابة سبب الرفض لإشعار المريض!';
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    const reason = encodeURIComponent(res.value);
                    window.location.href = `orders.php?action=${action}&order_id=${orderId}&reason=${reason}`;
                }
            });
            return;
        }

        let title, text, btnText, btnColor;
        if (action === 'Accepted') {
            title = 'قبول الطلب؟';
            text = 'سيتم إشعار المريض بأنك تقوم بتجهيز الطلب.';
            btnText = 'نعم، أقبل';
            btnColor = '#0A7A48';
        } else if (action === 'Delivered') {
            title = 'تأكيد التسليم؟';
            text = 'هل تم تسليم الطلب للعميل؟';
            btnText = 'نعم، تم التسليم';
            btnColor = '#0A7A48';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
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