<?php
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

$message = "";
$error = "";

if (isset($_POST['register'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $lname = mysqli_real_escape_string($conn, $_POST['lname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $pName = mysqli_real_escape_string($conn, $_POST['pName']);
    $license = mysqli_real_escape_string($conn, $_POST['license']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $workingHours = mysqli_real_escape_string($conn, $_POST['workingHours']);

    $lat = (float)$_POST['lat'];
    $lng = (float)$_POST['lng'];

    $logo = "default.png";
    if (!empty($_FILES['logo']['name'])) {
        $logo = time() . "_" . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/" . $logo);
    }

    $checkEmail = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");

    if (mysqli_num_rows($checkEmail) > 0) {
        $error = $lang['email_exists_error'];
    } elseif (empty($lat) || empty($lng)) {
        $error = $lang['location_error'];
    } else {
        $sqlUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                    VALUES ('$fname', '$lname', '$email', '$password', '$phone', 2)";

        if (mysqli_query($conn, $sqlUser)) {
            $userId = mysqli_insert_id($conn);

            $sqlPhar = "INSERT INTO Pharmacist (PharmacistID, PharmacyName, LicenseNumber, Location, Latitude, Longitude, WorkingHours, Logo, IsApproved)
                        VALUES ($userId, '$pName', '$license', '$location', $lat, $lng, '$workingHours', '$logo', 0)";

            if (mysqli_query($conn, $sqlPhar)) {
                $message = $lang['registration_success'];
            } else {
                $error = $lang['registration_error'] . " " . mysqli_error($conn);
            }
        } else {
            $error = $lang['user_creation_error'] . " " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaSmart - <?php echo $lang['register_title']; ?></title>
    
    <script src="https://kit.fontawesome.com/804071b851.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(16, 185, 129, 0.3); border-radius: 10px; }
    </style>
</head>

<body class="bg-white dark:bg-slate-950 text-gray-800 dark:text-gray-200 transition-colors duration-500 overflow-hidden h-screen flex">

    <!-- ========================================== -->
    <!-- 1. القسم التعريفي (1/3 - ثابت) -->
    <!-- ========================================== -->
    <div class="hidden lg:flex lg:w-1/3 bg-gradient-to-br from-emerald-600 to-teal-800 flex-col justify-between p-12 relative overflow-hidden h-full shadow-2xl z-10">
        
        <!-- الأشكال الطبية ثلاثية الأبعاد -->
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-10 -left-10 w-32 h-64 rounded-full transform rotate-[35deg] bg-gradient-to-b from-emerald-400 to-teal-500 shadow-[inset_15px_15px_30px_rgba(255,255,255,0.4),inset_-10px_-10px_30px_rgba(0,0,0,0.2),10px_20px_40px_rgba(0,0,0,0.3)] opacity-80"></div>
            <div class="absolute bottom-20 -right-10 w-48 h-48 rounded-full transform -rotate-[15deg] bg-gradient-to-tr from-green-300 to-emerald-500 shadow-[inset_-10px_-10px_30px_rgba(0,0,0,0.15),inset_15px_15px_30px_rgba(255,255,255,0.5),0_20px_40px_rgba(0,0,0,0.3)] opacity-90">
                <div class="absolute top-1/2 left-4 right-4 h-1 bg-white/30 rounded-full transform -translate-y-1/2"></div>
            </div>
            <div class="absolute top-1/2 left-1/2 w-24 h-24 transform -translate-x-1/2 -translate-y-1/2 rotate-[15deg] opacity-20">
                <div class="absolute inset-x-8 inset-y-0 rounded-xl bg-white"></div>
                <div class="absolute inset-y-8 inset-x-0 rounded-xl bg-white"></div>
            </div>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl mb-8 text-white shadow-inner border border-white/30">
                <i data-lucide="store" class="w-8 h-8"></i>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight mb-6 leading-tight">انضم إلى<br>PharmaSmart</h1>
            <p class="text-emerald-50 font-bold text-base leading-relaxed opacity-90 mb-8">
                قم بتسجيل صيدليتك الآن لتصبح جزءاً من شبكتنا الطبية، واستقبل طلبات المرضى بكل سهولة.
            </p>

            <div class="space-y-4">
                <div class="flex items-center gap-3 text-white">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-300"></i>
                    <span class="font-bold text-sm">وصول لشريحة مرضى أكبر</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-300"></i>
                    <span class="font-bold text-sm">إدارة الطلبات والوصفات الطبية</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-300"></i>
                    <span class="font-bold text-sm">نظام كتالوج أدوية موحد</span>
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-8">
            <p class="text-emerald-200/70 text-xs font-bold">&copy; <?php echo date('Y'); ?> PharmaSmart. All rights reserved.</p>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. قسم الفورم (2/3 - قابل للتمرير) -->
    <!-- ========================================== -->
    <div class="w-full lg:w-2/3 flex flex-col relative h-full bg-gray-50 dark:bg-slate-900 overflow-y-auto custom-scrollbar">
        
        <!-- أزرار التحكم -->
        <div class="absolute top-6 rtl:left-6 ltr:right-6 flex items-center gap-3 z-50">
            <button id="theme-toggle" type="button" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all border border-gray-200 dark:border-slate-700 shadow-sm">
                <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5"></i>
                <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
            </button>
            <a href="?lang=<?php echo $lang['switch_lang_code']; ?>" class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all border border-gray-200 dark:border-slate-700 shadow-sm flex items-center gap-2 font-bold text-sm">
                <i data-lucide="globe" class="w-4 h-4"></i>
                <span><?php echo $lang['switch_lang_text']; ?></span>
            </a>
        </div>

        <!-- الفورم -->
        <div class="flex-1 flex flex-col p-6 lg:p-12 w-full mt-16 lg:mt-0 pb-12">
            <div class="w-full max-w-2xl mx-auto">
                
                <div class="lg:hidden inline-flex items-center justify-center w-14 h-14 bg-emerald-600 rounded-2xl mb-6 text-white shadow-lg">
                    <i data-lucide="store" class="w-7 h-7"></i>
                </div>

                <div class="mb-8 text-<?php echo ($dir == 'rtl') ? 'right' : 'left'; ?>">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2"><?php echo $lang['register_title_short']; ?></h2>
                    <p class="text-gray-500 dark:text-gray-400 font-bold text-sm"><?php echo $lang['register_subtitle']; ?></p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">

                    <!-- القسم الأول: البيانات الشخصية -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-100 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-base font-black text-emerald-600 dark:text-emerald-400 mb-4 flex items-center gap-2">
                            <i data-lucide="user-round" class="w-5 h-5"></i> <?php echo $lang['personal_info']; ?>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['first_name']; ?></label>
                                <input type="text" name="fname" required class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['last_name']; ?></label>
                                <input type="text" name="lname" required class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['phone']; ?></label>
                                <input type="text" name="phone" required dir="ltr" placeholder="05XXXXXXXX" class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition text-left">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['email']; ?></label>
                                <input type="email" name="email" required dir="ltr" placeholder="name@domain.com" class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition text-left">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['password']; ?></label>
                                <input type="password" name="password" required dir="ltr" placeholder="••••••••" class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition text-left">
                            </div>
                        </div>
                    </div>

                    <!-- القسم الثاني: بيانات الصيدلية -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-100 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-base font-black text-emerald-600 dark:text-emerald-400 mb-4 flex items-center gap-2">
                            <i data-lucide="store" class="w-5 h-5"></i> <?php echo $lang['pharmacy_info']; ?>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['pharmacy_name']; ?></label>
                                <input type="text" name="pName" required class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['license_num']; ?></label>
                                <input type="text" name="license" required dir="ltr" class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition text-left">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['address']; ?></label>
                                <input type="text" name="location" required class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['working_hours']; ?></label>
                                <input type="text" name="workingHours" required placeholder="08:00 AM - 10:00 PM" class="w-full h-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 text-sm font-bold focus:border-emerald-500 outline-none transition">
                            </div>
                             <!-- ========================================== -->
    <!-- 1. القسم التعريفي (1/3 - ثابت) -->
    <!-- ========================================== -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5"><?php echo $lang['pharmacy_logo']; ?></label>
                                <label class="flex items-center justify-between w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 cursor-pointer hover:border-emerald-500 transition group">
                                    <span id="logo-file-name" class="text-gray-500 dark:text-gray-400 text-sm font-bold truncate max-w-[70%]">
                                        <?php echo $lang['choose_logo']; ?>
                                    </span>
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 transition">
                                        <?php echo $lang['upload']; ?> <i data-lucide="upload" class="inline w-3 h-3 ml-1"></i>
                                    </span>
                                    <input type="file" name="logo" accept="image/*" class="hidden" onchange="updateLogoName(this)">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- القسم الثالث: الخريطة -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-100 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-base font-black text-emerald-600 dark:text-emerald-400 mb-2 flex items-center gap-2">
                            <i data-lucide="map-pinned" class="w-5 h-5"></i> <?php echo $lang['location_picker']; ?>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-bold mb-4"><?php echo $lang['location_description']; ?></p>
                        
                        <div class="relative w-full h-[220px] rounded-xl overflow-hidden border border-gray-200 dark:border-slate-600 z-0">
                            <div id="pickerMap" class="absolute inset-0"></div>
                        </div>
                        
                        <input type="hidden" name="lat" id="latInput" required>
                        <input type="hidden" name="lng" id="lngInput" required>
                    </div>

                    <button type="submit" name="register" class="w-full h-12 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all font-black text-base shadow-md flex justify-center items-center gap-2">
                        <?php echo $lang['register_button']; ?>
                    </button>

                </form>

                <div class="mt-8 text-center text-sm font-bold text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-700 pt-6">
                    <?php echo $lang['already_have_account']; ?> 
                    <a href="login.php" class="text-emerald-600 dark:text-emerald-400 font-black hover:underline mx-1"><?php echo $lang['login_link']; ?></a>
                </div>

            </div>
        </div>

         <!-- ========================================== -->
    <!-- 1. القسم التعريفي (1/3 - ثابت) -->
    <!-- ========================================== -->

    </div>

    <!-- السكربتات -->
    <script>
        lucide.createIcons();

        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
            document.documentElement.classList.add('dark');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
            document.documentElement.classList.remove('dark');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');
        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            themeToggleLightIcon.classList.toggle('hidden');
            themeToggleDarkIcon.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                localStorage.setItem('color-theme', 'dark');
                document.cookie = "theme=dark; path=/";
            } else {
                localStorage.setItem('color-theme', 'light');
                document.cookie = "theme=light; path=/";
            }

            // تحديث الخريطة عند تغيير الثيم
            if (typeof map !== 'undefined') {
                var newTileUrl = document.documentElement.classList.contains('dark') ?
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                L.tileLayer(newTileUrl, { maxZoom: 19 }).addTo(map);
            }
        });

        // إعداد الخريطة
        var map = L.map('pickerMap').setView([31.90, 35.20], 8);
        var tileUrl = document.documentElement.classList.contains('dark') ?
            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

        L.tileLayer(tileUrl, { maxZoom: 19 }).addTo(map);

        var customIcon = L.divIcon({
            className: 'custom-leaflet-marker',
            html: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.3));">
                     <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" fill="#fff"></path>
                     <circle cx="12" cy="10" r="3" fill="#10b981"></circle>
                   </svg>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
        });

        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, { icon: customIcon }).addTo(map);
            }

            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
        });

        function updateLogoName(input) {
            const displayElement = document.getElementById('logo-file-name');
            if (input.files && input.files.length > 0) {
                displayElement.innerText = input.files[0].name;
                displayElement.classList.add('text-emerald-600', 'dark:text-emerald-400');
            } else {
                displayElement.innerText = "<?php echo $lang['choose_logo']; ?>";
                displayElement.classList.remove('text-emerald-600', 'dark:text-emerald-400');
            }
        }

        <?php if ($message): ?>
            Swal.fire({
                icon: 'success',
                title: '<?php echo $lang['success']; ?>',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php endif; ?>

        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: '<?php echo $lang['error']; ?>',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
        <?php endif; ?>
    </script>
</body>
</html>