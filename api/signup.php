<?php
// ==========================================
// 1. إعدادات السماحيات (CORS & Headers)
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ==========================================
// 2. استدعاء ملف الاتصال بقاعدة البيانات
// ==========================================
include_once '../config/database.php';

// ==========================================
// 3. استلام البيانات القادمة من الموبايل
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
    // تنظيف البيانات لحماية قاعدة البيانات من الاختراق
    $fname = mysqli_real_escape_string($conn, $data->fname);
    $lname = mysqli_real_escape_string($conn, $data->lname);
    $email = mysqli_real_escape_string($conn, $data->email);
    $phone = isset($data->phone) ? mysqli_real_escape_string($conn, $data->phone) : '';

    $password_input = $data->password;

    // التحقق من قوة كلمة المرور (8 خانات على الأقل، حرف كبير، حرف صغير، ورقم)
    if (strlen($password_input) < 8 || !preg_match("/[A-Z]/", $password_input) || !preg_match("/[a-z]/", $password_input) || !preg_match("/[0-9]/", $password_input)) {
        echo json_encode([
            "status" => "error",
            "message" => "كلمة المرور ضعيفة! يجب أن تتكون من 8 خانات على الأقل، وتحتوي على حرف كبير، حرف صغير، ورقم."
        ]);
        exit();
    }

    // تشفير كلمة المرور بأمان
    $password = password_hash($password_input, PASSWORD_DEFAULT);

    // بيانات جدول المريض (Patient)
    $address = isset($data->address) ? mysqli_real_escape_string($conn, $data->address) : '';
    $medicalHistory = isset($data->medicalHistory) ? mysqli_real_escape_string($conn, $data->medicalHistory) : '';
    $dob = isset($data->dob) && !empty($data->dob) ? "'" . mysqli_real_escape_string($conn, $data->dob) . "'" : "NULL";

    // خطوط الطول والعرض للخريطة
    $lat = isset($data->lat) && !empty($data->lat) ? (float)$data->lat : "NULL";
    $lng = isset($data->lng) && !empty($data->lng) ? (float)$data->lng : "NULL";

    // ==========================================
    // 5. التأكد من أن الإيميل غير مسجل مسبقاً
    // ==========================================
    $check_email = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo json_encode(["status" => "error", "message" => "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!"]);
        exit();
    }

    // ==========================================
    // 6. بدء عملية الحفظ (Transaction)
    // ==========================================
    mysqli_begin_transaction($conn);

    try {
        // الحفظ في جدول المستخدمين (User) أولاً
        $queryUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                      VALUES ('$fname', '$lname', '$email', '$password', '$phone', 3)";
        mysqli_query($conn, $queryUser);

        // الحصول على الـ ID الخاص بالمستخدم الذي تم إنشاؤه
        $userId = mysqli_insert_id($conn);

        // الحفظ في جدول المرضى (Patient)
        $queryPatient = "INSERT INTO Patient (PatientID, Address, Latitude, Longitude, DOB, MedicalHistory)
                         VALUES ($userId, '$address', $lat, $lng, $dob, '$medicalHistory')";
        mysqli_query($conn, $queryPatient);

        // تأكيد الحفظ
        mysqli_commit($conn);

        echo json_encode(["status" => "success", "message" => "تم إنشاء حسابك بنجاح!"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => "حدث خطأ في السيرفر أثناء التسجيل: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "الرجاء تعبئة جميع الحقول الأساسية!"]);
}
