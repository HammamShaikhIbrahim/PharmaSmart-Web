<?php
// ==========================================
// 1. استدعاء ملف الاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();

$message = ""; // متغير لحفظ رسالة النجاح
$error = "";   // متغير لحفظ رسائل الخطأ

// ==========================================
// 2. معالجة طلب التسجيل عند ضغط زر "إرسال"
// ==========================================
if (isset($_POST['register'])) {
    
    // تنظيف البيانات النصية لمنع ثغرات الحقن (SQL Injection)
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $lname = mysqli_real_escape_string($conn, $_POST['lname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); // الحقل الجديد: رقم الهاتف
    
    // بيانات الصيدلية
    $pName = mysqli_real_escape_string($conn, $_POST['pName']);
    $license = mysqli_real_escape_string($conn, $_POST['license']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $workingHours = mysqli_real_escape_string($conn, $_POST['workingHours']);
    
    // الإحداثيات القادمة من الخريطة التفاعلية (Hidden Inputs)
    $lat = (float)$_POST['lat'];
    $lng = (float)$_POST['lng'];

    // معالجة رفع صورة الشعار (Logo)
    $logo = "default_logo.png"; // صورة افتراضية في حال لم يرفع الصيدلاني صورة
    if (!empty($_FILES['logo']['name'])) {
        $logo = time() . "_" . $_FILES['logo']['name']; // وضع توقيت لمنع تكرار الأسماء
        move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/" . $logo);
    }

    // 3. التحقق من عدم تكرار البريد الإلكتروني في النظام
    $checkEmail = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    
    if (mysqli_num_rows($checkEmail) > 0) {
        $error = "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!";
    } elseif (empty($lat) || empty($lng)) {
        // تحقق إضافي: التأكد أن المستخدم اختار موقعاً على الخريطة
        $error = "يرجى تحديد موقع الصيدلية بدقة على الخريطة.";
    } else {
        
        // 4. إدخال بيانات المستخدم (لاحظ إضافة حقل Phone)
        $sqlUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID) 
                    VALUES ('$fname', '$lname', '$email', '$password', '$phone', 2)";
        
        if (mysqli_query($conn, $sqlUser)) {
            // جلب الـ ID الذي تم إنشاؤه للتو في جدول User
            $userId = mysqli_insert_id($conn);
            
            // 5. إدخال بيانات الصيدلية (لاحظ إضافة Latitude و Longitude)
            // حالة IsApproved تكون 0 افتراضياً (تحتاج موافقة الأدمن)
            $sqlPhar = "INSERT INTO Pharmacist (PharmacistID, PharmacyName, LicenseNumber, Location, Latitude, Longitude, WorkingHours, Logo, IsApproved) 
                        VALUES ($userId, '$pName', '$license', '$location', $lat, $lng, '$workingHours', '$logo', 0)";
            
            if (mysqli_query($conn, $sqlPhar)) {
                $message = "تم إرسال طلب انضمامك بنجاح! يرجى انتظار تفعيل حسابك من الإدارة.";
            } else {
                $error = "حدث خطأ أثناء حفظ بيانات الصيدلية: " . mysqli_error($conn);
            }
        } else {
            $error = "حدث خطأ أثناء إنشاء حساب المستخدم: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل صيدلية جديدة - PharmaSmart</title>
    
    <!-- مكتبات التصميم والتنبيهات -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- 💡 استدعاء مكتبة Leaflet لرسم الخريطة في فورم التسجيل -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* تثبيت الخلفية وجعل السكرول ناعم للفورم الطويل */
        body { background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen py-10">

    <!-- الحاوية الأساسية للفورم (أصبحت أعرض قليلاً لتستوعب الخريطة بشكل جميل) -->
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-3xl border border-gray-100 my-auto">
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black text-blue-600 mb-2">انضمام لشبكة PharmaSmart</h2>
            <p class="text-gray-500 text-sm">قم بملء بياناتك بدقة ليتم مراجعتها من قبل الإدارة</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- القسم الأول: بيانات المستخدم -->
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2"><i data-lucide="user"></i> البيانات الشخصية</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">الاسم الأول</label>
                        <input type="text" name="fname" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">اسم العائلة</label>
                        <input type="text" name="lname" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">رقم الهاتف</label>
                        <input type="text" name="phone" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500" dir="ltr" placeholder="05XXXXXXXX">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">البريد الإلكتروني</label>
                        <input type="email" name="email" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">كلمة المرور</label>
                        <input type="password" name="password" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- القسم الثاني: بيانات الصيدلية -->
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2"><i data-lucide="store"></i> بيانات الصيدلية</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">اسم الصيدلية الرسمي</label>
                        <input type="text" name="pName" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">رقم الترخيص من وزارة الصحة</label>
                        <input type="text" name="license" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">العنوان الوصفي (المدينة - الشارع)</label>
                        <input type="text" name="location" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">ساعات الدوام (مثال: 8 صباحاً - 10 مساءً)</label>
                        <input type="text" name="workingHours" required class="w-full p-3 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 mr-1">شعار الصيدلية (اختياري)</label>
                    <input type="file" name="logo" accept="image/*" class="w-full p-2 border rounded-xl bg-white">
                </div>
            </div>

            <!-- ========================================== -->
            <!-- القسم الثالث: الخريطة لتحديد الموقع بدقة -->
            <!-- ========================================== -->
            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                <h3 class="text-lg font-bold text-blue-800 mb-2 flex items-center gap-2"><i data-lucide="map-pin"></i> الموقع الجغرافي (GPS)</h3>
                <p class="text-xs text-blue-600 mb-4">يرجى الضغط على الخريطة لتحديد موقع الصيدلية بدقة، سيساعد هذا المرضى في العثور عليك.</p>
                
                <!-- حاوية الخريطة -->
                <div id="pickerMap" class="w-full h-64 rounded-xl border-2 border-blue-200 z-0"></div>
                
                <!-- حقول مخفية لتخزين الإحداثيات وإرسالها مع الفورم -->
                <!-- تم وضع required مع opacity 0 لجعل المتصفح يرفض الإرسال إذا لم يضغط المستخدم على الخريطة -->
                <div class="relative w-full h-0 overflow-hidden">
                    <input type="text" name="lat" id="latInput" required style="opacity: 0; position: absolute;">
                    <input type="text" name="lng" id="lngInput" required style="opacity: 0; position: absolute;">
                </div>
            </div>

            <!-- زر الإرسال -->
            <button type="submit" name="register" class="w-full bg-blue-600 text-white py-4 rounded-xl hover:bg-blue-700 transition font-bold text-lg shadow-xl shadow-blue-200">
                إرسال طلب الانضمام
            </button>
            
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-600 font-bold">
            <a href="login.php" class="text-blue-600 hover:underline">عودة لصفحة تسجيل الدخول</a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- سكربت الخريطة والتنبيهات -->
    <!-- ========================================== -->
    <script>
        // تفعيل الأيقونات
        lucide.createIcons();

        // 1. تهيئة الخريطة وتوجيهها لفلسطين
        var map = L.map('pickerMap').setView([31.90, 35.20], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // متغير لتخزين الدبوس (Marker)
        var marker;

        // 2. حدث الضغط على الخريطة (Click Event)
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            // إذا كان الدبوس موجوداً مسبقاً، قم بنقله، وإلا قم بإنشائه
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }

            // وضع الإحداثيات في الحقول المخفية لترسل مع الـ POST
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
        });

        // 3. عرض رسائل SweetAlert بناءً على استجابة الـ PHP
        <?php if($message): ?>
            Swal.fire({ 
                icon: 'success', 
                title: 'اكتمل الطلب!', 
                text: '<?php echo $message; ?>', 
                confirmButtonColor: '#2563eb' 
            }).then(() => { window.location.href = 'login.php'; });
        <?php endif; ?>
        
        <?php if($error): ?>
            Swal.fire({
                icon: 'error', 
                title: 'خطأ', 
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>
    </script>

</body>
</html>