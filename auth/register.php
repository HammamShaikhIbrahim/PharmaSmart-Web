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

    // جلب الإحداثيات
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 0;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;

    $logo = "default.png";
    if (!empty($_FILES['logo']['name'])) {
        $logo = time() . "_" . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/logos/" . $logo);
    }

    // التحقق المسبق من تكرار البريد الإلكتروني ورقم الترخيص
    $checkEmail = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    $checkLicense = mysqli_query($conn, "SELECT PharmacistID FROM Pharmacist WHERE LicenseNumber = '$license'");

    if (mysqli_num_rows($checkEmail) > 0) {
        $error = $lang['email_exists_error'];
    } elseif (mysqli_num_rows($checkLicense) > 0) {
        $error = $lang['license_exists_error'];
    } elseif ($lat == 0 || $lng == 0) {
        $error = $lang['location_error'];
    } elseif (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        // التحقق من قوة الباسوورد في السيرفر كطبقة حماية ثانية
        $error = "كلمة المرور ضعيفة! يجب أن تتكون من 8 خانات على الأقل، وتحتوي على حرف كبير، حرف صغير، ورقم.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // بدء المعاملة الأمنية لضمان سلامة إدخال البيانات المترابطة
        mysqli_begin_transaction($conn);

        try {
            // إدخال بيانات المستخدم أولاً (المالك)
            $sqlUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                        VALUES ('$fname', '$lname', '$email', '$hashed_password', '$phone', 2)";

            mysqli_query($conn, $sqlUser);
            $userId = mysqli_insert_id($conn);

            // إدخال بيانات الصيدلية ثانياً
            $sqlPhar = "INSERT INTO Pharmacist (PharmacistID, PharmacyName, LicenseNumber, Location, Latitude, Longitude, WorkingHours, Logo, IsApproved)
                        VALUES ($userId, '$pName', '$license', '$location', $lat, $lng, '$workingHours', '$logo', 0)";

            mysqli_query($conn, $sqlPhar);

            // تأكيد العملية بنجاح
            mysqli_commit($conn);
            $message = $lang['registration_success'];
        } catch (mysqli_sql_exception $e) {
            // التراجع عن الإدخالات السابقة في حال حدوث فشل في أي جزء من الاستعلامات
            mysqli_rollback($conn);

            if (strpos($e->getMessage(), 'LicenseNumber') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = $lang['license_exists_error'];
            } elseif (strpos($e->getMessage(), 'Email') !== false) {
                $error = $lang['email_exists_error'];
            } else {
                $error = $lang['db_error'] . " " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['register_title']; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

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
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body,
        html {
            height: 100%;
            overflow: hidden;
            margin: 0;
            font-family: 'Tajawal', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #10b981;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background-color: #047857;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }

        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(203, 213, 225, 0.6);
            color: #1e293b;
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: #ffffff;
            border-color: #10b981;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .dark .glass-input {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(71, 85, 105, 0.5);
            color: #f8fafc;
        }

        .dark .glass-input:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #10b981;
        }

        .step-circle {
            transition: all 0.4s ease;
        }

        .step-line {
            transition: all 0.4s ease;
            background-color: #e2e8f0;
        }

        .dark .step-line {
            background-color: #334155;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-teal-50 to-emerald-200 dark:from-slate-900 dark:to-teal-950 relative transition-colors duration-500">

    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-10 left-20 w-32 h-64 rounded-full transform rotate-[35deg]
                    bg-gradient-to-b from-emerald-300 to-teal-500 dark:from-emerald-600 dark:to-teal-800
                    shadow-[inset_15px_15px_30px_rgba(255,255,255,0.7),inset_-10px_-10px_30px_rgba(0,0,0,0.2),10px_20px_40px_rgba(20,184,166,0.3)]">
        </div>

        <div class="absolute top-1/4 right-20 w-48 h-48 rounded-full transform -rotate-[15deg]
                    bg-gradient-to-tr from-green-200 to-emerald-400 dark:from-green-700 dark:to-emerald-600
                    shadow-[inset_-10px_-10px_30px_rgba(0,0,0,0.15),inset_15px_15px_30px_rgba(255,255,255,0.8),0_20px_40px_rgba(16,185,129,0.2)]">
            <div class="absolute top-1/2 left-4 right-4 h-1 bg-white/40 dark:bg-black/10 rounded-full transform -translate-y-1/2 shadow-[inset_0_1px_2px_rgba(0,0,0,0.1)]"></div>
        </div>

        <div class="absolute bottom-20 left-1/4 w-32 h-32 transform rotate-[15deg] opacity-80">
            <div class="absolute inset-x-10 inset-y-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div>
            <div class="absolute inset-y-10 inset-x-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div>
        </div>

        <div class="absolute bottom-1/3 right-1/3 w-20 h-40 rounded-full transform -rotate-[40deg] blur-md
                    bg-gradient-to-r from-emerald-400 to-green-300 dark:from-emerald-700 dark:to-green-800
                    shadow-[inset_5px_5px_15px_rgba(255,255,255,0.5)]">
        </div>
    </div>

    <div class="absolute top-6 right-6 flex items-center gap-3 z-50">
        <button id="theme-toggle" type="button" class="glass-panel p-3 rounded-2xl text-gray-700 dark:text-white transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 focus:outline-none flex items-center justify-center group">
            <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:rotate-90"></i>
            <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:-rotate-12"></i>
        </button>

        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>"
            class="glass-panel text-gray-800 dark:text-white font-bold px-5 py-3 rounded-2xl transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 flex items-center gap-2 text-sm group">
            <i data-lucide="globe" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 transition-transform duration-500 group-hover:rotate-180"></i>
            <span class="group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors"><?php echo $lang['switch_lang_text']; ?></span>
        </a>
    </div>

    <main id="mainScrollArea" class="h-full w-full overflow-y-auto flex flex-col items-center p-4 relative z-10">
        <div class="w-full max-w-4xl mt-12 mb-20 relative">
            <div class="glass-panel p-8 md:p-12 rounded-[2.5rem] w-full transition-all duration-300 relative">

                <div class="text-center mb-8">
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2"><?php echo $lang['register_title']; ?></h2>
                    <p class="text-sm font-bold text-gray-600 dark:text-gray-400 opacity-90"><?php echo $lang['register_subtitle']; ?></p>
                </div>

                <div class="sticky top-0 z-50 bg-white/70 dark:bg-slate-800/80 backdrop-blur-xl p-4 rounded-3xl border border-gray-200 dark:border-slate-700 shadow-sm flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div id="circle-1" class="step-circle w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-emerald-500/40">1</div>
                        <span id="text-1" class="font-bold text-emerald-600 dark:text-emerald-400 hidden sm:block text-sm"><?php echo $lang['personal_info_step']; ?></span>
                    </div>
                    <div id="line-1" class="h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full"></div>
                    <div class="flex items-center gap-3">
                        <div id="circle-2" class="step-circle w-10 h-10 rounded-full bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-bold text-lg">2</div>
                        <span id="text-2" class="font-bold text-gray-500 dark:text-gray-400 hidden sm:block text-sm"><?php echo $lang['pharmacy_info_step']; ?></span>
                    </div>
                    <div id="line-2" class="h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full"></div>
                    <div class="flex items-center gap-3">
                        <div id="circle-3" class="step-circle w-10 h-10 rounded-full bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-bold text-lg">3</div>
                        <span id="text-3" class="font-bold text-gray-500 dark:text-gray-400 hidden sm:block text-sm"><?php echo $lang['geographic_location_step']; ?></span>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-8" onsubmit="return validateForm(event)">

                    <div id="section-1" class="bg-white/40 dark:bg-slate-800/60 p-6 md:p-8 rounded-3xl border border-white/60 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                            <i class="fa-solid fa-user-doctor text-emerald-600 dark:text-emerald-400"></i>
                            <?php echo $lang['personal_info']; ?>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['first_name']; ?></label> <input type="text" name="fname" required class="glass-input w-full p-3.5 rounded-xl"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['last_name']; ?></label><input type="text" name="lname" required class="glass-input w-full p-3.5 rounded-xl"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['phone']; ?></label><input type="text" name="phone" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="05XXXXXXXX"></div>
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['email']; ?></label><input type="email" name="email" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="name@pharma.com"></div>
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['password']; ?></label><input type="password" name="password" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="••••••••"></div>
                        </div>
                    </div>

                    <div id="section-2" class="bg-white/40 dark:bg-slate-800/60 p-6 md:p-8 rounded-3xl border border-white/60 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                            <i class="fa-solid fa-staff-snake text-emerald-600 dark:text-emerald-400"></i><?php echo $lang['pharmacy_info']; ?>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['pharmacy_name']; ?></label><input type="text" name="pName" required class="glass-input w-full p-3.5 rounded-xl"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['license_num']; ?></label><input type="text" name="license" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['address']; ?></label><input type="text" name="location" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['working_hours']; ?></label><input type="text" name="workingHours" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500" dir="ltr"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-300 mb-1.5 mx-1"><?php echo $lang['pharmacy_logo']; ?></label>
                            <label class="flex items-center justify-between w-full p-3 rounded-xl bg-white/40 dark:bg-slate-900/40 border border-gray-200 dark:border-slate-600 transition cursor-pointer group hover:bg-white/60">
                                <span id="logo-file-name" class="text-gray-600 dark:text-gray-400 text-sm font-bold truncate max-w-[70%]"><?php echo $lang['choose_logo']; ?></span>
                                <span class="px-4 py-1.5 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-400/30 transition duration-300 group-hover:bg-emerald-500/20 group-hover:shadow-[0_0_10px_rgba(16,185,129,0.4)]">
                                    <?php echo $lang['upload']; ?> <i data-lucide="upload" class="inline w-3 h-3 rlt:mr-1 ltr:ml-1"></i>
                                </span>
                                <input type="file" name="logo" accept="image/*" class="hidden" onchange="updateLogoName(this)">
                            </label>
                        </div>
                    </div>

                    <div id="section-3" class="bg-emerald-500/10 dark:bg-teal-900/20 p-6 md:p-8 rounded-3xl border border-emerald-500/20 dark:border-teal-500/20 shadow-sm">
                        <h3 class="text-lg font-black text-emerald-800 dark:text-emerald-400 mb-2 flex items-center gap-2"><i data-lucide="map-pin" class="text-emerald-600 dark:text-emerald-400"></i> <?php echo $lang['location_picker']; ?></h3>
                        <p class="text-xs text-emerald-700 dark:text-emerald-500/90 font-bold mb-5"><?php echo $lang['location_description']; ?></p>
                        <div class="relative w-full h-[300px] rounded-2xl overflow-hidden border border-emerald-200 dark:border-slate-600 shadow-inner z-0">
                            <div id="pickerMap" class="absolute inset-0"></div>
                        </div>

                        <div class="relative w-full h-0 overflow-hidden">
                            <input type="text" name="lat" id="latInput" style="opacity: 0; position: absolute;">
                            <input type="text" name="lng" id="lngInput" style="opacity: 0; position: absolute;">
                        </div>
                    </div>

                    <button type="submit" name="register" class="w-full bg-emerald-600 text-white py-4 rounded-2xl hover:bg-emerald-700 transition-all font-black text-lg mt-8 shadow-[0_10px_20px_rgba(16,185,129,0.3)] active:scale-[0.98] border border-emerald-500/50 flex justify-center items-center gap-2">
                        <?php echo $lang['register_button']; ?>
                        <i data-lucide="<?php echo ($dir == 'rtl') ? 'arrow-left' : 'arrow-right'; ?>" class="w-5 h-5"></i>
                    </button>

                </form>

                <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400 font-bold border-t border-gray-200 dark:border-slate-700/50 pt-6">
                    <?php echo $lang['already_have_account']; ?> <a href="login.php" class="text-emerald-600 dark:text-emerald-400 hover:underline mx-1 font-black"><?php echo $lang['login_link']; ?></a>
                </div>

            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        if (localStorage.getItem('color-theme') === 'light' || (!('color-theme' in localStorage))) {
            themeToggleDarkIcon.classList.remove('hidden');
        } else {
            themeToggleLightIcon.classList.remove('hidden');
        }

        function updateThemeIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            var sunIcon = document.getElementById('theme-toggle-light-icon');
            var moonIcon = document.getElementById('theme-toggle-dark-icon');
            if (isDark) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }
        updateThemeIcons();
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
            updateThemeIcons();
            if (typeof map !== 'undefined') {
                var newTileUrl = document.documentElement.classList.contains('dark') ?
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                L.tileLayer(newTileUrl, {
                    maxZoom: 19
                }).addTo(map);
            }
        });

        var map = L.map('pickerMap').setView([31.5126, 34.4475], 8);
        var tileUrl = document.documentElement.classList.contains('dark') ?
            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        L.tileLayer(tileUrl, {
            maxZoom: 19
        }).addTo(map);

        var customIcon = L.divIcon({
            className: 'custom-leaflet-marker',
            html: `<div class="flex items-center justify-center text-emerald-500"><i data-lucide="map-pin" class="w-6 h-6"></i></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
        });

        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: customIcon,
                    bounceOnAdd: true
                }).addTo(map);
            }
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
            lucide.createIcons();
        });

        function updateLogoName(input) {
            const displayElement = document.getElementById('logo-file-name');
            if (input.files && input.files.length > 0) {
                displayElement.innerText = input.files[0].name;
                displayElement.classList.add('text-emerald-600');
            } else {
                displayElement.innerText = "<?php echo $lang['choose_logo']; ?>";
                displayElement.classList.remove('text-emerald-600');
            }
        }

        function validateForm(e) {
            let lat = document.getElementById('latInput').value;
            let lng = document.getElementById('lngInput').value;
            let password = document.getElementsByName('password')[0].value;

            // التحقق من قوة كلمة المرور في المتصفح باستخدام التعبيرات القياسية
            let passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\w\W]{8,}$/;

            if (!passwordPattern.test(password)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: '<?php echo $lang['password_title']; ?>',
                    text: '<?php echo $lang['password_error']; ?>',
                    confirmButtonColor: '#10b981',
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
                });
                return false;
            }

            if (!lat || !lng) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: '<?php echo $lang['warning_title']; ?>',
                    text: '<?php echo $lang['location_error']; ?>',
                    confirmButtonColor: '#10b981',
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
                });
                return false;
            }
            return true;
        }

        const scrollArea = document.getElementById('mainScrollArea');
        scrollArea.addEventListener('scroll', function() {
            let scrollPos = scrollArea.scrollTop;

            let sec2 = document.getElementById('section-2').offsetTop - 200;
            let sec3 = document.getElementById('section-3').offsetTop - 300;

            let c1 = document.getElementById('circle-1');
            let t1 = document.getElementById('text-1');
            let l1 = document.getElementById('line-1');
            let c2 = document.getElementById('circle-2');
            let t2 = document.getElementById('text-2');
            let l2 = document.getElementById('line-2');
            let c3 = document.getElementById('circle-3');
            let t3 = document.getElementById('text-3');

            const activeColor = 'bg-emerald-500 text-white shadow-md shadow-emerald-500/40';
            const doneColor = 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 border border-emerald-400';
            const idleColor = 'bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-gray-400';
            const textActive = 'text-emerald-600 dark:text-emerald-400';
            const textIdle = 'text-gray-500 dark:text-gray-400';
            const lineActive = 'bg-emerald-500';
            const lineIdle = 'bg-gray-200 dark:bg-slate-700';

            [c1, c2, c3].forEach(c => c.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + idleColor);
            [t1, t2, t3].forEach(t => t.className = 'font-bold hidden sm:block text-sm ' + textIdle);
            [l1, l2].forEach(l => l.className = 'h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full ' + lineIdle);

            if (scrollPos >= sec3) {
                c1.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + doneColor;
                l1.className = 'h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full ' + lineActive;
                c2.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + doneColor;
                l2.className = 'h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full ' + lineActive;
                c3.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + activeColor;
                t3.className = 'font-bold hidden sm:block text-sm ' + textActive;
            } else if (scrollPos >= sec2) {
                c1.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + doneColor;
                l1.className = 'h-1 flex-1 mx-2 sm:mx-4 step-line rounded-full ' + lineActive;
                c2.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + activeColor;
                t2.className = 'font-bold hidden sm:block text-sm ' + textActive;
            } else {
                c1.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ' + activeColor;
                t1.className = 'font-bold hidden sm:block text-sm ' + textActive;
            }
        });

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