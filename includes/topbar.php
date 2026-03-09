<?php

// جلب بيانات المستخدم الأساسية

$current_uid = $_SESSION['user_id'];

$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;



$user_query = mysqli_query($conn, "SELECT Fname, Lname FROM User WHERE UserID = $current_uid");

$user_data = mysqli_fetch_assoc($user_query);

$fullname = $user_data['Fname'] . ' ' . $user_data['Lname'];



// إعدادات افتراضية (للأدمن)

$display_name = $fullname;

$role_text = isset($lang['admin']) ? $lang['admin'] : 'Admin';

$avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($fullname) . "&background=0ea5e9&color=fff&bold=true";



// إذا كان المستخدم صيدلي، نجلب شعار واسم صيدليته

if ($role_id == 2) {

    $pharma_query = mysqli_query($conn, "SELECT PharmacyName, Logo FROM Pharmacist WHERE PharmacistID = $current_uid");

    if ($pharma_query && mysqli_num_rows($pharma_query) > 0) {

        $pharma_data = mysqli_fetch_assoc($pharma_query);

        $display_name = $pharma_data['PharmacyName']; // نعرض اسم الصيدلية كعنوان رئيسي

        $role_text = $fullname; // ونعرض اسم الصيدلاني تحته



        // إذا كان رافع لوجو حقيقي، نعرضه

        if (!empty($pharma_data['Logo']) && $pharma_data['Logo'] != 'default.png') {

            $avatar_url = "../uploads/" . $pharma_data['Logo'];
        } else {

            // لو مش رافع لوجو، نعمله لوجو بالحروف الأولى من اسم الصيدلية بلون أخضر

            $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($pharma_data['PharmacyName']) . "&background=6E8649&color=fff&bold=true";
        }
    }
}

?>



<!-- ==========================================

     شريط المعلومات العلوي (TopBar)

     💡 ألوان ديناميكية حسب الدور:

     - الأدمن: أزرق (#048AC1)

     - الصيدلاني: أخضر (#6E8649)

=========================================== -->

<div class="w-full flex justify-between items-center mb-8 bg-white dark:bg-slate-800 p-4 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 transition-colors duration-300">



    <!-- 1. الجهة اليمنى: معلومات المستخدم / الصيدلية -->

    <div class="flex items-center gap-3">

        <!-- عرض الشعار (Logo) بشكل دائري ومقصوص باحترافية -->

        <img src="<?php echo $avatar_url; ?>"

            alt="Profile"

            class="w-11 h-11 rounded-full shadow-sm border-2 border-gray-100 dark:border-slate-600 object-cover bg-white">



        <div class="text-start rtl:text-right">

            <h3 class="text-sm font-black text-gray-800 dark:text-white leading-tight"><?php echo htmlspecialchars($display_name); ?></h3>

            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-bold tracking-wide mt-0.5"><?php echo htmlspecialchars($role_text); ?></p>

        </div>

    </div>



    <!-- 2. الجهة اليسرى: أدوات التحكم (اللغة والوضع الليلي) -->

    <div class="flex items-center gap-5">



        <!-- زر الوضع الليلي -->

        <!-- 💡 ألوان ديناميكية حسب الدور -->

        <button id="theme-toggle" type="button" class="bg-white dark:bg-slate-800 p-2.5 rounded-xl shadow-md border border-gray-100 dark:border-slate-700 hover:scale-105 transition-transform flex items-center justify-center <?php echo ($role_id == 1) ? 'hover:border-[#048AC1] dark:hover:border-[#048AC1]' : 'hover:border-[#6E8649] dark:hover:border-[#6E8649]'; ?>">

            <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>

            <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5 text-slate-400"></i>

        </button>



        <div class="w-px h-6 bg-gray-200 dark:bg-slate-700"></div> <!-- خط فاصل أنيق -->



        <!-- زر اللغة -->

        <!-- 💡 ألوان ديناميكية حسب الدور -->

        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>"

            class="bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 font-bold px-4 py-2.5 rounded-xl shadow-md border border-gray-100 dark:border-slate-700 hover:scale-105 transition-transform flex items-center gap-2 text-sm <?php echo ($role_id == 1) ? 'hover:text-[#048AC1] hover:border-[#048AC1] dark:hover:text-[#048AC1] dark:hover:border-[#048AC1]' : 'hover:text-[#6E8649] hover:border-[#6E8649] dark:hover:text-[#6E8649] dark:hover:border-[#6E8649]'; ?>">

            <i data-lucide="globe" class="w-4 h-4"></i>

            <?php echo $lang['switch_lang_text']; ?>

        </a>



    </div>

</div>



<!-- سكربت التحكم بالوضع الليلي -->

<script>
    var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');



    if (localStorage.getItem('color-theme') === 'light' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {

        themeToggleDarkIcon.classList.remove('hidden');

    } else {

        themeToggleLightIcon.classList.remove('hidden');

    }



    // دالة تحديث أيقونات الوضع الليلي/النهاري

    function updateThemeIcons() {

        var isDark = document.documentElement.classList.contains('dark');

        var sunIcon = document.getElementById('theme-toggle-light-icon');

        var moonIcon = document.getElementById('theme-toggle-dark-icon');



        if (isDark) {

            sunIcon.classList.remove('hidden'); // إظهار الشمس في الوضع الليلي

            moonIcon.classList.add('hidden');

        } else {

            sunIcon.classList.add('hidden');

            moonIcon.classList.remove('hidden'); // إظهار القمر في الوضع النهاري

        }

    }



    // تشغيل عند التحميل

    updateThemeIcons();



    // التبديل عند الضغط

    var themeToggleBtn = document.getElementById('theme-toggle');

    themeToggleBtn.addEventListener('click', function() {

        document.documentElement.classList.toggle('dark');



        if (document.documentElement.classList.contains('dark')) {

            localStorage.setItem('color-theme', 'dark');

            document.cookie = "theme=dark; path=/";

        } else {

            localStorage.setItem('color-theme', 'light');

            document.cookie = "theme=light; path=/";

        }

        updateThemeIcons(); // تحديث الأيقونات فوراً بعد التغيير

    });



    // تفعيل الأيقونات

    if (typeof lucide !== 'undefined') {

        lucide.createIcons();

    }
</script>