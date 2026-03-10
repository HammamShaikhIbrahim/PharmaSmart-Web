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
// 2. معالجة تحديث حالة الطلب (Actions)
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
// 3. جلب بيانات الطلبات 
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
$status_condition = ($filter_status !== 'All') ? "AND o.Status = '$filter_status'" : "";

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
    ORDER BY FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// ==========================================
// 4. جلب تفاصيل الأدوية لكل طلب
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
    .text-gradient {
        background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-image: linear-gradient(90deg, #0A7A48, #10b981);
    }
    .premium-card {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 2px solid transparent;
    }
    .premium-card:hover { transform: translateY(-8px); }
    .premium-card.status-Pending:hover { border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 20px 40px -10px rgba(245, 158, 11, 0.15); }
    .premium-card.status-Accepted:hover { border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.15); }
    .premium-card.status-Delivered:hover { border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.15); }
    .premium-card.status-Rejected:hover { border-color: rgba(244, 63, 94, 0.4); box-shadow: 0 20px 40px -10px rgba(244, 63, 94, 0.15); }

    .stripes-bg {
        background-image: repeating-linear-gradient(45deg, rgba(244, 63, 94, 0.05), rgba(244, 63, 94, 0.05) 10px, transparent 10px, transparent 20px);
    }
    .dark .stripes-bg { background-image: repeating-linear-gradient(45deg, rgba(244, 63, 94, 0.15), rgba(244, 63, 94, 0.15) 10px, transparent 10px, transparent 20px); }
    .hide-scroll::-webkit-scrollbar { display: none; }
</style>

<main class="flex-1 p-4 md:p-8 bg-gray-50/50 dark:bg-[#0B1120] h-full overflow-y-auto transition-colors duration-300 relative">

    <?php include('../includes/topbar.php'); ?>

    <!-- ترويسة الصفحة -->
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

        <!-- شريط الفلاتر الكبسولي المعدل -->
        <div class="bg-white dark:bg-slate-800 p-1.5 rounded-full shadow-sm border border-gray-200 dark:border-slate-700 flex items-center overflow-x-auto hide-scroll">
            <?php 
            // إعداد مصفوفة الفلاتر مع الألوان المطلوبة عند الهوفر والاختيار
            $filter_tabs = [
                ['All', $lang['filter_all'], 'hover:text-white active:text-white'],
                ['Pending', $lang['filter_pending'], 'hover:text-rose-500 active:text-rose-500'],
                ['Accepted', $lang['filter_processing'], 'hover:text-blue-500 active:text-blue-500'],
                ['Delivered', $lang['filter_delivered'], 'hover:text-emerald-500 active:text-emerald-500']
            ];

            foreach ($filter_tabs as $tab) {
                $isActive = ($filter_status === $tab[0]);
                // الكلاس الأساسي للفلاتر
                $baseClass = "px-7 py-2.5 rounded-full text-sm font-black transition-all duration-300 whitespace-nowrap ";
                
                // إضافة الألوان بناءً على حالة النشاط والهوفر
                if ($isActive) {
                    $activeColor = str_replace(['hover:', 'active:'], ['', ''], $tab[2]);
                    $stateClass = "bg-gray-50 dark:bg-slate-700 shadow-inner ring-1 ring-gray-100 dark:ring-slate-600 $activeColor";
                } else {
                    $stateClass = "text-gray-400 " . $tab[2];
                }
                
                echo "<a href='?status={$tab[0]}' class='$baseClass $stateClass'>{$tab[1]}</a>";
            }
            ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 pb-10">

        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders_result)):

                $statusColor = ""; $statusBg = ""; $statusIcon = "";
                $statusClass = "status-" . $order['Status']; 

                switch ($order['Status']) {
                    case 'Pending':
                        $statusColor = "text-amber-600 dark:text-amber-400";
                        $statusBg = "bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20";
                        $statusText = $lang['status_pending'];
                        $statusIcon = "clock";
                        break;
                    case 'Accepted':
                        $statusColor = "text-blue-600 dark:text-blue-400";
                        $statusBg = "bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20";
                        $statusText = "قيد التجهيز"; // 💡 تم التعديل هنا بناءً على طلبك
                        $statusIcon = "package";
                        break;
                    case 'Delivered':
                        $statusColor = "text-emerald-600 dark:text-emerald-400";
                        $statusBg = "bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20";
                        $statusText = $lang['status_delivered'];
                        $statusIcon = "check-circle-2";
                        break;
                    case 'Rejected':
                        $statusColor = "text-rose-600 dark:text-rose-400";
                        $statusBg = "bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20";
                        $statusText = $lang['status_rejected'];
                        $statusIcon = "x-circle";
                        break;
                }

                $current_order_items = isset($order_items_data[$order['OrderID']]) ? $order_items_data[$order['OrderID']] : [];
                $has_controlled = false; $total_items = 0;
                foreach ($current_order_items as $item) {
                    if ($item['IsControlled'] == 1) $has_controlled = true;
                    $total_items += $item['Quantity'];
                }

                $order_json = htmlspecialchars(json_encode([
                    'id' => $order['OrderID'], 'date' => date('Y-m-d h:i A', strtotime($order['OrderDate'])),
                    'status' => $order['Status'], 'total' => $order['TotalAmount'],
                    'patient' => $order['Fname'] . ' ' . $order['Lname'], 'phone' => $order['Phone'],
                    'address' => $order['DeliveryAddress'], 'items' => $current_order_items,
                    'prescription' => $order['PrescriptionImage'], 'has_controlled' => $has_controlled
                ]));
            ?>

                <div class="premium-card <?php echo $statusClass; ?> bg-white dark:bg-slate-800 rounded-[2rem] p-1.5 shadow-sm hover:shadow-xl relative flex flex-col h-full border border-gray-100 dark:border-slate-700/50">
                    <div class="bg-gray-50/50 dark:bg-slate-800/80 rounded-[1.75rem] p-6 h-full flex flex-col border border-gray-100 dark:border-slate-700/50">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight" dir="ltr">#ORD-<?php echo $order['OrderID']; ?></h3>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1 block"><?php echo date('M d, h:i A', strtotime($order['OrderDate'])); ?></span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl border <?php echo $statusBg; ?> <?php echo $statusColor; ?> flex items-center gap-1.5 text-[10px] font-black uppercase">
                                <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5"></i>
                                <?php echo $statusText; ?>
                            </div>
                        </div>

                        <!-- المريض والمحادثة المعدلة -->
                        <div class="mb-6 p-4 rounded-3xl bg-white dark:bg-slate-700/50 border border-gray-100 dark:border-slate-600 shadow-sm flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-inner">
                                    <i data-lucide="user" class="w-5 h-5"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="font-black text-gray-800 dark:text-gray-100 text-sm block truncate"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">مريض مسجل</span>
                                </div>
                            </div>
                            <!-- 💡 أيقونة المحادثة بدلاً من الاتصال -->
                            <button class="w-10 h-10 rounded-2xl bg-gray-50 dark:bg-slate-600 flex items-center justify-center text-gray-400 hover:text-emerald-600 transition-colors shadow-sm">
                                <i data-lucide="message-square-more" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="flex-1">
                            <?php if ($has_controlled): ?>
                                <div class="mb-6 rounded-[1.5rem] p-4 border border-rose-200 dark:border-rose-900/50 stripes-bg flex items-center gap-4 relative overflow-hidden">
                                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-rose-500"></div>
                                    <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center text-rose-500 shadow-sm shrink-0">
                                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-black text-rose-700 dark:text-rose-400 block italic">Rx Required</span>
                                        <span class="text-[10px] font-bold text-rose-600/70 dark:text-rose-300/70">يتطلب مراجعة الروشتة المرفقة</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- 💡 ملء الفراغ بالأصناف المطلوبة -->
                                <div class="mb-6">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-3 px-1">الأصناف المطلوبة:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <?php 
                                        $cnt = 0;
                                        foreach($current_order_items as $it): 
                                            if($cnt < 2): 
                                        ?>
                                            <div class="bg-emerald-50 dark:bg-emerald-900/20 text-[#0A7A48] dark:text-emerald-400 px-3 py-1.5 rounded-xl text-[10px] font-bold border border-emerald-100 dark:border-emerald-800/50 flex items-center gap-1.5">
                                                <i data-lucide="pill" class="w-3 h-3"></i>
                                                <span class="truncate max-w-[80px]"><?php echo $it['Name']; ?></span>
                                            </div>
                                        <?php $cnt++; endif; endforeach; ?>
                                        <?php if(count($current_order_items) > 2): ?>
                                            <div class="bg-gray-100 dark:bg-slate-700 text-gray-500 px-3 py-1.5 rounded-xl text-[10px] font-bold border border-gray-200 dark:border-slate-600">+ <?php echo count($current_order_items) - 2; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-5 border-t border-dashed border-gray-200 dark:border-slate-700 flex items-end justify-between mt-auto">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">المجموع</span>
                                <div class="flex items-baseline gap-1" dir="ltr">
                                    <span class="text-3xl font-black text-gray-900 dark:text-white leading-none"><?php echo number_format($order['TotalAmount'], 2); ?></span>
                                    <span class="text-sm font-bold text-gray-1000">₪</span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <!-- 💡 أيقونة العين مع الأنيميشن -->
                                <button onclick="viewOrderDetails('<?php echo $order_json; ?>')" class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 text-gray-500 hover:text-emerald-500 transition-all duration-300 hover:scale-125 flex items-center justify-center shadow-sm" title="معاينة">
                                    <i data-lucide="eye" class="w-6 h-6"></i>
                                </button>

                                <?php if ($order['Status'] == 'Pending'): ?>
                                    <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Accepted')" class="w-12 h-12 rounded-2xl bg-[#0A7A48] text-white flex items-center justify-center hover:bg-[#044E29] transition-all shadow-lg shadow-green-900/20" title="قبول وتجهيز">
                                        <i data-lucide="check" class="w-6 h-6"></i>
                                    </button>
                                <?php elseif ($order['Status'] == 'Accepted'): ?>
                                    <button onclick="confirmOrderStatus(<?php echo $order['OrderID']; ?>, 'Delivered')" class="px-5 h-12 rounded-2xl bg-[#0A7A48] text-white flex items-center justify-center gap-2 font-bold hover:bg-[#044E29] transition-all shadow-lg">
                                        <i data-lucide="truck" class="w-5 h-5"></i> تسليم
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full py-32 flex flex-col items-center opacity-50"><i data-lucide="inbox" class="w-20 h-20 mb-4 text-gray-300"></i><h2 class="text-xl font-bold text-gray-400">لا توجد طلبات</h2></div>
        <?php endif; ?>
    </div>
</main>

<!-- ==========================================
     نافذة تفاصيل الطلب (Modal)
=========================================== -->
<div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex justify-center items-end md:items-center transition-opacity p-0 md:p-4">
    <div class="bg-white dark:bg-slate-900 w-full md:max-w-5xl max-h-[90vh] md:max-h-[85vh] rounded-t-[3rem] md:rounded-[3rem] shadow-2xl overflow-hidden flex flex-col transform transition-transform translate-y-full md:translate-y-0" id="modalContent">
        
        <div class="px-8 py-8 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 shrink-0">
            <div class="flex items-center gap-4">
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-3xl text-[#0A7A48] shadow-inner"><i data-lucide="receipt" class="w-8 h-8"></i></div>
                <div><h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2"><?php echo $lang['order_details']; ?> <span id="modalOrderId" class="text-[#0A7A48]" dir="ltr"></span></h2><p id="modalOrderDate" class="text-xs font-bold text-gray-400 mt-1 uppercase tracking-widest"></p></div>
            </div>
            <button onclick="closeOrderModal()" class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-slate-800 text-gray-500 hover:text-rose-50 hover:text-rose-500 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-slate-900">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-gray-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-gray-100 dark:border-slate-800 overflow-hidden shadow-sm">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-white/50 dark:bg-slate-800 border-b dark:border-slate-700 text-gray-500"><tr><th class="py-5 px-8 font-black uppercase tracking-wider">المنتج</th><th class="py-5 px-8 font-black text-center uppercase tracking-wider">الكمية</th><th class="py-5 px-8 font-black uppercase tracking-wider text-left">الإجمالي</th></tr></thead>
                            <tbody id="modalItemsTable" class="divide-y divide-gray-100 dark:divide-slate-700/50 text-gray-800 dark:text-gray-200"></tbody>
                        </table>
                    </div>

                    <!-- 💡 ملء الفراغ في المودال بشريط الحالة -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm relative overflow-hidden">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">مراحل سير الطلب</h3>
                        <div class="flex justify-between items-center relative">
                            <div class="absolute left-0 top-1/2 w-full h-0.5 bg-gray-100 dark:bg-slate-700 -z-0"></div>
                            <div class="relative z-10 flex flex-col items-center gap-2"><div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-lg"><i data-lucide="check" class="w-4 h-4"></i></div><span class="text-[9px] font-black text-emerald-600">جديد</span></div>
                            <div class="relative z-10 flex flex-col items-center gap-2"><div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center text-gray-400"><i data-lucide="package" class="w-4 h-4"></i></div><span class="text-[9px] font-bold text-gray-400">تجهيز</span></div>
                            <div class="relative z-10 flex flex-col items-center gap-2"><div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center text-gray-400"><i data-lucide="truck" class="w-4 h-4"></i></div><span class="text-[9px] font-bold text-gray-400">توصيل</span></div>
                        </div>
                    </div>

                    <div id="prescriptionSection" class="hidden">
                        <div class="bg-rose-50 dark:bg-rose-950/20 rounded-[2.5rem] p-8 border-2 border-dashed border-rose-200 dark:border-rose-900/50 flex flex-col md:flex-row gap-8 items-center relative overflow-hidden">
                            <a id="prescriptionImgLink" href="#" target="_blank" class="block relative group overflow-hidden rounded-[2rem] border-8 border-white dark:border-slate-800 shadow-xl shrink-0 bg-white">
                                <img id="prescriptionImg" src="" alt="Rx" class="w-44 h-44 object-cover transition-transform duration-700 group-hover:scale-110">
                            </a>
                            <div class="flex-1 space-y-4">
                                <h3 class="text-xl font-black text-rose-700 dark:text-rose-400 flex items-center gap-2"><i data-lucide="file-check-2" class="w-6 h-6"></i> مراجعة الوصفة الطبية</h3>
                                <label class="flex items-center gap-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm cursor-pointer border border-rose-100 dark:border-rose-900/30 transition-all hover:ring-2 hover:ring-rose-500/20"><input type="checkbox" id="verifyPrescriptionCheck" class="w-6 h-6 text-rose-600 rounded-lg border-gray-300 focus:ring-rose-500"><span class="text-sm font-black text-gray-800 dark:text-gray-200 italic">أقر بمراجعتي للوصفة وتحملي للمسؤولية.</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="bg-gray-50 dark:bg-slate-800/50 rounded-[2.5rem] p-8 border border-gray-100 dark:border-slate-800 shadow-sm space-y-6 text-sm">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest border-b pb-3">بيانات العميل</h3>
                        <div><span class="text-[10px] font-bold text-gray-400 block mb-1">الاسم</span><span id="modalPatientName" class="font-black text-gray-800 dark:text-gray-200"></span></div>
                        <div><span class="text-[10px] font-bold text-gray-400 block mb-1">الهاتف</span><span id="modalPatientPhone" class="font-bold text-gray-800 dark:text-gray-200" dir="ltr"></span></div>
                        <div><span class="text-[10px] font-bold text-gray-400 block mb-1">العنوان</span><span id="modalPatientAddress" class="font-bold text-gray-700 dark:text-gray-300 leading-relaxed"></span></div>
                    </div>
                    <div class="bg-gradient-to-br from-[#0A7A48] to-[#10b981] rounded-[2.5rem] p-8 text-white shadow-2xl text-center">
                        <span class="text-green-100 text-xs font-black uppercase tracking-widest mb-2 block">إجمالي المطلوب</span>
                        <h2 id="modalTotalAmount" class="text-5xl font-black mb-4 tracking-tighter" dir="ltr"></h2>
                        <span class="bg-black/10 backdrop-blur-md px-6 py-2 rounded-2xl text-[10px] font-black border border-white/10 flex items-center gap-2 justify-center italic">الدفع عند الاستلام <i data-lucide="banknote" class="w-3.5 h-3.5"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900 flex justify-between items-center">
            <button onclick="closeOrderModal()" class="px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 text-gray-500 font-black text-sm border border-gray-200 dark:border-slate-700 hover:bg-gray-100 transition-all">إغلاق</button>
            <div id="dynamicActionButtons" class="flex gap-3"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() { lucide.createIcons(); });
    let currentOrderData = null;
    const currency = "₪";

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

<?php include('../includes/footer.php'); ?>