<?php
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';
$dir = ($current_lang == 'ar') ? 'rtl' : 'ltr';

$lang = array();

if ($current_lang == 'ar') {
    // عام
    $lang['dashboard'] = "لوحة القيادة";
    $lang['pharmacies'] = "إدارة الصيدليات";
    $lang['patients'] = "إدارة المرضى";
    $lang['logout'] = "تسجيل الخروج";
    $lang['actions'] = "الإجراءات";
    $lang['status'] = "الحالة";
    $lang['switch_lang_text'] = "English";
    $lang['switch_lang_code'] = "en";

    // الداشبورد
    $lang['active_pharma'] = "الصيدليات العاملة";
    $lang['pending_req'] = "طلبات الانضمام";
    $lang['total_patients'] = "إجمالي المرضى";
    $lang['total_orders'] = "عمليات الشراء";
    $lang['map_title'] = "خريطة انتشار الصيدليات";
    $lang['live_update'] = "تحديث مباشر";
    $lang['pharma_info'] = "معلومات الصيدلية";
    $lang['click_map'] = "اضغط على نقطة حمراء في الخريطة لعرض التفاصيل.";

    // معلومات الصيدلية (الجافاسكربت)
    $lang['pharmacist_name'] = "الصيدلاني";
    $lang['address'] = "العنوان";
    $lang['working_hours'] = "الدوام";
    $lang['phone'] = "الهاتف";
    $lang['license_num'] = "رقم الترخيص";
    $lang['not_available'] = "غير متوفر";

    // جدول الصيدليات
    $lang['pharmacy_name'] = "الصيدلية";
    $lang['owner'] = "المالك";
    $lang['phone'] = "الهاتف";
    $lang['location_work'] = "الموقع / العمل";
    $lang['join_date'] = "تاريخ الانضمام";
    $lang['active'] = "نشط";
    $lang['pending'] = "معلق";

    // جدول المرضى
    $lang['patient_name'] = "المريض";
    $lang['contact_info'] = "بيانات الاتصال";
    $lang['age'] = "العمر";
    $lang['years'] = "سنة";
    $lang['not_specified'] = "غير محدد";
    $lang['total_count'] = "العدد الإجمالي";

    // جدول التقارير والإحصائيات
    $lang['reports'] = "التقارير والإحصائيات";
    $lang['finance'] = "الإدارة المالية";
    $lang['roles'] = "الصلاحيات والإدارة";
    $lang['reports_analytics'] = "التقارير والإحصائيات";
    $lang['orders'] = " الطلبات";
    $lang['orders_last_week'] = "حركة الطلبات (آخر 7 أيام)";
    $lang['top_medicines'] = "الأدوية الأكثر طلباً";
    $lang['no_data'] = "لا يوجد بيانات لعرضها";

    // SweetAlert Translations
    $lang['swal_title'] = "هل أنت متأكد؟";
    $lang['swal_text'] = "لن تتمكن من التراجع عن هذا الإجراء!";
    $lang['swal_confirm'] = "نعم، احذف!";
    $lang['swal_cancel'] = "إلغاء";
    $lang['suspend_title'] = "تعليق الحساب؟";
    $lang['suspend_text'] = "سيتم إيقاف الصيدلية مؤقتاً عن العمل.";
    $lang['suspend_confirm'] = "نعم، علق الحساب";

    // Topbar
    $lang['search_placeholder'] = "بحث عن صيدلية، مريض، طلب...";
    $lang['notifications'] = "الإشعارات";
    $lang['no_notifications'] = "لا توجد إشعارات جديدة";
    $lang['view_all'] = "عرض الكل";
    $lang['profile'] = "الملف الشخصي";
    $lang['settings'] = "الإعدادات";
    $lang['new_pharmacy_req'] = "طلب انضمام صيدلية جديد";
} else {
    // General
    $lang['dashboard'] = "Dashboard";
    $lang['pharmacies'] = "Pharmacies";
    $lang['patients'] = "Patients";
    $lang['logout'] = "Logout";
    $lang['actions'] = "Actions";
    $lang['status'] = "Status";
    $lang['switch_lang_text'] = "عربي";
    $lang['switch_lang_code'] = "ar";

    // Dashboard
    $lang['active_pharma'] = "Active Pharmacies";
    $lang['pending_req'] = "Pending Requests";
    $lang['total_patients'] = "Total Patients";
    $lang['total_orders'] = "Total Orders";
    $lang['map_title'] = "Pharmacies Map";
    $lang['live_update'] = "Live";
    $lang['pharma_info'] = "Pharmacy Info";
    $lang['click_map'] = "Click a red dot on the map to view details.";

    // Pharmacy Info (JS)
    $lang['pharmacist_name'] = "Pharmacist Name";
    $lang['address'] = "Address";
    $lang['working_hours'] = " Working Hours";
    $lang['phone'] = "Phone";
    $lang['license_num'] = "License Number";
    $lang['not_available'] = "N/A";

    // Pharmacies Table
    $lang['pharmacy_name'] = "Pharmacy";
    $lang['owner'] = "Owner";
    $lang['phone'] = "Phone";
    $lang['location_work'] = "Location / Hours";
    $lang['join_date'] = "Join Date";
    $lang['active'] = "Active";
    $lang['pending'] = "Pending";

    // Patients Table
    $lang['patient_name'] = "Patient";
    $lang['contact_info'] = "Contact Info";
    $lang['age'] = "Age";
    $lang['years'] = "years";
    $lang['not_specified'] = "Not specified";
    $lang['total_count'] = "Total Count";

    // Reports & Analytics
    $lang['reports'] = "Reports & Analytics";
    $lang['finance'] = "Finance & Billing";
    $lang['roles'] = "Roles & Permissions";
    $lang['reports_analytics'] = "Reports & Analytics";
    $lang['orders'] = "Orders";
    $lang['orders_last_week'] = "Orders Trend (Last 7 Days)";
    $lang['top_medicines'] = "Top Medicines";
    $lang['no_data'] = "No data available";

    // SweetAlert Translations
    $lang['swal_title'] = "Are you sure?";
    $lang['swal_text'] = "You won't be able to revert this!";
    $lang['swal_confirm'] = "Yes, delete it!";
    $lang['swal_cancel'] = "Cancel";
    $lang['suspend_title'] = "Suspend Account?";
    $lang['suspend_text'] = "Pharmacy will be temporarily paused.";
    $lang['suspend_confirm'] = "Yes, Suspend";

    // Topbar
    $lang['search_placeholder'] = "Search pharmacy, patient, order...";
    $lang['notifications'] = "Notifications";
    $lang['no_notifications'] = "No new notifications";
    $lang['view_all'] = "View All";
    $lang['profile'] = "My Profile";
    $lang['settings'] = "Settings";
    $lang['new_pharmacy_req'] = "New Pharmacy Request";
}
