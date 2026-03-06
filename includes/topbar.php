<?php
// جلب بيانات المستخدم لعرض الاسم والصورة
$current_uid = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT Fname, Lname FROM User WHERE UserID = $current_uid");
$user_data = mysqli_fetch_assoc($user_query);
$fullname = $user_data['Fname'] . ' ' . $user_data['Lname'];
?>

<div class="w-full flex justify-between items-center mb-8 bg-white dark:bg-slate-800 p-4 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 transition-colors duration-300">

    <!-- 1. الجهة اليمنى: معلومات المستخدم (ثابتة) -->
    <div class="flex items-center gap-3">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=0ea5e9&color=fff&bold=true"
            class="w-10 h-10 rounded-full shadow-sm border-2 border-white dark:border-slate-600">
        <div class="text-start rtl:text-right">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white leading-tight"><?php echo htmlspecialchars($fullname); ?></h3>
            <p class="text-[10px] text-gray-400 font-medium tracking-wide">Admin</p>
        </div>
    </div>

    <!-- 2. الجهة اليسرى: أدوات التحكم (اللغة والوضع الليلي) -->
    <div class="flex items-center gap-6">

        <!-- زر الوضع الليلي -->
        <button id="theme-toggle" type="button" class="text-gray-400 hover:text-yellow-500 dark:text-yellow-400 transition-transform hover:rotate-12">
            <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
            <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5"></i>
        </button>

        <!-- زر اللغة -->
        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>"
            class="text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition no-underline">
            <?php echo $lang['switch_lang_text']; ?>
        </a>

    </div>
</div>

<!-- سكربت التحكم بالوضع الليلي -->
<script>
    var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // ضبط الأيقونة عند تحميل الصفحة
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    // التبديل عند الضغط
    var themeToggleBtn = document.getElementById('theme-toggle');
    themeToggleBtn.addEventListener('click', function() {
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

        // إعادة تحميل الخريطة في صفحة الداشبورد إذا كانت موجودة
        if (typeof map !== 'undefined') setTimeout(() => {
            location.reload();
        }, 100);
    });

    // تفعيل الأيقونات
    lucide.createIcons();
</script>