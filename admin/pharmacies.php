<?php
include('../config/database.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

// معالجة الأكشن (قبول/رفض/حذف/تعليق)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'approve') mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 1 WHERE PharmacistID = $id");
    elseif ($_GET['action'] == 'suspend') mysqli_query($conn, "UPDATE Pharmacist SET IsApproved = 0 WHERE PharmacistID = $id");
    elseif ($_GET['action'] == 'delete' || $_GET['action'] == 'reject') mysqli_query($conn, "DELETE FROM User WHERE UserID = $id");
    header("Location: pharmacies.php");
    exit();
}

// 💡 إضافة كود البحث المخصص:
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$query = "SELECT u.UserID, u.Fname, u.Lname, u.Phone, u.CreatedAt, p.* 
          FROM User u 
          JOIN Pharmacist p ON u.UserID = p.PharmacistID 
          WHERE p.PharmacyName LIKE '%$search%' OR u.Fname LIKE '%$search%'
          ORDER BY p.IsApproved ASC";
$result = mysqli_query($conn, $query);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <!-- الترويسة مع شريط البحث -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="hospital" class="text-blue-500"></i> <?php echo $lang['pharmacies']; ?>
        </h1>

        <!-- فورم البحث الخاص بالصيدليات فقط -->
        <form method="GET" class="w-full md:w-96">
            <div class="relative">
                <input type="text" name="search" placeholder="ابحث عن صيدلية بالاسم..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <i data-lucide="search" class="absolute left-3 top-3.5 text-gray-400"></i>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors">
        <table class="w-full border-collapse">
            <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700 ">
                <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <th class="p-6 font-bold"><?php echo $lang['pharmacy_name']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['owner']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['phone']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['location_work']; ?></th>
                    <th class="p-6 font-bold"><?php echo $lang['join_date']; ?></th>
                    <th class="p-6 font-bold text-center"><?php echo $lang['status']; ?></th>
                    <th class="p-6 font-bold text-center"><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr id="row_<?php echo $row['UserID']; ?>" class="transition-all duration-500 hover:bg-gray-100 dark:hover:bg-slate-700/50 transition">

                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <img src="../uploads/<?php echo $row['Logo'] ? $row['Logo'] : 'default.png'; ?>"
                                    class="w-12 h-12 rounded-full border dark:border-slate-600 object-cover shadow-sm">
                                <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['PharmacyName']); ?></span>
                            </div>
                        </td>

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">
                            <?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?>
                        </td>

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300 font-medium">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="phone" class="w-4 h-4 text-emerald-500"></i>
                                <?php echo htmlspecialchars($row['Phone']); ?>
                            </div>
                        </td>

                        <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                <span><?php echo htmlspecialchars($row['Location']); ?></span>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="clock" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                <span><?php echo htmlspecialchars($row['WorkingHours']); ?></span>
                            </div>
                        </td>

                        <td class="p-6 text-sm text-gray-500 dark:text-gray-400 text-center">
                            <span dir="ltr"><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?></span>
                        </td>

                        <td class="p-6">
                            <?php if ($row['IsApproved'] == 0): ?>
                                <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 px-4 py-1.5 rounded-full text-xs font-bold border border-amber-200 dark:border-amber-700 inline-block text-center min-w-[80px]">
                                    <?php echo $lang['pending']; ?>
                                </span>
                            <?php else: ?>
                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-4 py-1.5 rounded-full text-xs font-bold border border-emerald-200 dark:border-emerald-700 inline-block text-center min-w-[80px]">
                                    <?php echo $lang['active']; ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="p-6 text-center">
                            <div class="flex justify-center items-center gap-2">
                                <?php if ($row['IsApproved'] == 0): ?>
                                    <a href="pharmacies.php?action=approve&id=<?php echo $row['PharmacistID']; ?>" class="bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 p-2.5 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-800/50 transition"><i data-lucide="check"></i></a>
                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'reject')" class="bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-2.5 rounded-xl hover:bg-red-100 dark:hover:bg-red-800/50 transition"><i data-lucide="trash-2"></i></button>
                                <?php else: ?>
                                    <button onclick="confirmAction(<?php echo $row['PharmacistID']; ?>, 'suspend')" class="bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 p-2.5 rounded-xl hover:bg-amber-100 dark:hover:bg-amber-800/50 transition"><i data-lucide="pause"></i></button>
                                    <button onclick="confirmAction(<?php echo $row['UserID']; ?>, 'delete')" class="bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-2.5 rounded-xl hover:bg-red-100 dark:hover:bg-red-800/50 transition"><i data-lucide="trash-2"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    function confirmAction(id, type) {
        let modalTitle = Lang.title;
        let modalText = Lang.text;
        let modalBtn = Lang.confirm;
        let btnColor = '#ef4444';
        let iconType = 'warning';

        if (type === 'suspend') {
            modalTitle = Lang.suspendTitle;
            modalText = Lang.suspendText;
            modalBtn = Lang.suspendConfirm;
            btnColor = '#f59e0b';
            iconType = 'question';
        }

        Swal.fire({
            title: modalTitle,
            text: modalText,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: modalBtn,
            cancelButtonText: Lang.cancel,
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((res) => {
            if (res.isConfirmed) window.location.href = `pharmacies.php?action=${type}&id=${id}`;
        });
    }
    lucide.createIcons();
</script>
<?php include('../includes/footer.php'); ?>