<?php
// ==========================================
// 1. إعدادات السماحيات (CORS & Headers)
// هذه الأسطر ضرورية جداً لكي يسمح السيرفر لتطبيق الموبايل بالاتصال به
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ==========================================
// 2. استدعاء ملف الاتصال بقاعدة البيانات
// مسار الملف يعود خطوة للخلف (..) ليدخل مجلد config
// ==========================================
include_once '../config/database.php';

// ==========================================
// 3. استلام البيانات القادمة من الموبايل
// الموبايل يرسل البيانات بصيغة JSON، فنقوم بتحويلها لكي يفهمها الـ PHP
// ==========================================
$data = json_decode(file_get_contents("php://input"));

// ==========================================
// 4. التحقق من أن البيانات الأساسية ليست فارغة
// ==========================================
if (
    !empty($data->fname) &&
    !empty($data->lname) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    // تنظيف البيانات لحماية قاعدة البيانات من الاختراق (SQL Injection)
    $fname = mysqli_real_escape_string($conn, $data->fname);
    $lname = mysqli_real_escape_string($conn, $data->lname);
    $email = mysqli_real_escape_string($conn, $data->email);
    $phone = isset($data->phone) ? mysqli_real_escape_string($conn, $data->phone) : '';

    // تشفير كلمة المرور (أمان عالي)
    $password = password_hash($data->password, PASSWORD_DEFAULT);

    // بيانات جدول المريض (Patient)
    $address = isset($data->address) ? mysqli_real_escape_string($conn, $data->address) : '';
    $medicalHistory = isset($data->medicalHistory) ? mysqli_real_escape_string($conn, $data->medicalHistory) : '';
    $dob = isset($data->dob) && !empty($data->dob) ? "'" . mysqli_real_escape_string($conn, $data->dob) . "'" : "NULL";

    // خطوط الطول والعرض للخريطة (إذا لم تتوفر نضعها NULL)
    $lat = isset($data->lat) && !empty($data->lat) ? (float)$data->lat : "NULL";
    $lng = isset($data->lng) && !empty($data->lng) ? (float)$data->lng : "NULL";

    // ==========================================
    // 5. التأكد من أن الإيميل غير مسجل مسبقاً
    // ==========================================
    $check_email = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        // إذا كان الإيميل موجود، نرد على الموبايل برسالة خطأ
        echo json_encode(["status" => "error", "message" => "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!"]);
        exit();
    }

    // ==========================================
    // 6. بدء عملية الحفظ (Transaction)
    // نستخدم الـ Transaction لضمان أن يتم الحفظ في الجدولين معاً أو يتم إلغاء كل شيء في حال حدوث خطأ
    // ==========================================
    mysqli_begin_transaction($conn);

    try {
        // أ) الحفظ في جدول المستخدمين (User) أولاً
        // نضع RoleID = 3 لأن المريض دوره في النظام هو 3
        $queryUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID) 
                      VALUES ('$fname', '$lname', '$email', '$password', '$phone', 3)";
        mysqli_query($conn, $queryUser);

        // الحصول على الـ ID الخاص بالمستخدم الذي تم إنشاؤه للتو
        $userId = mysqli_insert_id($conn);

        // ب) الحفظ في جدول المرضى (Patient) باستخدام الـ ID الذي حصلنا عليه
        $queryPatient = "INSERT INTO Patient (PatientID, Address, Latitude, Longitude, DOB, MedicalHistory) 
                         VALUES ($userId, '$address', $lat, $lng, $dob, '$medicalHistory')";
        mysqli_query($conn, $queryPatient);

        // ج) تأكيد الحفظ
        mysqli_commit($conn);

        // الرد على الموبايل برسالة نجاح
        echo json_encode(["status" => "success", "message" => "تم إنشاء حسابك بنجاح!"]);
    } catch (Exception $e) {
        // في حال حدوث أي خطأ، نتراجع عن كل الإدخالات السابقة
        mysqli_rollback($conn);

        // الرد على الموبايل برسالة خطأ
        echo json_encode(["status" => "error", "message" => "حدث خطأ في السيرفر أثناء التسجيل: " . $e->getMessage()]);
    }
} else {
    // إذا أرسل الموبايل بيانات ناقصة
    echo json_encode(["status" => "error", "message" => "الرجاء تعبئة جميع الحقول الأساسية!"]);
}
