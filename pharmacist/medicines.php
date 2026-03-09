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

<main class="flex-1 p-8 bg-slate-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <?php include('../includes/topbar.php'); ?>

    <!-- ==========================================
	شريط البحث وزر الإضافة
	💡 تم توحيد هندسة شريط البحث ليتطابق مع الأدمن تماماً
=========================================== -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">

        <!-- عنوان الصفحة (للتناسق مع صفحات الأدمن) -->
        <h1 class="text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3 w-full md:w-auto">
            <i data-lucide="pill" class="text-teal-500"></i> <?php echo $lang['product_inventory']; ?>
        </h1>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
            <!-- فورم البحث المطابق للأدمن (group-focus-within) -->
            <form method="GET" class="w-full md:w-80">
                <div class="relative group">
                    <input type="text" name="search" placeholder="<?php echo $lang['search_product']; ?>" value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full p-3 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-all text-sm">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-teal-500 transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?> w-5 h-5"></i>
                </div>
            </form>

            <!-- 💡 زر إضافة مستوحى من واجهات Uiverse (Modern Gradient Button) -->
            <button onclick="openModal()" class="w-full md:w-auto relative group overflow-hidden rounded-2xl bg-teal-600 text-white px-6 py-3 font-bold shadow-lg shadow-teal-600/30 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                <span class="absolute right-0 w-8 h-32 -mt-12 transition-all duration-1000 transform translate-x-12 bg-white opacity-10 rotate-12 group-hover:-translate-x-40 ease"></span>
                <i data-lucide="plus-circle" class="w-5 h-5 relative z-10"></i>
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
            <i data-lucide="clipboard-list" class="text-[#6E8649] w-6 h-6"></i>
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
                            // حساب نسبة الربح
                            $margin = 0;
                            if ($med['Price'] > 0 && $med['CostPrice'] > 0) {
                                $margin = round((($med['Price'] - $med['CostPrice']) / $med['Price']) * 100);
                            }
                            // فحص الصلاحية (هل انتهى التاريخ؟)
                            $is_expired = strtotime($med['ExpiryDate']) < time();
                        ?>
                            <!-- 💡 هوفر أخضر خفيف -->
                            <tr class="hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition duration-150">
                                <!-- صورة واسم الدواء -->
                                <td class="p-4 flex items-center gap-3">
                                    <?php $imgSrc = !empty($med['Image']) ? '../uploads/' . $med['Image'] : 'https://ui-avatars.com/api/?name=' . urlencode($med['Name']) . '&background=f8fafc&color=94a3b8'; ?>
                                    <img src="<?php echo $imgSrc; ?>" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                                    <div>
                                        <span class="font-bold text-gray-800 dark:text-white block"><?php echo htmlspecialchars($med['Name']); ?></span>
                                        <?php if ($med['IsControlled']): ?>
                                            <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-bold">Rx</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- التصنيف (مع دعم الترجمة لكلمة غير مصنف) -->
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                                    <span class="bg-gray-100 dark:bg-slate-700 px-2.5 py-1 rounded-md text-xs">
                                        <?php echo $med['CategoryName'] ? htmlspecialchars($med['CategoryName']) : (isset($lang['uncategorized']) ? $lang['uncategorized'] : 'غير مصنف'); ?>
                                    </span>
                                </td>
                                <!-- المخزون -->
                                <td class="p-4 text-center">
                                    <?php if ($med['Stock'] <= $med['MinimumStock']): ?>
                                        <!-- مخزون ناقص: أحمر -->
                                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm"><?php echo $med['Stock']; ?></span>
                                    <?php else: ?>
                                        <!-- مخزون ممتاز: فيروزي -->
                                        <span class="bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400 px-3 py-1.5 rounded-lg font-bold text-xs inline-block min-w-[40px] shadow-sm"><?php echo $med['Stock']; ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- السعر والتكلفة (بإضافة علامة الشيكل ₪) -->
                                <td class="p-4 font-bold text-gray-800 dark:text-white">
                                    <span dir="ltr"><?php echo $med['Price']; ?> ₪</span>
                                </td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium">
                                    <span dir="ltr"><?php echo $med['CostPrice']; ?> ₪</span>
                                </td>

                                <td class="p-4 text-gray-500 dark:text-gray-400 font-medium" dir="ltr"><?php echo $margin; ?>%</td>

                                <!-- تاريخ الانتهاء -->
                                <td class="p-4 font-medium" dir="ltr">
                                    <?php if ($is_expired): ?>
                                        <span class="bg-rose-500 text-white px-2 py-1 rounded text-xs font-bold"><?php echo $med['ExpiryDate']; ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-600 dark:text-gray-300"><?php echo $med['ExpiryDate']; ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- أزرار التحكم (أيقونات جديدة: قلم وسلة مهملات واضحة) -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- 💡 أيقونة التعديل (Pen-Square) بلون أزرق -->
                                        <button onclick='editModal(<?php echo json_encode($med); ?>)' class="text-[#048AC1] bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-800/50 transition p-2 rounded-lg" title="<?php echo $lang['edit_product']; ?>">
                                            <i data-lucide="pen-square" class="w-4 h-4"></i>
                                        </button>
                                        <!-- 💡 أيقونة الحذف (Trash) بلون أحمر -->
                                        <button onclick="confirmDelete(<?php echo $med['MedicineID']; ?>)" class="text-rose-500 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 transition p-2 rounded-lg" title="<?php echo isset($lang['delete']) ? $lang['delete'] : 'حذف'; ?>">
                                            <i data-lucide="trash" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">لا يوجد أدوية في المخزون</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ==========================================
     النافذة المنبثقة (Modal) لإضافة/تعديل دواء
     💡 ألوان أخضر موحدة
=========================================== -->
<div id="medicineModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center transition-opacity">
    <div class="bg-white dark:bg-slate-800 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden transform transition-all p-8 relative mx-4">

        <!-- 💡 رأس النافذة مع أيقونة أخضر -->
        <!-- 💡 رأس النافذة -->
        <h2 id="modalTitle" class="text-2xl font-black text-gray-800 dark:text-white mb-6 flex items-center gap-2">
            <i data-lucide="package-plus" class="text-teal-500"></i>
            <span id="modalTitleText"><?php echo $lang['add_new_product']; ?></span>
        </h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="medicine_id" id="medicine_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <!-- اسم الدواء -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['product_name']; ?></label>
                    <input type="text" name="name" id="name" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition">
                </div>

                <!-- التصنيف (تم تحسين شكل الـ Dropdown) -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['category']; ?></label>
                    <!-- استخدام relative لتموضع السهم، و appearance-none لإخفاء سهم المتصفح الافتراضي -->
                    <div class="relative">
                        <select name="category_id" id="category_id" required class="w-full appearance-none bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition cursor-pointer pr-10 rtl:pr-4 rtl:pl-10">
                            <option value=""><?php echo isset($lang['select_category']) ? $lang['select_category'] : 'اختر تصنيفاً...'; ?></option>
                            <?php
                            mysqli_data_seek($categories, 0);
                            while ($cat = mysqli_fetch_assoc($categories)) {
                                echo "<option value='{$cat['CategoryID']}'>{$cat['Name']}</option>";
                            } ?>
                        </select>
                        <!-- 💡 أيقونة السهم المخصصة بلون أخضر -->
                        <div class="pointer-events-none absolute inset-y-0 <?php echo ($dir == 'rtl') ? 'left-0 pl-3' : 'right-0 pr-3'; ?> flex items-center text-[#6E8649]">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- السعر -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['price']; ?> (₪)</label>
                    <input type="number" min="0" step="0.01" name="price" id="price" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition">
                </div>
                <!-- التكلفة -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['cost']; ?> (₪)</label>
                    <input type="number" min="0" step="0.01" name="cost" id="cost" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition">
                </div>

                <!-- المخزون -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['stock']; ?></label>
                    <input type="number" min="0" step="1" name="stock" id="stock" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition">
                </div>
                <!-- الحد الأدنى للمخزون -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['min_stock']; ?></label>
                    <input type="number" min="0" step="1" name="min_stock" id="min_stock" value="10" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition">
                </div>

                <!-- تاريخ الانتهاء -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['expiry']; ?></label>
                    <input type="date" name="expiry" id="expiry" required class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white dark:[color-scheme:dark] transition" dir="ltr">
                </div>

                <!-- صورة المنتج -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['product_image']; ?></label>
                    <input type="file" name="image" accept="image/*" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-1.5 text-sm outline-none focus:border-[#6E8649] dark:text-gray-400 transition">
                </div>
            </div>

            <!-- ملاحظات -->
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo $lang['description']; ?></label>
                <textarea name="description" id="description" rows="2" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#6E8649] focus:ring-2 focus:ring-[#6E8649]/20 dark:text-white transition"></textarea>
            </div>

            <!-- دواء مراقب Checkbox -->
            <!-- 💡 خلفية صفراء/برتقالية للتحذير من الأدوية المراقبة -->
            <div class="mb-6 flex items-center gap-2 bg-amber-50 dark:bg-amber-900/20 p-3 rounded-xl border border-amber-100 dark:border-amber-900/50">
                <input type="checkbox" name="is_controlled" id="is_controlled" class="w-4 h-4 text-[#6E8649] focus:ring-[#6E8649] rounded">
                <label for="is_controlled" class="text-sm font-bold text-amber-700 dark:text-amber-500 cursor-pointer select-none">
                    <?php echo $lang['is_controlled']; ?>
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3 mt-8 border-t border-gray-100 dark:border-slate-700 pt-6">
                <button type="button" onclick="closeModal()" class="px-6 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-slate-700 transition text-sm">
                    <?php echo $lang['cancel']; ?>
                </button>
                <!-- 💡 زر التأكيد بألوان أخضر (#113f2b) مع نص أصفر (#d2f34c) -->
                <!-- 💡 زر التأكيد فيروزي عصري -->
                <button type="submit" name="save_medicine" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-teal-600/30 text-sm duration-300">
                    <?php echo $lang['confirm']; ?>
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