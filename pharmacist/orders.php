<?php
// ==========================================
// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// ==========================================
// 2. معالجة تحديث حالة الطلب (Actions) - لا تغيير هنا
// ==========================================
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];
    $valid_actions = ['Accepted', 'Rejected', 'Delivered'];

    if (in_array($action, $valid_actions)) {
        $update_sql = "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id";
        mysqli_query($conn, $update_sql);
        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }
        header("Location: orders.php");
        exit();
    }
}

// ==========================================
// 3. جلب بيانات الطلبات -    
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
$status_condition = ($filter_status !== 'All') ? "AND o.Status = '$filter_status'" : "";

$orders_query = "
    SELECT DISTINCT 
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress, o.PatientID,
        u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE m.PharmacistID = $pharmacist_id $status_condition
    ORDER BY FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// ==========================================
// 4. جلب تفاصيل الأدوية لكل طلب - لا تغيير هنا
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

<style>



/* ================================================== */
/* == تعديل: إضافة تصميم أزرار الفلتر الزجاجية == */
/* ================================================== */
.glass-radio-group {
  display: flex;
  position: relative;
  border-radius: 9999px; /* rounded-full */
  backdrop-filter: blur(12px);
  overflow: hidden;
  width: fit-content;
  padding: 0.375rem; /* p-1.5 */
}

/* الوضع الفاتح */
.light .glass-radio-group {
  background: rgba(255, 255, 255, 0.7); /* أبيض شفاف */
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); /* shadow-md */
}

/* الوضع الداكن */
.dark .glass-radio-group {
  background: rgba(41, 56, 81, 0.5); /* أزرق داكن شفاف */
  box-shadow: inset 0 2px 4px 0 rgba(255, 255, 255, 0.05);
}

/* هذا هو الرابط/الزر نفسه */
.glass-radio-group a {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem; /* text-sm */
  padding: 0.625rem 1.75rem; /* px-7 py-2.5 */
  cursor: pointer;
  font-weight: 900; /* font-black */
  position: relative;
  z-index: 2;
  transition: color 0.4s ease-in-out;
  border-radius: 9999px;
  white-space: nowrap;
}

.light .glass-radio-group a { color: #64748b; } /* slate-500 */
.dark .glass-radio-group a { color: #94a3b8; } /* slate-400 */

.light .glass-radio-group a.active-glider-item,
.light .glass-radio-group a:hover { color: #0f172a; } /* slate-900 */

.dark .glass-radio-group a.active-glider-item,
.dark .glass-radio-group a:hover { color: #ffffff; }

/* هذا هو الجزء المتحرك (الـ Glider) */
.glass-glider {
  position: absolute;
  top: 0.375rem;
  bottom: 0.375rem;
  z-index: 1;
  transition: transform 0.5s cubic-bezier(0.45, 0, 0.09, 1);
  border-radius: 9999px;
}

.light .glass-glider {
  background: white;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1); /* shadow */
}

.dark .glass-glider {
  background-color: #334155; /* slate-700 */
  box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.1);
}












    .table-container {
        border-radius: 1.5rem;
        overflow: hidden;
    }
    /* الوضع الداكن */
    .dark .table-container { 
        background-color: #1e293b; 
        border: 1px solid rgba(100, 116, 139, 0.2);
    }
    /* الوضع الفاتح */
    .light .table-container { 
        background-color: white; 
        border: 1px solid #e2e8f0; /* slate-200 */
    }


</style>

<main class="flex-1 p-4 md:p-8 bg-emerald-50 dark:bg-[#0B1120] h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <!-- ترويسة الصفحة والفلاتر - لا تغيير هنا -->
    <div class="mb-10 flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-3">
            <i data-lucide="clipboard-check" class="text-[#0A7A48] w-10 h-10"></i>
            <div>
                <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-1">
                    <span class="text-gradient"><?php echo $lang['manage_orders']; ?></span>
                </h1>
                <p class="text-sm text-gray-500 font-bold">تابع وقم بمعالجة طلبات عملائك بدقة.</p>
            </div>
        </div>
       <!-- ============================================= -->
<!-- == تعديل: تطبيق تصميم الفلاتر الزجاجية == -->
<!-- ============================================= -->
<div class="glass-radio-group">
    <?php 
        $filter_tabs = [
            ['All', $lang['filter_all']],
            ['Pending', $lang['filter_pending']],
            ['Accepted', $lang['filter_processing']],
            ['Delivered', $lang['filter_delivered']],
            ['Rejected', 'مرفوضة']
        ];
        
        $total_tabs = count($filter_tabs);
        $activeIndex = 0; // القيمة الافتراضية للفلتر "الكل"

        foreach ($filter_tabs as $index => $tab) {
            // تحقق من هو الفلتر النشط حالياً
            $isActive = ($filter_status === $tab[0]);
            if ($isActive) {
                $activeIndex = $index; // قم بتحديث مؤشر الفلتر النشط
            }
            // أضف كلاس "active-glider-item" للرابط النشط لتغيير لون النص
            $active_class = $isActive ? ' active-glider-item' : '';
            echo "<a href='?status={$tab[0]}' class='$active_class'>{$tab[1]}</a>";
        }
    ?>
    <!-- هذا هو العنصر المتحرك الذي سيتحرك خلف الزر النشط -->
    <!-- يتم حساب عرضه وموقعه ديناميكياً باستخدام PHP -->
    <div class="glass-glider" style="
        width: calc(100% / <?php echo $total_tabs; ?>); 
        transform: translateX(<?php echo $activeIndex * 100; ?>%); "></div>
</div>
    </div>

    <!-- جدول الطلبات -->
<div class="table-container bg-white dark:bg-[#1e293b]">        <table class="w-full text-sm text-right text-slate-300">
       <!-- ==  جعل رأس الجدول متكيفاً(Responsive) == -->
        <!-- ============================================= -->
        <thead class="text-xs text-gray-500 dark:text-slate-400 uppercase bg-gray-50 dark:bg-slate-900/50">
            <tr>
                <th scope="col" class="px-6 py-4 font-black">رقم الطلب</th>
                <th scope="col" class="px-6 py-4 font-black">المريض</th>
                <th scope="col" class="px-6 py-4 font-black">تاريخ الطلب</th>
                <th scope="col" class="px-6 py-4 font-black">الحالة</th>
                <th scope="col" class="px-6 py-4 font-black">الإجمالي</th>
                <th scope="col" class="px-6 py-4 font-black text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($orders_result)):
                        
                        $statusText = ''; $statusPillClass = '';
                        switch ($order['Status']) {
                            case 'Pending':
                                $statusPillClass = "bg-amber-500/10 text-amber-400"; $statusText = $lang['status_pending']; break;
                            case 'Accepted':
                                $statusPillClass = "bg-blue-500/10 text-blue-400"; $statusText = "قيد التجهيز"; break;
                            case 'Delivered':
                                $statusPillClass = "bg-emerald-500/10 text-emerald-400"; $statusText = $lang['status_delivered']; break;
                            case 'Rejected':
                                $statusPillClass = "bg-rose-500/10 text-rose-400"; $statusText = $lang['status_rejected']; break;
                        }

                        $current_order_items = isset($order_items_data[$order['OrderID']]) ? $order_items_data[$order['OrderID']] : [];
                        $has_controlled = false;
                        foreach ($current_order_items as $item) {
                            if ($item['IsControlled'] == 1) $has_controlled = true;
                        }
                        // تم تجهيز بيانات JSON كما هي لاستخدامها في النافذة المنبثقة والإشعار
                        $order_json = htmlspecialchars(json_encode([
                            'id' => $order['OrderID'], 'date' => date('Y-m-d h:i A', strtotime($order['OrderDate'])),
                            'status' => $order['Status'], 'total' => $order['TotalAmount'],
                            'patient' => $order['Fname'] . ' ' . $order['Lname'], 'phone' => $order['Phone'],
                            'address' => $order['DeliveryAddress'], 'items' => $current_order_items,
                            'prescription' => $order['PrescriptionImage'], 'has_controlled' => $has_controlled
                        ]));
                    ?>
                    
                    <!-- 1. الفاصل: تم تغيير لون `border-slate-800` إلى `border-slate-700/50` لفاصل أنعم. -->
                    <!-- 2. تأثير التأشير: `hover:bg-slate-900/90` يقوم بتغيير لون الخلفية عند التمرير. -->
                    <!-- 3. إشعار المعلومات: تم إضافة `onmousemove` و `onmouseout` لاستدعاء دوال الإشعار. -->
      <tr class="border-b border-slate-700/50 hover:bg-slate-800 hover:ring-1 hover:ring-slate-100 transition-all duration-200 cursor-pointer"
                    
                    <!-- رقم الطلب -->
                    <td onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="px-6 py-5 font-mono font-bold text-gray-00 dark:text-white">#ORD-<?php echo $order['OrderID']; ?></td>
                    
                    <!-- العميل -->
                    <td onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 dark:text-emerald-400 font-bold text-sm">
                                <?php echo mb_substr($order['Fname'], 0, 1); ?>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-slate-400" dir="ltr"><?php echo htmlspecialchars($order['Phone']); ?></div>
                            </div>
                        </div>
                    </td>
                    
                    <!-- تاريخ الطلب -->
                    <td onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="px-6 py-5">
                         <div class="font-medium text-gray-700 dark:text-slate-300"><?php echo date('Y-m-d', strtotime($order['OrderDate'])); ?></div>
                         <div class="text-xs text-gray-500 dark:text-slate-500"><?php echo date('h:i A', strtotime($order['OrderDate'])); ?></div>
                    </td>

                    <!-- الحالة -->
                    <td onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="px-6 py-5">
                        <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase <?php echo $statusPillClass; ?>">
                            <?php echo $statusText; ?>
                        </span>
                    </td>
                    
                    <!-- الإجمالي -->
                    <td onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="px-6 py-5 font-bold text-gray-800 dark:text-white" dir="ltr">
                        <?php echo number_format($order['TotalAmount'], 2); ?> ₪
                    </td>

                    <!-- الإجراءات -->
                    <td class="px-6 py-5 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <button onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="p-2 rounded-lg text-gray-400 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-gray-700 dark:hover:text-white transition-colors" title="معاينة التفاصيل">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                            
                            <?php if ($order['Status'] == 'Pending'): ?>
                                <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Accepted')" class="p-2 rounded-lg text-emerald-500 dark:text-emerald-400 hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors" title="قبول وتجهيز">
                                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['Status'] == 'Accepted'): ?>
                                 <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Delivered')" class="p-2 rounded-lg text-blue-500 dark:text-blue-400 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-300 transition-colors" title="تأكيد التسليم">
                                    <i data-lucide="truck" class="w-5 h-5"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['Status'] == 'Pending'): ?>
                                <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Rejected')" class="p-2 rounded-lg text-rose-500 hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-400 transition-colors" title="رفض الطلب">
                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-20">
                        <div class="flex flex-col items-center gap-4 text-slate-500">
                            <i data-lucide="inbox" class="w-16 h-16"></i>
                            <span class="font-bold text-lg">لا توجد طلبات تطابق الفلتر الحالي</span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    
</main>

<!-- ====== تعديل: إضافة عنصر الإشعار (Tooltip) إلى HTML ====== -->
<div id="orderTooltip"></div>

<!-- نافذة تفاصيل الطلب (Modal) - لا تغيير هنا -->
<div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex justify-center items-end md:items-center transition-opacity p-0 md:p-4">
    <!-- ... محتوى النافذة المنبثقة يبقى كما هو بدون أي تعديل ... -->
    <div class="bg-white dark:bg-slate-900 w-full md:max-w-5xl max-h-[90vh] md:max-h-[85vh] rounded-t-[3rem] md:rounded-[3rem] shadow-2xl overflow-hidden flex flex-col transform transition-transform translate-y-full md:translate-y-0" id="modalContent">
        <div class="px-8 py-8 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 shrink-0"><div class="flex items-center gap-4"><div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-3xl text-[#0A7A48] shadow-inner"><i data-lucide="receipt" class="w-8 h-8"></i></div><div><h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2"><?php echo $lang['order_details']; ?> <span id="modalOrderId" class="text-[#0A7A48]" dir="ltr"></span></h2><p id="modalOrderDate" class="text-xs font-bold text-gray-400 mt-1 uppercase tracking-widest"></p></div></div><button onclick="closeOrderModal()" class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-slate-800 text-gray-500 hover:text-rose-50 hover:text-rose-500 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button></div>
        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-slate-900"><div class="grid grid-cols-1 lg:grid-cols-3 gap-10"><div class="lg:col-span-2 space-y-8"><div class="bg-gray-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-gray-100 dark:border-slate-800 overflow-hidden shadow-sm"><table class="w-full text-sm text-right"><thead class="bg-white/50 dark:bg-slate-800 border-b dark:border-slate-700 text-gray-500"><tr><th class="py-5 px-8 font-black uppercase tracking-wider">المنتج</th><th class="py-5 px-8 font-black text-center uppercase tracking-wider">الكمية</th><th class="py-5 px-8 font-black uppercase tracking-wider text-left">الإجمالي</th></tr></thead><tbody id="modalItemsTable" class="divide-y divide-gray-100 dark:divide-slate-700/50 text-gray-800 dark:text-gray-200"></tbody></table></div><div id="prescriptionSection" class="hidden"><div class="bg-rose-50 dark:bg-rose-950/20 rounded-[2.5rem] p-8 border-2 border-dashed border-rose-200 dark:border-rose-900/50 flex flex-col md:flex-row gap-8 items-center relative overflow-hidden"><a id="prescriptionImgLink" href="#" target="_blank" class="block relative group overflow-hidden rounded-[2rem] border-8 border-white dark:border-slate-800 shadow-xl shrink-0 bg-white"><img id="prescriptionImg" src="" alt="Rx" class="w-44 h-44 object-cover transition-transform duration-700 group-hover:scale-110"></a><div class="flex-1 space-y-4"><h3 class="text-xl font-black text-rose-700 dark:text-rose-400 flex items-center gap-2"><i data-lucide="file-check-2" class="w-6 h-6"></i> مراجعة الوصفة الطبية</h3><label class="flex items-center gap-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm cursor-pointer border border-rose-100 dark:border-rose-900/30 transition-all hover:ring-2 hover:ring-rose-500/20"><input type="checkbox" id="verifyPrescriptionCheck" class="w-6 h-6 text-rose-600 rounded-lg border-gray-300 focus:ring-rose-500"><span class="text-sm font-black text-gray-800 dark:text-gray-200 italic">أقر بمراجعتي للوصفة وتحملي للمسؤولية.</span></label></div></div></div></div><div class="space-y-8"><div class="bg-gray-50 dark:bg-slate-800/50 rounded-[2.5rem] p-8 border border-gray-100 dark:border-slate-800 shadow-sm space-y-6 text-sm"><h3 class="text-xs font-black text-gray-400 uppercase tracking-widest border-b pb-3">بيانات العميل</h3><div><span class="text-[10px] font-bold text-gray-400 block mb-1">الاسم</span><span id="modalPatientName" class="font-black text-gray-800 dark:text-gray-200"></span></div><div><span class="text-[10px] font-bold text-gray-400 block mb-1">الهاتف</span><span id="modalPatientPhone" class="font-bold text-gray-800 dark:text-gray-200" dir="ltr"></span></div><div><span class="text-[10px] font-bold text-gray-400 block mb-1">العنوان</span><span id="modalPatientAddress" class="font-bold text-gray-700 dark:text-gray-300 leading-relaxed"></span></div></div><div class="bg-gradient-to-br from-[#0A7A48] to-[#10b981] rounded-[2.5rem] p-8 text-white shadow-2xl text-center"><span class="text-green-100 text-xs font-black uppercase tracking-widest mb-2 block">إجمالي المطلوب</span><h2 id="modalTotalAmount" class="text-5xl font-black mb-4 tracking-tighter" dir="ltr"></h2><span class="bg-black/10 backdrop-blur-md px-6 py-2 rounded-2xl text-[10px] font-black border border-white/10 flex items-center gap-2 justify-center italic">الدفع عند الاستلام <i data-lucide="banknote" class="w-3.5 h-3.5"></i></span></div></div></div></div>
        <div class="p-8 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900 flex justify-between items-center"><button onclick="closeOrderModal()" class="px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 text-gray-500 font-black text-sm border border-gray-200 dark:border-slate-700 hover:bg-gray-100 transition-all">إغلاق</button><div id="dynamicActionButtons" class="flex gap-3"></div></div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    let currentOrderData = null;
    const currency = "₪";

    // ====== تعديل: إضافة دوال الإشعار (Tooltip) ======
    const tooltip = document.getElementById('orderTooltip');
    function showTooltip(event, jsonString) {
        const data = JSON.parse(jsonString);
        
        // بناء محتوى الإشعار
        tooltip.innerHTML = `
            <h4 class="font-mono">#ORD-${data.id}</h4>
            <p>العميل: <span>${data.patient}</span></p>
            <p>عدد الأصناف: <span>${data.items.length}</span></p>
            ${data.has_controlled ? '<p style="color:#f43f5e;">يحتوي على دواء مراقب 💊</p>' : ''}
        `;

        // إظهار الإشعار بجانب مؤشر الفأرة
        tooltip.style.display = 'block';
        tooltip.style.left = event.pageX + 15 + 'px';
        tooltip.style.top = event.pageY + 15 + 'px';
    }

    function hideTooltip() {
        tooltip.style.display = 'none';
    }
    // ====== نهاية تعديل الإشعار ======


    function viewOrderDetails(jsonString) {
        const order = JSON.parse(jsonString); currentOrderData = order;
        document.getElementById('modalOrderId').innerText = `#ORD-${order.id}`;
        document.getElementById('modalOrderDate').innerText = order.date;
        document.getElementById('modalPatientName').innerText = order.patient;
        document.getElementById('modalPatientPhone').innerText = order.phone || 'N/A';
        document.getElementById('modalPatientAddress').innerText = order.address;
        document.getElementById('modalTotalAmount').innerText = parseFloat(order.total).toFixed(2) + ' ' + currency;

        const tbody = document.getElementById('modalItemsTable'); tbody.innerHTML = '';
        order.items.forEach(item => {
            const rx = item.IsControlled == 1 ? '<span class="mx-2 bg-rose-100 text-rose-700 text-[9px] px-2 py-0.5 rounded font-black">Rx</span>' : '';
            tbody.innerHTML += `<tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition"><td class="py-5 px-6 font-black text-gray-800 dark:text-gray-100">${item.Name}${rx}</td><td class="py-5 px-6 text-center font-bold text-gray-500"><span class="bg-white dark:bg-slate-800 px-3 py-1 rounded-lg">x${item.Quantity}</span></td><td class="py-5 px-6 font-black text-emerald-600 text-left" dir="ltr">${parseFloat(item.Quantity * item.SoldPrice).toFixed(2)} ${currency}</td></tr>`;
        });

        const rxSec = document.getElementById('prescriptionSection');
        const vCh = document.getElementById('verifyPrescriptionCheck');
        if (order.has_controlled) {
            rxSec.classList.remove('hidden'); vCh.checked = false;
            document.getElementById('prescriptionImg').src = order.prescription ? `../uploads/${order.prescription}` : 'https://via.placeholder.com/200';
            document.getElementById('prescriptionImgLink').href = order.prescription ? `../uploads/${order.prescription}` : '#';
        } else { rxSec.classList.add('hidden'); vCh.checked = true; }

        const btnContainer = document.getElementById('dynamicActionButtons'); btnContainer.innerHTML = '';
        if (order.status === 'Pending') {
            btnContainer.innerHTML = `<button onclick="confirmOrderStatus(${order.id}, 'Rejected')" class="px-8 py-4 rounded-2xl bg-rose-50 text-rose-600 font-black text-sm hover:bg-rose-100 transition-all">رفض</button><button onclick="attemptAcceptOrder()" class="bg-[#0A7A48] hover:bg-[#044E29] text-white px-10 py-4 rounded-2xl font-black text-sm shadow-xl shadow-green-900/30 flex items-center justify-center gap-2"><i data-lucide="check-circle-2" class="w-5 h-5"></i> قبول وتجهيز</button>`;
        } else if (order.status === 'Accepted') {
            btnContainer.innerHTML = `<button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="bg-[#0A7A48] hover:bg-[#044E29] text-white px-10 py-4 rounded-2xl font-black text-sm shadow-xl shadow-green-900/30 flex items-center justify-center gap-2"><i data-lucide="truck" class="w-5 h-5"></i> تأكيد التسليم</button>`;
        }
        lucide.createIcons();
        const m = document.getElementById('orderModal'); const c = document.getElementById('modalContent');
        m.classList.remove('hidden'); setTimeout(() => c.classList.remove('translate-y-full'), 10);
    }

    function closeOrderModal() {
        const c = document.getElementById('modalContent');
        c.classList.add('translate-y-full'); setTimeout(() => document.getElementById('orderModal').classList.add('hidden'), 300);
    }

    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled && !document.getElementById('verifyPrescriptionCheck').checked) {
            Swal.fire({ icon: 'warning', title: "تنبيه أمني", text: "يرجى الإقرار بمراجعة الوصفة الطبية.", confirmButtonText: "حسناً", confirmButtonColor: '#f43f5e', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937', customClass: { popup: 'rounded-3xl' } });
            return;
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }

    function confirmOrderStatus(orderId, action) {
        let t = '', txt = '', b = '', c = '#0A7A48', i = 'question';
        if (action === 'Accepted') { t = "قبول الطلب؟"; txt = "سيتم إشعار المريض ببدء التجهيز."; b = "نعم، أقبل"; }
        else if (action === 'Rejected') { t = "رفض الطلب؟"; txt = "هل تريد إلغاء الطلب نهائياً؟"; b = "نعم، ارفض"; c = '#f43f5e'; i = 'warning'; }
        else if (action === 'Delivered') { t = "تأكيد التسليم؟"; txt = "هل تم تسليم الطلب واستلام ثمنه؟"; b = "نعم، تم"; }

        Swal.fire({ title: t, text: txt, icon: i, showCancelButton: true, confirmButtonColor: c, cancelButtonColor: '#94a3b8', confirmButtonText: b, cancelButtonText: "إلغاء", background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937', customClass: { popup: 'rounded-3xl' } }).then((r) => { if (r.isConfirmed) window.location.href = `orders.php?action=${action}&order_id=${orderId}`; });
    }
</script>