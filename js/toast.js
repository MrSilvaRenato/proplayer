function showToast() {
    const toastEl = document.querySelector('.toast');
    if (toastEl) {
        toastEl.style.opacity = 1;
        setTimeout(() => {
            toastEl.style.opacity = 0;
            setTimeout(() => {
                toastEl.remove();
            }, 500);
        }, 3000);
    }
}

// Auto trigger if message exists in URL (optional for redirects)
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get("msg");
    const type = urlParams.get("type");
    if (msg && type) {
        showToast(type, msg);
    }
});
