@once
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* ===================================================================
       SweetAlert2 — Refined Toast & Confirm Dialog Styling
       =================================================================== */

    :root {
        --sa-success: #47d764;
        --sa-success-bg: #f0fdf4;
        --sa-error: #ff355b;
        --sa-error-bg: #fef2f2;
        --sa-warning: #ffc021;
        --sa-warning-bg: #fffbeb;
        --sa-info: #2f86eb;
        --sa-info-bg: #eff6ff;
    }

    .swal2-container {
        z-index: 100000 !important;
    }

    /* ---------- Base popup (modal) ---------- */
    .swal2-popup:not(.swal2-toast) {
        font-family: Inter, ui-sans-serif, system-ui, sans-serif !important;
        border-radius: 0.375rem !important; /* rounded-md */
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.04) !important;
        border: none !important;
        padding: 2.25rem 2rem !important;
        background-color: #ffffff !important;
    }
    .dark .swal2-popup:not(.swal2-toast) {
        background-color: #0f172a !important;
        color: #f8fafc !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.06) !important;
    }

    /* Backdrop blur for a more premium feel, disabled when displaying a toast */
    .swal2-container.swal2-backdrop-show:not(:has(.swal2-toast)) {
        background: rgba(15, 23, 42, 0.45) !important;
        backdrop-filter: blur(3px) !important;
        -webkit-backdrop-filter: blur(3px) !important;
    }

    /* Ensure toast container has no backdrop and allows user interaction with the page */
    .swal2-container:has(.swal2-toast) {
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        pointer-events: none !important;
    }
    .swal2-popup.swal2-toast {
        pointer-events: auto !important;
    }

    /* ---------- Icon styling (modal) ---------- */
    .swal2-icon.swal2-success {
        border-color: var(--sa-success) !important;
        color: var(--sa-success) !important;
    }
    .swal2-icon.swal2-success [class^='swal2-success-line'] {
        background-color: var(--sa-success) !important;
    }
    .swal2-icon.swal2-success .swal2-success-ring {
        border-color: rgba(22, 163, 74, 0.25) !important;
    }
    .swal2-icon.swal2-success [class^='swal2-success-circular-line'],
    .swal2-icon.swal2-success .swal2-success-fix {
        background-color: #ffffff !important;
    }
    .dark .swal2-icon.swal2-success [class^='swal2-success-circular-line'],
    .dark .swal2-icon.swal2-success .swal2-success-fix {
        background-color: #0f172a !important;
    }
    .swal2-icon.swal2-error {
        border-color: var(--sa-error) !important;
        color: var(--sa-error) !important;
    }
    .swal2-icon.swal2-error [class^='swal2-x-mark-line'] {
        background-color: var(--sa-error) !important;
    }
    .swal2-icon.swal2-warning {
        border-color: var(--sa-warning) !important;
        color: var(--sa-warning) !important;
    }
    .swal2-icon.swal2-info {
        border-color: var(--sa-info) !important;
        color: var(--sa-info) !important;
    }
    .swal2-icon.swal2-question {
        border-color: var(--sa-info) !important;
        color: var(--sa-info) !important;
    }

    /* ---------- Text (modal) ---------- */
    .swal2-title {
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        margin-top: 0.5rem !important;
        letter-spacing: -0.02em !important;
    }
    .dark .swal2-title {
        color: #f8fafc !important;
    }
    .swal2-html-container {
        color: #64748b !important;
        font-size: 0.875rem !important;
        margin-top: 0.5rem !important;
        line-height: 1.65 !important;
    }
    .dark .swal2-html-container {
        color: #94a3b8 !important;
    }

    /* ---------- Buttons (modal) ---------- */
    .swal2-actions {
        margin-top: 2rem !important;
        gap: 0.625rem !important;
        width: 100% !important;
        justify-content: center !important;
    }
    .swal2-styled {
        margin: 0 !important;
        padding: 0.625rem 1.5rem !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        border-radius: 0.375rem !important; /* rounded-md */
        transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        box-shadow: none !important;
    }
    .swal2-styled:focus {
        box-shadow: none !important;
        outline: none !important;
    }
    .swal2-styled.swal2-confirm {
        border: none !important;
    }
    .swal2-styled.swal2-confirm:hover {
        filter: brightness(0.95) !important; /* subtle premium darkening */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
    }
    .swal2-styled.swal2-cancel {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .dark .swal2-styled.swal2-cancel {
        background-color: #1e293b !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .swal2-styled.swal2-cancel:hover {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
    }
    .dark .swal2-styled.swal2-cancel:hover {
        background-color: #334155 !important;
        color: #ffffff !important;
    }

    /* Modal entrance animation — soft pop instead of default */
    @keyframes swalPopIn {
        from { transform: scale(0.92); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
    .swal2-popup:not(.swal2-toast).swal2-show {
        animation: swalPopIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    /* ===================================================================
       Toast
       =================================================================== */
    .swal2-popup.swal2-toast {
        padding: 0.75rem 1rem !important;
        border-radius: 0.375rem !important; /* rounded-md */
        box-shadow: 0 12px 24px -8px rgba(15, 23, 42, 0.15), 0 0 0 1px rgba(15, 23, 42, 0.05) !important;
        background-color: #ffffff !important;
        color: #1e293b !important;
        border: none !important;
        overflow: hidden !important;
        align-items: center !important;
        min-width: 320px !important;
    }
    .dark .swal2-popup.swal2-toast {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
    }

    /* Icon — colored circle badge instead of default outlined icon */
    .swal2-popup.swal2-toast .swal2-icon {
        margin: 0 0.75rem 0 0 !important;
        width: 2rem !important;
        height: 2rem !important;
        min-width: 2rem !important;
        border-width: 0 !important;
        border-radius: 50% !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-success .swal2-icon {
        background: var(--sa-success-bg) !important;
        color: var(--sa-success) !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-success [class^='swal2-success-line'] {
        background-color: var(--sa-success) !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-success .swal2-success-ring {
        border-color: transparent !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-error .swal2-icon {
        background: var(--sa-error-bg) !important;
        color: var(--sa-error) !important;
        border-color: var(--sa-error-bg) !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-error [class^='swal2-x-mark-line'] {
        background-color: var(--sa-error) !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-warning .swal2-icon {
        background: var(--sa-warning-bg) !important;
        color: var(--sa-warning) !important;
        border-color: var(--sa-warning-bg) !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-info .swal2-icon {
        background: var(--sa-info-bg) !important;
        color: var(--sa-info) !important;
        border-color: var(--sa-info-bg) !important;
    }

    /* Thin colored left accent, softer than before */
    .swal2-popup.swal2-toast {
        border-left: 10px solid transparent !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-success { border-left-color: var(--sa-success) !important; }
    .swal2-popup.swal2-toast.swal2-icon-error   { border-left-color: var(--sa-error) !important; }
    .swal2-popup.swal2-toast.swal2-icon-warning { border-left-color: var(--sa-warning) !important; }
    .swal2-popup.swal2-toast.swal2-icon-info    { border-left-color: var(--sa-info) !important; }

    /* Toast text */
    .swal2-popup.swal2-toast .swal2-title {
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        margin: 0 !important;
        letter-spacing: normal !important;
    }
    .dark .swal2-popup.swal2-toast .swal2-title {
        color: #f1f5f9 !important;
    }
    .swal2-popup.swal2-toast .swal2-html-container {
        color: #64748b !important;
        font-size: 0.78rem !important;
        font-weight: 400 !important;
        margin: 0.125rem 0 0 0 !important;
        line-height: 1.4 !important;
    }
    .dark .swal2-popup.swal2-toast .swal2-html-container {
        color: #94a3b8 !important;
    }

    /* Close button */
    .swal2-popup.swal2-toast .swal2-close {
        color: #94a3b8 !important;
        font-size: 1.25rem !important;
        outline: none !important;
        box-shadow: none !important;
        align-self: flex-start !important;
        margin: 0 0 0 0.5rem !important;
        padding: 0 !important;
        transition: color 0.15s ease, transform 0.15s ease !important;
    }
    .swal2-popup.swal2-toast .swal2-close:hover {
        color: #1e293b !important;
        transform: rotate(90deg) !important;
    }
    .dark .swal2-popup.swal2-toast .swal2-close:hover {
        color: #ffffff !important;
    }

    /* Progress bar — thinner, matches accent color */
    .swal2-popup.swal2-toast .swal2-timer-progress-bar {
        height: 2.5px !important;
        opacity: 0.85 !important;
    }
    .swal2-popup.swal2-toast.swal2-icon-success .swal2-timer-progress-bar { background-color: var(--sa-success) !important; }
    .swal2-popup.swal2-toast.swal2-icon-error   .swal2-timer-progress-bar { background-color: var(--sa-error) !important; }
    .swal2-popup.swal2-toast.swal2-icon-warning .swal2-timer-progress-bar { background-color: var(--sa-warning) !important; }
    .swal2-popup.swal2-toast.swal2-icon-info    .swal2-timer-progress-bar { background-color: var(--sa-info) !important; }

    /* Toast animation — smooth slide + fade + slight scale */
    @keyframes toastSlideInRight {
        from { transform: translateX(110%) scale(0.96); opacity: 0; }
        to   { transform: translateX(0) scale(1); opacity: 1; }
    }
    @keyframes toastSlideOutRight {
        from { transform: translateX(0) scale(1); opacity: 1; }
        to   { transform: translateX(110%) scale(0.96); opacity: 0; }
    }
    .toast-slide-in {
        animation: toastSlideInRight 0.32s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
    }
    .toast-slide-out {
        animation: toastSlideOutRight 0.24s cubic-bezier(0.4, 0, 1, 1) forwards !important;
    }
</style>
<script>
    // ---------- Toast ----------
    const erpSwalToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3500,
        timerProgressBar: true,
        showClass: { popup: 'toast-slide-in' },
        hideClass: { popup: 'toast-slide-out' },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    window.showToast = function (message, type = 'success') {
        const titleMap = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Info'
        };
        erpSwalToast.fire({
            icon: type,
            title: titleMap[type] || 'Notification',
            html: message
        });
    };

    // ---------- Confirm Dialog ----------
    window.confirmDialog = function ({
        title = 'Are you sure?',
        text = 'This action cannot be undone!',
        icon = 'warning',
        confirmButtonText = 'Yes, proceed',
        cancelButtonText = 'Cancel',
        confirmButtonColor = '#2563eb',
        cancelButtonColor = '#64748b',
        onConfirm = () => {},
        onCancel = () => {}
    }) {
        Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonColor,
            cancelButtonColor,
            confirmButtonText,
            cancelButtonText,
            buttonsStyling: true,
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                onCancel();
            }
        });
    };
</script>
@endonce