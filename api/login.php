<?php
// ==========================================
// 1. إعدادات السماحيات (CORS & Headers)
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ==========================================
// 2. الاتصال بقاعدة البيانات
// ==========================================
include_once '../config/database.php';

// استلام البيانات من الموبايل
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {

    $email = mysqli_real_escape_string($conn, $data->email);
    $password = $data->password;

    // البحث عن المستخدم في قاعدة البيانات (دور المريض RoleID = 3)
    $query = "SELECT * FROM User WHERE Email = '$email' AND RoleID = 3";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // التحقق من صحة كلمة المرور (سواء كانت مشفرة أو نص عادي للتوافق)
        if (password_verify($password, $user['Password']) || $password === $user['Password']) {

            // الرد بالنجاح مع إرسال بيانات المريض للتطبيق ليحفظها
            echo json_encode([
                "status" => "success",
                "message" => "تم تسجيل الدخول بنجاح",
                "user" => [
                    "id" => $user['UserID'],
                    "fname" => $user['Fname'],
                    "lname" => $user['Lname'],
                    "email" => $user['Email'],
                    "phone" => $user['Phone']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "كلمة المرور غير صحيحة!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "لا يوجد حساب مريض مسجل بهذا البريد الإلكتروني!"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "الرجاء إدخال البريد الإلكتروني وكلمة المرور!"]);
}
