<?php
include('../config/database.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

$activePharma = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=1"))['c'];
$pendingPharma = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Pharmacist WHERE IsApproved=0"))['c'];
$patientsCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM User WHERE RoleID=3"))['c'];
$ordersCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"))['c'];

$query = "SELECT u.Fname, u.Lname, u.Phone, p.PharmacyName, p.Location, p.WorkingHours, p.LicenseNumber, p.Latitude, p.Longitude 
          FROM Pharmacist p JOIN User u ON p.PharmacistID = u.UserID 
          WHERE p.IsApproved=1 AND p.Latitude IS NOT NULL";
$result = mysqli_query($conn, $query);
$pharmacies = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pharmacies[] = $row;
}

include('../includes/header.php');
include('../includes/sidebar.php');

// 💡 منطق تحديد الاتجاهات (التعديل الجديد)
if ($dir == 'rtl') {
    $panel_pos = 'right-4';      // اللوحة يمين
    $zoom_pos = 'bottomleft';    // الزووم يسار
} else {
    $panel_pos = 'left-4';       // اللوحة يسار
    $zoom_pos = 'bottomright';   // الزووم يمين
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<main class="flex-1 p-8 bg-gray-50 dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">

    <?php include('../includes/topbar.php'); ?>

    <div class="mb-8 flex justify-between items-center">
        <div class="w-full flex items-center gap-3">
            <i data-lucide="layout-dashboard" class="text-blue-600 w-8 h-8"></i>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo $lang['dashboard']; ?></h1>
        </div>
    </div>

    <!-- الكروت الإحصائية -->
    <!-- الكروت الإحصائية مع تأثيرات Hover للوضع النهاري والليلي -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <!-- 1. الصيدليات العاملة -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex items-center justify-between 
                    border-b-4 border-transparent hover:border-emerald-500 dark:hover:border-emerald-400 
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-emerald-900/20">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['active_pharma']; ?></p>
                <h3 class="text-3xl font-black text-emerald-500 dark:text-emerald-400"><?php echo $activePharma; ?></h3>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 p-4 rounded-2xl text-emerald-500 dark:text-emerald-400">
                <i data-lucide="check-circle" class="w-8 h-8"></i>
            </div>
        </div>

        <!-- 2. طلبات الانضمام -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex items-center justify-between relative 
                    border-b-4 border-transparent hover:border-amber-500 dark:hover:border-amber-400 
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-amber-900/20">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['pending_req']; ?></p>
                <h3 class="text-3xl font-black text-amber-500 dark:text-amber-400"><?php echo $pendingPharma; ?></h3>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 p-4 rounded-2xl text-amber-500 dark:text-amber-400">
                <i data-lucide="user-plus" class="w-8 h-8"></i>
            </div>
            <?php if ($pendingPharma > 0): ?>
                <span class="absolute top-4 rtl:left-4 ltr:right-4 w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-sm"></span>
            <?php endif; ?>
        </div>

        <!-- 3. إجمالي المرضى -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex items-center justify-between 
                    border-b-4 border-transparent hover:border-blue-500 dark:hover:border-blue-400 
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-blue-900/20">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['total_patients']; ?></p>
                <h3 class="text-3xl font-black text-blue-500 dark:text-blue-400"><?php echo $patientsCount; ?></h3>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-2xl text-blue-500 dark:text-blue-400">
                <i data-lucide="users" class="w-8 h-8"></i>
            </div>
        </div>

        <!-- 4. عمليات الشراء -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 flex items-center justify-between 
                    border-b-4 border-transparent hover:border-purple-500 dark:hover:border-purple-400 
                    transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:hover:shadow-purple-900/20">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2"><?php echo $lang['total_orders']; ?></p>
                <h3 class="text-3xl font-black text-purple-500 dark:text-purple-400"><?php echo $ordersCount; ?></h3>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/30 p-4 rounded-2xl text-purple-500 dark:text-purple-400">
                <i data-lucide="shopping-bag" class="w-8 h-8"></i>
            </div>
        </div>
    </div>

   <!-- قسم الخريطة -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 p-6">
        
        <!-- الترويسة المعدلة (تم عكس الترتيب) -->
        <div class="flex justify-between items-center mb-6">
            
            <!-- 1. العنوان أصبح هنا (في البداية) -->
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i data-lucide="map" class="text-blue-500"></i> <?php echo $lang['map_title']; ?>
            </h2>

            <!-- 2. زر Live أصبح هنا (في النهاية) -->
            <span class="bg-gray-100 dark:bg-slate-700 text-gray-400 dark:text-gray-300 text-xs px-3 py-1 rounded-md font-bold">
                <?php echo $lang['live_update']; ?>
            </span>
            
        </div>

        <div class="relative w-full h-[550px] rounded-2xl overflow-hidden border-2 border-gray-100 dark:border-slate-700 shadow-inner bg-gray-100 dark:bg-slate-600">
            <div id="map" class="absolute inset-0 z-0"></div>
            
            <!-- ... باقي كود الخريطة واللوحة العائمة ... -->

            <!-- اللوحة العائمة (مكانها يتغير حسب المتغير $panel_pos) -->
            <div class="absolute top-4 <?php echo $panel_pos; ?> bottom-4 w-80 bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-600 z-[1000] flex flex-col transition-all duration-300">
                <div class="bg-gray-50 dark:bg-slate-700 p-4 border-b border-gray-200 dark:border-slate-600 rounded-t-2xl text-center">
                    <h3 class="text-xl font-black text-gray-800 dark:text-white"><?php echo $lang['pharma_info']; ?></h3>
                </div>
                <div id="pharmacy-details" class="p-6 flex-1 overflow-y-auto flex flex-col justify-center items-center text-center">
                    <i data-lucide="mouse-pointer-click" class="w-16 h-16 text-gray-300 dark:text-gray-500 mb-4 animate-bounce"></i>
                    <p class="text-gray-500 dark:text-gray-400 text-sm"><?php echo $lang['click_map']; ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    var map = L.map('map', {
        zoomControl: false
    }).setView([31.90, 35.20], 8);

    // 💡 وضع أزرار الزووم في المكان الصحيح (ديناميكياً)
    L.control.zoom({
        position: '<?php echo $zoom_pos; ?>'
    }).addTo(map);

    var tileUrl = document.documentElement.classList.contains('dark') ?
        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

    L.tileLayer(tileUrl, {
        maxZoom: 19
    }).addTo(map);

    var pharmaciesData = <?php echo json_encode($pharmacies); ?>;

    var langJS = {
        pharmacist: "<?php echo $lang['pharmacist_name']; ?>",
        address: "<?php echo $lang['address']; ?>",
        hours: "<?php echo $lang['working_hours']; ?>",
        phone: "<?php echo $lang['phone']; ?>",
        license: "<?php echo $lang['license_num']; ?>",
        na: "<?php echo $lang['not_available']; ?>"
    };

    function updatePharmacyInfo(pharma) {
        const detailsContainer = document.getElementById('pharmacy-details');

        detailsContainer.innerHTML = `
            <div class="w-full flex flex-col pt-4 h-full">
                <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 py-3 px-4 rounded-xl mb-6 text-center border border-blue-100 dark:border-blue-800">
                    <h2 class="text-xl font-bold">${pharma.PharmacyName}</h2>
                </div>
                
                <div class="space-y-6 text-sm px-4 py-6 border-2 border-gray-100 dark:border-slate-600 rounded-2xl bg-white dark:bg-slate-700 shadow-sm flex-1">
                    
                    <div class="flex items-center gap-3">
                        <i data-lucide="user" class="text-gray-400 w-5 h-5"></i>
                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.pharmacist}:</span>
                        <span class="font-bold text-gray-900 dark:text-white">${pharma.Fname} ${pharma.Lname}</span>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="text-gray-400 w-5 h-5 mt-0.5"></i>
                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.address}:</span>
                        <span class="font-bold text-gray-900 dark:text-white leading-relaxed">${pharma.Location}</span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <i data-lucide="clock" class="text-gray-400 w-5 h-5"></i>
                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.hours}:</span>
                        <span class="font-bold text-gray-900 dark:text-white">${pharma.WorkingHours}</span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <i data-lucide="phone" class="text-gray-400 w-5 h-5"></i>
                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.phone}:</span>
                        <span class="font-bold text-gray-900 dark:text-white" dir="ltr">${pharma.Phone || langJS.na}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <i data-lucide="file-text" class="text-gray-400 w-5 h-5"></i>
                        <span class="font-bold text-gray-500 dark:text-gray-400 min-w-[80px]">${langJS.license}:</span>
                        <span class="font-bold text-gray-900 dark:text-white">${pharma.LicenseNumber}</span>
                    </div>
                </div>
            </div>
        `;
        lucide.createIcons();
    }

    if (pharmaciesData.length > 0) {
        pharmaciesData.forEach(function(p) {
            if (p.Latitude && p.Longitude) {
                var marker = L.circleMarker([p.Latitude, p.Longitude], {
                    radius: 8,
                    fillColor: "#ef4444",
                    color: "#ffffff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(map);

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
    lucide.createIcons();
</script>
<?php include('../includes/footer.php'); ?>