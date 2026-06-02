<?php
// ========================================================================
// إعدادات المتغيرات الأساسية (اللون والدور) | Base variables setup
// ========================================================================
$year = date('Y');
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
// لون أزرق للأدمن، أخضر للصيدلي | Blue for Admin, Green for Pharmacist
$accentColor = ($role_id == 1) ? '#048AC1' : '#0A7A48';
?>

</div> <!-- إغلاق حاوية الـ main القادمة من الهيدر | Close main container from header -->

<!-- ======================================================================== -->
<!-- بداية كود الفوتر البسيط | Minimalist Signature Footer -->
<!-- ======================================================================== -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const mainElement = document.querySelector('main');
        const roleColor = "<?php echo $accentColor; ?>";

        if (mainElement) {

            // إضافة كلاسات لوسم الـ main ليعمل التثبيت بالأسفل
            mainElement.classList.add('flex', 'flex-col');

            const footerHTML = `
                <style>
                    /* ========================================== */
                    /*  الحل السحري لمشكلة السكرول | Magic Scroll Fix */
                    /* يمنع العناصر من الانكماش داخل الـ flex container */
                    /* ========================================== */
                    main > * {
                        flex-shrink: 0;
                    }

                    /* 1. عنصر الفراغ الذكي الذي يمنع الالتصاق بالمحتوى */
                    .footer-spacer {
                        flex-grow: 1;
                        min-height: 3.5rem; /* هذه هي المسافة بين الخريطة والفوتر، يمكنك تكبيرها أو تصغيرها */
                    }

                    .simple-footer {
                        /* التمدد لعرض الصفحة كاملة (بإلغاء حواف الـ main) */
                        margin-left: -2rem; 
                        margin-right: -2rem;
                        margin-bottom: -2rem;
                        
                        /* زوايا دائرية من الأعلى فقط */
                        border-radius: 2rem 2rem 0 0; 
                        
                        /* تصميم بسيط بلون صلب */
                        background-color: #ffffff;
                        border-top: 1px solid #f1f5f9;
                        
                        padding: 1.5rem 2rem;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                        z-index: 10;
                        transition: background-color 0.3s, border-color 0.3s;
                    }

                    /* الوضع الليلي البسيط */
                    .dark .simple-footer {
                        background-color: #0f172a; 
                        border-top: 1px solid #1e293b;
                    }

                    .brand-name {
                        font-size: 1.25rem; 
                        font-weight: 900;
                        color: ${roleColor}; 
                        margin: 0;
                    }

                    .team-names {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: center;
                        gap: 1rem;
                        color: #64748b;
                        font-weight: 700;
                        font-size: 0.85rem; 
                    }

                    .dark .team-names {
                        color: #94a3b8;
                    }

                    .name-separator {
                        color: #cbd5e1;
                    }
                    
                    .dark .name-separator {
                        color: #334155;
                    }

                    @media (max-width: 640px) {
                        .simple-footer {
                            margin-left: -1rem; 
                            margin-right: -1rem;
                            margin-bottom: -1rem;
                            border-radius: 1.5rem 1.5rem 0 0;
                        }
                        .team-names {
                            flex-direction: column;
                            gap: 0.2rem;
                        }
                        .name-separator {
                            display: none;
                        }
                    }
                </style>

                <!-- تم إضافة عنصر الفراغ هنا قبل الفوتر -->
                <div class="footer-spacer"></div>
                
                <footer class="simple-footer">
                    <h2 class="brand-name">PharmaSmart</h2>
                    <div class="team-names" dir="ltr">
                        <span>Hammam Ibrahim</span>
                        <span class="name-separator">|</span>
                        <span>Obada Majdobi</span>
                        <span class="name-separator">|</span>
                        <span>Qais Daraghmeh</span>
                    </div>
                </footer>
            `;

            mainElement.insertAdjacentHTML('beforeend', footerHTML);
        }
    });

    // ========================================================================
    // إعدادات الإشعارات المنبثقة | SweetAlert Configuration
    // ========================================================================
    const Toast = Swal.mixin({
        toast: true,
        position: document.dir === 'rtl' ? 'top-start' : 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
</script>
</body>
</html>