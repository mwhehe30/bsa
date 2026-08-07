# Setup Environment (.env)

Panduan lengkap konfigurasi environment untuk aplikasi **Ujian Online**.

> **Stack:** PHP ^8.2 · Laravel 12 · MySQL · Inertia + Vue 3 · Laravel Reverb (realtime) · TinyMCE (editor soal)

---

## 1. Prasyarat

| Tools | Versi |
|---|---|
| PHP | ^8.2 (cek: `php -v`) |
| Composer | 2.x (cek: `composer -V`) |
| Node.js | 18+ (cek: `node -v`) |
| MySQL | 8.x (atau MariaDB) |
| Git | 2.x |

---

## 2. Setup Lokal (Development)

### Langkah 1 — Install dependency

```bash
composer install
npm install
```

### Langkah 2 — Buat file `.env`

```bash
cp .env.example .env
```

### Langkah 3 — Generate APP_KEY

```bash
php artisan key:generate
```

> ⚠️ Wajib! Tanpa `APP_KEY` semua session & enkripsi data akan error.

### Langkah 4 — Setup database MySQL

Buat database baru (misal lewat phpMyAdmin atau terminal):

```sql
CREATE DATABASE ujian_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu isi di `.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujian_online
DB_USERNAME=root
DB_PASSWORD=          # isi password MySQL kamu
```

### Langkah 5 — Jalankan migrasi + seeder

```bash
php artisan migrate --seed
```

Seeder membuat akun default & data mapel:

| Akun | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | `password` |

> ⚠️ Ganti password admin setelah login pertama!

### Langkah 6 — Generate key Reverb (realtime)

```bash
php artisan reverb:install
```

Command ini otomatis menambahkan `REVERB_APP_ID`, `REVERB_APP_KEY`, dan `REVERB_APP_SECRET` ke `.env`.

### Langkah 7 — Storage link (biar gambar soal tampil)

```bash
php artisan storage:link
```

### Langkah 8 — TinyMCE API key (opsional, untuk editor soal)

Daftar gratis di [https://www.tiny.cloud](https://www.tiny.cloud) → buat API key, lalu isi di `.env`:

```ini
API_KEY_TINYMCE=isi-api-key-dari-tiny-cloud
```

> Tanpa key ini editor soal akan tampil sebagai textarea biasa (tidak fatal).

### Langkah 9 — Jalankan aplikasi

Terminal 1 — server web:

```bash
php artisan serve
```

Terminal 2 — build frontend (untuk development):

```bash
npm run dev
```

Terminal 3 — queue worker (dibutuhkan untuk import & generate soal):

```bash
php artisan queue:work
```

Terminal 4 — Reverb server (dibutuhkan fitur realtime, misal deteksi kecurangan):

```bash
php artisan reverb:start
```

Buka **http://localhost:8000** di browser.

---

## 3. Referensi Isi `.env`

| Variable | Wajib | Keterangan |
|---|---|---|
| `APP_NAME` | ✅ | Nama aplikasi (tampil di browser & email) |
| `APP_ENV` | ✅ | `local` / `production` |
| `APP_KEY` | ✅ | Generate via `php artisan key:generate` |
| `APP_DEBUG` | ✅ | `true` saat development, **`false` wajib saat production** |
| `APP_URL` | ✅ | URL aplikasi, contoh `http://localhost:8000` / `https://ujian.example.com` |
| `DB_*` | ✅ | Kredensial database MySQL |
| `BROADCAST_CONNECTION` | ✅ | `reverb` (jangan `log`, karena dipakai fitur realtime) |
| `REVERB_APP_ID` | ✅ | Generate via `php artisan reverb:install` |
| `REVERB_APP_KEY` | ✅ | Sama seperti di atas |
| `REVERB_APP_SECRET` | ✅ | Sama seperti di atas — **jangan pernah commit ke git** |
| `REVERB_HOST` | ✅ | `localhost` (lokal) / domain (VPS) |
| `REVERB_PORT` | ✅ | `8080` (lokal) / `443` (VPS + HTTPS) |
| `REVERB_SCHEME` | ✅ | `http` (lokal) / `https` (VPS) |
| `FILESYSTEM_DISK` | ✅ | `public` — gambar soal disimpan di disk public |
| `QUEUE_CONNECTION` | ✅ | `database` (import & generate soal berjalan via queue) |
| `CACHE_STORE` | ✅ | `database` |
| `SESSION_DRIVER` | ✅ | `database` |
| `MAIL_MAILER` | ⬜ | `log` (dev) / `smtp` (production) |
| `MAIL_HOST` | ⬜ | Host SMTP, mis. `smtp.gmail.com` |
| `MAIL_PORT` | ⬜ | `587` (TLS) |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | ⬜ | Kredensial email pengirim |
| `MAIL_FROM_ADDRESS` | ⬜ | Alamat pengirim email |
| `API_KEY_TINYMCE` | ⬜ | Key editor soal dari tiny.cloud |
| `VITE_REVERB_*` | ✅ | **Wajib sama dengan `REVERB_*`** — dipakai frontend untuk WebSocket |

> ⚠️ **PENTING**: `VITE_*` di-bake saat `npm run build`. Jadi kalau ganti `REVERB_HOST`/`REVERB_*`, **wajib build ulang frontend** (`npm run build`).

---

## 4. Setup VPS / Production

### Langkah 1 — Salin & sesuaikan `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Ubah nilai berikut:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_HOST=127.0.0.1
DB_DATABASE=ujian_online
DB_USERNAME=user_produksi
DB_PASSWORD=password_kuat

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=xxx
REVERB_APP_KEY=xxx
REVERB_APP_SECRET=xxx
REVERB_HOST=domain-anda.com
REVERB_PORT=443
REVERB_SCHEME=https

FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email@pengirim.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="email@pengirim.com"

API_KEY_TINYMCE=isi-api-key
```

### Langkah 2 — Install & build

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Langkah 3 — Jalankan queue & Reverb via Supervisor

Buat 2 file di `/etc/supervisor/conf.d/`:

**`ujian-queue.conf`**
```ini
[program:ujian-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ujian-online/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ujian-online/storage/logs/queue.log
```

**`ujian-reverb.conf`**
```ini
[program:ujian-reverb]
command=php /var/www/ujian-online/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ujian-online/storage/logs/reverb.log
```

Lalu jalankan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### Langkah 4 — Nginx (contoh)

```nginx
server {
    listen 443 ssl http2;
    server_name domain-anda.com;

    root /var/www/ujian-online/public;
    index index.php;

    # ... SSL certificate ...

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    # WebSocket Reverb
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    # Block akses file .env
    location ~ /\.env { deny all; }
}
```

> Jalankan Reverb dengan `REVERB_HOST=domain-anda.com` di port 443, atau arahkan subdomain khusus (mis. `ws.domain-anda.com`) — sesuaikan proxy di atas.

---

## 5. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `No application encryption key has been specified` | `APP_KEY` kosong | `php artisan key:generate` |
| Gambar soal tidak muncul / 404 | Belum `storage:link` | `php artisan storage:link` |
| Error koneksi DB | Kredensial salah / DB belum dibuat | Cek `DB_*` di `.env`, pastikan database ada |
| Realtime (WebSocket) tidak jalan | `REVERB_HOST/PORT/SCHEME` salah atau server reverb mati | Cek isi `REVERB_*` + `VITE_REVERB_*`, jalankan `php artisan reverb:start`, **build ulang frontend** |
| Editor TinyMCE kosong | `API_KEY_TINYMCE` belum diisi | Isi key dari tiny.cloud, restart `npm run build` |
| Import soal menggantung | Queue worker tidak jalan | Jalankan `php artisan queue:work` |
| Push ke GitHub ditolak "contains secrets" | Ada token/secret ter-commit | Hapus secret dari commit, amend: `git add -A && git commit --amend --no-edit` |

---

## 6. Checklist Deploy VPS

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` = domain
- [ ] `APP_KEY` sudah di-generate
- [ ] Database MySQL dibuat & `DB_*` sesuai
- [ ] `php artisan migrate --force` berhasil
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] `REVERB_*` & `VITE_REVERB_*` sesuai domain, frontend di-build ulang
- [ ] Supervisor menjalankan queue & reverb
- [ ] `API_KEY_TINYMCE` terisi
- [ ] Folder `storage/` writable (chmod 775)
- [ ] Akses ke `.env` diblokir di Nginx
