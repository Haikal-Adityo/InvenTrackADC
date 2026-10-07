# Deploy NextLog (Zip)

Script membuat 2 zip di `_deploy/`:

| Zip | Isi | Upload ke |
|---|---|---|
| `nextlog-app-only_<stamp>.zip` | Kode Laravel + `vendor` production (tanpa `public/`) | Folder aplikasi di server |
| `nextlog-public-only_<stamp>.zip` | Isi `public/` (termasuk `build/` hasil Vite) | Folder public / `public_html` |

## Prasyarat

- PHP 8.4 lewat alias `php84` di profile PowerShell (`php84 -v` harus jalan)
- Composer di `C:\ProgramData\ComposerSetup\bin\composer.phar`
- Node + npm
- `C:\Windows\System32\tar.exe` (bawaan Windows 10 1803+)

## Cara jalan

Pilih salah satu, **jangan copy-paste isi script ke terminal** (kalau di-paste, error tidak menghentikan langkah berikutnya).

**PowerShell**
```powershell
cd C:\xampp\htdocs\InvenTrackADC
.\deploy-zip.ps1
```
Kalau muncul "running scripts is disabled": `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned` (sekali saja).

**Git Bash**
```bash
cd /c/xampp/htdocs/InvenTrackADC
bash deploy-zip.sh
```
PHP 8.4 diambil dari alias `php84` di PowerShell. Kalau gagal, set manual:
`PHP=/c/path/ke/php84/php.exe bash deploy-zip.sh`

## Alur

1. `npm run build`
2. `composer install --no-dev --optimize-autoloader` (pakai PHP 8.4)
3. Copy ke staging, buang file yang tidak perlu
4. Zip + cek tidak ada paket dev (`phpunit`, `mockery`, `collision`) di `vendor`
5. **Selalu** `composer install` lagi supaya `vendor` lokal kembali lengkap (sukses maupun gagal)

Kalau gagal: zip setengah jadi dan folder staging otomatis dihapus.

## Yang tidak ikut zip

- **Root:** `public/` (punya zip sendiri), `node_modules`, `tests`, `_deploy`, `.git`, `.github`, `.hermes`, `.idea`, `.vscode`
- **File di mana pun:** `.env*`, `*.log`, `*.pem`, `*.sql`, `*.zip`, `*.bat`, `*.vbs`, `*.ps1`, `*.sh`, `README.md`, `DEPLOY.md`, `IDEA.md`, `.hermes.md`, `phpunit.xml`, `auth.json`, file Docker/Railway/Nixpacks/nginx, `package*.json`, `vite.config.js`, dotfile editor
- **Runtime Laravel** (dibuat kosong + `.gitkeep`, data server tidak tertimpa): `storage/logs`, `storage/app/public`, `storage/framework/{sessions,cache/data,views}`, `bootstrap/cache`
- **Dari public:** `storage` (symlink upload), `signatures` (TTD manager), `hot`, `index.php`, `.htaccess` (versi server dipertahankan)

## Setelah upload

Kalau server punya akses terminal:
```bash
php artisan migrate --force        # hanya kalau ada migration baru
php artisan optimize:clear && php artisan optimize
```
Pastikan symlink `public/storage` di server masih ada (`php artisan storage:link` kalau hilang).

## Troubleshooting

| Gejala | Penyebab / solusi |
|---|---|
| `Butuh PHP 8.4, dapat 8.3.x` | Alias `php84` tidak terbaca. Jalankan dari terminal yang sudah load profile, atau set `PHP=...` (bash) |
| `app-only zip masih berisi paket dev` | `composer install --no-dev` tidak jalan dengan benar; cek output langkah 2 |
| `vendor` lokal kurang paket dev | `php84 C:\ProgramData\ComposerSetup\bin\composer.phar install` |
