<?php
// استدعاء الاتصال وبدء الجلسة
include('../config/database.php');
session_start();

$error = "";

// عند الضغط على زر الدخول
if (isset($_POST['login'])) {
    // تنظيف البيانات المدخلة
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // استعلام ذكي: يجلب بيانات المستخدم + حالة الموافقة للصيدلي (إن وجد)
    $query = "SELECT u.UserID, u.RoleID, u.Password, p.IsApproved 
              FROM User u 
              LEFT JOIN Pharmacist p ON u.UserID = p.PharmacistID 
              WHERE u.Email = '$email'";

    $result = mysqli_query($conn, $query);

    // هل وجدنا مستخدماً بهذا الإيميل؟
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // التحقق من كلمة المرور
        if ($password === $user['Password']) {

            // 1. حماية: منع المريض (Role 3) من الدخول
            if ($user['RoleID'] == 3) {
                $error = "عذراً، نظام الويب مخصص للصيادلة والإدارة فقط. يرجى استخدام التطبيق.";
            }
            // 2. حماية: منع الصيدلي المعلق (Approved 0)
            elseif ($user['RoleID'] == 2 && $user['IsApproved'] == 0) {
                $error = "حسابك قيد المراجعة، يرجى انتظار موافقة الإدارة.";
            } else {
                // دخول ناجح: حفظ البيانات في الجلسة
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['role_id'] = $user['RoleID'];

                // التوجيه حسب الصلاحية
                if ($user['RoleID'] == 1) header("Location: ../admin/dashboard.php");
                else header("Location: ../pharmacist/dashboard.php");
                exit();
            }
        } else {
            $error = "كلمة المرور غير صحيحة.";
        }
    } else {
        $error = "البريد الإلكتروني غير مسجل.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>تسجيل الدخول - PharmaSmart</title>
    <style>
        /* تثبيت الصفحة بالكامل لمنع حركة الفورم */
        body,
        html {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center font-sans">

    <!-- كارت تسجيل الدخول -->
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
        <h2 class="text-3xl font-bold mb-2 text-center text-blue-600">PharmaSmart</h2>
        <p class="text-gray-500 text-center mb-8">تسجيل الدخول للنظام</p>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور</label>
                <input type="password" name="password" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" name="login" class="w-full bg-blue-600 text-white py-3 rounded-xl hover:bg-blue-700 transition font-bold mt-4">
                تسجيل الدخول
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            صيدلي جديد؟ <a href="register.php" class="text-blue-600 font-bold hover:underline">سجل طلب انضمام</a>
        </div>
    </div>

    <!-- كود إظهار رسالة الخطأ (إن وجدت) -->
    <?php if ($error != ""): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false
            });
        </script>
    <?php endif; ?>

</body>

</html>