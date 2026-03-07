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
// 2. جلب بيانات الصيدلية (الشعار والاسم)
// ==========================================
$pharmaInfoQuery = mysqli_query($conn, "SELECT PharmacyName, Logo FROM Pharmacist WHERE PharmacistID = $pharmacist_id");
$pharmaInfo = mysqli_fetch_assoc($pharmaInfoQuery);
$pharmaName = $pharmaInfo['PharmacyName'];
$pharmaLogo = $pharmaInfo['Logo'] ? $pharmaInfo['Logo'] : 'default.png';

// ==========================================
// 3. جلب الإحصائيات (Cards Data)
// ==========================================
$salesQuery = "SELECT SUM(oi.Quantity * oi.SoldPrice) as TotalSales 
               FROM OrderItems oi 
               JOIN Medicine m ON oi.MedicineID = m.MedicineID 
               JOIN `Order` o ON oi.OrderID = o.OrderID 
               WHERE m.PharmacistID = $pharmacist_id 
               AND DATE(o.OrderDate) = CURDATE() 
               AND o.Status != 'Rejected'";
$salesResult = mysqli_fetch_assoc(mysqli_query($conn, $salesQuery));
$todaysSales = $salesResult['TotalSales'] ? number_format($salesResult['TotalSales'], 2) : "0.00";

$ordersQuery = "SELECT COUNT(DISTINCT o.OrderID) as PendingCount 
                FROM `Order` o 
                JOIN OrderItems oi ON o.OrderID = oi.OrderID 
                JOIN Medicine m ON oi.MedicineID = m.MedicineID 
                WHERE m.PharmacistID = $pharmacist_id AND o.Status = 'Pending'";
$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, $ordersQuery))['PendingCount'];

$lowStockQuery = "SELECT COUNT(*) as LowStockCount FROM Medicine 
                  WHERE PharmacistID = $pharmacist_id AND Stock <= MinimumStock";
$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn, $lowStockQuery))['LowStockCount'];

$expiryQuery = "SELECT COUNT(*) as ExpiringCount FROM Medicine 
                WHERE PharmacistID = $pharmacist_id 
                AND ExpiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$expiringCount = mysqli_fetch_assoc(mysqli_query($conn, $expiryQuery))['ExpiringCount'];


// ==========================================
// 4. جلب بيانات الجداول (Tables Data)
// ==========================================
$recentOrdersQ = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, u.Fname, u.Lname, 
                  COUNT(oi.MedicineID) as ItemsCount
                  FROM `Order` o
                  JOIN User u ON o.PatientID = u.UserID
                  JOIN OrderItems oi ON o.OrderID = oi.OrderID
                  JOIN Medicine m ON oi.MedicineID = m.MedicineID
                  WHERE m.PharmacistID = $pharmacist_id
                  GROUP BY o.OrderID
                  ORDER BY o.OrderDate DESC LIMIT 5";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQ);

$lowStockListQ = "SELECT m.Name, m.Stock, m.MinimumStock, c.Name as CategoryName 
                  FROM Medicine m
                  LEFT JOIN Category c ON m.CategoryID = c.CategoryID
                  WHERE m.PharmacistID = $pharmacist_id AND m.Stock <= m.MinimumStock 
                  ORDER BY m.Stock ASC LIMIT 5";
$lowStockListResult = mysqli_query($conn, $lowStockListQ);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <?php include('../includes/topbar.php'); ?>



    <!-- ==========================================
         قسم الكروت الإحصائية (تم إضافة تأثيرات الهوفر من الأدمن)
    =========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- 1. كرت مبيعات اليوم (Emerald) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex flex-col justify-center
                    border-b-4 border-transparent hover:border-emerald-500 dark:hover:border-emerald-400
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-emerald-900/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo isset($lang['todays_sales']) ? $lang['todays_sales'] : "مبيعات اليوم"; ?></p>
                    <h3 class="text-3xl font-black text-emerald-600 dark:text-emerald-400">
                        <?php echo $todaysSales; ?>
                        <span class="text-lg text-emerald-400 font-bold ml-1"><?php echo isset($lang['currency']) ? $lang['currency'] : "₪"; ?></span>
                    </h3>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <i data-lucide="banknote" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- 2. كرت الطلبات المعلقة (Teal) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex flex-col justify-center relative
                    border-b-4 border-transparent hover:border-teal-500 dark:hover:border-teal-400
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-teal-900/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo isset($lang['pending_orders']) ? $lang['pending_orders'] : "الطلبات المعلقة"; ?></p>
                    <h3 class="text-3xl font-black text-teal-600 dark:text-teal-400"><?php echo $pendingOrders; ?></h3>
                </div>
                <div class="p-3 bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded-xl">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
            </div>
            <?php if ($pendingOrders > 0): ?>
                <span class="absolute top-6 rtl:left-6 ltr:right-6 w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-sm"></span>
            <?php endif; ?>
        </div>

        <!-- 3. كرت نواقص المخزون (Rose) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex flex-col justify-center
                    border-b-4 border-transparent hover:border-rose-500 dark:hover:border-rose-400
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-rose-900/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo isset($lang['low_stock_items']) ? $lang['low_stock_items'] : "نواقص المخزون"; ?></p>
                    <h3 class="text-3xl font-black text-rose-500 dark:text-rose-400"><?php echo $lowStockCount; ?></h3>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-500 dark:text-rose-400 rounded-xl">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- 4. كرت قريبة الانتهاء (Orange) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex flex-col justify-center
                    border-b-4 border-transparent hover:border-orange-500 dark:hover:border-orange-400
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-orange-900/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo isset($lang['expiring_soon']) ? $lang['expiring_soon'] : "قريبة الانتهاء"; ?></p>
                    <h3 class="text-3xl font-black text-orange-500 dark:text-orange-400"><?php echo $expiringCount; ?></h3>
                </div>
                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 text-orange-500 dark:text-orange-400 rounded-xl">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- ==========================================
         قسم الجداول (الطلبات الأخيرة + النواقص)
    =========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- القسم الأكبر (2 من 3): جدول أحدث الطلبات -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden flex flex-col transition-colors">
            <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['recent_orders']; ?></h3>
                <a href="orders.php" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline font-bold"><?php echo $lang['view_all']; ?></a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-sm">
                    <thead class="bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700">
                        <tr class="text-gray-500 dark:text-gray-400 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                            <th class="p-4 font-bold">ID</th>
                            <th class="p-4 font-bold"><?php echo $lang['time']; ?></th>
                            <th class="p-4 font-bold"><?php echo $lang['customer']; ?></th>
                            <th class="p-4 font-bold"><?php echo $lang['amount']; ?></th>
                            <th class="p-4 font-bold"><?php echo $lang['items']; ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">
                                    <td class="p-4 font-bold text-gray-800 dark:text-gray-200">#ORD-<?php echo $order['OrderID']; ?></td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400 font-medium" dir="ltr">
                                        <?php echo date('h:i A', strtotime($order['OrderDate'])); ?>
                                    </td>
                                    <td class="p-4 font-bold text-gray-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?>
                                    </td>
                                    <td class="p-4 font-bold text-emerald-600 dark:text-emerald-400">
                                        <?php echo number_format($order['TotalAmount'], 2); ?> <span class="text-xs"><?php echo $lang['currency']; ?></span>
                                    </td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                                        <?php echo $order['ItemsCount']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-400 dark:text-gray-500 font-medium">
                                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                                    <p><?php echo $lang['no_recent_orders']; ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- القسم الأصغر (1 من 3): قائمة النواقص -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 flex flex-col transition-colors">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-slate-700">
                <i data-lucide="alert-triangle" class="text-rose-500 w-6 h-6 animate-pulse"></i>
                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['low_stock_alert']; ?></h3>
            </div>

            <div class="flex-1 space-y-4">
                <?php if (mysqli_num_rows($lowStockListResult) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($lowStockListResult)): ?>
                        <!-- كرت دواء ناقص -->
                        <div class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:border-rose-200 dark:hover:border-rose-900/50 transition duration-200 group">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white mb-1 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors"><?php echo htmlspecialchars($item['Name']); ?></h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium"><?php echo htmlspecialchars($item['CategoryName'] ? $item['CategoryName'] : 'غير مصنف'); ?></p>
                            </div>

                            <!-- شارة الكمية (البادج الأحمر) -->
                            <div class="bg-rose-500 text-white px-3 py-1.5 rounded-lg font-bold text-sm shadow-sm flex items-center gap-1 group-hover:scale-105 transition-transform" dir="ltr">
                                <span><?php echo $item['Stock']; ?></span>
                                <span class="text-rose-200 text-xs font-normal">/</span>
                                <span class="text-rose-100 text-xs"><?php echo $item['MinimumStock']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 opacity-70 pb-6">
                        <i data-lucide="check-circle-2" class="w-12 h-12 mb-3 text-emerald-400"></i>
                        <p class="font-medium text-sm"><?php echo $lang['no_low_stock_items']; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($lowStockListResult) > 0): ?>
                <a href="medicines.php" class="mt-6 block text-center bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-200 py-3 rounded-xl text-sm font-bold transition duration-200">
                    <?php echo $lang['manage_inventory']; ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });
</script>

<?php include('../includes/footer.php'); ?>