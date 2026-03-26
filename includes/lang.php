<?php
//==================================================
//  نظام إدارة اللغة والاتجاه (RTL/LTR)
//==================================================

// 1. بدء أو استئناف الجلسة لتخزين اختيار اللغة.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. التحقق إذا كان المستخدم قد طلب تغيير اللغة عبر الرابط (مثال: ?lang=en).
if (isset($_GET['lang'])) {
    // تخزين اللغة المختارة في الجلسة.
    $_SESSION['lang'] = $_GET['lang'];
}

// 3. تحديد اللغة الحالية: إما من الجلسة، أو تعيين 'ar' كلغة افتراضية.
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';

// 4. تحديد اتجاه الصفحة (dir) بناءً على اللغة الحالية.
$dir = ($current_lang == 'ar') ? 'rtl' : 'ltr';

// 5. إنشاء مصفوفة اللغة.
$lang = array();

//==================================================
//  قسم الترجمة (العربية والإنجليزية)
//==================================================
// اللغة العربية  

    if ($current_lang == 'ar') {
        // --- صفجة الداش بورد ---
            // --- كلمات عامة مشتركة ---
                $lang['dashboard'] = "لوحة القيادة";
                $lang['admin_panel'] = "لوحة الإدارة";
                $lang['pharmacy_system'] = "نظام إدارة الصيدلية";

            // --- كروت الإحصائيات ---
                $lang['active_pharma'] = "الصيدليات العاملة";
                $lang['pending_req'] = "طلبات الانضمام";
                $lang['total_patients'] = "إجمالي المرضى";

            // --- قسم الخريطة ---
                $lang['map_title'] = "خريطة انتشار الصيدليات";
                $lang['filter_all'] = "الكل";
                $lang['filter_active'] = "نشط";
                $lang['filter_pending'] = "معلق";
                $lang['pharma_info'] = "معلومات الصيدلية";
                $lang['click_map_prompt'] = "اضغط على أي نقطة في الخريطة لعرض التفاصيل.";

            // --- ترجمات خاصة بـ JavaScript (لوحة معلومات الصيدلية) ---


                $lang['js_pharmacist_name'] = "الصيدلاني";
                $lang['js_address'] = "العنوان";
                $lang['js_working_hours'] = "الدوام";
                $lang['js_phone'] = "الهاتف";
                $lang['js_license_number'] = "رقم الترخيص";
                $lang['js_not_available'] = "غير متوفر"

        // --- صفحة إدارة الصيدليات ---
            // --- صفحة إدارة الصيدليات ---
                $lang['pharmacies_page_title'] = "إدارة الصيدليات";
                $lang['search_pharmacy_placeholder'] = "ابحث بالاسم أو رقم الترخيص...";
                $lang['contact_info'] = "معلومات الاتصال";
                $lang['location_and_hours'] = "الموقع وساعات العمل";
                $lang['approve_tooltip'] = "قبول وتفعيل الصيدلية";
                $lang['reject_tooltip'] = "رفض وحذف الطلب";
                $lang['suspend_tooltip'] = "إيقاف الصيدلية مؤقتاً";
                $lang['delete_tooltip'] = "حذف الصيدلية نهائياً";
                $lang['no_pharmacies_found_title'] = "لا توجد صيدليات مطابقة";
                $lang['no_pharmacies_found_desc'] = "حاول تغيير كلمة البحث أو اختيار فلتر آخر.";

            // --- رسائل التأكيد (SweetAlert) ---
                $lang['swal_title'] = "هل أنت متأكد؟";
                $lang['swal_text'] = "لن تتمكن من التراجع عن هذا الإجراء!";
                $lang['swal_confirm_delete'] = "نعم، احذف!";
                $lang['swal_cancel'] = "إلغاء";
                $lang['swal_suspend_title'] = "تعليق الحساب؟";
                $lang['swal_suspend_text'] = "سيتم إيقاف الصيدلية مؤقتاً عن استقبال الطلبات.";
                $lang['swal_suspend_confirm'] = "نعم، علّق الحساب";
                $lang['swal_reject_title'] = "رفض الطلب؟";
                $lang['swal_reject_text'] = "سيتم حذف طلب الانضمام هذا نهائياً.";
                $lang['swal_reject_confirm'] = "نعم، ارفض الطلب";  

        // --- صفحة إدارة المرضى ---
            // --- صفحة إدارة المرضى ---

                $lang['patients_page_title'] = "إدارة المرضى";
                $lang['search_patient_placeholder'] = "ابحث بالاسم الأول أو اسم العائلة...";
                $lang['age_label'] = "العمر";
                $lang['years_unit'] = "سنة";
                $lang['address_label'] = "العنوان";
                $lang['join_date_label'] = "تاريخ الانضمام";
                $lang['delete_patient_tooltip'] = "حذف المريض نهائياً";
                $lang['not_specified'] = "غير محدد";
                $lang['no_patients_found_title'] = "لا يوجد مرضى مطابقين";
                $lang['no_patients_found_desc'] = "تأكد من كتابة الاسم بشكل صحيح أو حاول مرة أخرى.";

            // --- رسائل التأكيد (SweetAlert) للمرضى ---
                $lang['swal_delete_patient_title'] = "هل أنت متأكد من حذف المريض؟";
                $lang['swal_delete_patient_text'] = "سيتم حذف حساب المريض وكل الطلبات والمحادثات المرتبطة به بشكل نهائي!";
                $lang['swal_confirm_delete_patient'] = "نعم، احذف المريض";

        // --- صفحة تسجيل الدخول ---
                    $lang['login_page_title'] = "PharmaSmart - تسجيل الدخول";
                    $lang['app_name'] = "PharmaSmart";
                    $lang['email_placeholder'] = "بريدك الإلكتروني";
                    $lang['password_placeholder'] = "••••••••";
                    $lang['login_error_title'] = "خطأ في تسجيل الدخول";
                    $lang['ok_button'] = "حسناً";
                    $lang['error_patient_role'] = "عذراً، نظام الويب مخصص للصيادلة والإدارة فقط. يرجى استخدام تطبيق الموبايل.";
                    $lang['error_pending_approval'] = "حسابك معلق حالياً، يرجى انتظار موافقة الإدارة.";
                    $lang['error_wrong_password'] = "كلمة المرور غير صحيحة.";
                    $lang['error_email_not_found'] = "البريد الإلكتروني غير مسجل.";

} else {
    
// اللغة الإنجليزية
    //---admin page---
        // --- Common & General Strings ---
                $lang['dashboard'] = "Dashboard";
                $lang['admin_panel'] = "Admin Panel";
                $lang['pharmacy_system'] = "Pharmacy Management System";

        // --- Statistics Cards ---
            $lang['active_pharma'] = "Active Pharmacies";
            $lang['pending_req'] = "Pending Requests";
            $lang['total_patients'] = "Total Patients";

        // --- Map Section ---
            $lang['map_title'] = "Pharmacies Distribution Map";
            $lang['filter_all'] = "All";
            $lang['filter_active'] = "Active";
            $lang['filter_pending'] = "Pending";
            $lang['pharma_info'] = "Pharmacy Info";
            $lang['click_map_prompt'] = "Click on any point on the map to view details.";



        // --- JavaScript Specific Translations (Pharmacy Info Panel) ---
                $lang['js_pharmacist_name'] = "Pharmacist";
                $lang['js_address'] = "Address";
                $lang['js_working_hours'] = "Working Hours";
                $lang['js_phone'] = "Phone";
                $lang['js_license_number'] = "License No.";
                $lang['js_not_available'] = "N/A";

        // --- Pharmacies Management Page ---
            $lang['pharmacies_page_title'] = "Pharmacies Management";
            $lang['search_pharmacy_placeholder'] = "Search by name or license number...";
            $lang['contact_info'] = "Contact Info";
            $lang['location_and_hours'] = "Location & Working Hours";
            $lang['approve_tooltip'] = "Approve & Activate Pharmacy";
            $lang['reject_tooltip'] = "Reject & Delete Request";
            $lang['suspend_tooltip'] = "Suspend Temporarily";
            $lang['delete_tooltip'] = "Delete Permanently";
            $lang['no_pharmacies_found_title'] = "No Matching Pharmacies Found";
            $lang['no_pharmacies_found_desc'] = "Try changing the search term or selecting a different filter.";

        // --- Confirmation Messages (SweetAlert) ---
        $lang['swal_title'] = "Are you sure?";
        $lang['swal_text'] = "You won't be able to revert this!";
        $lang['swal_confirm_delete'] = "Yes, delete it!";
        $lang['swal_cancel'] = "Cancel";
        $lang['swal_suspend_title'] = "Suspend Account?";
        $lang['swal_suspend_text'] = "The pharmacy will be temporarily paused from receiving orders.";
        $lang['swal_suspend_confirm'] = "Yes, suspend";
        $lang['swal_reject_title'] = "Reject Request?";
        $lang['swal_reject_text'] = "This join request will be permanently deleted.";
        $lang['swal_reject_confirm'] = "Yes, reject";
          // --- Patients Management Page (users) ---
       // --- Patients Management Page ---
        $lang['patients_page_title'] = "Patients Management";
        $lang['search_patient_placeholder'] = "Search by first or last name...";
        $lang['age_label'] = "Age";
        $lang['years_unit'] = "years";
        $lang['address_label'] = "Address";
        $lang['join_date_label'] = "Join Date";
        $lang['delete_patient_tooltip'] = "Delete Patient Permanently";
        $lang['not_specified'] = "Not Specified";
        $lang['no_patients_found_title'] = "No Matching Patients Found";
        $lang['no_patients_found_desc'] = "Make sure the name is spelled correctly or try again.";
        // --- Confirmation Messages (SweetAlert) for Patients ---
            $lang['swal_delete_patient_title'] = "Are you sure you want to delete this patient?";
            $lang['swal_delete_patient_text'] = "The patient's account and all associated orders and chats will be permanently deleted!";
            $lang['swal_confirm_delete_patient'] = "Yes, delete patient";
    //---auth page---
        // --- Login ---
            $lang['login_page_title'] = "PharmaSmart - Login";
            $lang['app_name'] = "PharmaSmart";
            $lang['email_placeholder'] = "Your Email Address";
            $lang['password_placeholder'] = "••••••••";
            $lang['login_error_title'] = "Login Error";
            $lang['ok_button'] = "OK";
            $lang['error_patient_role'] = "Sorry, the web system is for Pharmacists & Admins only. Please use the mobile app.";
            $lang['error_pending_approval'] = "Your account is pending, please wait for admin approval.";
            $lang['error_wrong_password'] = "Incorrect password.";
            $lang['error_email_not_found'] = "Email not registered.";
        
}
?>