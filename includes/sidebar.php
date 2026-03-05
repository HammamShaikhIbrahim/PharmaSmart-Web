<?php
$pending_count = 0;
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    $pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=0"))['c'];
}
?>
<!-- السايدبار ثابت (لا يتحرك مع السكرول) -->
<aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex flex-col p-6 shadow-2xl z-20 flex-shrink-0 transition-colors duration-300">
    <div class="mb-10 text-center border-b border-gray-700 pb-4">
        <h2 class="text-2xl font-black tracking-tight text-blue-400">PharmaSmart</h2>
    </div>

    <nav class="flex-grow space-y-2 overflow-y-auto pr-2">
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
            <a href="../admin/dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition hover:bg-blue-600">
                <i data-lucide="layout-dashboard"></i> <span><?php echo $lang['dashboard']; ?></span>
            </a>

            <a href="../admin/pharmacies.php" class="flex items-center justify-between p-3 rounded-xl transition hover:bg-blue-600">
                <div class="flex items-center gap-3">
                    <i data-lucide="hospital"></i> <span><?php echo $lang['pharmacies']; ?></span>
                </div>
                <?php if ($pending_count > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="../admin/users.php" class="flex items-center gap-3 p-3 rounded-xl transition hover:bg-blue-600">
                <i data-lucide="users"></i> <span><?php echo $lang['patients']; ?></span>
            </a>
        <?php endif; ?>
    </nav>

    <a href="../auth/logout.php" class="flex items-center gap-3 p-3 bg-red-600/10 text-red-400 hover:bg-red-600 hover:text-white rounded-xl transition mt-auto">
        <i data-lucide="log-out"></i> <span><?php echo $lang['logout']; ?></span>
    </a>
</aside>