import DOMPurify from 'dompurify';

/**
 * Bersihkan HTML soal / pilihan jawaban (dari TinyMCE atau import)
 * sebelum dirender dengan v-html, untuk mencegah XSS.
 *
 * DOMPurify default hanya mengizinkan tag & atribut yang aman
 * (menghapus <script>, on* handlers, javascript: URL, dst).
 */
export function sanitizeHtml(html) {
    if (!html) return '';
    return DOMPurify.sanitize(String(html));
}
