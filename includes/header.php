<?php require_once('lang.php'); ?>
<!DOCTYPE html>
<!-- إضافة كلاس dark ديناميكياً إذا كان محفوظاً في المتصفح -->
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaSmart</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            darkMode: 'class', // الاعتماد على class="dark" في <html>
        }
    </script>

    <!-- سكربت منع الوميض الأبيض (يعمل فورا) -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        /* إخفاء سكرول الشاشة الرئيسي، السكرول سيكون داخل الـ main فقط */
        body,
        html {
            height: 100%;
            overflow: hidden;
        }

        /* تنسيق السكرول بار ليكون أنيقاً ومتوافقاً مع الدارك مود */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background-color: #475569;
        }

        body {
            transition: background-color 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 dark:bg-slate-900 dark:text-gray-200">
    <!-- الحاوية الأساسية تملأ الشاشة تماماً -->
    <div class="flex h-screen w-full overflow-hidden">