<?php
// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);

// جلب عدد الطلبات المعلقة (فقط للأدمن) للعداد الأحمر
$pending_count = 0;

if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=0");
    if ($count_query) {
        $pending_count = mysqli_fetch_assoc($count_query)['c'];
    }
}

// ==========================================
// 💡 إعدادات الألوان والأيقونات العلوية حسب الدور
// ==========================================
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;

if ($role_id == 1) {
    // ---- الأدمن (Admin) - نظام أزرق ----
    $role_icon = 'shield-check'; 
    $sidebar_bg = 'bg-[#011C3B] dark:bg-[#010C22]';
    $text_color = 'text-white';
    $title_color = 'text-[#048AC1]'; 

    $link_classes = 'flex items-center gap-3 p-3 rounded-xl transition duration-200 hover:bg-[#024F86] text-gray-300 hover:text-white font-medium whitespace-nowrap';
    $active_classes = 'flex items-center gap-3 p-3 rounded-xl transition duration-200 bg-[#048AC1] text-white font-bold shadow-lg shadow-blue-900/30 whitespace-nowrap';
} else {
    // ---- الصيدلاني (Pharmacist) - نظام أخضر طبيعي ----
    $role_icon = 'stethoscope';

    $sidebar_bg = 'bg-[#012314] dark:bg-[#010C22]';
    $text_color = 'text-gray-200';
    $title_color = 'text-[#4ADE80]';

    $link_classes = 'flex items-center gap-3 p-3 rounded-xl transition duration-200 hover:bg-[#044E29] text-gray-300 hover:text-white font-medium whitespace-nowrap';
    $active_classes = 'flex items-center gap-3 p-3 rounded-xl transition duration-200 bg-[#0A7A48] text-white font-bold shadow-lg shadow-green-900/30 whitespace-nowrap';
}

?>

<!-- بداية القائمة الجانبية -->
<aside class="w-64 <?php echo $sidebar_bg; ?> <?php echo $text_color; ?> flex flex-col p-6 shadow-2xl z-20 flex-shrink-0 transition-colors duration-300">

    <!-- قسم الشعار والعنوان -->
    <div class="mb-10 text-center border-b border-white/10 pb-4">
        <h2 class="text-2xl font-bold tracking-tight <?php echo $title_color; ?> flex justify-center items-center gap-2">
            PharmaSmart
        </h2>
        <p class="text-xs text-gray-400 mt-2 font-medium flex items-center justify-center gap-1">
            <?php echo ($role_id == 1) ? $lang['admin_panel'] : $lang['pharmacy_system']; ?>
            <i data-lucide="<?php echo $role_icon; ?>" class="w-6 h-6"></i>
        </p>
    </div>

    <!-- روابط التنقل -->
    <nav class="flex-grow space-y-2 overflow-y-auto pr-2 custom-scrollbar overflow-x-hidden">

        <!-- ========================================== -->
        <!-- روابط الأدمن (Admin Links) -->
        <!-- ========================================== -->
        <?php if ($role_id == 1): ?>

            <a href="../admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? $active_classes : $link_classes; ?>">
                <i data-lucide="layout-dashboard" class="flex-shrink-0"></i> <span><?php echo $lang['dashboard']; ?></span>
            </a>

            <a href="../admin/pharmacies.php" class="<?php echo ($current_page == 'pharmacies.php') ? $active_classes : $link_classes; ?> justify-between">
                <div class="flex items-center gap-3">
                    <i data-lucide="hospital" class="flex-shrink-0"></i> <span><?php echo $lang['pharmacies']; ?></span>
                </div>
                <?php if ($pending_count > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse shadow-sm shadow-red-900/50 flex-shrink-0">
                        <?php echo $pending_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="../admin/users.php" class="<?php echo ($current_page == 'users.php') ? $active_classes : $link_classes; ?>">
                <i data-lucide="users" class="flex-shrink-0"></i> <span><?php echo $lang['patients']; ?></span>
            </a>

            <!-- 🚀 الكتالوج الموحد للأدوية (قريباً) -->
            <a href="#" onclick="event.preventDefault(); showComingSoon('admin_catalog');" class="<?php echo $link_classes; ?> justify-between opacity-80 hover:opacity-100 border border-transparent hover:border-amber-500/30">
                <div class="flex items-center gap-3">
                    <i data-lucide="database" class="flex-shrink-0 text-amber-400"></i> 
                    <span><?php echo isset($lang['system_medicines']) ? $lang['system_medicines'] : 'مخزون الأدوية'; ?></span>
                </div>
                <span class="bg-amber-500/20 text-amber-300 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0 uppercase tracking-widest">
                    <?php echo (isset($dir) && $dir == 'rtl') ? 'قريباً' : 'SOON'; ?>
                </span>
            </a>

            <!-- 🚀 التقارير الشاملة للمنصة (قريباً) -->
            <a href="#" onclick="event.preventDefault(); showComingSoon('admin_reports');" class="<?php echo $link_classes; ?> justify-between opacity-80 hover:opacity-100 border border-transparent hover:border-amber-500/30">
                <div class="flex items-center gap-3">
                    <i data-lucide="chart-line" class="flex-shrink-0 text-amber-400"></i> 
                    <span><?php echo isset($lang['reports']) ? $lang['reports'] : 'التقارير '; ?></span>
                </div>
                <span class="bg-amber-500/20 text-amber-300 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0 uppercase tracking-widest">
                    <?php echo (isset($dir) && $dir == 'rtl') ? 'قريباً' : 'SOON'; ?>
                </span>
            </a>

        <!-- ========================================== -->
        <!-- روابط الصيدلي (Pharmacist Links) -->
        <!-- ========================================== -->
        <?php elseif ($role_id == 2): ?>

            <a href="../pharmacist/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? $active_classes : $link_classes; ?>">
                <i data-lucide="layout-dashboard" class="flex-shrink-0"></i> <span><?php echo $lang['dashboard']; ?></span>
            </a>

            <a href="../pharmacist/medicines.php" class="<?php echo ($current_page == 'medicines.php') ? $active_classes : $link_classes; ?>">
                <i data-lucide="pill" class="flex-shrink-0"></i> <span><?php echo $lang['manage_inventory']; ?></span>
            </a>

            <a href="../pharmacist/orders.php" class="<?php echo ($current_page == 'orders.php') ? $active_classes : $link_classes; ?>">
                <i data-lucide="shopping-bag" class="flex-shrink-0"></i> <span><?php echo isset($lang['orders']) ? $lang['orders'] : 'الطلبات'; ?></span>
            </a>

            <!-- 🚀 ميزة المحادثات (قريباً) -->
            <a href="#" onclick="event.preventDefault(); showComingSoon('chat');" class="<?php echo $link_classes; ?> justify-between opacity-80 hover:opacity-100 border border-transparent hover:border-amber-500/30">
                <div class="flex items-center gap-3">
                    <i data-lucide="message-square" class="flex-shrink-0 text-amber-400"></i> 
                    <span><?php echo isset($lang['chats']) ? $lang['chats'] : 'المحادثات'; ?></span>
                </div>
                <span class="bg-amber-500/20 text-amber-300 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0 uppercase tracking-widest">
                    <?php echo (isset($dir) && $dir == 'rtl') ? 'قريباً' : 'SOON'; ?>
                </span>
            </a>
             <!-- 🚀 التقارير الشاملة للصيدلية (قريباً) -->
            <a href="#" onclick="event.preventDefault(); showComingSoon('pharmacist_reports');" class="<?php echo $link_classes; ?> justify-between opacity-80 hover:opacity-100 border border-transparent hover:border-amber-500/30">
                <div class="flex items-center gap-3">
                    <i data-lucide="chart-line" class="flex-shrink-0 text-amber-400"></i> 
                    <span><?php echo isset($lang['reports']) ? $lang['reports'] : 'التقارير '; ?></span>
                </div>
                <span class="bg-amber-500/20 text-amber-300 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0 uppercase tracking-widest">
                    <?php echo (isset($dir) && $dir == 'rtl') ? 'قريباً' : 'SOON'; ?>
                </span>
            </a>

        <?php endif; ?>

    </nav>

    <!-- زر تسجيل الخروج -->
    <a href="../auth/logout.php" class="flex items-center gap-3 p-3 bg-red-600/10 text-red-400 hover:bg-red-600 hover:text-white rounded-xl transition duration-200 mt-auto border border-red-600/20 group whitespace-nowrap">
        <i data-lucide="log-out" class="flex-shrink-0 group-hover:translate-x-1 transition-transform rtl:group-hover:-translate-x-1"></i>
        <span><?php echo $lang['logout']; ?></span>
    </a>
</aside>

<!-- سكربت رسائل (قريباً) الديناميكي -->
<script>
function showComingSoon(feature) {
    const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
    const titleText = isRtl ? 'قريباً جداً!' : 'Coming Soon!';
    const btnText = isRtl ? 'حسناً' : 'OK';

    // 💡 تحديد لون الزر بناءً على دور المستخدم (أزرق للأدمن، أخضر للصيدلاني)
    const isPharmacist = <?php echo ($role_id == 2) ? 'true' : 'false'; ?>;
    const btnColor = isPharmacist ? '#0A7A48' : '#048AC1';
    let bodyText = '';

    // تحديد الرسالة بناءً على الزر المضغوط
    if (feature === 'admin_catalog') {
        bodyText = isRtl 
            ? 'جاري العمل على واجهة إدارة "الكتالوج الموحد" والتي ستمكنك من (إضافة، تعديل، إخفاء) الأدوية والتصنيفات المركزية للمنصة.' 
            : 'The System Medicines Catalog management is under development.';
    } else if (feature === 'admin_reports') {
        bodyText = isRtl 
            ? 'نعمل على تصميم لوحة "التقارير الشاملة" لتعرض لك إحصائيات المبيعات، نمو المنصة، والمنتجات الأكثر طلباً في رسوم بيانية تفاعلية.' 
            : 'Global Reports and Analytics dashboard is under development.';
    } else if (feature === 'chat') {
        bodyText = isRtl 
            ? 'ميزة المحادثات المباشرة قيد التطوير وسيتم إتاحتها قريباً لتسهيل تواصلك المباشر والآمن مع المرضى.' 
            : 'The chat feature is under development and will be available soon to help you communicate with patients.';
    } else if (feature === 'pharmacist_reports') {
        bodyText = isRtl 
            ? 'نعمل على تصميم لوحة "التقارير الشاملة" لتعرض لك إحصائيات المبيعات، نمو الصيدلية والمنتجات الأكثر طلباً في رسوم بيانية تفاعلية.' 
            : 'Pharmacist Reports and Analytics dashboard is under development.';
    } else {
        bodyText = isRtl ? 'هذه الميزة قيد التطوير وسيتم إتاحتها قريباً.' : 'This feature is under development.';
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: titleText,
            text: bodyText,
            confirmButtonColor: btnColor,
            confirmButtonText: btnText,
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
            iconColor: '#F59E0B' // الأيقونة برتقالية دائماً للتنبيه
        });
    } else {
        alert(bodyText);
    }
}
</script>