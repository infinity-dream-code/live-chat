# Setup Live Chat dengan Laravel Reverb

## 1. Konfigurasi .env

Tambahkan konfigurasi berikut di file `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 2. Generate Reverb Keys (Opsional)

Jika belum ada, jalankan:
```bash
php artisan reverb:install
```

## 3. Jalankan Reverb Server

Buka terminal baru dan jalankan:
```bash
php artisan reverb:start
```

Server Reverb akan berjalan di port 8080.

## 4. Jalankan Laravel Server

Di terminal lain, jalankan:
```bash
php artisan serve
```

## 5. Build Assets (jika belum)

```bash
npm run build
```

Atau untuk development dengan hot reload:
```bash
npm run dev
```

## Testing

1. Buka 2 browser/tab berbeda
2. Login dengan user berbeda (delkira dan iruma)
3. Kirim pesan dari satu browser
4. Pesan akan langsung muncul di browser lain tanpa refresh!

## Troubleshooting

- Pastikan Reverb server berjalan di port 8080
- Cek console browser (F12) untuk error WebSocket
- Pastikan `.env` sudah dikonfigurasi dengan benar
- Clear config cache: `php artisan config:clear`


