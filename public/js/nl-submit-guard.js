/* ================================================================
   NextLog — PENCEGAH KLIK / SUBMIT GANDA (global)
   - Form POST/PUT/DELETE hanya bisa dikirim SEKALI sampai halaman berpindah.
   - Berlaku juga untuk form.submit() dari swalConfirm (yang tidak memicu event submit).
   - Tombol pengirim dinonaktifkan + spinner setelah data form terbentuk.
   - Form AJAX (yang memanggil preventDefault) tidak disentuh: masing-masing sudah
     menonaktifkan tombolnya sendiri.
   - Pengecualian: form GET (filter/export Excel) dan form ber-atribut data-allow-resubmit.
   ================================================================ */
(function () {
    if (window.__nlSubmitGuard) return;
    window.__nlSubmitGuard = true;

    const isGuarded = (form) => {
        if (!form || form.hasAttribute('data-allow-resubmit')) return false;
        return (form.getAttribute('method') || form.method || 'get').toLowerCase() !== 'get';
    };

    // Form yang membuka tab baru (mis. PDF) → halaman tidak berpindah, buka kunci setelah beberapa detik
    const scheduleRelease = (form) => {
        const target = (form.getAttribute('target') || form.target || '').toLowerCase();
        if (target && target !== '_self') setTimeout(() => release(form), 4000);
    };

    const lockButtons = (form, submitter) => {
        const buttons = form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]');
        buttons.forEach((b) => { b.disabled = true; b.setAttribute('data-nl-locked', '1'); });
        if (submitter && submitter.tagName === 'BUTTON' && !submitter.querySelector('.spinner-border')) {
            submitter.setAttribute('data-nl-html', submitter.innerHTML);
            submitter.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
        }
    };

    function release(form) {
        delete form.__nlSubmitting;
        form.querySelectorAll('[data-nl-locked]').forEach((b) => {
            b.disabled = false;
            b.removeAttribute('data-nl-locked');
            if (b.hasAttribute('data-nl-html')) { b.innerHTML = b.getAttribute('data-nl-html'); b.removeAttribute('data-nl-html'); }
        });
    }

    // 1) Submit lewat tombol / Enter
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!isGuarded(form)) return;
        if (form.__nlSubmitting) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        form.__nlSubmitting = true;
        const submitter = e.submitter || null;
        // Tunggu handler lain selesai: kalau form AJAX (preventDefault) biarkan handler-nya yang mengatur
        setTimeout(function () {
            if (e.defaultPrevented) { delete form.__nlSubmitting; return; }
            lockButtons(form, submitter);
            scheduleRelease(form);
        }, 0);
    }, true);

    // 2) form.submit() langsung (swalConfirm, tombol modal approve/reject)
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (isGuarded(this)) {
            if (this.__nlSubmitting) return;
            this.__nlSubmitting = true;
            lockButtons(this, null);
            scheduleRelease(this);
        }
        return nativeSubmit.call(this);
    };

    // 3) Kembali lewat tombol Back (bfcache) → aktifkan lagi
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form').forEach(release);
    });
})();
