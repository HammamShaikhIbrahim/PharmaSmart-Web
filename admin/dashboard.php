<?php

// ==========================================

// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات

// ==========================================



// استدعاء ملف الاتصال بقاعدة البيانات عشان نقدر نكلم الـ Database

include('../config/database.php');



// تشغيل الجلسة (Session) عشان نقدر نعرف مين المستخدم اللي مسجل دخول حالياً

session_start();



// فحص الحماية: هل المستخدم مسجل دخول؟ وهل هو "أدمن" (RoleID = 1)؟

// إذا ما كان مسجل، أو كان مريض أو صيدلي، بنطرده وبنحوله لصفحة تسجيل الدخول فوراً

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {

    header("Location: ../auth/login.php");

    exit(); // بنوقف تنفيذ باقي الكود عشان ما يقدر يشوف الصفحة

}



// ==========================================

// 2. جلب الإحصائيات (الأرقام) لعرضها في الكروت العلوية

// ==========================================



// جلب عدد الصيدليات "المفعلة" (IsApproved=1)

$activePharma = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=1"))['c'];



// جلب عدد الصيدليات "المعلقة / قيد الانتظار" (IsApproved=0)

$pendingPharma = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=0"))['c'];



// جلب عدد "المرضى" المسجلين في النظام (RoleID=3)

$patientsCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM User WHERE RoleID=3"))['c'];



// جلب إجمالي عدد "الطلبات" (المشتريات) اللي تمت في النظام

$ordersCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"))['c'];



// ==========================================

// 3. جلب بيانات الصيدليات لعرضها على الخريطة

// ==========================================



// 💡 تعديل الاستعلام: بنجيب كل الصيدليات (المفعلة والمعلقة) اللي عندها إحداثيات (Latitude مش فاضي)

// عشان نرسمهم على الخريطة، وبعدين بنفلترهم بالجافاسكربت (أخضر للمفعل، أصفر للمعلق)

$query = "SELECT u.Fname, u.Lname, u.Phone, p.PharmacyName, p.Location, p.WorkingHours, p.LicenseNumber, p.Latitude, p.Longitude, p.IsApproved 

          FROM Pharmacist p JOIN User u ON p.PharmacistID = u.UserID 

          WHERE p.Latitude IS NOT NULL";

$result = mysqli_query($conn, $query);



$pharmacies = []; // بنعمل مصفوفة (Array) فاضية

// بنلف على كل النتائج اللي رجعت من الداتابيز ونحطها جوة المصفوفة

while ($row = mysqli_fetch_assoc($result)) {

    $pharmacies[] = $row;
}



// ==========================================

// 4. استدعاء أجزاء التصميم (الهيدر والقائمة الجانبية)

// ==========================================

include('../includes/header.php'); // الهيدر (ملفات الـ CSS والـ Meta tags)

include('../includes/sidebar.php'); // القائمة الجانبية (الـ Sidebar)



// ==========================================

// 5. ضبط اتجاهات التصميم بناءً على لغة الموقع (عربي RTL / إنجليزي LTR)

// ==========================================

// متغير $dir جاي من ملف lang.php المربوط في الهيدر

if ($dir == 'rtl') {

    $panel_pos = 'right-4'; // اللوحة العائمة تبعت الخريطة تكون على اليمين

    $zoom_pos = 'bottomleft'; // أزرار التكبير/التصغير تبعت الخريطة تكون تحت يسار

} else {

    $panel_pos = 'left-4'; // اللوحة العائمة على اليسار

    $zoom_pos = 'bottomright'; // أزرار التكبير تحت يمين

}

?>



<!-- استدعاء ملفات مكتبة الخرائط Leaflet.js (التصميم والسكربت) -->

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>



<style>
    /* تنسيق للزر "النشط" في فلاتر الخريطة (الكل / المفعلة / المعلقة) */
   .active-filter {

        background-color: #048AC1;

        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

        color: white !important;

    }

    /* تنسيق الزر النشط في حالة الـ Dark Mode (الوضع الليلي) */

    .dark .active-filter {

        background-color: #01305A; /* 🎨 Midnight Blue للأدمن */

        color: white !important;

    }
</style>



<!-- ==========================================

     بداية محتوى الصفحة الفعلي (Main Content)

=========================================== -->

<main class="flex-1 p-8 bg-blue-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">



    <?php include('../includes/topbar.php'); ?>



    <!-- عنوان الصفحة -->

    <div class="mb-8 flex justify-between items-center">

        <div class="w-full flex items-center gap-3">

            <!-- 💡 تم تغيير لون الأيقونة إلى الأزرق (#048AC1) بما يتوافق مع ثيم الإدمن -->

            <i data-lucide="layout-dashboard" class="text-[#048AC1] w-8 h-8"></i>

            <h1 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $lang['dashboard']; ?></h1>

        </div>

    </div>



    <!-- ==========================================

         قسم الكروت الإحصائية الأربعة (Admin Grid)

         💡 تم تطبيق نظام ألوان موحد:

         - الأيقونات: أزرق بدرجات مختلفة حسب القيمة

         - الهوفر: حدود زرقاء فاتحة (#048AC1)

    =========================================== -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">



        <!-- 1. كرت: الصيدليات العاملة (المفعلة) - أخضر للدلالة على النشاط -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex items-center justify-between border-b-4 border-transparent hover:border-b-emerald-500 dark:hover:border-b-emerald-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div>

                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['active_pharma']; ?></p>

                <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $activePharma; ?></h3>

            </div>

            <!-- أيقونة: نقطة اختيار بلون أخضر (دلالة على النشاط) -->

            <i data-lucide="check-circle" class="w-12 h-12 text-emerald-500 drop-shadow-sm opacity-80"></i>

        </div>



        <!-- 2. كرت: طلبات الانضمام (الصيدليات المعلقة) - برتقالي للتنبيه -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex items-center justify-between relative border-b-4 border-transparent hover:border-b-amber-500 dark:hover:border-b-amber-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div>

                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['pending_req']; ?></p>

                <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $pendingPharma; ?></h3>

            </div>

            <!-- أيقونة: إضافة مستخدم بلون برتقالي (تنبيه من الانتظار) -->

            <i data-lucide="user-plus" class="w-12 h-12 text-amber-500 drop-shadow-sm opacity-80"></i>

            <!-- النقطة الحمراء الوامضة (إذا كان في طلبات معلقة) -->

            <?php if ($pendingPharma > 0): ?>

                <span class="absolute top-4 rtl:left-4 ltr:right-4 w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-sm"></span>

            <?php endif; ?>

        </div>



        <!-- 3. كرت: إجمالي المرضى - أزرق -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex items-center justify-between border-b-4 border-transparent hover:border-b-[#048AC1] dark:hover:border-b-[#048AC1] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div>

                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['total_patients']; ?></p>

                <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $patientsCount; ?></h3>

            </div>

            <!-- أيقونة: مستخدمون بلون أزرق (ثيم الإدمن) -->

            <i data-lucide="users" class="w-12 h-12 text-[#048AC1] drop-shadow-sm opacity-80"></i>

        </div>



        <!-- 4. كرت: عمليات الشراء - بنفسجي -->

        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 flex items-center justify-between border-b-4 border-transparent hover:border-b-purple-500 dark:hover:border-b-purple-400 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">

            <div>

                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['total_orders']; ?></p>

                <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $ordersCount; ?></h3>

            </div>

            <!-- أيقونة: حقيبة تسوق بلون بنفسجي -->

            <i data-lucide="shopping-bag" class="w-12 h-12 text-purple-500 drop-shadow-sm opacity-80"></i>

        </div>

    </div>



    <!-- ==========================================

         قسم الخريطة التفاعلية (Leaflet Map)

    =========================================== -->

    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-md border border-gray-200 dark:border-slate-700 p-6">



        <!-- الترويسة وأزرار الفلترة فوق الخريطة -->

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">



            <h2 class="text-2xl font-black text-gray-800 dark:text-white flex items-center gap-2">

                <!-- 💡 أيقونة الخريطة بلون أزرق -->

                <i data-lucide="map" class="text-[#048AC1]"></i> <?php echo $lang['map_title']; ?>

            </h2>



            <!-- 💡 أزرار فلترة الخريطة (بتستدعي دالة filterMap بالجافاسكربت) -->

            <div class="flex bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 p-1 rounded-xl">

                <button onclick="filterMap('all', this)" class="filter-btn active-filter px-4 py-1.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-400 transition"><?php echo $lang['filter_all']; ?></button>

                <button onclick="filterMap('active', this)" class="filter-btn px-4 py-1.5 rounded-lg text-sm font-bold text-emerald-600 transition"><?php echo $lang['filter_active']; ?></button>

                <button onclick="filterMap('pending', this)" class="filter-btn px-4 py-1.5 rounded-lg text-sm font-bold text-amber-500 transition"><?php echo $lang['filter_pending']; ?></button>

            </div>

        </div>



        <!-- حاوية الخريطة -->

        <div class="relative w-full h-[550px] rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-slate-700 shadow-inner bg-gray-100 dark:bg-slate-600">

            <!-- الديف اللي رح ترتسم فيه الخريطة -->

            <div id="map" class="absolute inset-0 z-0"></div>



            <!-- اللوحة العائمة اللي بتعرض تفاصيل الصيدلية -->

            <div class="absolute top-4 <?php echo $panel_pos; ?> bottom-4 w-120 bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-600 z-[1000] flex flex-col transition-all duration-300">

                <!-- رأس اللوحة -->

                <div class="bg-gray-50 dark:bg-slate-700 p-4 border-b border-gray-200 dark:border-slate-600 rounded-t-2xl text-center">

                    <h3 class="text-xl font-black text-gray-800 dark:text-white"><?php echo $lang['pharma_info']; ?></h3>

                </div>

                <!-- تفاصيل الصيدلية (ديناميكي) -->

                <div id="pharmacy-details" class="p-6 flex-1 overflow-y-auto flex flex-col justify-center items-center text-center">

                    <!-- 💡 أيقونة الفأرة بلون أزرق فاتح للإشارة للأدمن -->

                    <i data-lucide="mouse-pointer-click" class="w-16 h-16 text-[#048AC1] mb-4 animate-bounce opacity-60"></i>

                    <p class="text-gray-500 dark:text-gray-400 font-bold text-sm"><?php echo $lang['click_map']; ?></p>

                </div>

            </div>

        </div>

    </div>

</main>



<script>
    // 1. تهيئة الخريطة (Leaflet.js)

    var map = L.map('map', {

        zoomControl: false // بنطفي أزرار الزووم الافتراضية عشان نحطها بمكان مخصص

    }).setView([31.90, 35.20], 8);



    // إضافة أزرار الزووم في المكان اللي حددناه بالـ PHP

    L.control.zoom({

        position: '<?php echo $zoom_pos; ?>'

    }).addTo(map);



    // 2. تحديد شكل الخريطة (Tile Layer) ديناميكياً

    var lightTileUrl = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

    var darkTileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';

    var currentTileLayer; // متغير لحفظ طبقة الخريطة الحالية



    // دالة لتحديث ثيم الخريطة

    function updateMapTheme() {

        var isDark = document.documentElement.classList.contains('dark');

        var newTileUrl = isDark ? darkTileUrl : lightTileUrl;



        // إذا كانت هناك طبقة مرسومة مسبقاً، قم بإزالتها

        if (currentTileLayer) {

            map.removeLayer(currentTileLayer);

        }



        // ارسم الطبقة الجديدة

        currentTileLayer = L.tileLayer(newTileUrl, {

            maxZoom: 19

        }).addTo(map);

    }



    // تشغيل الدالة لأول مرة

    updateMapTheme();



    // الاستماع لزر التبديل (Theme Toggle)

    var themeToggleBtnMap = document.getElementById('theme-toggle');

    if (themeToggleBtnMap) {

        themeToggleBtnMap.addEventListener('click', function() {

            setTimeout(updateMapTheme, 50);

        });

    }



    // 3. مجموعة الطبقات (LayerGroup)

    var markersLayer = L.layerGroup().addTo(map);



    // تحويل مصفوفة الصيدليات إلى JSON

    var pharmaciesData = <?php echo json_encode($pharmacies); ?>;



    // جلب الترجمات من الـ PHP للـ JS

    var langJS = {

        pharmacist: "<?php echo $lang['pharmacist_name']; ?>",

        address: "<?php echo $lang['address']; ?>",

        hours: "<?php echo $lang['working_hours']; ?>",

        phone: "<?php echo $lang['phone']; ?>",

        license: "<?php echo $lang['license_num']; ?>",

        na: "<?php echo $lang['not_available']; ?>"

    };



    // 4. دالة تحديث اللوحة الجانبية

    function updatePharmacyInfo(pharma) {

        const detailsContainer = document.getElementById('pharmacy-details');



        // تحديد لون الهيدر حسب حالة الصيدلية

        let headerBg = pharma.IsApproved == 1 ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200' : 'bg-amber-50 dark:bg-amber-900/30 border-amber-200';

        let headerText = pharma.IsApproved == 1 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';



        // 💡 تم توحيد أيقونات الكرت لتصبح زرقاء (#048AC1) لتعكس ثيم الإدارة

        detailsContainer.innerHTML = `

            <div class="w-full flex flex-col pt-4 h-full">

                <div class="${headerBg} ${headerText} py-3 px-4 rounded-xl mb-6 text-center border dark:border-slate-700 shadow-sm">

                    <h2 class="text-xl font-black">${pharma.PharmacyName}</h2>

                </div>

                <div class="space-y-6 text-sm px-4 py-6 border border-gray-200 dark:border-slate-600 rounded-2xl bg-white dark:bg-slate-700 shadow flex-1">

                    <div class="flex items-center gap-3">

                        <i data-lucide="user" class="text-[#048AC1] w-5 h-5"></i>

                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.pharmacist}:</span>

                        <span class="font-bold text-gray-900 dark:text-white">${pharma.Fname} ${pharma.Lname}</span>

                    </div>

                    <div class="flex items-start gap-3">

                        <i data-lucide="map-pin" class="text-[#048AC1] w-5 h-5 mt-0.5"></i>

                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.address}:</span>

                        <span class="font-bold text-gray-900 dark:text-white leading-relaxed">${pharma.Location}</span>

                    </div>

                    <div class="flex items-center gap-3">

                        <i data-lucide="clock" class="text-[#048AC1] w-5 h-5"></i>

                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.hours}:</span>

                        <span class="font-bold text-gray-900 dark:text-white">${pharma.WorkingHours}</span>

                    </div>

                    <div class="flex items-center gap-3">

                        <i data-lucide="phone" class="text-[#048AC1] w-5 h-5"></i>

                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.phone}:</span>

                        <span class="font-bold text-gray-900 dark:text-white" dir="ltr">${pharma.Phone || langJS.na}</span>

                    </div>

                    <div class="flex items-center gap-3">

                        <i data-lucide="file-text" class="text-[#048AC1] w-5 h-5"></i>

                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.license}:</span>

                        <span class="font-bold text-gray-900 dark:text-white">${pharma.LicenseNumber}</span>

                    </div>

                </div>

            </div>

        `;



        // تفعيل الأيقونات الجديدة

        lucide.createIcons();

    }



    // 5. دالة رسم النقاط على الخريطة وفلترتها

    function drawMarkers(filter) {

        markersLayer.clearLayers(); // مسح النقاط القديمة



        pharmaciesData.forEach(function(p) {



            // شروط الفلترة

            if (filter === 'active' && p.IsApproved != 1) return;

            if (filter === 'pending' && p.IsApproved != 0) return;



            // رسم النقطة إذا كانت عندها إحداثيات

            if (p.Latitude && p.Longitude) {



                // تحديد لون النقطة: أخضر للمفعلة، برتقالي للمعلقة

                let markerColor = p.IsApproved == 1 ? "#10b981" : "#f59e0b";



                // رسم الدائرة على الخريطة

                var marker = L.circleMarker([p.Latitude, p.Longitude], {

                    radius: 8,

                    fillColor: markerColor,

                    color: "#ffffff",

                    weight: 2,

                    opacity: 1,

                    fillOpacity: 1

                }).addTo(markersLayer);



                // حدث الضغط على النقطة

                marker.on('click', function() {

                    updatePharmacyInfo(p);

                    map.flyTo([p.Latitude, p.Longitude], 12, {

                        animate: true,

                        duration: 1.5

                    });

                });

            }

        });

    }



    // 6. دالة الفلترة

    window.filterMap = function(type, btnElement) {

        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active-filter'));

        btnElement.classList.add('active-filter');

        drawMarkers(type);

    }



    // تشغيل الدالة لأول مرة

    drawMarkers('all');

    lucide.createIcons();
</script>



<!-- استدعاء ملف الفوتر -->

<?php include('../includes/footer.php'); ?>