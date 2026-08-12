/**
 * Helper CSRF bersama untuk semua halaman (kecermatan, ujian reguler, admin).
 *
 * Strategi: request fetch memakai header X-XSRF-TOKEN yang nilainya diambil
 * dari cookie XSRF-TOKEN. Cookie ini dikirim ulang oleh Laravel pada SETIAP
 * respons (nilai = token sesi terenkripsi), sehingga SELALU segar — sama
 * persis seperti yang dilakukan axios/Inertia.
 *
 * Mengapa tidak lagi memakai meta tag <meta name="csrf-token">: meta tag hanya
 * dirender sekali di HTML awal. Setelah login/logout (session()->regenerate()),
 * token sesi berubah tetapi navigasi SPA tidak merender ulang <head>, sehingga
 * meta tag menjadi basi dan POST berbasis fetch ditolak 419 sampai token
 * di-refresh manual.
 *
 * Helper ini menyediakan:
 *   - getXsrfToken()      : baca token dari cookie XSRF-TOKEN (decode URI)
 *   - csrfHeaders(extra)  : header JSON + X-XSRF-TOKEN (dipakai semua request)
 *   - getCsrfToken()      : baca token dari meta tag (fallback/legacy)
 *   - setCsrfToken(token) : tulis ulang token ke meta tag
 *   - refreshCsrfToken()  : ambil token terbaru dari server (GET, no-store);
 *                           responsnya otomatis memperbarui cookie XSRF-TOKEN
 *   - fetchJsonWithCsrf() : fetch + retry + refresh otomatis saat 419
 */

let refreshPromise = null;
let lastRefreshAttemptAt = 0;
const REFRESH_COOLDOWN_MS = 2000;
const REFRESH_TIMEOUT_MS = 10000;

// Baca token dari cookie XSRF-TOKEN. Nilai cookie di-URL-encode oleh browser,
// jadi perlu decodeURIComponent dulu sebelum dikirim sebagai header.
export function getXsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (!match) return "";
    try {
        return decodeURIComponent(match[1]);
    } catch {
        // Nilai cookie tidak valid (mis. mengandung % yang bukan escape) —
        // jangan biarkan request gagal karena exception.
        return "";
    }
}

// Header standar untuk request JSON. Hanya mengirim X-XSRF-TOKEN (cookie) —
// header X-CSRF-TOKEN yang basi justru diutamakan Laravel dan menyebabkan 419.
export function csrfHeaders(extra = {}) {
    return {
        "Content-Type": "application/json",
        "X-XSRF-TOKEN": getXsrfToken(),
        Accept: "application/json",
        ...extra,
    };
}

// Baca token dari meta tag HTML (dipakai sebagai fallback/legacy).
export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
}

export function setCsrfToken(token) {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute("content", token);
}

// Ambil CSRF token terbaru dari server lalu tulis ulang ke meta tag. Yang
// lebih penting: respons GET ini ikut mengirim ulang cookie XSRF-TOKEN yang
// segar, sehingga request berikutnya (termasuk keepalive saat pagehide) memakai
// token baru.
//
// Pengaman:
//   - Promise di-dedupe: request 419 yang datang bersamaan hanya satu fetch.
//   - Timeout: endpoint yang menggantung tidak memblokir submit jawaban.
//   - Cooldown: saat 419 persisten (sesi rusak), endpoint tidak dihantam
//     tiap tick flush; cukup sekali tiap beberapa detik.
//   - force=true (dipakai pada retry 419): abaikan cooldown agar retry selalu
//     mendapat token yang benar-benar baru.
export function refreshCsrfToken(url, force = false) {
    if (!url) return Promise.resolve(null);
    if (refreshPromise) return refreshPromise;

    const now = Date.now();
    if (!force && now - lastRefreshAttemptAt < REFRESH_COOLDOWN_MS) {
        return Promise.resolve(getCsrfToken() || null);
    }
    lastRefreshAttemptAt = now;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), REFRESH_TIMEOUT_MS);

    refreshPromise = fetch(url, {
        method: "GET",
        headers: { Accept: "application/json" },
        cache: "no-store",
        signal: controller.signal,
    })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
            const token = data?.csrf_token || "";
            if (token) setCsrfToken(token);
            return token || null;
        })
        .catch(() => null)
        .finally(() => {
            clearTimeout(timeoutId);
            refreshPromise = null;
        });

    return refreshPromise;
}

// fetch + parse JSON + retry dengan backoff. Header CSRF SELALU diambil dari
// cookie XSRF-TOKEN (fresh), dan X-CSRF-TOKEN basi apa pun dari pemanggil
// dibuang. Saat ditolak 419, ambil token baru dari csrfRefreshUrl lalu ulangi
// sekali; pembatas csrfRefreshed mencegah perulangan tak terbatas.
export async function fetchJsonWithCsrf(
    url,
    options = {},
    { csrfRefreshUrl = null, maxAttempts = 3, timeoutMs = 10000 } = {},
) {
    let lastError = null;
    let csrfRefreshed = false;

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const response = await fetch(url, {
                ...options,
                headers: (() => {
                    const headers = { ...(options.headers || {}) };
                    // X-CSRF-TOKEN (meta) bisa basi dan diutamakan Laravel —
                    // buang, lalu selalu isi X-XSRF-TOKEN dari cookie.
                    delete headers["X-CSRF-TOKEN"];
                    headers["X-XSRF-TOKEN"] = getXsrfToken();
                    return headers;
                })(),
                signal: controller.signal,
            });
            const data = await response.json().catch(() => ({}));

            if (response.status === 419 && !csrfRefreshed) {
                csrfRefreshed = true;
                // force=true: retry 419 harus memakai token baru yang asli,
                // jangan biarkan cooldown mengembalikan token basi.
                const freshToken = await refreshCsrfToken(csrfRefreshUrl, true);
                if (freshToken) {
                    // Cookie XSRF-TOKEN sudah diperbarui oleh respons refresh;
                    // iterasi berikutnya otomatis membacanya ulang.
                    attempt = 0; // beri satu kesempatan ekstra dengan token baru
                    continue;
                }
            }

            if (!response.ok) {
                throw new Error(
                    data.message ||
                        data.error ||
                        `Request gagal dengan status HTTP ${response.status}`,
                );
            }

            return data;
        } catch (error) {
            lastError = error;
            if (attempt < maxAttempts) {
                await new Promise((resolve) =>
                    setTimeout(resolve, attempt * 300),
                );
            }
        } finally {
            clearTimeout(timeoutId);
        }
    }

    throw lastError || new Error("Request gagal diproses.");
}
