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

// الإحصائيات العلوية (Statistics)

// ==========================================

// 💡 جلب مبيعات اليوم من جميع الأدوية التابعة للصيدليّ

$salesQuery = "SELECT SUM(oi.Quantity * oi.SoldPrice) as TotalSales FROM OrderItems oi JOIN Medicine m ON oi.MedicineID = m.MedicineID JOIN `Order` o ON oi.OrderID = o.OrderID WHERE m.PharmacistID = $pharmacist_id AND DATE(o.OrderDate) = CURDATE() AND o.Status != 'Rejected'";

$salesResult = mysqli_fetch_assoc(mysqli_query($conn, $salesQuery));

$todaysSales = $salesResult['TotalSales'] ? number_format($salesResult['TotalSales'], 2) : "0.00";



// جلب عدد الطلبات المعلقة (قيد الانتظار)

$ordersQuery = "SELECT COUNT(DISTINCT o.OrderID) as PendingCount FROM `Order` o JOIN OrderItems oi ON o.OrderID = oi.OrderID JOIN Medicine m ON oi.MedicineID = m.MedicineID WHERE m.PharmacistID = $pharmacist_id AND o.Status = 'Pending'";

$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, $ordersQuery))['PendingCount'];



// جلب عدد الأدوية الناقصة في المخزون

$lowStockQuery = "SELECT COUNT(*) as LowStockCount FROM Medicine WHERE PharmacistID = $pharmacist_id AND Stock <= MinimumStock";

$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn, $lowStockQuery))['LowStockCount'];



// جلب عدد الأدوية قريبة الانتهاء (خلال 30 يوم)

$expiryQuery = "SELECT COUNT(*) as ExpiringCount FROM Medicine WHERE PharmacistID = $pharmacist_id AND ExpiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";

$expiringCount = mysqli_fetch_assoc(mysqli_query($conn, $expiryQuery))['ExpiringCount'];



// ==========================================

// 1. جلب الطلبات الأخيرة (Recent Orders)

// ==========================================

$recentOrdersQ = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, u.Fname, u.Lname, COUNT(oi.MedicineID) as ItemsCount FROM `Order` o JOIN User u ON o.PatientID = u.UserID JOIN OrderItems oi ON o.OrderID = oi.OrderID JOIN Medicine m ON oi.MedicineID = m.MedicineID WHERE m.PharmacistID = $pharmacist_id GROUP BY o.OrderID ORDER BY o.OrderDate DESC LIMIT 5";

$recentOrdersResult = mysqli_query($conn, $recentOrdersQ);



// ==========================================

// 2. جلب قائمة الأدوية الناقصة (Low Stock Items)

// ==========================================

$lowStockListQ = "SELECT m.Name, m.Stock, m.MinimumStock, c.Name as CategoryName FROM Medicine m LEFT JOIN Category c ON m.CategoryID = c.CategoryID WHERE m.PharmacistID = $pharmacist_id AND m.Stock <= m.MinimumStock ORDER BY m.Stock ASC LIMIT 5";

$lowStockListResult = mysqli_query($conn, $lowStockListQ);



// ==========================================

// 3. جلب قائمة الأدوية قريبة الانتهاء (Expiring Items)

// ==========================================

// 💡 الاستعلام الجديد: قائمة الأدوية المنتهية أو قريبة الانتهاء (خلال 30 يوم)

$expiringListQ = "SELECT Name, ExpiryDate, DATEDIFF(ExpiryDate, CURDATE()) as DaysLeft 

                  FROM Medicine 

                  WHERE PharmacistID = $pharmacist_id 

                  AND ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 

                  ORDER BY ExpiryDate ASC LIMIT 5";

$expiringListResult = mysqli_query($conn, $expiringListQ);



include('../includes/header.php');

include('../includes/sidebar.php');

?>



<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">
    <?php include('../includes/topbar.php'); ?>



    <!-- ==========================================

         الكروت الإحصائية الأربعة (Pharmacist Cards)

         💡 نظام الألوان: أخضر لثيم الصيدلاني

         - مبيعات اليوم: أخضر فاتح (#6E8649)

         - طلبات معلقة: أزرق فاتح

         - نواقص المخزون: أحمر/وردي

         - قريبة الانتهاء: برتقالي

    =========================================== -->


    <div class="mb-8 flex justify-between items-center">

        <div class="w-full flex items-center gap-3">

            <!-- (#4ADE80) بما يتوافق مع ثيم الصيدلي -->

            <i data-lucide="layout-dashboard" class="text-[#4ADE80] w-8 h-8"></i>

            <h1 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $lang['dashboard']; ?></h1>

        </div>

    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- مبيعات اليوم -->
        <!-- 💡 تطابق هيكلي مع الأدمن: shadow-lg, hover:-translate-y-1, border-b-4 -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex flex-col justify-center border-b-4 border-transparent hover:border-b-[#4ADE80] dark:hover:border-b-[#4ADE80] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo $lang['todays_sales']; ?></p>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $todaysSales; ?> ₪</h3>
                </div>
                <i data-lucide="banknote" class="w-12 h-12 text-[#4ADE80] drop-shadow-sm opacity-80"></i>
            </div>
        </div>

        <!-- الطلبات المعلقة -->

        <!-- 💡 أزرق فاتح للتمييز بين الكروت -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex flex-col justify-center relative border-b-4 border-transparent hover:border-b-blue-500 dark:hover:border-b-blue-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div class="flex justify-between items-center mb-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo $lang['pending_orders']; ?></p>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $pendingOrders; ?></h3>
                </div>
                <i data-lucide="shopping-bag" class="w-12 h-12 text-blue-500 dark:text-blue-400 opacity-80 drop-shadow-sm"></i>
            </div>

            <!-- تنبيه احمر إذا كان هناك طلبات معلقة -->

            <?php if ($pendingOrders > 0): ?>

                <span class="absolute top-4 rtl:left-4 ltr:right-4 w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-sm"></span>

            <?php endif; ?>

        </div>



        <!-- نواقص المخزون -->

        <!-- 💡 أحمر/وردي للتنبيه من النواقص -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex flex-col justify-center border-b-4 border-transparent hover:border-b-rose-500 dark:hover:border-b-rose-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div class="flex justify-between items-center mb-2">

                <div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo $lang['low_stock_items']; ?></p>

                    <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $lowStockCount; ?></h3>

                </div>

                <i data-lucide="alert-triangle" class="w-12 h-12 text-rose-500 dark:text-rose-400 opacity-80 drop-shadow-sm"></i>

            </div>

        </div>



        <!-- قريبة الانتهاء -->

        <!-- 💡 برتقالي للدلالة على انتهاء الصلاحية -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex flex-col justify-center border-b-4 border-transparent hover:border-b-orange-500 dark:hover:border-b-orange-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div class="flex justify-between items-center mb-2">

                <div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-1"><?php echo $lang['expiring_soon']; ?></p>

                    <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $expiringCount; ?></h3>

                </div>

                <i data-lucide="clock" class="w-12 h-12 text-orange-500 dark:text-orange-400 opacity-80 drop-shadow-sm"></i>

            </div>

        </div>

    </div>



    <!-- ==========================================

         القسم السفلي: 3 أعمدة (الطلبات، النواقص، الصلاحية)

    =========================================== -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">



        <!-- 1. جدول أحدث الطلبات -->

        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden flex flex-col transition-colors">

            <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">

                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['recent_orders']; ?></h3>

                <!-- 💡 رابط "عرض الكل" بلون أخضر (#6E8649) -->

                <a href="orders.php" class="text-sm text-teal-600 dark:text-teal-400 hover:underline font-bold transition-colors"><?php echo $lang['view_all']; ?></a>
            </div>

            <div class="overflow-x-auto flex-1 p-2">

                <table class="w-full text-sm">

                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">

                        <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>

                            <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>

                                <tr class="hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200">
                                    <td class="p-4">

                                        <div class="font-bold text-gray-800 dark:text-white">#ORD-<?php echo $order['OrderID']; ?></div>

                                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></div>

                                    </td>

                                    <td class="p-4 font-bold text-gray-800 dark:text-white" dir="ltr">

                                        <?php echo number_format($order['TotalAmount'], 2); ?> ₪

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="2" class="p-10 text-center text-gray-400">

                                    <p><?php echo $lang['no_recent_orders']; ?></p>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>



        <!-- 2. قائمة النواقص -->

        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 flex flex-col transition-colors">

            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-slate-700">

                <!-- 💡 تنبيه أقوى لوناً وحركة، أيقونة محذرة بلون أحمر -->

                <i data-lucide="alert-triangle" class="text-rose-600 w-6 h-6 animate-[pulse_1s_ease-in-out_infinite]"></i>

                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['low_stock_alert']; ?></h3>

            </div>

            <div class="flex-1 space-y-4">

                <?php if (mysqli_num_rows($lowStockListResult) > 0): ?>

                    <?php while ($item = mysqli_fetch_assoc($lowStockListResult)): ?>

                        <div class="flex items-center justify-between p-4 rounded-2xl border border-rose-100 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-900/10">

                            <div>

                                <h4 class="font-bold text-gray-800 dark:text-white mb-1"><?php echo htmlspecialchars($item['Name']); ?></h4>

                            </div>

                            <!-- 💡 تمييز الكمية الدنيا: أبيض مع خلفية رمادية داكنة -->

                            <div class="bg-rose-500 text-white px-3 py-1.5 rounded-lg font-bold text-sm shadow-sm flex items-center gap-1.5" dir="ltr">

                                <span><?php echo $item['Stock']; ?></span>

                                <span class="text-rose-200 text-xs font-normal">/</span>

                                <span class="text-gray-900 bg-white/90 px-1.5 rounded text-xs"><?php echo $item['MinimumStock']; ?></span>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="flex flex-col items-center justify-center h-full text-gray-400">

                        <p><?php echo $lang['no_low_stock_items']; ?></p>

                    </div>

                <?php endif; ?>

            </div>

        </div>



        <!-- 3. التنبيه الجديد: تواريخ الصلاحية -->

        <!-- 💡 قسم متقدم لتنبيهات الصلاحية -->

        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 flex flex-col transition-colors">

            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-slate-700">

                <!-- 💡 أيقونة تقويم برتقالية للصلاحية -->

                <i data-lucide="calendar-off" class="text-orange-500 w-6 h-6"></i>

                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo isset($lang['expiry_alerts']) ? $lang['expiry_alerts'] : "تنبيهات الصلاحية"; ?></h3>

            </div>

            <div class="flex-1 space-y-4">

                <?php if (mysqli_num_rows($expiringListResult) > 0): ?>

                    <?php while ($exp = mysqli_fetch_assoc($expiringListResult)):

                        // تحديد حالة الدواء (منتهي تماماً أم قريب)

                        if ($exp['DaysLeft'] < 0) {

                            // منتهي الصلاحية فعلاً: أحمر قوي

                            $badgeClass = "bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-400";

                            $badgeText = "منتهي الصلاحية";

                            $icon = "x-circle";
                        } else {

                            // قريب الانتهاء: برتقالي

                            $badgeClass = "bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-900/40 dark:text-orange-400";

                            $badgeText = "باقي " . $exp['DaysLeft'] . " يوم";

                            $icon = "clock-4";
                        }

                    ?>

                        <div class="flex flex-col p-4 rounded-2xl border <?php echo $badgeClass; ?> transition duration-200">

                            <div class="flex justify-between items-start mb-2">

                                <h4 class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($exp['Name']); ?></h4>

                                <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 opacity-70"></i>

                            </div>

                            <div class="flex justify-between items-center text-xs font-bold">

                                <span dir="ltr" class="opacity-80"><?php echo $exp['ExpiryDate']; ?></span>

                                <span class="px-2 py-1 rounded bg-white/50 dark:bg-black/20"><?php echo $badgeText; ?></span>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="flex flex-col items-center justify-center h-full text-emerald-500">

                        <i data-lucide="check-circle" class="w-12 h-12 mb-3"></i>

                        <p class="font-bold text-sm">جميع الأدوية صالحة</p>

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
</script>



<?php include('../includes/footer.php'); ?>