@once
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Custom SweetAlert2 Premium styling override */
    .swal2-popup {
        font-family: inherit !important;
        border-radius: 0px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        padding: 1.25rem !important;
    }
    .dark .swal2-popup {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border-color: rgba(51, 65, 85, 0.8) !important;
    }
    .swal2-title {
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 1.15rem !important;
    }
    .dark .swal2-title {
        color: #f8fafc !important;
    }
    .swal2-html-container {
        color: #475569 !important;
        font-size: 0.825rem !important;
        margin-top: 0.5rem !important;
    }
    .dark .swal2-html-container {
        color: #94a3b8 !important;
    }
    .swal2-confirm {
        border-radius: 0px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        padding: 0.45rem 1rem !important;
    }
    .swal2-cancel {
        border-radius: 0px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        padding: 0.45rem 1rem !important;
    }

    /* Premium Compact Toast Override */
    .swal2-popup.swal2-toast {
        padding: 0.5rem 0.75rem !important;
        border-radius: 0px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
    }
    .dark .swal2-popup.swal2-toast {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    .swal2-popup.swal2-toast .swal2-title {
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        color: #334155 !important;
        margin: 0 !important;
        padding-left: 0.35rem !important;
    }
    .dark .swal2-popup.swal2-toast .swal2-title {
        color: #f1f5f9 !important;
    }
    /* Custom margins and sizing for toast icons to prevent distortion */
    .swal2-popup.swal2-toast .swal2-icon {
        margin: 0 0.25rem 0 0 !important;
        transform: scale(0.8);
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
