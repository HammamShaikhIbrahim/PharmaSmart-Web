<?php
include('../config/database.php');
session_start();
// استدعاء ملف القاموس الموحد
require_once('../includes/lang.php');

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT u.UserID, u.RoleID, u.Password, p.IsApproved 
              FROM User u 
              LEFT JOIN Pharmacist p ON u.UserID = p.PharmacistID 
              WHERE u.Email = '$email'";
    
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if ($password === $user['Password']) {
            if ($user['RoleID'] == 3) { $error = $lang['err_patient']; } 
            elseif ($user['RoleID'] == 2 && $user['IsApproved'] == 0) { $error = $lang['err_pending']; } 
            else {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['role_id'] = $user['RoleID'];
                
                if ($user['RoleID'] == 1) header("Location: ../admin/dashboard.php");
                else header("Location: ../pharmacist/dashboard.php");
                exit();
            }
        } else { $error = $lang['err_pass']; }
    } else { $error = $lang['err_email']; }
}
?>

<!DOCTYPE html>
<!-- قراءة اتجاه الصفحة وكلاس الدارك مود من الـ Cookies/Session -->
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaSmart - <?php echo $lang['login_title']; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>tailwind.config = { darkMode: 'class', }</script>

    <!-- تحديد الوضع النهاري كوضع افتراضي إذا لم يتم اختيار أي شيء -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        }
    </script>

    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { transition: background-color 0.3s ease, color 0.3s ease; }
    </style>
</head>

<body class="bg-gray-50 dark:bg-slate-950 flex items-center justify-center relative">

    <!-- الدوائر الخلفية المضببة -->
    <div class="absolute top-[-20%] rtl:right-[-10%] ltr:left-[-10%] w-96 h-96 bg-blue-400/20 dark:bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-20%] rtl:left-[-10%] ltr:right-[-10%] w-96 h-96 bg-emerald-400/20 dark:bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- أزرار التحكم باللغة والوضع الليلي -->
    <div class="absolute top-6 rtl:left-6 ltr:right-6 flex items-center gap-3 z-50">
        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>" 
           class="bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 font-bold px-4 py-2 rounded-xl shadow-md border border-gray-100 dark:border-slate-700 hover:text-blue-600 transition flex items-center gap-2">
            <i data-lucide="globe" class="w-4 h-4"></i> <?php echo $lang['switch_lang_text']; ?>
        </a>
        
        <button id="theme-toggle" type="button" class="bg-white dark:bg-slate-800 text-gray-500 dark:text-yellow-400 p-2.5 rounded-xl shadow-md border border-gray-100 dark:border-slate-700 hover:rotate-12 transition-transform">
            <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
            <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5"></i>
        </button>
    </div>

    <!-- حاوية تسجيل الدخول -->
    <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-slate-800 w-full max-w-md z-10 transition-colors duration-300">
        
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mb-4 text-blue-600 dark:text-blue-400">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-800 dark:text-white tracking-tight mb-2">PharmaSmart</h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?php echo $lang['login_subtitle']; ?></p>
        </div>

        <form method="POST" class="space-y-5 text-<?php echo ($dir == 'rtl') ? 'right' : 'left'; ?>">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2"><?php echo $lang['email']; ?></label>
                <div class="relative">
                    <input type="email" name="email" required placeholder="name@pharmacy.com" dir="ltr"
                           class="w-full rtl:pl-4 rtl:pr-12 ltr:pr-4 ltr:pl-12 py-3.5 bg-gray-50 dark:bg-slate-800 border border-transparent focus:bg-white dark:focus:bg-slate-900 focus:border-blue-500 rounded-2xl text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none transition-all">
                    <i data-lucide="mail" class="absolute rtl:right-4 ltr:left-4 top-3.5 text-gray-400 w-5 h-5 pointer-events-none"></i>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2"><?php echo $lang['password']; ?></label>
                <div class="relative">
                    <input type="password" name="password" required placeholder="••••••••" dir="ltr"
                           class="w-full rtl:pl-4 rtl:pr-12 ltr:pr-4 ltr:pl-12 py-3.5 bg-gray-50 dark:bg-slate-800 border border-transparent focus:bg-white dark:focus:bg-slate-900 focus:border-blue-500 rounded-2xl text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none transition-all">
                    <i data-lucide="lock" class="absolute rtl:right-4 ltr:left-4 top-3.5 text-gray-400 w-5 h-5 pointer-events-none"></i>
                </div>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 text-white py-4 rounded-2xl hover:bg-blue-700 transition-all font-bold text-base mt-6 shadow-lg shadow-blue-600/20 active:scale-[0.98] flex justify-center items-center gap-2">
                <?php echo $lang['btn_login']; ?> 
                <i data-lucide="<?php echo ($dir == 'rtl') ? 'arrow-left' : 'arrow-right'; ?>" class="w-5 h-5"></i>
            </button>
            
        </form>
        
        <div class="mt-8 text-center pt-6 border-t border-gray-100 dark:border-slate-800">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                <?php echo $lang['new_account']; ?> 
                <a href="register.php" class="text-blue-600 dark:text-blue-400 font-bold hover:underline mx-1"><?php echo $lang['register_link']; ?></a>
            </p>
        </div>
    </div>

    <!-- رسائل الأخطاء -->
    <?php if ($error != ""): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'error',
                title: '<?php echo $lang['err_title']; ?>',
                text: '<?php echo $error; ?>',
                confirmButtonText: '<?php echo $lang['ok_btn']; ?>',
                confirmButtonColor: '#ef4444',
                allowOutsideClick: false,
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
        });
    </script>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (localStorage.getItem('color-theme') === 'dark') {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        document.getElementById('theme-toggle').addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (localStorage.getItem('color-theme') === 'dark') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
                document.cookie = "theme=light; path=/";
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
                document.cookie = "theme=dark; path=/";
            }
        });
    </script>

</body>
</html>