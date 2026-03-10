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
    // floatval لضمان أن القيمة رقمية بكسور (للسعر)، و intval لرقم صحيح (للمخزون)
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $stock = intval($_POST['stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry = mysqli_real_escape_string($conn, $_POST['expiry']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $is_controlled = isset($_POST['is_controlled']) ? 1 : 0;

    // معالجة الصورة
    $image = "";
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    if ($med_id > 0) {
        // تحديث دواء موجود (Edit)
        $img_query = $image != "" ? ", Image='$image'" : "";
        $sql = "UPDATE Medicine SET Name='$name', CategoryID=$cat_id, Price=$price, CostPrice=$cost,
                Stock=$stock, MinimumStock=$min_stock, ExpiryDate='$expiry', Description='$desc',
                IsControlled=$is_controlled $img_query WHERE MedicineID=$med_id AND PharmacistID=$pharmacist_id";
    } else {
        // إضافة دواء جديد (Add)
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
// 3. جلب بيانات التصنيفات للقائمة المنسدلة (Dropdown)
// ==========================================
$categories = mysqli_query($conn, "SELECT * FROM Category ORDER BY Name ASC");

// ==========================================
// 4. جلب الأدوية وعمل البحث
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT m.*, c.Name as CategoryName
          FROM Medicine m
          LEFT JOIN Category c ON m.CategoryID = c.CategoryID
          WHERE m.PharmacistID = $pharmacist_id AND m.Name LIKE '%$search%'
          ORDER BY m.MedicineID DESC";
$medicines = mysqli_query($conn, $query);

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<style>
    /* ==========================================
   CSS الخاص بأزرار Uiverse (التعديل والحذف)
========================================== */

    /* 1. زر التعديل (القلم المتحرك) */
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
        /* لون أزرق خفيف للهوفر */
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

    /* 2. زر الحذف (سلة المهملات التي تفتح) */
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
        /* لون أحمر خفيف للهوفر */
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

    /* 3. إخفاء أسهم الزيادة والنقصان من حقول الأرقام */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* 4. تأثيرات منطقة رفع الصورة (Uiverse Style) */
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
	شريط البحث وزر الإضافة (بنية متطابقة مع الأدمن)
=========================================== -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">

        <!-- عنوان الصفحة مطابق لنمط الأدمن -->
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 w-full md:w-auto">
            <i data-lucide="pill-bottle" class="text-[#0A7A48]"></i> <?php echo $lang['product_inventory']; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">

            <!-- 💡 فورم البحث مطابق حرفياً لبنية الأدمن (group-focus-within) -->
            <form method="GET" class="w-full md:w-96">
                <div class="relative group">
                    <input type="text" name="search" placeholder="<?php echo $lang['search_product']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] focus:border-[#0A7A48] outline-none transition-all text-sm">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?> w-5 h-5"></i>
                </div>
            </form>

            <!-- 🚀 زر إضافة من Uiverse (Sweep/Shine Animation) -->
            <button onclick="openModal()" class="w-full md:w-auto group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[#0A7A48] px-6 py-2 font-bold text-white shadow-lg shadow-green-900/20 transition-all duration-300 hover:scale-[1.02] active:scale-95 border border-transparent hover:border-green-400/30">
                <!-- تأثير اللمعة المتحركة (Shine) -->
                <div class="absolute inset-0 flex h-full w-full justify-center [transform:skew(-12deg)_translateX(-150%)] group-hover:duration-1000 group-hover:[transform:skew(-12deg)_translateX(150%)]">
                    <div class="relative h-full w-10 bg-white/20"></div>
                </div>
                <i data-lucide="plus-circle" class="relative z-10 w-5 h-5"></i>
                <span class="relative z-10 text-sm"><?php echo $lang['add_product']; ?></span>
            </button>
        </div>
    </div>

    <!-- ==========================================
         جدول المخزون (Product Inventory)
         💡 تطبيق كامل للألوان الخضراء
    =========================================== -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3">
            <!-- 💡 أيقونة قائمة مرجعية بلون أخضر -->
            <i data-lucide="clipboard-list" class="text-[#0A7A48] w-6 h-6"></i>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white"><?php echo $lang['product_inventory']; ?></h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700">
                    <tr class="text-gray-600 dark:text-gray-400 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <!-- إزالة عمود الـ ID من الرأس -->
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
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php if (mysqli_num_rows($medicines) > 0): ?>
                        <?php while ($med = mysqli_fetch_assoc($medicines)):
                            // 1. حساب نسبة الربح
                            $margin = 0;
                            if ($med['Price'] > 0 && $med['CostPrice'] > 0) {
                                $margin = round((($med['Price'] - $med['CostPrice']) / $med['Price']) * 100);
                            }

                            // 2. فحص الصلاحية الذكي (حساب التواريخ)
                            $expiry_timestamp = strtotime($med['ExpiryDate']);
                            $current_timestamp = time();
                            $days_30_timestamp = strtotime('+30 days'); // تاريخ بعد 30 يوم من الآن

                            if ($expiry_timestamp < $current_timestamp) {
                                // منتهي الصلاحية (أحمر)
                                $expiry_class = "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400";
                            } elseif ($expiry_timestamp <= $days_30_timestamp) {
                                // قريب الانتهاء خلال شهر (أصفر/برتقالي)
                                $expiry_class = "bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400";
                            } else {
                                // صالح وممتاز (أخضر/فيروزي)
                                $expiry_class = "bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/40 dark:text-[#0A7A48]";
                            }
                        ?>
                            <!-- 💡 هوفر أخضر خفيف متناسق مع ثيم الصيدلاني -->
                            <tr class="hover:bg-[#E6F7ED] dark:hover:bg-[#044E29]/30 transition-colors duration-200 border-b border-transparent hover:border-gray-100 dark:hover:border-slate-700">

                                <!-- 1. صورة واسم الدواء + أيقونة Rx -->
                                <td class="p-4 flex items-center gap-3">
                                    <div>
                                        <span class="font-bold text-gray-800 dark:text-white block"><?php echo htmlspecialchars($med['Name']); ?></span>
                                        <?php if ($med['IsControlled']): ?>
                                            <!-- 💡 أيقونة Rx (دواء مراقب) بدلاً من النص العادي -->
                                            <div class="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded shadow-sm mt-0.5">
                                                <i data-lucide="file-text" class="w-3 h-3 text-ce"></i>
                                                <span class="text-[10px] font-black tracking-wider">PX</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- 2. التصنيف -->
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                                    <span class="bg-gray-100 dark:bg-slate-700 px-2.5 py-1 rounded-md text-xs">
                                        <?php echo $med['CategoryName'] ? htmlspecialchars($med['CategoryName']) : (isset($lang['uncategorized']) ? $lang['uncategorized'] : 'غير مصنف'); ?>
                                    </span>
                                </td>
                                <!-- 3. المخزون (أحمر للنواقص، أخضر طبيعي للممتاز) -->
                                <td class="p-4 text-center">
                                    <?php if ($med['Stock'] <= $med['MinimumStock']): ?>
                                        <!-- مخزون ناقص: أحمر تحذيري -->
                                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm"><?php echo $med['Stock']; ?></span>
                                    <?php else: ?>
                                        <!-- 💡 مخزون ممتاز: أخضر نعناعي متناسق مع ثيم الصيدلية -->
                                        <span class="bg-[#E6F7ED] text-[#0A7A48] dark:bg-[#044E29]/50 dark:text-[#4ADE80] px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm border border-transparent dark:border-[#0A7A48]/30"><?php echo $med['Stock']; ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- 4. السعر والتكلفة والربح -->
                                <td class="p-4 font-bold text-gray-800 dark:text-white"><span dir="ltr"><?php echo $med['Price']; ?> ₪</span></td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium"><span dir="ltr"><?php echo $med['CostPrice']; ?> ₪</span></td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium" dir="ltr"><?php echo $margin; ?>%</td>

                                <!-- 5. تاريخ الانتهاء (حسب الحالة بالألوان) -->
                                <!-- 💡 تم تطبيق الشريطة الدائرية الشفافة الملونة -->
                                <td class="p-4 font-medium" dir="ltr">
                                    <span class="<?php echo $expiry_class; ?> px-3 py-1.5 rounded-full text-xs font-bold inline-block shadow-sm">
                                        <?php echo $med['ExpiryDate']; ?>
                                    </span>
                                </td>

                                <!-- 6. أزرار التحكم (Uiverse.io) -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1">

                                        <!-- 🚀 زر التعديل (Uiverse - Short Mule) -->
                                        <button onclick='editModal(<?php echo json_encode($med); ?>)' class="edit-button text-[#048AC1]" title="<?php echo $lang['edit_product']; ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20h9"></path>
                                                <path class="line" d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- 🚀 زر الحذف (Uiverse - Dry Shrimp) -->
                                        <button onclick="confirmDelete(<?php echo $med['MedicineID']; ?>)" class="bin-button text-rose-500" title="<?php echo isset($lang['delete']) ? $lang['delete'] : 'حذف'; ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                                <!-- غطاء السلة (يتحرك في الهوفر) -->
                                                <path d="M3 6h18" class="bin-top"></path>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" class="bin-top"></path>
                                                <!-- جسم السلة (ثابت) -->
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" class="bin-bottom"></path>
                                                <path d="M10 11v6" class="bin-bottom"></path>
                                                <path d="M14 11v6" class="bin-bottom"></path>
                                            </svg>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-16">
                                <!-- 🚀 Empty State مستوحى من Uiverse -->
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="relative w-20 h-20 mb-6">
                                        <!-- دائرة تنبض في الخلفية -->
                                        <div class="absolute inset-0 bg-[#0A7A48] rounded-full opacity-20 animate-ping"></div>
                                        <!-- الأيقونة في المقدمة -->
                                        <div class="relative flex items-center justify-center w-full h-full bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-full shadow-inner">
                                            <i data-lucide="package-x" class="w-10 h-10 text-[#0A7A48]"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2"><?php echo $lang['no_medicines']; ?></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $lang['no_medicines_description']; ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ==========================================
     النافذة المنبثقة (Modal) لإضافة/تعديل دواء
     💡 ألوان الغابات الخضراء + UX Compact & Scrollable
=========================================== -->
<div id="medicineModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 hidden flex justify-center items-center transition-opacity">

    <!-- 💡 إضافة max-h-[95vh] و flex-col لتقسيم النافذة بذكاء -->
    <div class="bg-white dark:bg-slate-800 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden transform transition-all relative mx-4 border border-gray-100 dark:border-slate-700 flex flex-col">

        <!-- ==========================================
             1. رأس النافذة (ثابت لا يتحرك مع السكرول) 
        =========================================== -->
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-700 shrink-0">
            <h2 id="modalTitle" class="text-xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                <div class="p-2 bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-lg shrink-0">
                    <i data-lucide="package-plus" class="text-[#0A7A48] dark:text-[#4ADE80] w-5 h-5"></i>
                </div>
                <span id="modalTitleText"><?php echo $lang['add_new_product']; ?></span>
            </h2>
        </div>

        <!-- ==========================================
             2. النموذج (يحتوي على المحتوى القابل للتمرير) 
        =========================================== -->
        <form method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="medicine_id" id="medicine_id" value="">

            <!-- مساحة قابلة للتمرير (Scrollable Area) -->
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- 1. اسم الدواء -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['product_name']; ?></label>
                        <input type="text" name="name" id="name" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition">
                    </div>

                    <!-- 2. التصنيف -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['category']; ?></label>
                        <div class="relative group">
                            <select name="category_id" id="category_id" required class="w-full appearance-none bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition cursor-pointer pr-10 rtl:pr-4 rtl:pl-10">
                                <option value=""><?php echo isset($lang['select_category']) ? $lang['select_category'] : 'اختر تصنيفاً...'; ?></option>
                                <?php
                                mysqli_data_seek($categories, 0);
                                while ($cat = mysqli_fetch_assoc($categories)) {
                                    echo "<option value='{$cat['CategoryID']}'>{$cat['Name']}</option>";
                                }
                                ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 <?php echo ($dir == 'rtl') ? 'left-0 pl-3' : 'right-0 pr-3'; ?> flex items-center text-gray-400 group-focus-within:text-[#0A7A48] transition-colors">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 3. السعر -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['price']; ?> (₪)</label>
                        <input type="number" min="0" name="price" id="price" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>

                    <!-- 4. التكلفة -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['cost']; ?> (₪)</label>
                        <input type="number" min="0" name="cost" id="cost" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>

                    <!-- 5. المخزون -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['stock']; ?></label>
                        <input type="number" min="0" name="stock" id="stock" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold">
                    </div>

                    <!-- 6. الحد الأدنى -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['min_stock']; ?></label>
                        <input type="number" min="0" name="min_stock" id="min_stock" value="10" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition font-bold text-gray-500">
                    </div>

                    <!-- 7. تاريخ الانتهاء -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['expiry']; ?></label>
                        <input type="date" name="expiry" id="expiry" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white dark:[color-scheme:dark] transition" dir="ltr">
                    </div>

                    <!-- 8. ملاحظات -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['description']; ?></label>
                        <input type="text" name="description" id="description" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#0A7A48] focus:ring-2 focus:ring-[#0A7A48]/20 dark:text-white transition">
                    </div>

                </div>

                <!-- ==========================================
                     9. منطقة رفع الصورة (Uiverse Drag & Drop Style) 
                =========================================== -->
                <div class="mt-4">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5"><?php echo $lang['product_image']; ?></label>
                    <label for="image_upload" class="upload-area flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-2xl cursor-pointer bg-gray-50/50 dark:bg-slate-900/50 group">
                        <div class="flex flex-col items-center justify-center pt-3 pb-3">
                            <i data-lucide="cloud-upload" class="w-6 h-6 mb-2 text-gray-400 group-hover:text-[#0A7A48] dark:group-hover:text-[#4ADE80] transition-colors"></i>
                            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400 font-bold" id="file-name-display"><?php echo $lang['select_image']; ?></p>
                        </div>
                        <input id="image_upload" type="file" name="image" accept="image/*" class="hidden" onchange="updateFileName(this)" />
                    </label>
                </div>

                <!-- ==========================================
                     10. مفتاح دواء مراقب (Toggle Switch) 
                =========================================== -->
                <div class="flex items-center justify-between bg-amber-50 dark:bg-amber-900/20 p-3 rounded-2xl border border-amber-200 dark:border-amber-900/50 shadow-sm mt-4">

                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-amber-100 dark:bg-amber-800/50 rounded-lg shrink-0">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 select-none"><?php echo $lang['is_controlled']; ?></h4>
                            <p class="text-[11px] text-amber-600 dark:text-amber-500 mt-0.5 leading-tight"><?php echo $lang['controlled_description']; ?></p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="is_controlled" id="is_controlled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-slate-700 rounded-full peer-focus:ring-2 peer-focus:ring-[#0A7A48] peer-focus:ring-offset-2 peer-focus:ring-offset-gray-100 dark:peer-focus:ring-offset-slate-800 transition-colors duration-300 peer-checked:bg-[#0A7A48]"></div>
                        <div class="absolute left-[2px] top-[2px] bg-white w-5 h-5 rounded-full transition-transform duration-300 peer-checked:translate-x-full shadow-md"></div>
                    </label>

                </div>

            </div> <!-- نهاية منطقة التمرير -->

            <!-- ==========================================
                 11. ذيل النافذة (الأزرار السفلية الثابتة) 
            =========================================== -->
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
    lucide.createIcons();

    const modal = document.getElementById('medicineModal');

    // جلب الترجمات من PHP إلى JavaScript
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

    // 💡 دالة لتغيير النص عند اختيار صورة في المودال
    function updateFileName(input) {
        const displayElement = document.getElementById('file-name-display');
        if (input.files && input.files.length > 0) {
            // إظهار اسم الملف المرفوع ولونه أخضر
            displayElement.innerText = "<?php echo $lang['file_selected']; ?> " + input.files[0].name;
            displayElement.classList.remove('text-gray-500');
            displayElement.classList.add('text-[#0A7A48]', 'dark:text-[#4ADE80]');
        } else {
            // العودة للنص الافتراضي إذا تم الإلغاء
            displayElement.innerText = "<?php echo $lang['select_file']; ?>";
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