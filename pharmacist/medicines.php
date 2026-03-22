<?php

include('../config/database.php');
session_start();
require_once('../includes/lang.php');

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];
$message = "";
$error = "";

// ==========================================
// 1. جلب التصنيفات لاستخدامها في الفلتر
// ==========================================
$cat_col = ($current_lang == 'en') ? 'NameEN' : 'NameAR';
$categories_query = mysqli_query($conn, "SELECT CategoryID, $cat_col AS Name FROM Category ORDER BY $cat_col ASC");

// ==========================================
// 2. معالجة حفظ المخزون (إضافة / تعديل) - التكلفة ثابتة ومحمية
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_stock'])) {
    $stock_id = isset($_POST['stock_id']) ? intval($_POST['stock_id']) : 0;
    $system_med_id = isset($_POST['system_med_id']) ? intval($_POST['system_med_id']) : 0;

    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry = mysqli_real_escape_string($conn, $_POST['expiry']);

    // 🚀 جلب سعر التكلفة الثابت من النظام بدلاً من الاعتماد على الفورم للحماية
    $sys_med_query = mysqli_query($conn, "SELECT FixedCostPrice FROM SystemMedicine WHERE SystemMedID = $system_med_id");
    if ($sys_med_query && mysqli_num_rows($sys_med_query) > 0) {
        $sys_med_data = mysqli_fetch_assoc($sys_med_query);
        $cost = floatval($sys_med_data['FixedCostPrice']);
    } else {
        $cost = 0.00;
    }

    if ($stock_id > 0) {
        $sql = "UPDATE PharmacyStock
                SET Price=$price, CostPrice=$cost, Stock=$stock, MinimumStock=$min_stock, ExpiryDate='$expiry'
                WHERE StockID=$stock_id AND PharmacistID=$pharmacist_id";
        mysqli_query($conn, $sql);
    } else {
        $check = mysqli_query($conn, "SELECT StockID FROM PharmacyStock WHERE PharmacistID=$pharmacist_id AND SystemMedID=$system_med_id");
        if (mysqli_num_rows($check) > 0) {
            $error = "هذا الدواء موجود مسبقاً في مخزونك! يمكنك تعديل كميته بدلاً من إضافته مرة أخرى.";
        } else {
            $sql = "INSERT INTO PharmacyStock (SystemMedID, PharmacistID, Price, CostPrice, Stock, MinimumStock, ExpiryDate)
                    VALUES ($system_med_id, $pharmacist_id, $price, $cost, $stock, $min_stock, '$expiry')";
            mysqli_query($conn, $sql);
        }
    }

    if (empty($error)) {
        header("Location: medicines.php");
        exit();
    }
}

// ==========================================
// 3. معالجة عملية الحذف (Delete Stock) مع التحديث الذكي للطلبات
// ==========================================
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    // 🚀 الحل الجذري: البحث عن الطلبات التي تحتوي على هذا الدواء وخصم ثمنه من الإجمالي العام للطلب قبل الحذف
    $find_orders_query = mysqli_query($conn, "SELECT OrderID, Quantity, SoldPrice FROM OrderItems WHERE StockID = $del_id");
    if ($find_orders_query && mysqli_num_rows($find_orders_query) > 0) {
        while ($order_item = mysqli_fetch_assoc($find_orders_query)) {
            $o_id = $order_item['OrderID'];
            $deduct_amount = $order_item['Quantity'] * $order_item['SoldPrice'];

            // تحديث الإجمالي في جدول الطلبات (بشرط ألا يقل عن 0)
            mysqli_query($conn, "UPDATE `Order` SET TotalAmount = GREATEST(TotalAmount - $deduct_amount, 0) WHERE OrderID = $o_id");
        }
    }

    // حذف الدواء من تفاصيل الطلبات لتجنب الأخطاء
    mysqli_query($conn, "DELETE FROM OrderItems WHERE StockID = $del_id");

    // أخيرًا حذف الدواء من المخزون
    mysqli_query($conn, "DELETE FROM PharmacyStock WHERE StockID=$del_id AND PharmacistID=$pharmacist_id");

    header("Location: medicines.php");
    exit();
}

// ==========================================
// 4. معالجة طلب AJAX للبحث في الكتالوج الموحد
// ==========================================
if (isset($_GET['search_system_med'])) {
    $q = mysqli_real_escape_string($conn, $_GET['search_system_med']);
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

    $cat_condition = ($cat_id > 0) ? "AND sm.CategoryID = $cat_id" : "";
    $q_condition = !empty($q) ? "AND (sm.Name LIKE '%$q%' OR sm.ScientificName LIKE '%$q%')" : "";

    $sys_query = "
        SELECT sm.SystemMedID, sm.Name, sm.ScientificName, sm.Image, sm.IsControlled, sm.FixedCostPrice, c.$cat_col as CategoryName
        FROM SystemMedicine sm
        LEFT JOIN Category c ON sm.CategoryID = c.CategoryID
        WHERE 1=1
        $q_condition
        $cat_condition
        AND sm.SystemMedID NOT IN (SELECT SystemMedID FROM PharmacyStock WHERE PharmacistID = $pharmacist_id)
        ORDER BY sm.SystemMedID DESC
        LIMIT 20
    ";

    $sys_res = mysqli_query($conn, $sys_query);
    $results = [];
    while ($row = mysqli_fetch_assoc($sys_res)) {
        $results[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// ==========================================
// 5. نظام البحث وجلب مخزون الصيدلية للجدول (AJAX Table)
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "
    SELECT ps.*, sm.Name, sm.ScientificName, sm.Image, sm.IsControlled, sm.FixedCostPrice, c.$cat_col as CategoryName
    FROM PharmacyStock ps
    JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
    LEFT JOIN Category c ON sm.CategoryID = c.CategoryID
    WHERE ps.PharmacistID = $pharmacist_id
    AND (sm.Name LIKE '%$search%' OR sm.ScientificName LIKE '%$search%')
    ORDER BY ps.StockID DESC
";
$medicines = mysqli_query($conn, $query);

if (isset($_GET['ajax_table'])) {
    ob_start();
    if (mysqli_num_rows($medicines) > 0) {
        while ($med = mysqli_fetch_assoc($medicines)) {
            $margin = 0;
            if ($med['Price'] > 0 && $med['CostPrice'] > 0) {
                $margin = round((($med['Price'] - $med['CostPrice']) / $med['Price']) * 100);
            }

            $expiry_timestamp = strtotime($med['ExpiryDate']);
            $current_timestamp = time();
            $days_30_timestamp = strtotime('+30 days');

            if ($expiry_timestamp < $current_timestamp) {
                $expiry_class = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border-rose-200 dark:border-rose-800";
            } elseif ($expiry_timestamp <= $days_30_timestamp) {
                $expiry_class = "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border-amber-200 dark:border-amber-800";
            } else {
                $expiry_class = "bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/40 dark:text-[#4ADE80] border-[#0A7A48]/20 dark:border-[#4ADE80]/20";
            }

            $imgSrc = $med['Image'];
            if (!empty($imgSrc) && $imgSrc != 'default_med.png') {
                if (!str_starts_with($imgSrc, 'uploads/') && !str_starts_with($imgSrc, '../uploads/')) {
                    $imgSrc = "../uploads/" . $imgSrc;
                } elseif (str_starts_with($imgSrc, 'uploads/')) {
                    $imgSrc = "../" . $imgSrc;
                }
            } else {
                $imgSrc = 'https://ui-avatars.com/api/?name=' . urlencode($med['Name']) . '&background=e2e8f0&color=475569';
            }

            $med_json = htmlspecialchars(json_encode([
                'StockID' => $med['StockID'],
                'SystemMedID' => $med['SystemMedID'],
                'Name' => $med['Name'],
                'ScientificName' => $med['ScientificName'],
                'Image' => $imgSrc,
                'IsControlled' => $med['IsControlled'],
                'CategoryName' => $med['CategoryName'],
                'Price' => $med['Price'],
                'CostPrice' => $med['CostPrice'],
                'FixedCostPrice' => $med['FixedCostPrice'],
                'Stock' => $med['Stock'],
                'MinimumStock' => $med['MinimumStock'],
                'ExpiryDate' => $med['ExpiryDate']
            ]), ENT_QUOTES, 'UTF-8');
?>
            <tr class="hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                <td class="p-4 items-center gap-3">
                    <div class="flex items-center gap-3 mb-1.5">
                        <span class="font-black text-gray-800 dark:text-white"><?php echo htmlspecialchars($med['Name']); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($med['IsControlled']): ?>
                            <span class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded-full uppercase font-black border border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800 tracking-widest">Rx</span>
                        <?php endif; ?>
                        <span class="text-[11px] font-bold text-gray-500 block"><?php echo htmlspecialchars($med['ScientificName'] ?? ''); ?></span>
                    </div>
                </td>
                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                    <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800 px-2.5 py-1 rounded-full text-xs font-bold">
                        <?php echo htmlspecialchars($med['CategoryName'] ?? 'غير مصنف'); ?>
                    </span>
                </td>
                <td class="p-4 text-center">
                    <?php if ($med['Stock'] <= $med['MinimumStock']): ?>
                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 px-3 py-1.5 rounded-full font-black text-sm inline-block min-w-[40px] shadow-sm border border-rose-200 dark:border-rose-800/50"><?php echo $med['Stock']; ?></span>
                    <?php else: ?>
                        <span class="bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/50 dark:text-[#4ADE80] px-3 py-1.5 rounded-full font-black text-sm inline-block min-w-[40px] shadow-sm border border-transparent dark:border-[#0A7A48]/30"><?php echo $med['Stock']; ?></span>
                    <?php endif; ?>
                </td>
                <td class="p-4 font-black text-gray-800 dark:text-white text-[15px]"><span dir="ltr"><?php echo $med['Price']; ?> ₪</span></td>
                <td class="p-4 text-gray-500 dark:text-gray-400 font-bold"><span dir="ltr"><?php echo $med['CostPrice']; ?> ₪</span></td>
                <td class="p-4 font-black <?php echo $margin > 20 ? 'text-emerald-500' : ($margin > 0 ? 'text-amber-500' : 'text-rose-500'); ?>" dir="ltr"><?php echo $margin; ?>%</td>
                <td class="p-4 font-medium" dir="ltr">
                    <span class="border <?php echo $expiry_class; ?> px-3 py-1.5 rounded-full text-xs font-bold inline-block shadow-sm">
                        <?php echo $med['ExpiryDate']; ?>
                    </span>
                </td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick='editModal(<?php echo $med_json; ?>)' class="edit-button text-[#0A7A48] dark:text-[#4ADE80]" title="تعديل السعر/الكمية">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path class="line" d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>
                        <button onclick="confirmDelete(<?php echo $med['StockID']; ?>)" class="bin-button text-rose-500" title="إزالة من المخزون">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M3 6h18" class="bin-top"></path>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" class="bin-top"></path>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" class="bin-bottom"></path>
                                <path d="M10 11v6" class="bin-bottom"></path>
                                <path d="M14 11v6" class="bin-bottom"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        <?php
        }
    } else {
        ?>
        <tr>
            <td colspan="8" class="p-16">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative w-24 h-24 mb-6">
                        <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                        <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                            <i data-lucide="package-x" class="w-10 h-10 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white mb-2">لا يوجد أدوية مطابقة في مخزونك</h3>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">جرب كتابة اسم مختلف أو قم بإضافة دواء جديد لمخزونك.</p>
                </div>
            </td>
        </tr>
<?php
    }
    $content = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['html' => $content, 'has_data' => mysqli_num_rows($medicines) > 0]);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
    .edit-button {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background-color: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .edit-button:hover {
        background-color: rgba(10, 122, 72, 0.1);
    }

    .dark .edit-button:hover {
        background-color: rgba(74, 222, 128, 0.2);
    }

    .edit-button svg {
        width: 18px;
        height: 18px;
    }

    .edit-button path {
        transition: stroke-dasharray 0.5s ease, stroke-dashoffset 0.5s ease;
    }

    .edit-button:hover .line {
        stroke-dasharray: 20;
        stroke-dashoffset: 0;
    }

    .edit-button .line {
        stroke-dasharray: 20;
        stroke-dashoffset: 20;
    }

    .bin-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background-color: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .bin-button:hover {
        background-color: rgba(225, 29, 72, 0.1);
    }

    .dark .bin-button:hover {
        background-color: rgba(225, 29, 72, 0.3);
    }

    .bin-bottom,
    .bin-top {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: bottom center;
    }

    .bin-button:hover .bin-top {
        transform: rotate(20deg) translateY(-2px);
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .search-dropdown {
        overflow-y: auto;
    }

    .search-dropdown::-webkit-scrollbar {
        width: 6px;
    }

    .search-dropdown::-webkit-scrollbar-thumb {
        background-color: rgba(10, 122, 72, 0.3);
        border-radius: 10px;
    }
</style>

<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 border-l-4 border-rose-500 p-4 rounded-xl flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 mt-0.5"></i>
            <p class="text-sm font-bold text-rose-700 dark:text-rose-400"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 w-full md:w-auto">
            <i data-lucide="briefcase-medical" class="text-[#0A7A48] dark:text-[#4ADE80] w-[42px] h-[30px]"></i> <?php echo $lang['product_inventory']; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
            <div class="w-full md:w-96 relative group">
                <input type="text" id="searchInput" oninput="fetchTableData()" placeholder="ابحث في مخزونك الحالي..."
                    class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] focus:border-[#0A7A48] outline-none transition-all text-sm">
                <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?> w-5 h-5"></i>
            </div>

            <button onclick="openModal()" class="w-full md:w-auto group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[#0A7A48] px-6 py-3 font-bold text-white shadow-lg shadow-green-900/20 transition-all duration-300 hover:scale-[1.02] active:scale-95 border border-transparent hover:border-green-400/30">
                <div class="absolute inset-0 flex h-full w-full justify-center [transform:skew(-12deg)_translateX(-150%)] group-hover:duration-1000 group-hover:[transform:skew(-12deg)_translateX(150%)]">

                    <div class="relative h-full w-10 bg-white/20"></div>
                </div>
                <i data-lucide="plus-circle" class="relative z-10 w-5 h-5"></i>
                <span class="relative z-10 text-sm">إضافة دواء للمخزون</span>
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden transition-colors mb-6">
        <div class="overflow-x-auto" id="tableContainer" style="transition: opacity 0.3s ease;">
            <table class="w-full border-collapse min-w-[900px]">

                <thead id="tableHeader" class="bg-gray-50/50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700" <?php if (mysqli_num_rows($medicines) == 0) echo 'style="display:none;"'; ?>>
                    <tr class="text-gray-600 dark:text-gray-400 text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-4 font-bold"><?php echo $lang['product']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['category']; ?></th>
                        <th class="p-4 font-bold text-center"><?php echo $lang['stock']; ?></th>
                        <th class="p-4 font-bold">سعر البيع</th>
                        <th class="p-4 font-bold">التكلفة</th>
                        <th class="p-4 font-bold">الربح</th>
                        <th class="p-4 font-bold"><?php echo $lang['expiry']; ?></th>
                        <th class="p-4 font-bold text-center"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody id="medicinesBody" class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <!-- يُعبأ بـ AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- النافذة المنبثقة (Modal) للبحث والإضافة والتعديل -->
<div id="medicineModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden flex justify-center items-center transition-opacity py-4 px-4">
    <div class="bg-[#F2FBF5] dark:bg-slate-900 w-full max-w-3xl max-h-[95vh] rounded-3xl shadow-2xl overflow-hidden transform transition-all border border-gray-200 dark:border-slate-700 flex flex-col">

        <!-- الهيدر -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700 flex justify-between items-center bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm">
            <h2 id="modalTitle" class="text-xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                <div class="p-2 text-[#0A7A48] dark:text-[#4ADE80]">
                    <i data-lucide="briefcase-medical" class="w-5 h-5"></i>
                </div>
                <span id="modalTitleText">إضافة دواء للمخزون</span>
            </h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-rose-500 bg-white dark:bg-slate-800 shadow-sm border border-gray-200 dark:border-slate-600 p-1.5 rounded-full transition">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- محتوى النافذة -->
        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar relative">

            <!-- 1. قسم البحث -->
            <div id="searchSection" class="mb-6 relative z-20 flex flex-col h-[400px]">
                <div class="bg-white dark:bg-slate-800 p-5 rounded-t-2xl border border-gray-200 dark:border-slate-700 border-b-0 shadow-sm">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">1. ابحث عن الدواء في الكتالوج الموحد:</label>

                    <div class="flex flex-col md:flex-row gap-4 relative">

                        <div class="w-full md:w-1/3 relative group">
                            <select id="systemSearchCategory" onchange="triggerSystemSearch()" class="w-full h-[50px] bg-gray-50 dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-700 rounded-xl px-4 text-sm font-bold text-gray-900 dark:text-white outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 cursor-pointer transition-all">
                                <option value="0">جميع التصنيفات</option>
                                <?php
                                mysqli_data_seek($categories_query, 0);
                                while ($cat = mysqli_fetch_assoc($categories_query)) {
                                    echo '<option value="' . $cat['CategoryID'] . '" class="text-gray-900 dark:text-white bg-white dark:bg-slate-800">' . htmlspecialchars($cat['Name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="w-full md:w-2/3 relative">
                            <input type="text" id="systemSearchInput" oninput="triggerSystemSearch()" placeholder="اكتب اسم الدواء التجاري أو العلمي..." autocomplete="off"
                                class="w-full h-[50px] bg-gray-50 dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-700 rounded-xl px-4 rtl:pr-11 ltr:pl-11 text-sm font-bold text-gray-900 dark:text-white outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 transition-all placeholder-gray-400">
                            <i data-lucide="search" class="absolute top-0 bottom-0 my-auto <?php echo ($dir == 'rtl') ? 'right-4' : 'left-4'; ?> w-5 h-5 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- قائمة النتائج -->
                <div class="flex-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-b-2xl overflow-hidden flex flex-col relative shadow-sm">
                    <div class="px-5 py-3 bg-gray-50/80 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700">
                        <span id="resultsTitle" class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">الأدوية المقترحة لك:</span>
                    </div>
                    <ul id="systemSearchResults" class="flex-1 overflow-y-auto search-dropdown p-2 divide-y divide-gray-50 dark:divide-slate-700/50">
                        <!-- تعبأ بـ AJAX -->
                    </ul>
                </div>
            </div>

            <!-- 2. كرت الدواء المختار -->
            <div id="selectedMedicineCard" class="hidden mb-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 p-5 rounded-2xl flex items-start gap-4 relative shadow-sm">
                <img id="selMedImg" src="" class="w-20 h-20 rounded-xl object-cover border border-gray-200 dark:border-slate-600 bg-gray-50 shadow-sm shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <h3 id="selMedName" class="font-black text-gray-800 dark:text-white text-xl"></h3>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span id="selMedRx" class="hidden bg-amber-100 text-amber-700 text-[10px] px-1.5 py-0.5 rounded-full uppercase font-black border border-amber-200 dark:bg-amber-900/40 dark:border-amber-800 dark:text-amber-400 tracking-widest shadow-sm">Rx</span>
                        <p id="selMedScientific" class="text-sm text-gray-500 font-bold"></p>
                    </div>
                    <span id="selMedCategory" class="bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs px-2.5 py-1 rounded-full border border-blue-200 dark:border-blue-700 font-bold inline-flex items-center gap-1.5"><span></span></span>
                </div>
            </div>

            <!-- 3. فورم إدخال بيانات المخزون -->
            <form id="stockForm" method="POST" class="hidden flex flex-col gap-5">
                <input type="hidden" name="stock_id" id="stock_id" value="">
                <input type="hidden" name="system_med_id" id="system_med_id" value="">

                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm">
                    <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 mb-5 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="settings-2" class="w-4 h-4"></i> إعدادات المخزون والسعر والتنبيهات
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <!-- حقل التكلفة (مغلق ومحمي) -->
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400">سعر التكلفة (₪)</label>
                                <span id="dynamicMargin" class="text-[10px] font-black text-gray-400 transition-colors">الربح: 0%</span>
                            </div>
                            <input type="number" name="cost" id="cost" readonly class="w-full bg-gray-100 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none text-gray-500 font-bold cursor-not-allowed opacity-80 shadow-inner" dir="ltr" title="سعر التكلفة محدد من الإدارة">
                        </div>

                        <!-- حقل سعر البيع -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2">سعر البيع للجمهور (₪)</label>
                            <input type="number" min="0.01" step="0.01" name="price" id="price" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white font-black text-[#0A7A48] dark:text-[#4ADE80] transition-all" dir="ltr">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2">الكمية الحالية المتوفرة</label>
                            <input type="number" min="0" step="1" name="stock" id="stock" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white font-black text-center transition-all" dir="ltr">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2">حد التنبيه بنقص المخزون</label>
                            <input type="number" min="0" step="1" name="min_stock" id="min_stock" value="10" required class="w-full bg-gray-50 dark:bg-slate-900/50 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white font-bold text-gray-500 text-center transition-all" dir="ltr">
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2">تاريخ انتهاء الصلاحية</label>
                            <input type="date" name="expiry" id="expiry" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white dark:[color-scheme:dark] font-bold transition-all" dir="ltr">
                        </div>
                    </div>
                </div>

                <!-- الأزرار. السفلية -->
                <div class="mt-2 pt-5 border-t border-gray-200 dark:border-slate-700 flex flex-col-reverse md:flex-row justify-between items-center gap-3">
                    <button type="button" onclick="closeModal()" class="w-full md:w-auto px-6 py-3 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-white dark:hover:bg-slate-800 transition text-sm bg-transparent">
                        إغلاق
                    </button>

                    <div class="flex w-full md:w-auto gap-3">
                        <button type="button" id="backToSearchBtn" onclick="resetSearch()" class="w-full md:w-auto px-5 py-3 rounded-xl bg-white text-blue-600 dark:bg-slate-800 dark:text-blue-400 font-bold hover:bg-blue-50 dark:hover:bg-slate-700 transition text-sm flex items-center justify-center gap-2 border border-gray-200 dark:border-slate-700 shadow-sm">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i> عودة للكتالوج
                        </button>

                        <button type="submit" name="save_stock" class="w-full md:w-auto bg-[#0A7A48] hover:bg-[#044E29] text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-green-900/30 text-sm flex items-center justify-center gap-2 border border-transparent">
                            <i data-lucide="save" class="w-4 h-4"></i> حفظ في المخزون
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let fetchTimeoutId;
    let searchSysTimeoutId;

    async function fetchTableData() {
        const body = document.getElementById('medicinesBody');
        const header = document.getElementById('tableHeader');
        const container = document.getElementById('tableContainer');
        const search = document.getElementById('searchInput').value;

        container.style.opacity = '0.4';
        container.style.pointerEvents = 'none';

        const newUrl = `?search=${encodeURIComponent(search)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        clearTimeout(fetchTimeoutId);
        fetchTimeoutId = setTimeout(async () => {
            try {
                const response = await fetch(`medicines.php?ajax_table=1&search=${encodeURIComponent(search)}`);
                const data = await response.json();

                body.innerHTML = data.html;

                if (header) {
                    header.style.display = data.has_data ? '' : 'none';
                }

                lucide.createIcons();
            } catch (error) {
                console.error("Error fetching stock:", error);
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }, 300);
    }
    document.addEventListener('DOMContentLoaded', fetchTableData);

    function triggerSystemSearch() {
        const query = document.getElementById('systemSearchInput').value;
        const catId = document.getElementById('systemSearchCategory').value;
        searchSystemMedicine(query, catId);
    }

    async function searchSystemMedicine(query, catId) {
        const resultsBox = document.getElementById('systemSearchResults');
        const resultsTitle = document.getElementById('resultsTitle');

        if (query.length === 0 && catId == 0) {
            resultsTitle.innerText = "أحدث الأدوية المضافة للكتالوج:";
        } else {
            resultsTitle.innerText = "نتائج البحث المخصصة:";
        }

        clearTimeout(searchSysTimeoutId);
        searchSysTimeoutId = setTimeout(async () => {
            try {
                const res = await fetch(`medicines.php?search_system_med=${encodeURIComponent(query)}&cat_id=${catId}`);
                const data = await res.json();

                resultsBox.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(med => {
                        const rxBadge = med.IsControlled == 1 ? `<span class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded-full uppercase font-black border border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800 tracking-widest">Rx</span>` : '';
                        let imgSrc = med.Image;
                        if (imgSrc && imgSrc !== 'default_med.png') {
                            if (!imgSrc.startsWith('uploads/') && !imgSrc.startsWith('../uploads/')) imgSrc = `../uploads/${imgSrc}`;
                            else if (imgSrc.startsWith('uploads/')) imgSrc = `../${imgSrc}`;
                        } else {
                            imgSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(med.Name)}&background=e2e8f0&color=475569`;
                        }

                        const medDataStr = JSON.stringify({
                            id: med.SystemMedID,
                            name: med.Name,
                            scientific: med.ScientificName || '',
                            img: imgSrc,
                            rx: med.IsControlled,
                            cat: med.CategoryName || 'غير مصنف',
                            fixed_cost: med.FixedCostPrice
                        }).replace(/'/g, "&apos;").replace(/"/g, "&quot;");

                        resultsBox.innerHTML += `
                            <li onclick="selectSystemMedicine('${medDataStr}')" class="p-3 hover:bg-gray-50 dark:hover:bg-slate-700/50 cursor-pointer flex items-center justify-between transition group">
                                <div class="flex items-center gap-4">
                                    <img src="${imgSrc}" class="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-slate-600 bg-white shrink-0 shadow-sm">
                                    <div>
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="font-black text-gray-800 dark:text-white text-base">${med.Name}</span>
                                            ${rxBadge}
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-gray-500 font-bold">${med.ScientificName || ''}</span>
                                            <span class="bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-300 text-[10px] px-2 py-0.5 rounded-full border border-gray-200 dark:border-slate-600 font-bold group-hover:bg-blue-100 group-hover:text-blue-700 group-hover:dark:bg-blue-900/40 group-hover:dark:text-blue-400 group-hover:border border-blue-200 group-hover:dark:border-blue-800 px-2.5 py-1 rounded-full text-xs font-bold">${med.CategoryName || 'غير مصنف'}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-300 text-[10px] px-2 py-0.5 rounded-full border border-gray-200 dark:border-slate-600 font-bold group-hover:bg-green-100 group-hover:text-green-700 group-hover:dark:bg-green-900/40 group-hover:dark:text-green-400 group-hover:border border-green-200 group-hover:dark:border-green-800 px-2.5 py-1 rounded-full text-xs font-bold" dir="ltr">التكلفة: ${med.FixedCostPrice} ₪</div>
                            </li>
                        `;
                    });
                } else {
                    resultsBox.innerHTML = `
                        <li class="p-8 text-center flex flex-col items-center bg-gray-50/50 dark:bg-slate-800/30 rounded-xl m-2 border border-dashed border-gray-200 dark:border-slate-700 transition-all">
                            <div class="relative w-20 h-20 mb-5">
                                <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                                <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                                    <i data-lucide="package-search" class="w-8 h-8 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                                </div>
                            </div>
                            <span class="text-sm font-black text-gray-700 dark:text-gray-300 mb-1.5">لم يتم العثور على أدوية مطابقة.</span>
                            <span class="text-xs font-bold text-gray-400">تأكد من الاسم، أو قد يكون هذا الدواء موجوداً مسبقاً في مخزونك.</span>
                        </li>
                    `;
                    lucide.createIcons();
                }
                resultsBox.classList.remove('hidden');
            } catch (e) {
                console.error(e);
            }
        }, 300);
    }

    const modal = document.getElementById('medicineModal');

    function openModal() {
        document.getElementById('modalTitleText').innerText = "إضافة دواء جديد للمخزون";

        document.getElementById('stock_id').value = "";
        document.getElementById('system_med_id').value = "";
        document.getElementById('price').value = "";
        document.getElementById('cost').value = "";
        document.getElementById('stock').value = "";
        document.getElementById('min_stock').value = "10";
        document.getElementById('expiry').value = "";
        document.getElementById('systemSearchInput').value = "";
        document.getElementById('systemSearchCategory').value = "0";

        document.getElementById('dynamicMargin').innerText = "الربح: 0%";
        document.getElementById('dynamicMargin').className = "text-[10px] font-black text-gray-400 transition-colors";

        document.getElementById('searchSection').classList.remove('hidden');
        document.getElementById('selectedMedicineCard').classList.add('hidden');
        document.getElementById('stockForm').classList.add('hidden');
        document.getElementById('backToSearchBtn').classList.remove('hidden');
        document.getElementById('systemSearchResults').classList.remove('hidden');

        modal.classList.remove('hidden');
        setTimeout(() => document.getElementById('systemSearchInput').focus(), 100);

        triggerSystemSearch();
    }

    function resetSearch() {
        document.getElementById('searchSection').classList.remove('hidden');
        document.getElementById('selectedMedicineCard').classList.add('hidden');
        document.getElementById('stockForm').classList.add('hidden');
        setTimeout(() => document.getElementById('systemSearchInput').focus(), 100);
    }

    function calculateMargin() {
        let cost = parseFloat(document.getElementById('cost').value) || 0;
        let price = parseFloat(document.getElementById('price').value) || 0;
        let marginSpan = document.getElementById('dynamicMargin');

        if (price > cost && cost > 0) {
            let margin = Math.round(((price - cost) / price) * 100);
            marginSpan.innerText = `الربح: ${margin}%`;
            marginSpan.className = "text-[10px] font-black text-emerald-500 animate-pulse";
        } else if (price < cost && price > 0) {
            marginSpan.innerText = `خسارة!`;
            marginSpan.className = "text-[10px] font-black text-rose-500 animate-pulse";
        } else {
            marginSpan.innerText = `الربح: 0%`;
            marginSpan.className = "text-[10px] font-black text-gray-400 transition-colors";
        }
    }

    document.getElementById('price').addEventListener('input', calculateMargin);

    function selectSystemMedicine(dataStr) {
        const med = JSON.parse(dataStr);

        document.getElementById('system_med_id').value = med.id;
        document.getElementById('selMedImg').src = med.img;
        document.getElementById('selMedName').innerText = med.name;
        document.getElementById('selMedScientific').innerText = med.scientific;
        document.querySelector('#selMedCategory span').innerText = med.cat;

        document.getElementById('cost').value = med.fixed_cost;
        document.getElementById('price').value = "";
        calculateMargin();

        if (med.rx == 1) document.getElementById('selMedRx').classList.remove('hidden');
        else document.getElementById('selMedRx').classList.add('hidden');

        document.getElementById('searchSection').classList.add('hidden');
        document.getElementById('selectedMedicineCard').classList.remove('hidden');
        document.getElementById('stockForm').classList.remove('hidden');

        setTimeout(() => document.getElementById('price').focus(), 100);
        lucide.createIcons();
    }

    function editModal(stock) {
        document.getElementById('modalTitleText').innerText = "تعديل بيانات المخزون";

        document.getElementById('stock_id').value = stock.StockID;
        document.getElementById('system_med_id').value = stock.SystemMedID;
        document.getElementById('price').value = stock.Price;

        document.getElementById('cost').value = stock.FixedCostPrice;
        document.getElementById('stock').value = stock.Stock;
        document.getElementById('min_stock').value = stock.MinimumStock;
        document.getElementById('expiry').value = stock.ExpiryDate;

        calculateMargin();

        document.getElementById('selMedImg').src = stock.Image;
        document.getElementById('selMedName').innerText = stock.Name;
        document.getElementById('selMedScientific').innerText = stock.ScientificName || '';
        document.querySelector('#selMedCategory span').innerText = stock.CategoryName || 'غير مصنف';

        if (stock.IsControlled == 1) document.getElementById('selMedRx').classList.remove('hidden');
        else document.getElementById('selMedRx').classList.add('hidden');

        document.getElementById('searchSection').classList.add('hidden');
        document.getElementById('backToSearchBtn').classList.add('hidden');
        document.getElementById('selectedMedicineCard').classList.remove('hidden');
        document.getElementById('stockForm').classList.remove('hidden');

        lucide.createIcons();
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'إزالة الدواء من المخزون؟',
            text: 'لن تتمكن من التراجع عن هذا، وسيتم مسحه من قائمة أدويتك المعروضة للمرضى، وإذا كان موجوداً في طلبات سابقة سيتم تعديل سعرها.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، إزالة',
            cancelButtonText: 'إلغاء',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'medicines.php?delete=' + id;
            }
        });
    }

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault();
        });
        input.addEventListener('input', function() {
            if (this.value < 0) this.value = Math.abs(this.value);
        });
    });
</script>

<?php include('../includes/footer.php'); ?>