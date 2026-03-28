<?php
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';
$dir = ($current_lang == 'ar') ? 'rtl' : 'ltr';

$lang = array();

if ($current_lang == 'ar') {

    // تسجيل الدخول | Login Page
    $lang['login_title'] = "تسجيل الدخول";
    $lang['login_subtitle'] = "بوابتك لإدارة الصيدلية باحترافية";
    $lang['email'] = "البريد الإلكتروني";
    $lang['password'] = "كلمة المرور";
    $lang['btn_login'] = "تسجيل الدخول";
    $lang['new_account'] = "صيدلية جديدة؟";
    $lang['register_link'] = "قدم طلب انضمام";

    // تسجيل صيدلية جديدة | Register Page
    $lang['email_exists_error'] = "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!";
    $lang['license_exists_error'] = "عذراً، رقم الترخيص هذا مسجل مسبقاً لصيدلية أخرى!";
    $lang['db_error'] = "حدث خطأ في قاعدة البيانات:";
    $lang['location_error'] = "يرجى تحديد موقع الصيدلية بدقة على الخريطة.";
    $lang['registration_success'] = "تم إرسال طلب انضمامك بنجاح! يرجى انتظار تفعيل حسابك من الإدارة.";
    $lang['registration_error'] = "حدث خطأ أثناء التسجيل. الرجاء المحاولة مرة أخرى.";
    $lang['user_creation_error'] = "حدث خطأ أثناء إنشاء حساب المستخدم. الرجاء المحاولة مرة أخرى.";
    $lang['register_title'] = "تسجيل صيدلية جديدة - PharmaSmart";
    $lang['register_title_short'] = "تسجيل صيدلية جديدة";
    $lang['register_subtitle'] = "قم بملء بياناتك بدقة ليتم مراجعتها من قبل الإدارة";
    
    // خطوات التسجيل | Registration Steps
    $lang['personal_info_step'] = "بيانات شخصية";
    $lang['pharmacy_info_step'] = "بيانات الصيدلية";
    $lang['geographic_location_step'] = "الموقع الجغرافي";

    $lang['personal_info'] = "البيانات الشخصية";
    $lang['first_name'] = "الاسم الأول";
    $lang['last_name'] = "اسم العائلة";
    $lang['phone'] = "رقم الهاتف";
    $lang['pharmacy_info'] = "بيانات الصيدلية";
    $lang['pharmacy_name'] = "اسم الصيدلية الرسمي";
    $lang['license_num'] = "رقم الترخيص من وزارة الصحة";
    $lang['address'] = "العنوان الوصفي (المدينة - الشارع)";
    $lang['working_hours'] = "ساعات الدوام";
    $lang['pharmacy_logo'] = "شعار الصيدلية (اختياري)";
    $lang['choose_logo'] = "اختر شعار الصيدلية";
    $lang['upload'] = "رفع";
    $lang['location_picker'] = "تحديد الموقع الجغرافي";
    $lang['location_description'] = "يرجى الضغط على الخريطة لتحديد موقع الصيدلية بدقة، سيساعد هذا المرضى في العثور عليك عبر التطبيق.";
    $lang['register_button'] = "إرسال طلب الانضمام";
    $lang['already_have_account'] = "لديك حساب بالفعل؟";
    $lang['login_link'] = "العودة لتسجيل الدخول";
    $lang['success'] = "اكتمل الطلب!";
    $lang['error'] = "خطأ";
    $lang['warning_title'] = "تنبيه!";

    // أخطاء تسجيل الدخول | Login Errors
    $lang['err_patient'] = "عذراً، نظام الويب مخصص للصيادلة والإدارة فقط. يرجى استخدام تطبيق الموبايل.";
    $lang['err_pending'] = "حسابك معلق حالياً، يرجى انتظار موافقة الإدارة.";
    $lang['err_incomplete_account'] = "حساب غير مكتمل، يرجى التواصل مع الإدارة.";
    $lang['err_pass'] = "كلمة المرور غير صحيحة.";
    $lang['err_email'] = "البريد الإلكتروني غير مسجل.";
    $lang['err_title'] = "خطأ في تسجيل الدخول";
    $lang['ok_btn'] = "حسناً";

    // عام ومصطلحات متكررة | General & Common Terms
    $lang['dashboard'] = "لوحة القيادة";
    $lang['pharmacies'] = "إدارة الصيدليات";
    $lang['patients'] = "إدارة المرضى";
    $lang['logout'] = "تسجيل الخروج";
    $lang['actions'] = "الإجراءات";
    $lang['status'] = "الحالة";
    $lang['dashboard_link'] = "الرئيسية";
    $lang['medicines_stock'] = "الأدوية والمخزون";
    $lang['orders'] = "الطلبات";
    $lang['chats'] = "المحادثات";
    $lang['switch_lang_text'] = "English";
    $lang['switch_lang_code'] = "en";

    // القائمة الجانبية (روابط إضافية) | Sidebar Links
    $lang['system_medicines'] = "الأدوية";
    $lang['reports'] = "التقارير";
    $lang['sales'] = "المبيعات";
    $lang['suppliers'] = "الموردين";
    $lang['coming_soon_badge'] = "قريباً";

    // رسائل الميزات القادمة (Sidebar JS) | Coming Soon Messages
    $lang['msg_admin_catalog'] = 'جاري العمل على واجهة إدارة "الأدوية الموحدة" والتي ستمكنك من إضافة وتعديل الأدوية والتصنيفات المركزية للمنصة.';
    $lang['msg_admin_reports'] = 'نعمل على تصميم لوحة "التقارير الشاملة" لتعرض لك إحصائيات المبيعات، نمو المنصة، والمنتجات الأكثر طلباً.';
    $lang['msg_chat'] = 'ميزة المحادثات المباشرة قيد التطوير وسيتم إتاحتها قريباً لتسهيل تواصلك المباشر والآمن مع المرضى.';
    $lang['msg_pharmacist_reports'] = 'نعمل على تصميم لوحة "التقارير الشاملة" لتعرض لك إحصائيات المبيعات ونمو الصيدلية في رسوم بيانية تفاعلية.';
    $lang['msg_sales'] = 'ميزة إدارة المشتريات والمبيعات قيد التطوير وسيتم إتاحتها قريباً لتسهيل تتبع عمليات البيع والشراء في الصيدلية.';
    $lang['msg_suppliers'] = 'ميزة إدارة الموردين قيد التطوير وسيتم إتاحتها قريباً لتسهيل تتبع معلومات الموردين والتعاملات معهم.';
    $lang['msg_feature_default'] = 'هذه الميزة قيد التطوير وسيتم إتاحتها قريباً.';

    // لوحة التحكم (المدير) | Admin Dashboard
    $lang['admin_panel'] = "لوحة الإدارة";
    $lang['pharmacy_system'] = "نظام إدارة الصيدلية";
    $lang['active_pharma'] = "الصيدليات العاملة";
    $lang['pending_req'] = "طلبات الانضمام";
    $lang['total_patients'] = "إجمالي المرضى";
    $lang['total_orders'] = "عمليات الشراء";
    $lang['map_title'] = "خريطة انتشار الصيدليات";
    $lang['filter_all'] = "الكل";
    $lang['filter_active'] = "نشط";
    $lang['filter_pending'] = "معلق";
    $lang['live_update'] = "تحديث مباشر";
    $lang['pharma_info'] = "معلومات الصيدلية";
    $lang['click_map'] = "اضغط على أي نقطة في الخريطة لعرض التفاصيل.";
    
    // معلومات الصيدلية في الخريطة | Pharmacy Info (JS Map)
    $lang['pharmacist_name'] = "الصيدلاني";
    $lang['address'] = "العنوان";
    $lang['working_hours'] = "الدوام";
    $lang['phone'] = "الهاتف";
    $lang['license_num'] = "رقم الترخيص";
    $lang['not_available'] = "غير متوفر";

    // جدول إدارة الصيدليات (الأدمن) | Pharmacies Table (Admin)
    $lang['pharmacy_name'] = "الصيدلية";
    $lang['owner'] = "المالك";
    $lang['contact_info'] = "الاتصال";
    $lang['location_work'] = "الموقع / العمل";
    $lang['join_date'] = "تاريخ الانضمام";
    $lang['active'] = "نشط";
    $lang['pending'] = "معلق";
    $lang['search_pharmacy'] = "ابحث عن صيدلية بالاسم...";
    $lang['approve_activate'] = "قبول وتفعيل";
    $lang['reject_request'] = "رفض الطلب";
    $lang['suspend_temp'] = "إيقاف مؤقت";
    $lang['delete_permanently'] = "حذف نهائي";
    $lang['no_matching_pharmacies'] = "لا توجد صيدليات مطابقة";
    $lang['try_changing_search'] = "حاول تغيير كلمة البحث أو اختيار فلتر آخر.";

    // جدول إدارة المرضى | Patients Table
    $lang['patient_name'] = "المريض";
    $lang['age'] = "العمر";
    $lang['years'] = "سنة";
    $lang['not_specified'] = "غير محدد";
    $lang['search_patient'] = "ابحث عن مريض بالاسم...";
    $lang['no_data'] = "لا يوجد بيانات";
    $lang['delete_patient'] = "حذف المريض";
    $lang['no_matching_patients'] = "لا يوجد مرضى مطابقين";
    $lang['check_patient_name'] = "تأكد من كتابة الاسم بشكل صحيح.";

    // الأدوار | Roles
    $lang['admin'] = "مدير";
    $lang['pharmacist'] = "صيدلي";

    // تنبيهات الحذف والإيقاف | SweetAlert Translations
    $lang['swal_title'] = "هل أنت متأكد؟";
    $lang['swal_text'] = "لن تتمكن من التراجع عن هذا الإجراء!";
    $lang['swal_confirm'] = "نعم، احذف!";
    $lang['swal_cancel'] = "إلغاء";
    $lang['suspend_title'] = "تعليق الحساب؟";
    $lang['suspend_text'] = "سيتم إيقاف الصيدلية مؤقتاً عن العمل.";
    $lang['suspend_confirm'] = "نعم، علق الحساب";
    $lang['coming_soon_title'] = "قريباً جداً!";

    // لوحة التحكم (الصيدلي) | Pharmacist Dashboard
    $lang['todays_sales'] = "مبيعات اليوم";
    $lang['currency'] = "₪";
    $lang['pending_orders'] = "قيد الانتظار";
    $lang['low_stock_items'] = "نواقص المخزون";
    $lang['expiring_soon'] = "قريب الانتهاء";
    $lang['recent_orders'] = "أحدث الطلبات";
    $lang['order_id'] = "رقم الطلب";
    $lang['time'] = "الوقت";
    $lang['customer'] = "العميل";
    $lang['amount'] = "المبلغ";
    $lang['items'] = "العناصر";
    $lang['low_stock_alert'] = "تنبيه نقص المخزون";
    $lang['out_of'] = "من أصل";
    $lang['days'] = "يوم";
    $lang['view_all'] = "عرض الكل";
    $lang['no_recent_orders'] = "لا يوجد طلبات حديثة";
    $lang['stock_excellent'] = "المخزون ممتاز، لا يوجد نواقص";
    $lang['manage_inventory'] = "إدارة المخزون";
    $lang['no_low_stock_items'] = "لا توجد عناصر ناقصة في المخزون";
    $lang['new_badge'] = "جديد";
    $lang['needs_your_approval'] = "تحتاج موافقتك!";
    $lang['no_pending_orders_now'] = "لا توجد طلبات قيد الانتظار حالياً.";
    $lang['items_word'] = "عناصر";
    $lang['qty_word'] = "الكمية";
    $lang['update_qty'] = "تحديث الكمية";
    $lang['expiry_alerts'] = "تنبيهات الصلاحية";
    $lang['expired_word'] = "منتهي";
    $lang['soon_word'] = "قريباً";
    $lang['update_expiry'] = "تحديث الصلاحية";
    $lang['all_meds_valid'] = "جميع الأدوية صالحة تماماً";

    // صفحة الأدوية والمخزون | Medicines & Stock Page
    $lang['product_inventory'] = "مخزون الأدوية";
    $lang['search_inventory_placeholder'] = "ابحث في مخزونك (اسم، باركود)...";
    $lang['add_med_to_stock'] = "إضافة دواء للمخزون";
    $lang['add_new_med_to_stock'] = "إضافة دواء جديد للمخزون";
    $lang['product'] = "المنتج";
    $lang['category'] = "التصنيف";
    $lang['stock'] = "المخزون";
    $lang['price'] = "سعر البيع";
    $lang['cost'] = "التكلفة";
    $lang['margin'] = "الربح";
    $lang['expiry'] = "تاريخ الانتهاء";
    $lang['uncategorized'] = "غير مصنف";
    $lang['no_matching_meds'] = "لا يوجد أدوية مطابقة في مخزونك";
    $lang['try_different_search_add'] = "جرب كتابة اسم مختلف أو قم بإضافة دواء جديد لمخزونك.";
    $lang['edit_price_qty'] = "تعديل السعر/الكمية";
    $lang['remove_from_stock'] = "إزالة من المخزون";
    $lang['expired_since'] = "منتهي منذ ";
    $lang['expires_today'] = "ينتهي اليوم!";
    $lang['remaining'] = "باقي ";
    $lang['day_s'] = " يوم";

    // نافذة إضافة وتعديل الدواء | Modal Add/Edit Stock
    $lang['med_exists_error'] = "هذا الدواء موجود مسبقاً في مخزونك! يمكنك تعديل كميته بدلاً من إضافته مرة أخرى.";
    $lang['search_in_catalog'] = "1. ابحث عن الدواء في الكتالوج:";
    $lang['scan_barcode_manual'] = "مسح باركود / إضافة يدوية";
    $lang['all_categories'] = "جميع التصنيفات";
    $lang['search_med_name_scientific'] = "اكتب اسم الدواء التجاري أو العلمي...";
    $lang['suggested_meds'] = "الأدوية المقترحة لك:";
    $lang['latest_added_meds'] = "أحدث الأدوية المضافة للكتالوج:";
    $lang['custom_search_results'] = "نتائج البحث المخصصة:";
    $lang['cost_price_val'] = "التكلفة: ";
    $lang['no_matching_meds_catalog'] = "لم يتم العثور على أدوية مطابقة.";
    $lang['check_name_manual_soon'] = "تأكد من الاسم، الميزة اليدوية ستتوفر قريباً.";
    $lang['stock_price_alerts_settings'] = "إعدادات المخزون والسعر والتنبيهات";
    $lang['cost_price_ils'] = "سعر التكلفة (₪)";
    $lang['profit_0'] = "الربح: 0%";
    $lang['cost_determined_admin'] = "سعر التكلفة محدد من الإدارة";
    $lang['sell_price_public'] = "سعر البيع للجمهور (₪)";
    $lang['current_qty'] = "الكمية الحالية المتوفرة";
    $lang['low_stock_alert_limit'] = "حد التنبيه بنقص المخزون";
    $lang['expiry_date_label'] = "تاريخ انتهاء الصلاحية";
    $lang['expiry_alert_months_label'] = "تنبيه الصلاحية قبل (بالأشهر)";
    $lang['months'] = "أشهر";
    $lang['close_window'] = "إغلاق النافذة";
    $lang['back_to_search'] = "عودة للبحث";
    $lang['save_to_stock'] = "حفظ في المخزون";
    $lang['edit_stock_data'] = "تعديل بيانات المخزون";
    $lang['profit'] = "الربح: ";
    $lang['loss'] = "خسارة!";
    $lang['remove_med_title'] = "إزالة الدواء من المخزون؟";
    $lang['remove_med_text'] = "لن تتمكن من التراجع عن هذا، وسيتم مسحه من قائمة أدويتك المعروضة للمرضى، وإذا كان موجوداً في طلبات سابقة سيتم تعديل سعرها.";
    $lang['yes_remove'] = "نعم، إزالة";
    $lang['barcode_feature_soon'] = "ميزة 'مسح الباركود والإضافة اليدوية' قيد التطوير حالياً. سيتم إتاحتها قريباً لتسهيل إضافة الأدوية غير الموجودة في الكتالوج باستخدام كاميرا الجهاز.";

    // إدارة الطلبات | Orders Page
    $lang['manage_orders'] = "إدارة الطلبات";
    $lang['filter_processing'] = "جاري التجهيز";
    $lang['filter_delivered'] = "مكتملة";
    $lang['status_pending'] = "قيد الانتظار";
    $lang['status_processing'] = "جاري التجهيز";
    $lang['status_delivered'] = "مكتمل";
    $lang['status_rejected'] = "مرفوض";
    $lang['order_number'] = "رقم الطلب";
    $lang['order_date'] = "تاريخ الطلب";
    $lang['rx_alert'] = "يحتوي على أدوية مراقبة (يستلزم مراجعة الوصفة)";
    $lang['details_btn'] = "التفاصيل";
    $lang['delivered_btn'] = "تم التوصيل";
    $lang['no_orders'] = "لا توجد طلبات";
    $lang['no_orders_desc'] = "لا يوجد طلبات مطابقة للبحث أو الفلتر";

    // نافذة تفاصيل الطلب | Order Details Modal
    $lang['order_details'] = "تفاصيل الطلب";
    $lang['customer_info'] = "بيانات العميل";
    $lang['payment_summary'] = "ملخص الدفع";
    $lang['total_required'] = "الإجمالي المطلوب:";
    $lang['cod'] = "الدفع عند الاستلام (COD)";
    $lang['requested_items'] = "المنتجات المطلوبة";
    $lang['qty'] = "الكمية";
    $lang['item_total'] = "الإجمالي";
    $lang['attached_rx'] = "الوصفة الطبية المرفقة (مطلوبة)";
    $lang['rx_protocol'] = "بناءً على بروتوكول وزارة الصحة، يرجى التحقق مما يلي قبل قبول الطلب:";
    $lang['rx_verify_check'] = "أقر بأني راجعت الوصفة الطبية وصحتها.";
    $lang['close'] = "إغلاق";
    $lang['accept_prepare'] = "قبول وتجهيز";
    $lang['confirm_delivery'] = "تأكيد التسليم";

    // كلمات إضافية للنافذة | Extra Modal Translations
    $lang['order_summary'] = "ملخص الطلب";
    $lang['purchases'] = "المشتريات";
    $lang['prescription_rx'] = "الوصفة الطبية (Rx)";
    $lang['contains_controlled'] = "الطلب يحتوي على أدوية مراقبة";
    $lang['action_taken'] = "تم اتخاذ إجراء مسبقاً";
    $lang['reject'] = "رفض";
    $lang['delivered_successfully'] = "تم تسليم الطلب بنجاح";
    $lang['pickup_pharmacy'] = "استلام من الصيدلية";
    $lang['no_phone'] = "لا يوجد رقم";
    $lang['quantity'] = "الكمية:";

    // تنبيهات إدارة الطلبات | Orders Page Javascript Alerts
    $lang['rejection_reason_title'] = "سبب إلغاء الطلب:";
    $lang['controlled_meds_rx'] = "أدوية مراقبة (Rx)";
    $lang['rx_verification_required'] = "يلزم التحقق من الوصفة";
    $lang['extra_attachment_rx'] = "مرفق إضافي (وصفة)";
    $lang['security_alert'] = "تنبيه أمني";
    $lang['rx_review_required_alert'] = "يجب مراجعة الوصفة الطبية وإقرار صحتها قبل قبول الطلب.";
    $lang['reject_order_title'] = "رفض الطلب؟";
    $lang['reject_order_text'] = "يرجى كتابة سبب الرفض (سيظهر للمريض):";
    $lang['reject_order_placeholder'] = "مثال: الدواء غير متوفر حالياً...";
    $lang['confirm_reject'] = "تأكيد الرفض";
    $lang['reject_reason_required'] = "يجب كتابة سبب الرفض لإشعار المريض!";
    $lang['canceled_without_reason'] = "تم الإلغاء بدون كتابة سبب";
    $lang['accept_order_title'] = "قبول الطلب؟";
    $lang['accept_order_text'] = "سيتم إشعار المريض بأنك تقوم بتجهيز الطلب.";
    $lang['yes_accept'] = "نعم، أقبل";
    $lang['confirm_delivery_title'] = "تأكيد التسليم؟";
    $lang['confirm_delivery_text'] = "هل تم تسليم الطلب للعميل؟";
    $lang['yes_delivered'] = "نعم، تم التسليم";

    $lang['filter_rejected'] = "مرفوض";
    $lang['delivery_location'] = "موقع التوصيل (الخريطة)";
    $lang['verify_rx_btn'] = "اعتماد الوصفة";
    $lang['reject_rx_btn'] = "رفض الوصفة";
    $lang['no_location_provided'] = "لم يقم المريض بتحديد موقعه على الخريطة";
    $lang['all_orders'] = "الكل";
    $lang['search_order_patient'] = "ابحث برقم الطلب أو المريض...";
    $lang['customer_contact'] = "العميل / الاتصال";
    $lang['address_col'] = "العنوان";
    $lang['items_col'] = "الأصناف";
    $lang['total_amount'] = "الإجمالي";

} else {

    // تسجيل الدخول | Login Page
    $lang['login_title'] = "Login";
    $lang['login_subtitle'] = "Your gateway to professional pharmacy management";
    $lang['email'] = "Email Address";
    $lang['password'] = "Password";
    $lang['btn_login'] = "Login";
    $lang['new_account'] = "New Pharmacy?";
    $lang['register_link'] = "Submit Join Request";

    // تسجيل صيدلية جديدة | Register Page
    $lang['email_exists_error'] = "Sorry, this email is already registered!";
    $lang['license_exists_error'] = "Sorry, this license number is already registered to another pharmacy!";
    $lang['db_error'] = "Database error occurred:";
    $lang['location_error'] = "Please select the pharmacy location accurately on the map.";
    $lang['registration_success'] = "Your join request has been submitted successfully! Please wait for admin approval.";
    $lang['registration_error'] = "An error occurred during registration. Please try again.";
    $lang['user_creation_error'] = "An error occurred while creating the user account. Please try again.";
    $lang['register_title'] = "New Pharmacy Registration - PharmaSmart";
    $lang['register_title_short'] = "New Pharmacy Registration";
    $lang['register_subtitle'] = "Please fill in your details accurately for review by the administration.";
    
    // خطوات التسجيل | Registration Steps
    $lang['personal_info_step'] = "Personal Info";
    $lang['pharmacy_info_step'] = "Pharmacy Info";
    $lang['geographic_location_step'] = "Geographic Location";

    $lang['personal_info'] = "Personal Information";
    $lang['first_name'] = "First Name";
    $lang['last_name'] = "Last Name";
    $lang['phone'] = "Phone Number";
    $lang['pharmacy_info'] = "Pharmacy Information";
    $lang['pharmacy_name'] = "Official Pharmacy Name";
    $lang['license_num'] = "License Number from Ministry of Health";
    $lang['address'] = "Address (City - Street)";
    $lang['working_hours'] = "Working Hours";
    $lang['pharmacy_logo'] = "Pharmacy Logo (Optional)";
    $lang['choose_logo'] = "Choose Pharmacy Logo";
    $lang['upload'] = "Upload";
    $lang['location_picker'] = "Select Geographic Location";
    $lang['location_description'] = "Please click on the map to select the pharmacy location accurately, this will help patients find you through the app.";
    $lang['register_button'] = "Submit Join Request";
    $lang['already_have_account'] = "Already have an account?";
    $lang['login_link'] = "Return to Login";
    $lang['success'] = "Request completed!";
    $lang['error'] = "Error";
    $lang['warning_title'] = "Warning!";

    // أخطاء تسجيل الدخول | Login Errors
    $lang['err_patient'] = "Sorry, Web system is for Pharmacists & Admin only. Please use the mobile app.";
    $lang['err_pending'] = "Your account is pending, please wait for admin approval.";
    $lang['err_incomplete_account'] = "Incomplete account, please contact administration.";
    $lang['err_pass'] = "Incorrect password.";
    $lang['err_email'] = "Email not registered.";
    $lang['err_title'] = "Login Error";
    $lang['ok_btn'] = "OK";

    // عام ومصطلحات متكررة | General & Common Terms
    $lang['dashboard'] = "Dashboard";
    $lang['pharmacies'] = "Pharmacies";
    $lang['patients'] = "Patients";
    $lang['logout'] = "Logout";
    $lang['actions'] = "Actions";
    $lang['status'] = "Status";
    $lang['dashboard_link'] = "Dashboard";
    $lang['medicines_stock'] = "Medicines & Stock";
    $lang['orders'] = "Orders";
    $lang['chats'] = "Chats";
    $lang['switch_lang_text'] = "عربي";
    $lang['switch_lang_code'] = "ar";

    // القائمة الجانبية (روابط إضافية) | Sidebar Links
    $lang['system_medicines'] = "Medicines";
    $lang['reports'] = "Reports";
    $lang['sales'] = "Sales";
    $lang['suppliers'] = "Suppliers";
    $lang['coming_soon_badge'] = "SOON";

    // رسائل الميزات القادمة (Sidebar JS) | Coming Soon Messages
    $lang['msg_admin_catalog'] = 'The System Medicines Catalog management is under development. It will allow adding and editing centralized medicines and categories.';
    $lang['msg_admin_reports'] = 'Global Reports and Analytics dashboard is under development to show sales stats, platform growth, and top products.';
    $lang['msg_chat'] = 'The live chat feature is under development and will be available soon to facilitate direct secure communication with patients.';
    $lang['msg_pharmacist_reports'] = 'Pharmacist Reports and Analytics dashboard is under development to display sales stats and pharmacy growth in interactive charts.';
    $lang['msg_sales'] = 'The sales and purchases management feature is under development and will be available soon.';
    $lang['msg_suppliers'] = 'The suppliers management feature is under development to help you track supplier information and interactions.';
    $lang['msg_feature_default'] = 'This feature is under development and will be available soon.';

    // لوحة التحكم (المدير) | Admin Dashboard
    $lang['admin_panel'] = "Admin Panel";
    $lang['pharmacy_system'] = "Pharmacy Management System";
    $lang['active_pharma'] = "Active Pharmacies";
    $lang['pending_req'] = "Pending Requests";
    $lang['total_patients'] = "Total Patients";
    $lang['total_orders'] = "Total Orders";
    $lang['map_title'] = "Pharmacies Map";
    $lang['filter_all'] = "All";
    $lang['filter_active'] = "Active";
    $lang['filter_pending'] = "Pending";
    $lang['live_update'] = "Live";
    $lang['pharma_info'] = "Pharmacy Info";
    $lang['click_map'] = "Click on any point on the map to view details.";

    // معلومات الصيدلية في الخريطة | Pharmacy Info (JS Map)
    $lang['pharmacist_name'] = "Pharmacist Name";
    $lang['address'] = "Address";
    $lang['working_hours'] = " Working Hours";
    $lang['phone'] = "Phone";
    $lang['license_num'] = "License Number";
    $lang['not_available'] = "N/A";

    // جدول إدارة الصيدليات (الأدمن) | Pharmacies Table (Admin)
    $lang['pharmacy_name'] = "Pharmacy";
    $lang['owner'] = "Owner";
    $lang['contact_info'] = "Contact";
    $lang['location_work'] = "Location / Hours";
    $lang['join_date'] = "Join Date";
    $lang['active'] = "Active";
    $lang['pending'] = "Pending";
    $lang['search_pharmacy'] = "Search pharmacy by name...";
    $lang['approve_activate'] = "Approve & Activate";
    $lang['reject_request'] = "Reject Request";
    $lang['suspend_temp'] = "Suspend Temporarily";
    $lang['delete_permanently'] = "Delete Permanently";
    $lang['no_matching_pharmacies'] = "No matching pharmacies found";
    $lang['try_changing_search'] = "Try changing the search keyword or selecting another filter.";

    // جدول إدارة المرضى | Patients Table
    $lang['patient_name'] = "Patient";
    $lang['age'] = "Age";
    $lang['years'] = "years";
    $lang['not_specified'] = "Not specified";
    $lang['search_patient'] = "Search patient by name...";
    $lang['no_data'] = "No data available";
    $lang['delete_patient'] = "Delete Patient";
    $lang['no_matching_patients'] = "No matching patients found";
    $lang['check_patient_name'] = "Make sure the name is spelled correctly.";

    // الأدوار | Roles
    $lang['admin'] = "Admin";
    $lang['pharmacist'] = "Pharmacist";

    // تنبيهات الحذف والإيقاف | SweetAlert Translations
    $lang['swal_title'] = "Are you sure?";
    $lang['swal_text'] = "You won't be able to revert this!";
    $lang['swal_confirm'] = "Yes, delete it!";
    $lang['swal_cancel'] = "Cancel";
    $lang['suspend_title'] = "Suspend Account?";
    $lang['suspend_text'] = "Pharmacy will be temporarily paused.";
    $lang['suspend_confirm'] = "Yes, Suspend";
    $lang['coming_soon_title'] = "Coming Soon!";

    // لوحة التحكم (الصيدلي) | Pharmacist Dashboard
    $lang['todays_sales'] = "Today's Sales";
    $lang['currency'] = "₪";
    $lang['pending_orders'] = "Pending";
    $lang['low_stock_items'] = "Low Stock Items";
    $lang['expiring_soon'] = "Expiring Soon";
    $lang['recent_orders'] = "Recent Orders";
    $lang['order_id'] = "Order ID";
    $lang['time'] = "Time";
    $lang['customer'] = "Customer";
    $lang['amount'] = "Amount";
    $lang['items'] = "Items";
    $lang['low_stock_alert'] = "Low Stock Alert";
    $lang['out_of'] = "out of";
    $lang['days'] = "days";
    $lang['view_all'] = "View All";
    $lang['no_recent_orders'] = "No recent orders.";
    $lang['stock_excellent'] = "Stock is excellent, no shortages!";
    $lang['manage_inventory'] = "Manage Inventory";
    $lang['no_low_stock_items'] = "No low stock items in inventory.";
    $lang['new_badge'] = "New";
    $lang['needs_your_approval'] = "Needs your approval!";
    $lang['no_pending_orders_now'] = "No pending orders at the moment.";
    $lang['items_word'] = "Items";
    $lang['qty_word'] = "Qty";
    $lang['update_qty'] = "Update Qty";
    $lang['expiry_alerts'] = "Expiry Alerts";
    $lang['expired_word'] = "Expired";
    $lang['soon_word'] = "Soon";
    $lang['update_expiry'] = "Update Expiry";
    $lang['all_meds_valid'] = "All medicines are perfectly valid";

    // صفحة الأدوية والمخزون | Medicines & Stock Page
    $lang['product_inventory'] = "Product Inventory";
    $lang['search_inventory_placeholder'] = "Search your inventory (Name, Barcode)...";
    $lang['add_med_to_stock'] = "Add Med to Stock";
    $lang['add_new_med_to_stock'] = "Add New Med to Stock";
    $lang['product'] = "Product";
    $lang['category'] = "Category";
    $lang['stock'] = "Stock";
    $lang['price'] = "Price";
    $lang['cost'] = "Cost";
    $lang['margin'] = "Margin";
    $lang['expiry'] = "Expiry Date";
    $lang['uncategorized'] = "Uncategorized";
    $lang['no_matching_meds'] = "No matching medicines in your inventory";
    $lang['try_different_search_add'] = "Try a different name or add a new medicine to your stock.";
    $lang['edit_price_qty'] = "Edit Price/Quantity";
    $lang['remove_from_stock'] = "Remove from Stock";
    $lang['expired_since'] = "Expired since ";
    $lang['expires_today'] = "Expires today!";
    $lang['remaining'] = "Remaining ";
    $lang['day_s'] = " days";

    // نافذة إضافة وتعديل الدواء | Modal Add/Edit Stock
    $lang['med_exists_error'] = "This medicine already exists in your inventory! You can edit its quantity instead of adding it again.";
    $lang['search_in_catalog'] = "1. Search for medicine in the catalog:";
    $lang['scan_barcode_manual'] = "Scan Barcode / Add Manually";
    $lang['all_categories'] = "All Categories";
    $lang['search_med_name_scientific'] = "Type brand or scientific name...";
    $lang['suggested_meds'] = "Suggested Medicines:";
    $lang['latest_added_meds'] = "Latest medicines added to catalog:";
    $lang['custom_search_results'] = "Custom search results:";
    $lang['cost_price_val'] = "Cost: ";
    $lang['no_matching_meds_catalog'] = "No matching medicines found.";
    $lang['check_name_manual_soon'] = "Check the name, manual feature will be available soon.";
    $lang['stock_price_alerts_settings'] = "Stock, Price, & Alerts Settings";
    $lang['cost_price_ils'] = "Cost Price (₪)";
    $lang['profit_0'] = "Profit: 0%";
    $lang['cost_determined_admin'] = "Cost is determined by admin";
    $lang['sell_price_public'] = "Selling Price (₪)";
    $lang['current_qty'] = "Current Available Quantity";
    $lang['low_stock_alert_limit'] = "Low Stock Alert Limit";
    $lang['expiry_date_label'] = "Expiry Date";
    $lang['expiry_alert_months_label'] = "Expiry Alert Before (Months)";
    $lang['months'] = "Months";
    $lang['close_window'] = "Close Window";
    $lang['back_to_search'] = "Back to Search";
    $lang['save_to_stock'] = "Save to Stock";
    $lang['edit_stock_data'] = "Edit Stock Data";
    $lang['profit'] = "Profit: ";
    $lang['loss'] = "Loss!";
    $lang['remove_med_title'] = "Remove Medicine from Stock?";
    $lang['remove_med_text'] = "You won't be able to revert this, and it will be removed from your patient-facing list. If present in previous orders, their prices will be adjusted.";
    $lang['yes_remove'] = "Yes, Remove";
    $lang['barcode_feature_soon'] = "The 'Barcode Scan & Manual Add' feature is currently under development. It will be available soon to easily add unlisted medicines using the camera.";

    // إدارة الطلبات | Orders Page
    $lang['manage_orders'] = "Orders Management";
    $lang['filter_processing'] = "Processing";
    $lang['filter_delivered'] = "Delivered";
    $lang['status_pending'] = "Pending";
    $lang['status_processing'] = "Processing";
    $lang['status_delivered'] = "Completed";
    $lang['status_rejected'] = "Rejected";
    $lang['order_number'] = "Order ID";
    $lang['order_date'] = "Order Date";
    $lang['rx_alert'] = "Contains Controlled Meds (Rx Review Required)";
    $lang['details_btn'] = "Details";
    $lang['delivered_btn'] = "Delivered";
    $lang['no_orders'] = "No Orders Found";
    $lang['no_orders_desc'] = "No orders match the current filter criteria.";
    $lang['try_changing_search'] = "Try changing the search keyword or selecting another filter.";

    // نافذة تفاصيل الطلب | Order Details Modal
    $lang['order_details'] = "Order Details";
    $lang['customer_info'] = "Customer Info";
    $lang['payment_summary'] = "Payment Summary";
    $lang['total_required'] = "Total Required:";
    $lang['cod'] = "Cash on Delivery (COD)";
    $lang['requested_items'] = "Requested Items";
    $lang['qty'] = "Qty";
    $lang['item_total'] = "Total";
    $lang['attached_rx'] = "Attached Prescription (Required)";
    $lang['rx_protocol'] = "Based on health protocols, please verify the following before accepting:";
    $lang['rx_verify_check'] = "I have reviewed and verified the prescription.";
    $lang['close'] = "Close";
    $lang['accept_prepare'] = "Accept & Prepare";
    $lang['confirm_delivery'] = "Confirm Delivery";

    // كلمات إضافية للنافذة | Extra Modal Translations
    $lang['order_summary'] = "Order Summary";
    $lang['purchases'] = "Purchases";
    $lang['prescription_rx'] = "Prescription (Rx)";
    $lang['contains_controlled'] = "Order contains controlled medicines";
    $lang['action_taken'] = "Action already taken";
    $lang['reject'] = "Reject";
    $lang['delivered_successfully'] = "Order Delivered Successfully";
    $lang['pickup_pharmacy'] = "Pickup from Pharmacy";
    $lang['no_phone'] = "No phone number";
    $lang['quantity'] = "Qty:";

    // تنبيهات إدارة الطلبات | Orders Page Javascript Alerts
    $lang['rejection_reason_title'] = "Reason for Cancellation:";
    $lang['controlled_meds_rx'] = "Controlled Meds (Rx)";
    $lang['rx_verification_required'] = "Prescription verification required";
    $lang['extra_attachment_rx'] = "Extra Attachment (Prescription)";
    $lang['security_alert'] = "Security Alert";
    $lang['rx_review_required_alert'] = "You must review and verify the prescription before accepting the order.";
    $lang['reject_order_title'] = "Reject Order?";
    $lang['reject_order_text'] = "Please write the reason for rejection (will be shown to the patient):";
    $lang['reject_order_placeholder'] = "e.g., Medicine is currently out of stock...";
    $lang['confirm_reject'] = "Confirm Rejection";
    $lang['reject_reason_required'] = "You must provide a reason for rejection to notify the patient!";
    $lang['canceled_without_reason'] = "Canceled without providing a reason";
    $lang['accept_order_title'] = "Accept Order?";
    $lang['accept_order_text'] = "The patient will be notified that you are preparing the order.";
    $lang['yes_accept'] = "Yes, Accept";
    $lang['confirm_delivery_title'] = "Confirm Delivery?";
    $lang['confirm_delivery_text'] = "Has the order been delivered to the customer?";
    $lang['yes_delivered'] = "Yes, Delivered";

    $lang['filter_rejected'] = "Rejected";
    $lang['delivery_location'] = "Delivery Location (Map)";
    $lang['verify_rx_btn'] = "Approve Rx";
    $lang['reject_rx_btn'] = "Reject Rx";
    $lang['no_location_provided'] = "Patient did not provide a map location";
    $lang['all_orders'] = "All";
    $lang['search_order_patient'] = "Search by Order ID or Patient...";
    $lang['customer_contact'] = "Customer / Contact";
    $lang['address_col'] = "Address";
    $lang['items_col'] = "Items";
    $lang['total_amount'] = "Total";

}

?>