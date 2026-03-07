<?php
// جلب عدد الطلبات المعلقة (فقط للأدمن)
$pending_count = 0;
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=0");
    if ($count_query) {
        $pending_count = mysqli_fetch_assoc($count_query)['c'];
    }
}

// تحديد لون الثيم بناءً على الدور (أزرق للأدمن، أخضر للصيدلي)
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
$theme_color = ($role_id == 1) ? 'text-blue-400' : 'text-emerald-400';
$hover_color = ($role_id == 1) ? 'hover:bg-blue-600' : 'hover:bg-emerald-600';
?>

<!-- بداية القائمة الجانبية -->
<!-- z-20 لضمان بقائها فوق أي عنصر آخر -->
<aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex flex-col p-6 shadow-2xl z-20 flex-shrink-0 transition-colors duration-300">

    <!-- قسم الشعار والعنوان -->
    <div class="mb-10 text-center border-b border-gray-700 pb-4">
        <h2 class="text-2xl font-black tracking-tight <?php echo $theme_color; ?>">
            PharmaSmart
        </h2>
        <p class="text-xs text-gray-400 mt-2 font-medium">
            <?php echo ($role_id == 1) ? $lang['admin_panel'] : $lang['pharmacy_system']; ?>
        </p>
    </div>

    <!-- روابط التنقل (قابلة للسكرول إذا كانت القائمة طويلة) -->
    <nav class="flex-grow space-y-2 overflow-y-auto pr-2 custom-scrollbar">

        <!-- ========================================== -->
        <!-- 1. روابط المدير (Admin Links) -->
        <!-- ========================================== -->
        <?php if ($role_id == 1): ?>

            <!-- الرئيسية -->
            <a href="../admin/dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="layout-dashboard"></i> <span><?php echo $lang['dashboard']; ?></span>
            </a>

            <!-- إدارة الصيدليات (مع العداد الأحمر) -->
            <a href="../admin/pharmacies.php" class="flex items-center justify-between p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <div class="flex items-center gap-3">
                    <i data-lucide="hospital"></i> <span><?php echo $lang['pharmacies']; ?></span>
                </div>
                <?php if ($pending_count > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse shadow-sm shadow-red-900/50">
                        <?php echo $pending_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- إدارة المرضى -->
            <a href="../admin/users.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="users"></i> <span><?php echo $lang['patients']; ?></span>
            </a>



            <!-- ========================================== -->
            <!-- 2. روابط الصيدلي (Pharmacist Links) -->
            <!-- ========================================== -->
        <?php elseif ($role_id == 2): ?>

            <a href="../pharmacist/dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="layout-dashboard"></i> <span><?php echo $lang['dashboard_link']; ?></span>
            </a>

            <a href="../pharmacist/medicines.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="pill"></i> <span><?php echo $lang['medicines_stock']; ?></span>
            </a>

            <a href="../pharmacist/orders.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="shopping-bag"></i> <span><?php echo $lang['orders']; ?></span>
            </a>

            <a href="../pharmacist/chat.php" class="flex items-center gap-3 p-3 rounded-xl transition duration-200 <?php echo $hover_color; ?>">
                <i data-lucide="message-square"></i> <span><?php echo $lang['chats']; ?></span>
            </a>

        <?php endif; ?>

    </nav>

    <!-- زر تسجيل الخروج (يظهر للجميع في الأسفل) -->
    <a href="../auth/logout.php" class="flex items-center gap-3 p-3 bg-red-600/10 text-red-400 hover:bg-red-600 hover:text-white rounded-xl transition duration-200 mt-auto border border-red-600/20 group">
        <i data-lucide="log-out" class="group-hover:translate-x-1 transition-transform rtl:group-hover:-translate-x-1"></i>
        <span><?php echo $lang['logout']; ?></span>
    </a>
</aside>