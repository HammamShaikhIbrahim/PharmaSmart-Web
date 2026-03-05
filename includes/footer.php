<script>
    const Lang = {
        // ... (المتغيرات السابقة: title, text, confirm, cancel) ...
        title: "<?php echo $lang['swal_title']; ?>",
        text: "<?php echo $lang['swal_text']; ?>",
        confirm: "<?php echo $lang['swal_confirm']; ?>",
        cancel: "<?php echo $lang['swal_cancel']; ?>",

        // --- الإضافة الجديدة للتعليق ---
        suspendTitle: "<?php echo $lang['suspend_title']; ?>",
        suspendText: "<?php echo $lang['suspend_text']; ?>",
        suspendConfirm: "<?php echo $lang['suspend_confirm']; ?>",

        isDark: document.documentElement.classList.contains('dark')
    };
    lucide.createIcons();
</script>