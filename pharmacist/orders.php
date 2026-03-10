<?php
// ==========================================
// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

// حماية الصفحة: للصيادلة فقط
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

    // التحقق من أن الأكشن مسموح به
    $valid_actions = ['Accepted', 'Rejected', 'Delivered'];

    if (in_array($action, $valid_actions)) {
        // تحديث حالة الطلب
        $update_sql = "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id";
        mysqli_query($conn, $update_sql);

        // إذا كان الطلب "مقبول"، نحدث حالة الوصفة الطبية لتكون "تم التحقق منها"
        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }

        // إعادة توجيه لتنظيف الرابط
        header("Location: orders.php");
        exit();
    }
}

// ==========================================
// 3. جلب بيانات الطلبات المرتبطة بهذه الصيدلية
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';

$status_condition = "";
if ($filter_status !== 'All') {
    $status_condition = "AND o.Status = '$filter_status'";
}

$orders_query = "
    SELECT DISTINCT 
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress,
        u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE m.PharmacistID = $pharmacist_id $status_condition
    ORDER BY 
        FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), 
        o.OrderDate DESC
";

$orders_result = mysqli_query($conn, $orders_query);

// ==========================================
// 4. جلب تفاصيل الأدوية لكل طلب (لإرسالها للـ Modal)
// ==========================================
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

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300 relative">

    <?php include('../includes/topbar.php'); ?>

    <!-- ترويسة الصفحة وأزرار الفلترة -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="shopping-cart" class="text-[#1a4a38] dark:text-emerald-400 w-8 h-8"></i>
            <?php echo $lang['manage_orders']; ?>
        </h1>

        <!-- أزرار الفلترة -->
        <div class="flex bg-white dark:bg-slate-800 p-1.5 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-x-auto">
            <a href="?status=All" class="px-5 py-2 rounded-xl text-sm font-bold transition <?php echo $filter_status == 'All' ? 'bg-gray-100 dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'; ?>"><?php echo $lang['filter_all']; ?></a>
            <a href="?status=Pending" class="px-5 py-2 rounded-xl text-sm font-bold transition <?php echo $filter_status == 'Pending' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'; ?>"><?php echo $lang['filter_pending']; ?></a>
            <a href="?status=Accepted" class="px-5 py-2 rounded-xl text-sm font-bold transition <?php echo $filter_status == 'Accepted' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'; ?>"><?php echo $lang['filter_processing']; ?></a>
            <a href="?status=Delivered" class="px-5 py-2 rounded-xl text-sm font-bold transition <?php echo $filter_status == 'Delivered' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'; ?>"><?php echo $lang['filter_delivered']; ?></a>
        </div>
    </div>

    <!-- شبكة عرض الطلبات (Grid Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders_result)):

                // تحديد ألوان وتصميم حالة الطلب
                $statusColor = "";
                $statusText = "";
                $statusIcon = "";

                switch ($order['Status']) {
                    case 'Pending':
                        $statusColor = "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border-amber-200 dark:border-amber-800";
                        $statusText = $lang['status_pending'];
                        $statusIcon = "clock";
                        break;
                    case 'Accepted':
                        $statusColor = "bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border-blue-200 dark:border-blue-800";
                        $statusText = $lang['status_processing'];
                        $statusIcon = "package";
                        break;
                    case 'Delivered':
                        $statusColor = "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800";
                        $statusText = $lang['status_delivered'];
                        $statusIcon = "check-circle";
                        break;
                    case 'Rejected':
                        $statusColor = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border-rose-200 dark:border-rose-800";
                        $statusText = $lang['status_rejected'];
                        $statusIcon = "x-circle";
                        break;
                }

                // تجهيز بيانات المنتجات لتحويلها لـ JSON
                $current_order_items = isset($order_items_data[$order['OrderID']]) ? $order_items_data[$order['OrderID']] : [];
                $has_controlled = false;
                foreach ($current_order_items as $item) {
                    if ($item['IsControlled'] == 1) $has_controlled = true;
                }

                // تشفير البيانات لإرسالها للنافذة المنبثقة
                $order_json = htmlspecialchars(json_encode([
                    'id' => $order['OrderID'],
                    'date' => date('Y-m-d h:i A', strtotime($order['OrderDate'])),
                    'status' => $order['Status'],
                    'total' => $order['TotalAmount'],
                    'patient' => $order['Fname'] . ' ' . $order['Lname'],
                    'phone' => $order['Phone'],
                    'address' => $order['DeliveryAddress'],
                    'items' => $current_order_items,
                    'prescription' => $order['PrescriptionImage'],
                    'has_controlled' => $has_controlled
                ]));
            ?>

                <!-- كرت الطلب -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-700 hover:shadow-lg transition-all duration-300 flex flex-col">

                    <!-- رأس الكرت -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 block mb-1"><?php echo $lang['order_number']; ?></span>
                            <h3 class="text-lg font-black text-gray-800 dark:text-white">#ORD-<?php echo $order['OrderID']; ?></h3>
                        </div>
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 border <?php echo $statusColor; ?>">
                            <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5"></i>
                            <?php echo $statusText; ?>
                        </span>
                    </div>

                    <!-- تفاصيل مختصرة -->
                    <div class="space-y-3 mb-6 flex-1">
                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                            <div class="w-8 h-8 rounded-full bg-gray-50 dark:bg-slate-700 flex items-center justify-center text-gray-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <span class="font-bold"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span>
                        </div>

                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                            <div class="w-8 h-8 rounded-full bg-gray-50 dark:bg-slate-700 flex items-center justify-center text-gray-400">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </div>
                            <span class="line-clamp-1" title="<?php echo htmlspecialchars($order['DeliveryAddress']); ?>"><?php echo htmlspecialchars($order['DeliveryAddress']); ?></span>
                        </div>

                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-500">
                                <i data-lucide="banknote" class="w-4 h-4"></i>
                            </div>
                            <span class="font-black text-emerald-600 dark:text-emerald-400"><?php echo number_format($order['TotalAmount'], 2); ?> <span class="text-xs"><?php echo isset($lang['currency']) ? $lang['currency'] : '₪'; ?></span></span>
                            <span class="text-xs text-gray-400">(<?php echo $order['PaymentMethod']; ?>)</span>
                        </div>
                    </div>

                    <!-- التنبيه بوجود وصفة طبية -->
                    <?php if ($has_controlled): ?>
                        <div class="mb-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-900/50 rounded-xl p-2.5 flex items-center gap-2 text-rose-600 dark:text-rose-400 text-xs font-bold">
                            <i data-lucide="alert-octagon" class="w-4 h-4 animate-pulse"></i>
                            <?php echo $lang['rx_alert']; ?>
                        </div>
                    <?php endif; ?>

                    <!-- أزرار الإجراءات -->
                    <div class="mt-auto border-t border-gray-100 dark:border-slate-700 pt-4 flex gap-2">
                        <button onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white py-2.5 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2">
                            <i data-lucide="eye" class="w-4 h-4"></i> <?php echo $lang['details_btn']; ?>
                        </button>

                        <?php if ($order['Status'] == 'Pending'): ?>
                            <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Accepted')" class="w-12 bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-600 dark:hover:text-white py-2.5 rounded-xl flex justify-center items-center transition">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </button>
                            <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Rejected')" class="w-12 bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white dark:bg-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white py-2.5 rounded-xl flex justify-center items-center transition">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        <?php elseif ($order['Status'] == 'Accepted'): ?>
                            <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Delivered')" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-2.5 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2 shadow-md shadow-emerald-500/20">
                                <i data-lucide="truck" class="w-4 h-4"></i> <?php echo $lang['delivered_btn']; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- حالة فارغة -->
            <div class="col-span-full py-20 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                <i data-lucide="inbox" class="w-20 h-20 mb-4 opacity-50"></i>
                <h2 class="text-xl font-bold mb-2"><?php echo $lang['no_orders']; ?></h2>
                <p class="text-sm"><?php echo $lang['no_orders_desc']; ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- ==========================================
     نافذة تفاصيل الطلب (Order Details Modal)
=========================================== -->
<div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex justify-center items-center transition-opacity p-4">
    <div class="bg-white dark:bg-slate-800 w-full max-w-3xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col transform transition-all">

        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
            <div>
                <h2 class="text-xl font-black text-gray-800 dark:text-white flex items-center gap-2">
                    <i data-lucide="receipt" class="text-blue-500"></i> <?php echo $lang['order_details']; ?> <span id="modalOrderId" class="text-blue-500"></span>
                </h2>
                <p id="modalOrderDate" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium"></p>
            </div>
            <button onclick="closeOrderModal()" class="text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-slate-700 p-2 rounded-full transition">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- بيانات العميل -->
                <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl p-5 border border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 mb-4 border-b border-gray-200 dark:border-slate-700 pb-2"><?php echo $lang['customer_info']; ?></h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <i data-lucide="user" class="text-gray-400 w-4 h-4"></i>
                            <span id="modalPatientName" class="font-bold text-gray-800 dark:text-gray-200"></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <i data-lucide="phone" class="text-gray-400 w-4 h-4"></i>
                            <span id="modalPatientPhone" class="font-bold text-gray-800 dark:text-gray-200" dir="ltr"></span>
                        </div>
                        <div class="flex items-start gap-3 text-sm">
                            <i data-lucide="map-pin" class="text-gray-400 w-4 h-4 mt-0.5"></i>
                            <span id="modalPatientAddress" class="font-bold text-gray-800 dark:text-gray-200 leading-relaxed"></span>
                        </div>
                    </div>
                </div>

                <!-- ملخص الدفع -->
                <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl p-5 border border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 mb-4 border-b border-gray-200 dark:border-slate-700 pb-2"><?php echo $lang['payment_summary']; ?></h3>
                    <div class="flex flex-col h-full justify-center items-center text-center">
                        <span class="text-gray-500 dark:text-gray-400 text-sm font-bold mb-1"><?php echo $lang['total_required']; ?></span>
                        <h2 id="modalTotalAmount" class="text-4xl font-black text-emerald-600 dark:text-emerald-400 mb-2" dir="ltr"></h2>
                        <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-3 py-1 rounded-lg text-xs font-bold border border-emerald-200 dark:border-emerald-800"><?php echo $lang['cod']; ?></span>
                    </div>
                </div>
            </div>

            <!-- جدول المنتجات -->
            <h3 class="text-sm font-black text-gray-700 dark:text-gray-300 mb-3"><?php echo $lang['requested_items']; ?></h3>
            <div class="border border-gray-200 dark:border-slate-700 rounded-2xl overflow-hidden mb-6">
                <table class="w-full text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <thead class="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-3 px-4 font-bold"><?php echo isset($lang['product']) ? $lang['product'] : 'المنتج'; ?></th>
                            <th class="py-3 px-4 font-bold text-center"><?php echo $lang['qty']; ?></th>
                            <th class="py-3 px-4 font-bold"><?php echo isset($lang['price']) ? $lang['price'] : 'السعر'; ?></th>
                            <th class="py-3 px-4 font-bold"><?php echo $lang['item_total']; ?></th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTable" class="divide-y divide-gray-100 dark:divide-slate-700 text-gray-800 dark:text-gray-200 font-medium">
                        <!-- يعبأ بواسطة الجافاسكربت -->
                    </tbody>
                </table>
            </div>

            <!-- قسم الوصفة الطبية (يظهر فقط إذا كان هناك أدوية مراقبة) -->
            <div id="prescriptionSection" class="hidden border-2 border-dashed border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-900/10 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="file-check-2" class="text-rose-500 w-5 h-5"></i>
                    <h3 class="font-black text-rose-700 dark:text-rose-400"><?php echo $lang['attached_rx']; ?></h3>
                </div>

                <div class="flex flex-col md:flex-row gap-6 items-center">
                    <a id="prescriptionImgLink" href="#" target="_blank" class="block relative group overflow-hidden rounded-xl border-2 border-white dark:border-slate-700 shadow-md">
                        <img id="prescriptionImg" src="" alt="Prescription" class="w-48 h-48 object-cover transition duration-300 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center">
                            <i data-lucide="zoom-in" class="text-white w-8 h-8"></i>
                        </div>
                    </a>

                    <div class="flex-1 space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-300 font-bold"><?php echo $lang['rx_protocol']; ?></p>
                        <!-- بنترك النقاط ثابتة أو بننقلها للترجمة مستقبلاً (تترك هكذا لأنها جزء من البروتوكول التشغيلي العام) -->
                        <ul class="text-sm text-gray-500 dark:text-gray-400 space-y-2 list-disc list-inside">
                            <li>مطابقة اسم الدواء والتركيز مع الوصفة.</li>
                            <li>تاريخ الوصفة واسم الطبيب المعالج والتوقيع.</li>
                            <li>التأكد من عدم وجود تداخلات دوائية خطيرة.</li>
                        </ul>
                        <div class="mt-4 flex items-center gap-2 bg-white dark:bg-slate-800 p-3 rounded-xl border border-rose-100 dark:border-rose-900/50">
                            <input type="checkbox" id="verifyPrescriptionCheck" class="w-5 h-5 text-rose-600 rounded focus:ring-rose-500">
                            <label for="verifyPrescriptionCheck" class="text-sm font-bold text-gray-800 dark:text-gray-200 cursor-pointer select-none"><?php echo $lang['rx_verify_check']; ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer (Action Buttons) -->
        <div id="modalFooterActions" class="p-4 border-t border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50 flex <?php echo ($dir == 'rtl') ? 'justify-end' : 'justify-end flex-row-reverse'; ?> gap-3">
            <button onclick="closeOrderModal()" class="px-6 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-white dark:hover:bg-slate-700 transition text-sm">
                <?php echo $lang['close']; ?>
            </button>
            <div id="dynamicActionButtons" class="flex gap-2 <?php echo ($dir == 'rtl') ? '' : 'flex-row-reverse'; ?>">
                <!-- أزرار القبول والرفض تضاف برمجياً حسب الحالة -->
            </div>
        </div>

    </div>
</div>

<!-- السكربتات -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    let currentOrderData = null;
    const currency = "<?php echo isset($lang['currency']) ? $lang['currency'] : '₪'; ?>";

    function viewOrderDetails(jsonString) {
        const order = JSON.parse(jsonString);
        currentOrderData = order;

        // تعبئة البيانات الأساسية
        document.getElementById('modalOrderId').innerText = `#ORD-${order.id}`;
        document.getElementById('modalOrderDate').innerText = order.date;
        document.getElementById('modalPatientName').innerText = order.patient;
        document.getElementById('modalPatientPhone').innerText = order.phone || 'N/A';
        document.getElementById('modalPatientAddress').innerText = order.address;
        document.getElementById('modalTotalAmount').innerText = parseFloat(order.total).toFixed(2) + ' ' + currency;

        // تعبئة جدول المنتجات
        const tbody = document.getElementById('modalItemsTable');
        tbody.innerHTML = '';
        order.items.forEach(item => {
            const rxBadge = item.IsControlled == 1 ? '<span class="mx-2 bg-rose-100 text-rose-700 text-[10px] px-1.5 py-0.5 rounded font-bold">Rx</span>' : '';
            const totalItemPrice = parseFloat(item.Quantity * item.SoldPrice).toFixed(2);

            tbody.innerHTML += `
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition">
                    <td class="py-3 px-4 flex items-center">${item.Name} ${rxBadge}</td>
                    <td class="py-3 px-4 text-center"><span class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-md text-xs font-bold">${item.Quantity}</span></td>
                    <td class="py-3 px-4" dir="ltr">${parseFloat(item.SoldPrice).toFixed(2)} ${currency}</td>
                    <td class="py-3 px-4 font-bold text-emerald-600 dark:text-emerald-400" dir="ltr">${totalItemPrice} ${currency}</td>
                </tr>
            `;
        });

        // إدارة قسم الوصفة الطبية
        const prescriptionSection = document.getElementById('prescriptionSection');
        const verifyCheckbox = document.getElementById('verifyPrescriptionCheck');

        if (order.has_controlled) {
            prescriptionSection.classList.remove('hidden');
            verifyCheckbox.checked = false; // Reset checkbox
            if (order.prescription) {
                const imgUrl = `../uploads/${order.prescription}`;
                document.getElementById('prescriptionImg').src = imgUrl;
                document.getElementById('prescriptionImgLink').href = imgUrl;
            } else {
                document.getElementById('prescriptionImg').src = 'https://via.placeholder.com/200x200?text=No+Prescription';
            }
        } else {
            prescriptionSection.classList.add('hidden');
            verifyCheckbox.checked = true; // تجاوز فحص الشيك بوكس برمجياً
        }

        // بناء أزرار التحكم بناءً على الحالة
        const actionsContainer = document.getElementById('dynamicActionButtons');
        actionsContainer.innerHTML = '';

        if (order.status === 'Pending') {
            actionsContainer.innerHTML = `
                <button onclick="attemptAcceptOrder()" class="bg-[#1a4a38] hover:bg-[#133729] text-white px-6 py-2.5 rounded-xl font-bold transition shadow-md text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> <?php echo $lang['accept_prepare']; ?>
                </button>
            `;
        } else if (order.status === 'Accepted') {
            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold transition shadow-md text-sm flex items-center gap-2">
                    <i data-lucide="truck" class="w-4 h-4"></i> <?php echo $lang['confirm_delivery']; ?>
                </button>
            `;
        }

        lucide.createIcons();
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
        currentOrderData = null;
    }

    // دالة مخصصة لقبول الطلب من داخل المودال (للتحقق من الوصفة)
    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled) {
            const isVerified = document.getElementById('verifyPrescriptionCheck').checked;
            if (!isVerified) {
                Swal.fire({
                    icon: 'warning',
                    title: "<?php echo ($current_lang == 'ar') ? 'تنبيه أمني' : 'Security Alert'; ?>",
                    text: "<?php echo ($current_lang == 'ar') ? 'يجب مراجعة الوصفة الطبية وتأكيد الإقرار المهني قبل قبول الطلب.' : 'You must review the prescription and confirm professional responsibility before accepting.'; ?>",
                    confirmButtonText: "<?php echo ($current_lang == 'ar') ? 'حسناً' : 'OK'; ?>",
                    confirmButtonColor: '#f43f5e',
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
                });
                return;
            }
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }

    // SweetAlert لتأكيد تغيير الحالة
    function confirmOrderStatus(orderId, action) {
        let title = '';
        let text = '';
        let btnText = '';
        let btnColor = '';
        let icon = 'question';

        if (action === 'Accepted') {
            title = "<?php echo ($current_lang == 'ar') ? 'قبول الطلب؟' : 'Accept Order?'; ?>";
            text = "<?php echo ($current_lang == 'ar') ? 'سيتم إشعار المريض بأنك تقوم بتجهيز الطلب.' : 'Patient will be notified that you are preparing the order.'; ?>";
            btnText = "<?php echo ($current_lang == 'ar') ? 'نعم، أقبل الطلب' : 'Yes, Accept'; ?>";
            btnColor = '#10b981'; // Emerald
        } else if (action === 'Rejected') {
            title = "<?php echo ($current_lang == 'ar') ? 'رفض الطلب؟' : 'Reject Order?'; ?>";
            text = "<?php echo ($current_lang == 'ar') ? 'هل أنت متأكد من رغبتك في إلغاء هذا الطلب؟' : 'Are you sure you want to cancel this order?'; ?>";
            btnText = "<?php echo ($current_lang == 'ar') ? 'نعم، ارفض' : 'Yes, Reject'; ?>";
            btnColor = '#f43f5e'; // Rose
            icon = 'warning';
        } else if (action === 'Delivered') {
            title = "<?php echo ($current_lang == 'ar') ? 'تأكيد التسليم؟' : 'Confirm Delivery?'; ?>";
            text = "<?php echo ($current_lang == 'ar') ? 'هل قمت بتسليم الدواء (واستلام النسخة الأصلية للوصفة إن وجدت)؟' : 'Did you deliver the medicine (and receive original Rx if applicable)?'; ?>";
            btnText = "<?php echo ($current_lang == 'ar') ? 'نعم، تم التسليم' : 'Yes, Delivered'; ?>";
            btnColor = '#10b981';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: btnText,
            cancelButtonText: Lang.cancel,
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `orders.php?action=${action}&order_id=${orderId}`;
            }
        });
    }
</script>

<style>
    /* تنسيق سكرول خفيف داخل الـ Modal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 10px;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #475569;
    }
</style>

<?php include('../includes/footer.php'); ?>