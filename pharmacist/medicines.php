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

// ==========================================
// 1. معالجة عمليات الإضافة والتعديل (Add / Edit)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_medicine'])) {
    $med_id = isset($_POST['medicine_id']) ? intval($_POST['medicine_id']) : 0;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cat_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $stock = intval($_POST['stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry = mysqli_real_escape_string($conn, $_POST['expiry']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $is_controlled = isset($_POST['is_controlled']) ? 1 : 0;

    $image = "";
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    if ($med_id > 0) {
        $img_query = $image != "" ? ", Image='$image'" : "";
        $sql = "UPDATE Medicine SET Name='$name', CategoryID=$cat_id, Price=$price, CostPrice=$cost,
                Stock=$stock, MinimumStock=$min_stock, ExpiryDate='$expiry', Description='$desc',
                IsControlled=$is_controlled $img_query WHERE MedicineID=$med_id AND PharmacistID=$pharmacist_id";
    } else {
        $sql = "INSERT INTO Medicine (Name, CategoryID, Price, CostPrice, Stock, MinimumStock, ExpiryDate, Description, IsControlled, Image, PharmacistID)
                VALUES ('$name', $cat_id, $price, $cost, $stock, $min_stock, '$expiry', '$desc', $is_controlled, '$image', $pharmacist_id)";
    }
    mysqli_query($conn, $sql);
    header("Location: medicines.php");
    exit();
}

// ==========================================
// 2. معالجة عملية الحذف (Delete)
// ==========================================
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM Medicine WHERE MedicineID=$del_id AND PharmacistID=$pharmacist_id");
    header("Location: medicines.php");
    exit();
}

// ==========================================
// 3. جلب بيانات التصنيفات 
// ==========================================
$categories = mysqli_query($conn, "SELECT * FROM Category ORDER BY Name ASC");

// ==========================================
// 4. نظام البحث المباشر (AJAX) وجلب الأدوية
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT m.*, c.Name as CategoryName
          FROM Medicine m
          LEFT JOIN Category c ON m.CategoryID = c.CategoryID
          WHERE m.PharmacistID = $pharmacist_id AND m.Name LIKE '%$search%'
          ORDER BY m.MedicineID DESC";
$medicines = mysqli_query($conn, $query);

// 🚀 معالجة طلب AJAX لرسم الجدول
if (isset($_GET['ajax'])) {
    ob_start();
    if (mysqli_num_rows($medicines) > 0) {
        while ($med = mysqli_fetch_assoc($medicines)) {
            // حساب نسبة الربح
            $margin = 0;
            if ($med['Price'] > 0 && $med['CostPrice'] > 0) {
                $margin = round((($med['Price'] - $med['CostPrice']) / $med['Price']) * 100);
            }

            // فحص الصلاحية الذكي
            $expiry_timestamp = strtotime($med['ExpiryDate']);
            $current_timestamp = time();
            $days_30_timestamp = strtotime('+30 days');

            if ($expiry_timestamp < $current_timestamp) {
                $expiry_class = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400";
            } elseif ($expiry_timestamp <= $days_30_timestamp) {
                $expiry_class = "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400";
            } else {
                $expiry_class = "bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/40 dark:text-[#4ADE80]";
            }

            $imgSrc = !empty($med['Image']) ? '../uploads/' . $med['Image'] : 'https://ui-avatars.com/api/?name=' . urlencode($med['Name']) . '&background=f8fafc&color=94a3b8';
            $med_json = htmlspecialchars(json_encode($med), ENT_QUOTES, 'UTF-8');
?>
            <tr class="hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700">
                <td class="p-4 flex items-center gap-3">
                    <img src="<?php echo $imgSrc; ?>" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                    <div>
                        <span class="font-bold text-gray-800 dark:text-white block"><?php echo htmlspecialchars($med['Name']); ?></span>
                        <?php if ($med['IsControlled']): ?>
                            <div class="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded shadow-sm mt-0.5">
                                <i data-lucide="file-text" class="w-3 h-3"></i>
                                <span class="text-[10px] font-black tracking-wider">RX</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                    <span class="bg-gray-100 dark:bg-slate-700 px-2.5 py-1 rounded-md text-xs">
                        <?php echo $med['CategoryName'] ? htmlspecialchars($med['CategoryName']) : (isset($lang['uncategorized']) ? $lang['uncategorized'] : 'غير مصنف'); ?>
                    </span>
                </td>
                <td class="p-4 text-center">
                    <?php if ($med['Stock'] <= $med['MinimumStock']): ?>
                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm"><?php echo $med['Stock']; ?></span>
                    <?php else: ?>
                        <span class="bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/50 dark:text-[#4ADE80] px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm border border-transparent dark:border-[#0A7A48]/30"><?php echo $med['Stock']; ?></span>
                    <?php endif; ?>
                </td>
                <td class="p-4 font-bold text-gray-800 dark:text-white"><span dir="ltr"><?php echo $med['Price']; ?> ₪</span></td>
                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium"><span dir="ltr"><?php echo $med['CostPrice']; ?> ₪</span></td>
                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium" dir="ltr"><?php echo $margin; ?>%</td>
                <td class="p-4 font-medium" dir="ltr">
                    <span class="<?php echo $expiry_class; ?> px-3 py-1.5 rounded-full text-xs font-bold inline-block shadow-sm">
                        <?php echo $med['ExpiryDate']; ?>
                    </span>
                </td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick='editModal(<?php echo $med_json; ?>)' class="edit-button text-[#048AC1]" title="<?php echo $lang['edit_product']; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path class="line" d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>
                        <button onclick="confirmDelete(<?php echo $med['MedicineID']; ?>)" class="bin-button text-rose-500" title="<?php echo isset($lang['delete']) ? $lang['delete'] : 'حذف'; ?>">
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
        <?php }
    } else { ?>
        <tr>
            <td colspan="8" class="p-16">
                <!-- 🚀 Empty State متحرك مستوحى من Uiverse -->
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative w-24 h-24 mb-6">
                        <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                        <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                            <i data-lucide="package-x" class="w-10 h-10 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">لا يوجد أدوية مطابقة</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">جرب كتابة اسم مختلف أو أضف دواءً جديداً.</p>
                </div>
            </td>
        </tr>
<?php }
    $content = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['html' => $content, 'has_data' => mysqli_num_rows($medicines) > 0]);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
    /* أزرار Uiverse (التعديل والحذف) */
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
        background-color: rgba(4, 138, 193, 0.1);
    }

    .dark .edit-button:hover {
        background-color: rgba(4, 138, 193, 0.3);
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

    .bin-bottom {
        transform-origin: bottom center;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bin-top {
        transform-origin: bottom right;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bin-button:hover .bin-top {
        transform: rotate(20deg) translateY(-2px);
    }

    /* إخفاء أسهم الأرقام */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* منطقة الرفع */
    .upload-area {
        transition: all 0.3s ease-in-out;
    }

    .upload-area:hover {
        background-color: rgba(10, 122, 72, 0.05);
        border-color: #0A7A48;
    }

    .dark .upload-area:hover {
        background-color: rgba(10, 122, 72, 0.15);
        border-color: #4ADE80;
    }
</style>

<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">
    <?php include('../includes/topbar.php'); ?>

    <!-- ==========================================
         شريط البحث وزر الإضافة 
    =========================================== -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 w-full md:w-auto">
            <i data-lucide="briefcase-medical" class="text-[#0A7A48] dark:text-[#4ADE80] w-[42px] h-[30px]"></i> <?php echo $lang['product_inventory']; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
            <!-- 🚀 شريط البحث المباشر (AJAX) -->
            <div class="w-full md:w-96 relative group">
                <input type="text" id="searchInput" oninput="fetchTableData()" placeholder="<?php echo $lang['search_product']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] focus:border-[#0A7A48] outline-none transition-all text-sm">
                <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?> w-5 h-5"></i>
            </div>

            <!-- زر إضافة دواء -->
            <button onclick="openModal()" class="w-full md:w-auto group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[#0A7A48] px-6 py-3 font-bold text-white shadow-lg shadow-green-900/20 transition-all duration-300 hover:scale-[1.02] active:scale-95 border border-transparent hover:border-green-400/30">
                <div class="absolute inset-0 flex h-full w-full justify-center [transform:skew(-12deg)_translateX(-150%)] group-hover:duration-1000 group-hover:[transform:skew(-12deg)_translateX(150%)]">
                    <div class="relative h-full w-10 bg-white/20"></div>
                </div>
                <i data-lucide="plus-circle" class="relative z-10 w-5 h-5"></i>
                <span class="relative z-10 text-sm"><?php echo $lang['add_product']; ?></span>
            </button>
        </div>
    </div>

    <!-- ==========================================
         جدول المخزون (يتحدث ديناميكياً)
    =========================================== -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden transition-colors mb-6">
        <div class="overflow-x-auto" id="tableContainer" style="transition: opacity 0.3s ease;">
            <table class="w-full border-collapse">
                <thead id="tableHeader" class="bg-gray-50/50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700">
                    <tr class="text-gray-600 dark:text-gray-400 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-4 font-bold"><?php echo $lang['product']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['category']; ?></th>
                        <th class="p-4 font-bold text-center"><?php echo $lang['stock']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['price']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['cost']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['margin']; ?></th>
                        <th class="p-4 font-bold"><?php echo $lang['expiry']; ?></th>
                        <th class="p-4 font-bold text-center"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <!-- 💡 سيتم تحديث الـ tbody بواسطة الجافاسكربت -->
                <tbody id="medicinesBody" class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <!-- الفراغ يعبأ عبر الجافاسكربت -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ==========================================
     النافذة المنبثقة (Modal) لإضافة/تعديل دواء
=========================================== -->
<div id="medicineModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 hidden flex justify-center items-center transition-opacity py-4">
    <div class="bg-white dark:bg-slate-800 w-full max-w-2xl max-h-full rounded-3xl shadow-2xl overflow-hidden transform transition-all relative mx-4 border border-gray-100 dark:border-slate-700 flex flex-col">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-700 shrink-0">
            <h2 id="modalTitle" class="text-xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                <div class="p-2 bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-lg shrink-0">
                    <i data-lucide="package-plus" class="text-[#0A7A48] dark:text-[#4ADE80] w-5 h-5"></i>
                </div>
                <span id="modalTitleText"><?php echo $lang['add_new_product']; ?></span>
            </h2>
        </div>

        <form method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="medicine_id" id="medicine_id" value="">
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['product_name']; ?></label>
                        <input type="text" name="name" id="name" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['category']; ?></label>
                        <div class="relative group">
                            <select name="category_id" id="category_id" required class="w-full appearance-none bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition cursor-pointer pr-10 rtl:pr-4 rtl:pl-10">
                                <option value=""><?php echo isset($lang['select_category']) ? $lang['select_category'] : 'اختر تصنيفاً...'; ?></option>
                                <?php
                                mysqli_data_seek($categories, 0);
                                while ($cat = mysqli_fetch_assoc($categories)) {
                                    echo "<option value='{$cat['CategoryID']}'>{$cat['Name']}</option>";
                                } ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 <?php echo ($dir == 'rtl') ? 'left-0 pl-3' : 'right-0 pr-3'; ?> flex items-center text-gray-400 group-focus-within:text-[#0A7A48] transition-colors"><i data-lucide="chevron-down" class="w-4 h-4"></i></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['price']; ?> (₪)</label>
                        <input type="number" min="0" step="0.01" name="price" id="price" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['cost']; ?> (₪)</label>
                        <input type="number" min="0" step="0.01" name="cost" id="cost" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['stock']; ?></label>
                        <input type="number" min="0" step="1" name="stock" id="stock" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['min_stock']; ?></label>
                        <input type="number" min="0" step="1" name="min_stock" id="min_stock" value="10" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['expiry']; ?></label>
                        <input type="date" name="expiry" id="expiry" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white dark:[color-scheme:dark] transition" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['description']; ?></label>
                        <input type="text" name="description" id="description" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['product_image']; ?></label>
                    <label for="image_upload" class="upload-area flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-2xl cursor-pointer bg-gray-50/50 dark:bg-slate-900/50 group">
                        <div class="flex flex-col items-center justify-center pt-3 pb-3">
                            <i data-lucide="cloud-upload" class="w-6 h-6 mb-2 text-gray-400 group-hover:text-[#0A7A48] dark:group-hover:text-[#4ADE80] transition-colors"></i>
                            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400 font-bold" id="file-name-display">اضغط هنا لرفع الصورة</p>
                        </div>
                        <input id="image_upload" type="file" name="image" accept="image/*" class="hidden" onchange="updateFileName(this)" />
                    </label>
                </div>

                <div class="flex items-center justify-between bg-amber-50 dark:bg-amber-900/20 p-3 rounded-2xl border border-amber-200 dark:border-amber-900/50 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-amber-100 dark:bg-amber-800/50 rounded-lg shrink-0"><i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i></div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 select-none"><?php echo $lang['is_controlled']; ?></h4>
                            <p class="text-[11px] text-amber-600 dark:text-amber-500 mt-0.5 leading-tight">وصفة طبية مطلوبة.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="is_controlled" id="is_controlled" class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] rtl:after:right-[2px] ltr:after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500 shadow-inner"></div>
                    </label>
                </div>
            </div>

            <div class="p-5 border-t border-gray-100 dark:border-slate-700 bg-gray-50/80 dark:bg-slate-900/50 shrink-0 flex justify-end gap-3 rounded-b-3xl">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-white dark:hover:bg-slate-700 transition text-sm">
                    <?php echo $lang['cancel']; ?>
                </button>
                <button type="submit" name="save_medicine" class="bg-[#0A7A48] hover:bg-[#044E29] text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-green-900/30 text-sm flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> <?php echo $lang['confirm']; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ==========================================
    // 🚀 دالة جلب بيانات الجدول عبر AJAX بدون Reload
    // ==========================================
    let fetchTimeoutId;

    async function fetchTableData() {
        const body = document.getElementById('medicinesBody');
        const header = document.getElementById('tableHeader');
        const container = document.getElementById('tableContainer');
        const search = document.getElementById('searchInput').value;

        // تأثير تحميل ناعم
        container.style.opacity = '0.3';
        container.style.pointerEvents = 'none';

        // تحديث الرابط في المتصفح بسلاسة
        const newUrl = `?search=${encodeURIComponent(search)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        clearTimeout(fetchTimeoutId);
        fetchTimeoutId = setTimeout(async () => {
            try {
                const response = await fetch(`medicines.php?ajax=1&search=${encodeURIComponent(search)}`);
                const data = await response.json();

                body.innerHTML = data.html;
                header.style.display = data.has_data ? '' : 'none';

                lucide.createIcons();

            } catch (error) {
                console.error("Error fetching medicines:", error);
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }, 300);
    }

    // التحميل الأولي
    document.addEventListener('DOMContentLoaded', fetchTableData);

    // منع الإدخال السالب
    document.addEventListener('DOMContentLoaded', function() {
        const numberInputs = document.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            });
            input.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = Math.abs(this.value);
                }
            });
        });
    });

    const modal = document.getElementById('medicineModal');
    const langAddProduct = "<?php echo $lang['add_new_product']; ?>";
    const langEditProduct = "<?php echo $lang['edit_product']; ?>";

    function openModal() {
        document.getElementById('medicine_id').value = "";
        document.getElementById('name').value = "";
        document.getElementById('category_id').value = "";
        document.getElementById('price').value = "";
        document.getElementById('cost').value = "";
        document.getElementById('stock').value = "";
        document.getElementById('min_stock').value = "10";
        document.getElementById('expiry').value = "";
        document.getElementById('description').value = "";
        document.getElementById('is_controlled').checked = false;

        document.getElementById('modalTitleText').innerText = langAddProduct;
        modal.classList.remove('hidden');
    }

    function editModal(med) {
        document.getElementById('medicine_id').value = med.MedicineID;
        document.getElementById('name').value = med.Name;
        document.getElementById('category_id').value = med.CategoryID;
        document.getElementById('price').value = med.Price;
        document.getElementById('cost').value = med.CostPrice;
        document.getElementById('stock').value = med.Stock;
        document.getElementById('min_stock').value = med.MinimumStock;
        document.getElementById('expiry').value = med.ExpiryDate;
        document.getElementById('description').value = med.Description;
        document.getElementById('is_controlled').checked = med.IsControlled == 1 ? true : false;

        document.getElementById('modalTitleText').innerText = langEditProduct;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function updateFileName(input) {
        const displayElement = document.getElementById('file-name-display');
        if (input.files && input.files.length > 0) {
            displayElement.innerText = "تم اختيار: " + input.files[0].name;
            displayElement.classList.remove('text-gray-500');
            displayElement.classList.add('text-[#0A7A48]', 'dark:text-[#4ADE80]');
        } else {
            displayElement.innerText = "اضغط هنا لرفع الصورة";
            displayElement.classList.add('text-gray-500');
            displayElement.classList.remove('text-[#0A7A48]', 'dark:text-[#4ADE80]');
        }
    }

    function confirmDelete(id) {
        Swal.fire({
            title: Lang.title,
            text: Lang.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.confirm,
            cancelButtonText: Lang.cancel,
            background: Lang.isDark ? '#1e293b' : '#fff',
            color: Lang.isDark ? '#f8fafc' : '#1f2937'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'medicines.php?delete=' + id;
            }
        });
    }
</script>

<?php include('../includes/footer.php'); ?>