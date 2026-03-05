<!-- الشريط العلوي -->
<div class="w-full flex justify-end gap-4 mb-6">
    <a href="?lang=<?php echo $lang['switch_lang_code']; ?>"
        class="flex items-center gap-2 bg-white dark:bg-slate-800 px-4 py-2 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 hover:bg-gray-50 transition font-bold text-sm">
        <i data-lucide="globe" class="w-4 h-4"></i>
        <?php echo $lang['switch_lang_text']; ?>
    </a>

    <button id="theme-toggle" type="button" class="bg-white dark:bg-slate-800 text-gray-500 dark:text-yellow-400 p-2.5 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 hover:bg-gray-50 transition">
        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5"></i>
    </button>
</div>

<script>
    var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // تحديد الأيقونة الصحيحة
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    var themeToggleBtn = document.getElementById('theme-toggle');

    themeToggleBtn.addEventListener('click', function() {
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        if (localStorage.getItem('color-theme') === 'dark') {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
            document.cookie = "theme=light; path=/"; // حفظ الكوكي
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
            document.cookie = "theme=dark; path=/"; // حفظ الكوكي
        }

        // إعادة تحميل الخريطة إن وجدت لأن ألوان الخريطة يجب أن تتحدث (تخص صفحة الداشبورد)
        if (typeof map !== 'undefined') {
            setTimeout(() => {
                location.reload();
            }, 100);
        }
    });
</script>