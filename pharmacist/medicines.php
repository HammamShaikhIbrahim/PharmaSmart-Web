<?php
include('../config/database.php');
session_start();

// 1. التحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pId = $_SESSION['user_id'];
$status = ""; // لتعامل مع رسائل SweetAlert

// 2. معالجة الحذف
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM Medicine WHERE MedicineID = $id AND PharmacistID = $pId");
    $status = "deleted";
}

// 3. معالجة إضافة دواء جديد
if (isset($_POST['add_medicine'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $expiryDate = $_POST['expiryDate'];
    $isControlled = isset($_POST['isControlled']) ? 1 : 0;

    $image = time() . "_" . $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);

    $sql = "INSERT INTO Medicine (Name, Description, Price, Stock, ExpiryDate, IsControlled, Image, PharmacistID) 
            VALUES ('$name', '$description', '$price', '$stock', '$expiryDate', '$isControlled', '$image', $pId)";

    if (mysqli_query($conn, $sql)) {
        $status = "added";
    }
}

// 4. معالجة تعديل دواء (Update)
if (isset($_POST['edit_medicine'])) {
    $id = intval($_POST['medicine_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $expiryDate = $_POST['expiryDate'];
    $isControlled = isset($_POST['isControlled']) ? 1 : 0;

    $updateImg = "";
    if (!empty($_FILES['image']['name'])) {
        $image = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
        $updateImg = ", Image='$image'";
    }

    $sql = "UPDATE Medicine SET Name='$name', Description='$description', Price='$price', Stock='$stock', 
            ExpiryDate='$expiryDate', IsControlled='$isControlled' $updateImg 
            WHERE MedicineID=$id AND PharmacistID=$pId";

    if (mysqli_query($conn, $sql)) {
        $status = "updated";
    }
}

// 5. جلب الأدوية
$result = mysqli_query($conn, "SELECT * FROM Medicine WHERE PharmacistID = $pId ORDER BY MedicineID DESC");

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<main class="flex-1 p-8 bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
            <i data-lucide="pill" class="text-emerald-600"></i> إدارة المخزون
        </h1>
        <button onclick="openModal('add')"
            class="bg-emerald-600 text-white px-6 py-2 rounded-xl flex items-center gap-2 hover:bg-emerald-700 shadow-lg transition-all transform hover:scale-105">
            <i data-lucide="plus-circle"></i> إضافة دواء جديد
        </button>
    </div>

    <!-- جدول الأدوية -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead class="bg-gray-50 border-b">
                <tr class="text-gray-600 text-sm">
                    <th class="p-6">المنتج</th>
                    <th class="p-6">التفاصيل</th>
                    <th class="p-6">السعر</th>
                    <th class="p-6">الكمية</th>
                    <th class="p-6">تاريخ الانتهاء</th>
                    <th class="p-6">الحالة</th>
                    <th class="p-6 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 flex items-center gap-3">
                            <img src="../uploads/<?php echo $row['Image']; ?>" class="w-12 h-12 rounded-lg object-cover border">
                            <span class="font-bold text-gray-800"><?php echo htmlspecialchars($row['Name']); ?></span>
                        </td>
                        <td class="p-4 text-xs text-gray-400 max-w-[150px] truncate"><?php echo htmlspecialchars($row['Description']); ?></td>
                        <td class="p-4 font-bold text-emerald-600"><?php echo $row['Price']; ?> $</td>
                        <td class="p-4"><?php echo $row['Stock']; ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo $row['ExpiryDate']; ?></td>
                        <td class="p-4">
                            <?php echo $row['IsControlled'] ? '<span class="bg-red-100 text-red-600 px-2 py-1 rounded-md text-[10px] font-bold">مراقب Rx</span>' : '<span class="text-gray-400 text-xs">عادي</span>'; ?>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition"><i data-lucide="edit-3"></i></button>
                                <button onclick="confirmDelete(<?php echo $row['MedicineID']; ?>)" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition"><i data-lucide="trash-2"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal (إضافة وتعديل) -->
<div id="medicineModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-lg">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2"></h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="medicine_id" id="med_id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-400 mr-2">اسم الدواء</label>
                    <input type="text" name="name" id="med_name" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mr-2">تاريخ انتهاء الصلاحية</label>
                    <input type="date" name="expiryDate" id="med_expiry" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <textarea name="description" id="med_desc" placeholder="وصف الدواء..." rows="2" class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500"></textarea>

            <div class="grid grid-cols-2 gap-4">
                <input type="number" step="0.01" name="price" id="med_price" placeholder="السعر" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
                <input type="number" name="stock" id="med_stock" placeholder="الكمية" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center gap-2 bg-gray-50 p-3 rounded-xl border border-dashed">
                <input type="checkbox" name="isControlled" id="med_controlled" class="w-4 h-4 text-emerald-600 rounded">
                <label for="med_controlled" class="text-sm text-gray-600">هذا الدواء يتطلب وصفة طبية</label>
            </div>

            <div>
                <label class="text-xs text-gray-400 mr-2">صورة الدواء (اختياري عند التعديل)</label>
                <input type="file" name="image" class="w-full text-xs p-2 border rounded-xl">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" id="submitBtn" class="flex-1 bg-emerald-600 text-white p-3 rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition"></button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-500 p-3 rounded-xl hover:bg-gray-200 transition">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
    // التحكم في الـ Modal
    function openModal(type) {
        const modal = document.getElementById('medicineModal');
        const title = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');

        modal.classList.remove('hidden');
        if (type === 'add') {
            title.innerHTML = '<i data-lucide="plus-circle" class="text-emerald-600"></i> إضافة دواء';
            submitBtn.name = 'add_medicine';
            submitBtn.innerText = 'إضافة للمخزون';
            document.querySelector('form').reset();
        }
        lucide.createIcons();
    }

    function openEditModal(data) {
        openModal('edit');
        document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit-3" class="text-blue-600"></i> تعديل بيانات الدواء';
        document.getElementById('submitBtn').name = 'edit_medicine';
        document.getElementById('submitBtn').innerText = 'تحديث البيانات';
        document.getElementById('submitBtn').classList.replace('bg-emerald-600', 'bg-blue-600');

        // تعبئة الحقول
        document.getElementById('med_id').value = data.MedicineID;
        document.getElementById('med_name').value = data.Name;
        document.getElementById('med_desc').value = data.Description;
        document.getElementById('med_price').value = data.Price;
        document.getElementById('med_stock').value = data.Stock;
        document.getElementById('med_expiry').value = data.ExpiryDate;
        document.getElementById('med_controlled').checked = (data.IsControlled == 1);
    }

    function closeModal() {
        document.getElementById('medicineModal').classList.add('hidden');
    }

    // تنبيهات الحذف والسويت اليرت
    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف هذا الدواء نهائياً من مخزنك!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'نعم، احذف الدواء',
            cancelButtonText: 'تراجع'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'medicines.php?delete_id=' + id;
            }
        });
    }

    // عرض رسائل النجاح
    <?php if ($status == "added") echo "Swal.fire('تمت الإضافة!', 'تم إدراج الدواء في المخزون بنجاح.', 'success');"; ?>
    <?php if ($status == "updated") echo "Swal.fire('تم التحديث!', 'تم تعديل بيانات الدواء بنجاح.', 'success');"; ?>
    <?php if ($status == "deleted") echo "Swal.fire('تم الحذف!', 'تمت إزالة الدواء من القائمة.', 'success');"; ?>

    lucide.createIcons();
</script>

<?php include('../includes/footer.php'); ?>