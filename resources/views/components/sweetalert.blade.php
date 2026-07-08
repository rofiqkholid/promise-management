@once
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Custom SweetAlert2 Premium styling override */
    .swal2-popup {
        font-family: inherit !important;
        border-radius: 14px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        padding: 1.75rem !important;
        background-color: #ffffff !important;
    }
    .dark .swal2-popup {
        background-color: #0f172a !important; /* Theme dark color */
        color: #f8fafc !important;
        border-color: rgba(51, 65, 85, 0.8) !important;
    }
    .swal2-title {
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        margin-top: 0.5rem !important;
    }
    .dark .swal2-title {
        color: #f8fafc !important;
    }
    .swal2-html-container {
        color: #64748b !important;
        font-size: 0.875rem !important;
        margin-top: 0.75rem !important;
        line-height: 1.5 !important;
    }
    .dark .swal2-html-container {
        color: #94a3b8 !important;
    }
    
    /* Modern Circular Icons Override */
    .swal2-icon {
        border: none !important;
        border-radius: 50% !important;
        width: 56px !important;
        height: 56px !important;
        margin: 1rem auto 0.5rem auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    /* Icon Color Schemes */
    .swal2-icon.swal2-success {
        background-color: #10b981 !important;
        color: white !important;
    }
    .swal2-icon.swal2-success .swal2-success-ring {
        display: none !important;
    }
    .swal2-icon.swal2-success .swal2-success-line-tip, 
    .swal2-icon.swal2-success .swal2-success-line-long {
        background-color: #ffffff !important;
    }
    .swal2-icon.swal2-warning, .swal2-icon.swal2-error {
        background-color: #ef4444 !important;
        color: white !important;
    }
    .swal2-icon.swal2-warning .swal2-icon-content,
    .swal2-icon.swal2-error .swal2-icon-content {
        color: #ffffff !important;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
    }
    .swal2-icon.swal2-info {
        background-color: #3b82f6 !important;
        color: white !important;
    }
    .swal2-icon.swal2-info .swal2-icon-content {
        color: #ffffff !important;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
    }

    /* Actions & Buttons Override */
    .swal2-actions {
        margin-top: 1.75rem !important;
        gap: 0.75rem !important;
        width: 100% !important;
        justify-content: center !important;
    }
    .swal2-styled {
        margin: 0 !important;
        padding: 0.625rem 1.5rem !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
    }
    .swal2-styled.swal2-confirm {
        box-shadow: none !important;
    }
    .swal2-styled.swal2-cancel {
        background-color: transparent !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
    }
    .dark .swal2-styled.swal2-cancel {
        color: #94a3b8 !important;
        border-color: #475569 !important;
    }
    .swal2-styled.swal2-cancel:hover {
        background-color: #f8fafc !important;
        color: #0f172a !important;
    }
    .dark .swal2-styled.swal2-cancel:hover {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }

    /* Premium Compact Toast Override */
    .swal2-popup.swal2-toast {
        padding: 0.75rem 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        overflow: hidden !important;
        position: relative !important;
    }
    .dark .swal2-popup.swal2-toast {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    /* bottom accent border */
    .swal2-popup.swal2-toast::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        transition: height 0.2s;
    }
    .swal2-popup.swal2-toast:has(.swal2-success)::after {
        background-color: #10b981;
    }
    .swal2-popup.swal2-toast:has(.swal2-error)::after,
    .swal2-popup.swal2-toast:has(.swal2-warning)::after {
        background-color: #ef4444;
    }
    .swal2-popup.swal2-toast:has(.swal2-info)::after {
        background-color: #3b82f6;
    }
    
    .swal2-popup.swal2-toast .swal2-title {
        font-size: 0.825rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        margin: 0 !important;
        padding-left: 0.5rem !important;
    }
    .dark .swal2-popup.swal2-toast .swal2-title {
        color: #f1f5f9 !important;
    }
    .swal2-popup.swal2-toast .swal2-icon {
        margin: 0 !important;
        transform: scale(0.75);
        width: 24px !important;
        height: 24px !important;
    }
</style>
<script>
    // Premium Toast Configuration
    const erpSwalToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    window.showToast = function (message, type = 'success') {
        erpSwalToast.fire({
            icon: type,
            title: message
        });
    };

    window.confirmDialog = function ({
        title = 'Are you sure?',
        text = "You won't be able to revert this!",
        icon = 'warning',
        confirmButtonText = 'Yes',
        cancelButtonText = 'No',
        confirmButtonColor = '#2563eb', // Indigo / Blue 600
        cancelButtonColor = '#64748b', // Slate 500
        onConfirm = () => {}
    }) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: cancelButtonColor,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            buttonsStyling: true
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    };
</script>
@endonce
