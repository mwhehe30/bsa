import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import 'sweetalert2/dist/sweetalert2.min.css';
import { refreshCsrfToken } from './utils/csrf';

// ── Recovery global CSRF 419 untuk semua request Inertia (POST/PUT/PATCH/DELETE) ──
// Inertia v2 mengirim token CSRF lewat header X-XSRF-TOKEN yang dibaca Axios
// dari cookie XSRF-TOKEN. Saat cookie itu basi (mis. halaman awal sempat
// disajikan dari cache CDN/proxy), Laravel menolak request dengan status 419
// dan Inertia menampilkan modal "invalid response".
//
// Handler ini: mencegah modal, mengambil token terbaru dari server (endpoint
// csrf-token juga mengirim ulang cookie XSRF-TOKEN yang segar lewat Set-Cookie),
// lalu mengulang visit yang sama secara otomatis. Batas retry mencegah loop.
let lastVisit = null;
const csrfRetryCount = new Map();
const CSRF_RETRY_LIMIT = 2;
const CSRF_RETRY_FORGET_MS = 15000;

router.on('start', (event) => {
    lastVisit = event.detail?.visit || null;
});

// Lapisan pengaman tambahan: request fetch kini memakai header X-XSRF-TOKEN
// dari cookie yang SELALU segar (lihat utils/csrf.js), jadi 419 akibat token
// basi sudah hilang dari sumbernya. Sinkronisasi berikut menjaga cookie
// XSRF-TOKEN dan meta tag tetap segar setelah setiap navigasi Inertia (mis.
// setelah login/logout yang meregenerasi token sesi), sekaligus menjadi
// fallback bagi jalur kode legacy yang masih membaca meta tag.
router.on('success', () => {
    refreshCsrfToken('/csrf-token');
});

router.on('invalid', (event) => {
    const response = event.detail?.response;
    if (!response || response.status !== 419) return;

    // Snapshot visit saat ini: retry async memakai request yang benar, bukan
    // lastVisit yang mungkin sudah tergantikan selama refresh berlangsung.
    const visit = lastVisit;
    if (!visit || visit.method === 'get') return;

    const key = `${visit.method} ${visit.url.pathname}${visit.url.search}`;
    const attempts = csrfRetryCount.get(key) || 0;
    if (attempts >= CSRF_RETRY_LIMIT) return; // sudah dicoba ulang; biarkan perilaku default

    event.preventDefault(); // cegah modal "invalid response" Inertia

    // Endpoint publik (tanpa auth) sehingga recovery juga bekerja di halaman
    // login yang belum punya sesi. Responsnya mengirim ulang cookie sesi +
    // XSRF-TOKEN yang segar sehingga retry berikutnya lolos verifikasi CSRF.
    // force=true: sudah terjadi 419, jangan biarkan cooldown mengembalikan
    // token yang sama lagi — pastikan token benar-benar di-refresh.
    const refreshUrl = '/csrf-token';

    refreshCsrfToken(refreshUrl, true).then((token) => {
        if (!token) {
            // Tidak dapat refresh token (mis. sesi kedaluwarsa / endpoint
            // mengarahkan ke login). Muat ulang halaman agar mendapat token
            // baru dari server, bukan membiarkan POST gagal tanpa feedback.
            window.location.reload();
            return;
        }

        csrfRetryCount.set(key, attempts + 1);
        // Lupakan penanda setelah jeda agar request baru ke URL yang sama
        // tetap bisa retry di kemudian hari.
        setTimeout(() => csrfRetryCount.delete(key), CSRF_RETRY_FORGET_MS);

        // Ulangi visit yang sama (url, method, data, dan callback aslinya).
        router.visit(visit.url, visit);
    });
});

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            //set mixins
            .mixin({
                methods: {
                    examTimeRangeChecker: function (start_time, end_time) {
                        return (
                            new Date() >= new Date(start_time) &&
                            new Date() <= new Date(end_time)
                        );
                    },

                    examTimeStartChecker: function (start_time) {
                        return new Date() < new Date(start_time);
                    },

                    examTimeEndChecker: function (end_time) {
                        return new Date() > new Date(end_time);
                    },
                },
            })
            .use(plugin)
            .mount(el);
    },
    progress: {
        // The delay after which the progress bar will appear, in milliseconds...
        delay: 250,

        // The color of the progress bar...
        color: '#29d',

        // Whether to include the default NProgress styles...
        includeCSS: true,

        // Whether the NProgress spinner will be shown...
        showSpinner: false,
    },
});
