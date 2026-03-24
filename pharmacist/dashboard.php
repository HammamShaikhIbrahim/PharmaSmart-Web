<?php

include('../config/database.php');
session_start();
require_once('../includes/lang.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// ==========================================
// 1. الإحصائيات السريعة
// ==========================================
$salesQuery = "SELECT SUM(oi.Quantity * oi.SoldPrice) as TotalSales FROM OrderItems oi JOIN PharmacyStock ps ON oi.StockID = ps.StockID JOIN `Order` o ON oi.OrderID = o.OrderID WHERE ps.PharmacistID = $pharmacist_id AND DATE(o.OrderDate) = CURDATE() AND o.Status = 'Delivered'";
$salesResult = mysqli_fetch_assoc(mysqli_query($conn, $salesQuery));
$todaysSales = $salesResult['TotalSales'] ? number_format($salesResult['TotalSales'], 2) : "0.00";

$ordersQuery = "SELECT COUNT(DISTINCT o.OrderID) as PendingCount FROM `Order` o JOIN OrderItems oi ON o.OrderID = oi.OrderID JOIN PharmacyStock ps ON oi.StockID = ps.StockID WHERE ps.PharmacistID = $pharmacist_id AND o.Status = 'Pending'";
$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, $ordersQuery))['PendingCount'];

$lowStockQuery = "SELECT COUNT(*) as LowStockCount FROM PharmacyStock WHERE PharmacistID = $pharmacist_id AND Stock <= MinimumStock";
$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn, $lowStockQuery))['LowStockCount'];

// 💡 التعديل هنا: جلب بناءً على الأشهر المحددة من قبل الصيدلاني في قاعدة البيانات
$expiryQuery = "SELECT COUNT(*) as ExpiringCount FROM PharmacyStock WHERE PharmacistID = $pharmacist_id AND ExpiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ExpiryAlertMonths MONTH)";
$expiringCount = mysqli_fetch_assoc(mysqli_query($conn, $expiryQuery))['ExpiringCount'];

// ==========================================
// 2. الطلبات الأخيرة
// ==========================================
$recentOrdersQ = "
    SELECT DISTINCT
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress,
        u.Fname, u.Lname, u.Phone
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN PharmacyStock ps ON oi.StockID = ps.StockID
    WHERE ps.PharmacistID = $pharmacist_id
    ORDER BY o.OrderDate DESC LIMIT 5
";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQ);

$order_items_data = [];
$items_query = "
    SELECT oi.OrderID, sm.IsControlled
    FROM OrderItems oi
    JOIN PharmacyStock ps ON oi.StockID = ps.StockID
    JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
    WHERE ps.PharmacistID = $pharmacist_id
";
$items_result = mysqli_query($conn, $items_query);
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items_data[$item['OrderID']][] = $item;
}

// ==========================================
// 3. الطلبات المعلقة
// ==========================================
$pendingListQ = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, u.Fname, u.Lname
                 FROM `Order` o
                 JOIN User u ON o.PatientID = u.UserID
                 JOIN OrderItems oi ON o.OrderID = oi.OrderID
                 JOIN PharmacyStock ps ON oi.StockID = ps.StockID
                 WHERE ps.PharmacistID = $pharmacist_id AND o.Status = 'Pending'
                 GROUP BY o.OrderID
                 ORDER BY o.OrderDate ASC";
$pendingListResult = mysqli_query($conn, $pendingListQ);

// ==========================================
// 4. تنبيهات النواقص والصلاحية
// ==========================================
// 💡 أضفنا جلب StockID لتمريره في الرابط للتعديل الذكي
$lowStockListQ = "SELECT ps.StockID, sm.Name, ps.Stock, ps.MinimumStock
                  FROM PharmacyStock ps
                  JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
                  WHERE ps.PharmacistID = $pharmacist_id AND ps.Stock <= ps.MinimumStock
                  ORDER BY ps.Stock ASC LIMIT 5";
$lowStockListResult = mysqli_query($conn, $lowStockListQ);

$expiringListQ = "SELECT ps.StockID, sm.Name, ps.ExpiryDate, DATEDIFF(ps.ExpiryDate, CURDATE()) as DaysLeft
                  FROM PharmacyStock ps
                  JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
                  WHERE ps.PharmacistID = $pharmacist_id AND ps.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL ps.ExpiryAlertMonths MONTH)
                  ORDER BY ps.ExpiryDate ASC LIMIT 5";
$expiringListResult = mysqli_query($conn, $expiringListQ);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">
    <?php include('../includes/topbar.php'); ?>

    <div class="mb-8 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="p-2.5">
                <i data-lucide="layout-dashboard" class="text-[#0A7A48] dark:text-[#4ADE80] w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $lang['dashboard']; ?></h1>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-5 py-3 rounded-2xl shadow-sm flex items-center gap-3 transition-colors">
            <i data-lucide="calendar-days" class="text-[#0A7A48] dark:text-[#4ADE80] w-5 h-5"></i>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 tracking-wide" dir="ltr"><?php echo date('d M, Y'); ?></span>
        </div>
    </div>

    <!-- 1. شريط الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-200 dark:border-slate-700 shadow-sm flex items-center justify-between transition-all hover:shadow-md border-b-4 border-b-transparent hover:border-b-[#0A7A48] dark:hover:border-b-[#0A7A48] hover:-translate-y-1">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['todays_sales']; ?></p>
                <h3 class="text-3xl font-black text-gray-800 dark:text-white" dir="ltr"><?php echo $todaysSales; ?> ₪</h3>
            </div>
            <i data-lucide="banknote" class="w-12 h-12 text-[#0A7A48] dark:text-[#4ADE80] drop-shadow-sm opacity-80"></i>
        </div>

        <div class="relative">
            <button onclick="togglePendingOrders()" class="w-full bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-200 dark:border-slate-700 shadow-sm flex items-center justify-between transition-all hover:shadow-md border-b-4 border-b-transparent hover:border-b-amber-500 dark:hover:border-b-amber-500 hover:-translate-y-1 focus:outline-none text-right group">
                <div class="flex flex-col items-start gap-2.5">
                    <div class="flex items-center gap-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-bold"><?php echo $lang['pending_orders']; ?></p>
                        <?php if ($pendingOrders > 0): ?>
                            <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded-md text-[10px] font-black flex items-center gap-1 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                جديد
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $pendingOrders; ?></h3>
                </div>

                <div class="flex items-center gap-4">
                    <i data-lucide="clock" class="w-12 h-12 text-amber-500 drop-shadow-sm opacity-80"></i>
                    <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded-xl transition-colors group-hover:bg-gray-100 dark:group-hover:bg-slate-600 hidden md:block">
                        <i data-lucide="chevron-down" id="pendingChevron" class="w-5 h-5 text-gray-500 transition-transform duration-300"></i>
                    </div>
                </div>
            </button>

            <!-- القائمة المنسدلة -->
            <div id="pendingOrdersList" class="absolute top-[calc(100%+0.5rem)] w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-3xl shadow-xl z-20 overflow-hidden origin-top scale-y-0 opacity-0 transition-all duration-300 pointer-events-none">
                <div class="p-4 bg-amber-50/50 dark:bg-amber-900/10 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center">
                    <span class="text-sm font-black text-amber-600 dark:text-amber-400">تحتاج موافقتك!</span>
                    <a href="orders.php?status=Pending" class="text-xs font-bold text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">عرض الكل</a>
                </div>
                <div class="max-h-[300px] overflow-y-auto custom-scrollbar p-3 space-y-2">
                    <?php if (mysqli_num_rows($pendingListResult) > 0): ?>
                        <?php while ($p_order = mysqli_fetch_assoc($pendingListResult)): ?>
                            <a href="orders.php?status=Pending" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/30 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-2xl transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm border border-gray-200 dark:border-slate-600 group-hover:border-amber-200 dark:group-hover:border-amber-700 transition-colors">
                                        <i data-lucide="user" class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-sm text-gray-800 dark:text-white mb-0.5" dir="ltr">#ORD-<?php echo $p_order['OrderID']; ?></div>
                                        <div class="text-[11px] font-bold text-gray-500"><?php echo htmlspecialchars($p_order['Fname'] . ' ' . $p_order['Lname']); ?></div>
                                    </div>
                                </div>
                                <div class="font-black text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-800 px-3 py-1.5 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700" dir="ltr">
                                    <?php echo number_format($p_order['TotalAmount'], 2); ?> ₪
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-8 text-center flex flex-col items-center gap-3">
                            <i data-lucide="check-double" class="w-10 h-10 text-[#0A7A48] dark:text-[#4ADE80] opacity-80"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">لا توجد طلبات قيد الانتظار حالياً.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. جدول الطلبات -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden flex flex-col mb-8">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
            <div class="flex items-center gap-3">
                <i data-lucide="shopping-bag" class="text-[#0A7A48] dark:text-[#4ADE80] w-6 h-6"></i>
                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['recent_orders']; ?></h3>
            </div>
            <a href="orders.php" class="text-sm bg-[#E6F7ED] dark:bg-[#044E29]/40 text-[#0A7A48] dark:text-[#4ADE80] px-4 py-2 rounded-xl hover:bg-[#0A7A48] hover:text-white dark:hover:bg-[#4ADE80] dark:hover:text-[#012314] font-bold transition-colors shadow-sm">
                <?php echo $lang['view_all']; ?>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse min-w-[900px]">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700">
                    <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-5 font-bold whitespace-nowrap">رقم الطلب</th>
                        <th class="p-5 font-bold whitespace-nowrap">
                            <div class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-4 h-4 text-[#0A7A48]"></i> تاريخ الطلب</div>
                        </th>
                        <th class="p-5 font-bold whitespace-nowrap">العميل / الاتصال</th>
                        <th class="p-5 font-bold min-w-[180px]">العنوان</th>
                        <th class="p-5 font-bold whitespace-nowrap text-center">الأصناف</th>
                        <th class="p-5 font-bold whitespace-nowrap text-center">الإجمالي</th>
                        <th class="p-5 font-bold whitespace-nowrap text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)):
                            $current_items = $order_items_data[$order['OrderID']] ?? [];
                            $items_count = count($current_items);
                            $has_controlled = array_reduce($current_items, fn($carry, $item) => $carry || $item['IsControlled'] == 1, false);

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
                        ?>
                            <tr class="hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 group">
                                <td class="p-5 whitespace-nowrap">
                                    <div class="font-black text-gray-800 dark:text-white flex items-center gap-1.5" dir="ltr"><span class="text-[#0A7A48] dark:text-[#4ADE80] text-xs font-black">#</span>ORD-<?php echo $order['OrderID']; ?></div>
                                </td>
                                <td class="p-5 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200 mb-1"><span><?php echo date('d M Y', strtotime($order['OrderDate'])); ?></span></div>
                                    <div class="flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400"><span dir="ltr"><?php echo date('h:i A', strtotime($order['OrderDate'])); ?></span></div>
                                </td>
                                <td class="p-5 whitespace-nowrap">
                                    <div class="flex items-center gap-3 mb-1.5"><span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span></div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 text-[#0A7A48] shrink-0"></i><span dir="ltr"><?php echo htmlspecialchars($order['Phone'] ?? 'لا يوجد رقم'); ?></span></div>
                                </td>
                                <td class="p-5">
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 font-medium"><i data-lucide="map-pin" class="w-4 h-4 text-[#0A7A48] shrink-0"></i><span class="leading-relaxed font-medium line-clamp-2"><?php echo htmlspecialchars($order['DeliveryAddress'] ?? 'الاستلام من الصيدلية'); ?></span></div>
                                </td>
                                <td class="p-5 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#0A7A48]/10 dark:bg-[#4ADE80]/10 text-[#0A7A48] dark:text-[#4ADE80] font-black text-[13px] shadow-sm border border-[#0A7A48]/15"><?php echo $items_count; ?></span>
                                        <?php if ($has_controlled): ?><span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 text-[9px] px-1.5 py-0.5 rounded-full font-black border border-amber-200 dark:border-amber-800 uppercase tracking-wide">Rx</span><?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-5 whitespace-nowrap text-center">
                                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-[15px] mb-1.5" dir="ltr"><?php echo number_format($order['TotalAmount'], 2); ?> ₪</div>
                                    <?php if ($order['PaymentMethod'] == 'COD'): ?><span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 text-[10px] px-2 py-0.5 rounded-full font-bold border border-gray-200 dark:border-slate-600">COD</span>
                                    <?php else: ?><span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] px-2 py-0.5 rounded-full font-bold border border-blue-100 dark:border-blue-800">Card</span><?php endif; ?>
                                </td>
                                <td class="p-5 text-center whitespace-nowrap">
                                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold inline-flex items-center justify-center gap-1.5 shadow-sm <?php echo $statusColor; ?>">
                                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5"></i>
                                        <?php $statusLabels = ['Pending' => 'قيد الانتظار', 'Accepted' => 'جاري التجهيز', 'Delivered' => 'مكتمل', 'Rejected' => 'مرفوض'];
                                        echo $statusLabels[$order['Status']] ?? $order['Status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-16">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="relative w-24 h-24 mb-6">
                                        <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                                        <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10"><i data-lucide="shopping-cart" class="w-10 h-10 text-[#0A7A48] dark:text-[#4ADE80]"></i></div>
                                    </div>
                                    <h3 class="text-lg font-black text-gray-800 dark:text-white mb-2">لا توجد طلبات حديثة</h3>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. التنبيهات -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- نواقص المخزون -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-rose-100 dark:border-rose-900/20 flex flex-col overflow-hidden relative">
            <div class="absolute top-0 right-0 w-2 h-full bg-rose-500"></div>
            <div class="p-5 border-b border-gray-50 dark:border-slate-700/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i data-lucide="package-minus" class="w-6 h-6 text-rose-500 opacity-80"></i>
                    <h3 class="text-base font-black text-gray-800 dark:text-white"><?php echo $lang['low_stock_alert']; ?></h3>
                </div>
                <?php if ($lowStockCount > 0): ?><span class="bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 px-3 py-1 rounded-full text-xs font-bold shadow-sm"><?php echo $lowStockCount; ?> عناصر</span><?php endif; ?>
            </div>
            <div class="p-2 flex-1">
                <?php if (mysqli_num_rows($lowStockListResult) > 0): ?>
                    <ul class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <?php while ($item = mysqli_fetch_assoc($lowStockListResult)): ?>
                            <li class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 rounded-xl transition-colors group/item">
                                <span class="font-bold text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($item['Name']); ?></span>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-400">الكمية:</span>
                                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800 px-2 py-0.5 rounded-lg text-xs font-black min-w-[30px] text-center shadow-sm" dir="ltr"><?php echo $item['Stock']; ?></span>
                                    </div>
                                    <!-- 💡 رابط التوجيه لصفحة الأدوية مع فتح التعديل مباشرة -->
                                    <a href="medicines.php?edit_id=<?php echo $item['StockID']; ?>" class="group w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 hover:bg-[#0A7A48]/10 dark:hover:bg-[#4ADE80]/20 text-[#0A7A48] dark:text-[#4ADE80]" title="تحديث الكمية">
                                        <i data-lucide="arrow-left" class="w-4 h-4 rtl:-scale-x-100"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center p-12 text-center">
                        <div class="relative w-16 h-16 mb-4">
                            <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                            <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                                <i data-lucide="check-circle" class="w-8 h-8 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                            </div>
                        </div>
                        <p class="font-bold text-sm text-gray-600 dark:text-gray-300">المخزون ممتاز، لا يوجد نواقص</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- تواريخ الصلاحية -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-orange-100 dark:border-orange-900/20 flex flex-col overflow-hidden relative">
            <div class="absolute top-0 right-0 w-2 h-full bg-orange-400"></div>
            <div class="p-5 border-b border-gray-50 dark:border-slate-700/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i data-lucide="calendar-off" class="w-6 h-6 text-orange-500 opacity-80"></i>
                    <h3 class="text-base font-black text-gray-800 dark:text-white">تنبيهات الصلاحية</h3>
                </div>
                <?php if ($expiringCount > 0): ?><span class="bg-orange-50 text-orange-600 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-800/50 dark:text-orange-400 px-3 py-1 rounded-full text-xs font-bold shadow-sm"><?php echo $expiringCount; ?> عناصر</span><?php endif; ?>
            </div>
            <div class="p-2 flex-1">
                <?php if (mysqli_num_rows($expiringListResult) > 0): ?>
                    <ul class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <?php while ($exp = mysqli_fetch_assoc($expiringListResult)):
                            $daysLeft = (int)$exp['DaysLeft'];
                            if ($daysLeft < 0) {
                                $badgeClass = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border-rose-200 dark:border-rose-800";
                                $badgeText = "منتهي منذ " . abs($daysLeft) . " يوم";
                            } elseif ($daysLeft == 0) {
                                $badgeClass = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border-rose-200 dark:border-rose-800 animate-pulse";
                                $badgeText = "ينتهي اليوم!";
                            } else {
                                $badgeClass = "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border-amber-200 dark:border-amber-800";
                                $badgeText = "باقي " . $daysLeft . " يوم";
                            }
                        ?>
                            <li class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 rounded-xl transition-colors group/item">
                                <span class="font-bold text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($exp['Name']); ?></span>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-400" dir="ltr"><?php echo $exp['ExpiryDate']; ?></span>
                                        <span class="border <?php echo $badgeClass; ?> px-2 py-0.5 rounded-lg text-xs font-black min-w-[75px] text-center shadow-sm"><?php echo $badgeText; ?></span>
                                    </div>
                                    <!-- 💡 رابط التوجيه الذكي -->
                                    <a href="medicines.php?edit_id=<?php echo $exp['StockID']; ?>" class="group w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 hover:bg-[#0A7A48]/10 dark:hover:bg-[#4ADE80]/20 text-[#0A7A48] dark:text-[#4ADE80]" title="تحديث الصلاحية">
                                        <i data-lucide="arrow-left" class="w-4 h-4 rtl:-scale-x-100"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center p-12 text-center">
                        <div class="relative w-16 h-16 mb-4">
                            <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                            <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10"><i data-lucide="shield-check" class="w-8 h-8 text-[#0A7A48] dark:text-[#4ADE80]"></i></div>
                        </div>
                        <p class="font-bold text-sm text-gray-600 dark:text-gray-300">جميع الأدوية صالحة تماماً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    function togglePendingOrders() {
        const list = document.getElementById('pendingOrdersList');
        const chevron = document.getElementById('pendingChevron');

        if (list.classList.contains('scale-y-0')) {
            list.classList.remove('scale-y-0', 'opacity-0', 'pointer-events-none');
            list.classList.add('scale-y-100', 'opacity-100');
            chevron.style.transform = 'rotate(180deg)';
        } else {
            list.classList.remove('scale-y-100', 'opacity-100');
            list.classList.add('scale-y-0', 'opacity-0', 'pointer-events-none');
            chevron.style.transform = 'rotate(0deg)';
        }
    }

    document.addEventListener('click', function(event) {
        const list = document.getElementById('pendingOrdersList');
        if (!list) return;

        const button = list.previousElementSibling;
        if (!button.contains(event.target) && !list.contains(event.target)) {
            list.classList.remove('scale-y-100', 'opacity-100');
            list.classList.add('scale-y-0', 'opacity-0', 'pointer-events-none');
            document.getElementById('pendingChevron').style.transform = 'rotate(0deg)';
        }
    });
</script>

<?php include('../includes/footer.php'); ?>