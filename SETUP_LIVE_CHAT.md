# Setup Live Chat - Instruksi Lengkap

## 1. Konfigurasi .env

Pastikan file `.env` memiliki konfigurasi berikut (hapus duplikat jika ada):

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=167275
REVERB_APP_KEY=qzxhf7rhc8esyzazrhd3
REVERB_APP_SECRET=ebcrzskpm1pdqjuipkzu
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 2. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## 3. Build Assets

```bash
npm run build
```

## 4. Jalankan Server

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Reverb Server (untuk WebSocket):**
```bash
php artisan reverb:start
```

## 5. Testing

1. Buka 2 browser/tab berbeda
2. Login dengan user berbeda:
   - User 1: username `delkira`, password `123`
   - User 2: username `iruma`, password `123`
3. Kirim pesan dari satu browser
4. Pesan akan langsung muncul di browser lain dalam 1 detik (via polling) atau instant (via WebSocket)

## Catatan

- **Polling selalu berjalan** sebagai fallback (setiap 1 detik)
- Jika WebSocket terhubung, polling dikurangi menjadi setiap 3 detik sebagai backup
- Chat akan tetap berfungsi meskipun Reverb server tidak berjalan (menggunakan polling)
- Untuk performa terbaik, jalankan Reverb server


