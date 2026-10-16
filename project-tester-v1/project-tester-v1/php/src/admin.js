document.addEventListener('DOMContentLoaded', function () {
    /* ---------------------------------------------------------
     * ยืนยันก่อนลบ/แบน-ปลดแบน (แทนที่ inline onclick="return confirm(...)")
     * ใช้ attribute data-confirm="ข้อความ" บนลิงก์ที่ต้องการยืนยัน
     * --------------------------------------------------------- */
    document.querySelectorAll('a[data-confirm]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var message = link.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-confirm-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm-form');
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    /* ---------------------------------------------------------
     * ปุ่มเปิด/ปิด sidebar บนหน้าจอขนาดเล็ก
     * --------------------------------------------------------- */
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        document.addEventListener('click', function (event) {
            var isClickInsideSidebar = sidebar.contains(event.target);
            var isClickOnToggle = sidebarToggle.contains(event.target);

            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
});
