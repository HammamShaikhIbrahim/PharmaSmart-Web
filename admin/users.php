<?php
include('../config/database.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM User WHERE UserID=$id AND RoleID=3");
    header("Location: users.php?msg=deleted");
}

$query = "SELECT u.*, p.Address, 
          TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS Age 
          FROM User u 
          LEFT JOIN Patient p ON u.UserID = p.PatientID 
          WHERE u.RoleID = 3 
          ORDER BY u.CreatedAt DESC";
$result = mysqli_query($conn, $query);

// 💡 إضافة كود البحث المخصص:
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$query = "SELECT u.*, p.Address, 
          TIMESTAMPDIFF(YEAR, p.DOB, CURDATE()) AS Age 
          FROM User u 
          LEFT JOIN Patient p ON u.UserID = p.PatientID 
          WHERE u.RoleID = 3 AND (u.Fname LIKE '%$search%' OR u.Lname LIKE '%$search%')
          ORDER BY u.CreatedAt DESC";
$result = mysqli_query($conn, $query);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">



    <!-- الترويسة مع شريط البحث -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="users" class="text-blue-500"></i> <?php echo $lang['patients']; ?>
        </h1>

        <!-- فورم البحث الخاص بالصيدليات فقط -->
        <form method="GET" class="w-full md:w-96">
            <div class="relative">
                <input type="text" name="search" placeholder="ابحث عن مريض..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <i data-lucide="search" class="absolute left-3 top-3.5 text-gray-400"></i>
            </div>
        </form>
    </div>

    <!-- الجدول -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <table class="w-full border-collapse">

                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700">
                    <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-6 font-bold"><?php echo $lang['patient_name']; ?></th>
                        <th class="p-6 font-bold"><?php echo $lang['contact_info']; ?></th>
                        <th class="p-6 font-bold"><?php echo $lang['address']; ?></th>
                        <th class="p-6 font-bold"><?php echo $lang['join_date']; ?></th>
                        <th class="p-6 font-bold text-center"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition duration-150">

                            <td class="p-6">
                                <div class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['Fname'] . ' ' . $row['Lname']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo $lang['age']; ?>:
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        <?php echo ($row['Age'] !== null) ? $row['Age'] . ' ' . $lang['years'] : '-'; ?>
                                    </span>
                                </div>
                            </td>

                            <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="mail" class="w-4 h-4 text-emerald-500 flex-shrink-0 "></i>
                                    <span><?php echo htmlspecialchars($row['Email']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                    <span dir="ltr"><?php echo htmlspecialchars($row['Phone'] ?? $lang['not_specified']); ?></span>
                                </div>
                            </td>

                            <td class="p-6 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                                    <span><?php echo htmlspecialchars($row['Address'] ?? $lang['not_specified']); ?></span>
                                </div>
                            </td>

                            <td class="p-6 text-sm text-gray-500 dark:text-gray-400">
                                <span dir="ltr"><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?></span>
                            </td>

                            <td class="p-6 text-center">
                                <button onclick="confirmDelete(<?php echo $row['UserID']; ?>)"
                                    class="bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-800/50 transition inline-flex"
                                    title="Delete">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-16 text-center text-gray-500 dark:text-gray-400 flex flex-col items-center">
                <i data-lucide="user-x" class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600"></i>
                <p class="text-lg">لا يوجد بيانات</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: Lang.title, // من ملف footer.php
            text: Lang.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.confirm,
            cancelButtonText: Lang.cancel,
            // تنسيق الدارك مود المضمون
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'users.php?delete_id=' + id;
            }
        });
    }

    lucide.createIcons();
</script>

<?php include('../includes/footer.php'); ?>