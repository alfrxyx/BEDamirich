# TODO: Perbaikan Notifikasi untuk Admin

## Tugas Utama
Perbaiki sistem notifikasi agar notifikasi yang dikirim oleh user masuk ke kedua admin (super admin dan admin biasa).

## Analisis Masalah
- Saat ini, notifikasi hanya dikirim ke user dengan `posisi_id = 1` (hanya Super Admin).
- Admin biasa (Manager) tidak menerima notifikasi.
- Perlu ubah logika dari `posisi_id = 1` ke `level_position IN ('Partner', 'Manager')`.

## File yang Perlu Diubah
- [ ] `app/Http/Controllers/LeaveRequestController.php` - Notifikasi pengajuan cuti
- [ ] `app/Http/Controllers/AbsensiController.php` - Notifikasi keterlambatan absensi

## Langkah Implementasi
1. Ubah query admin dari `where('posisi_id', 1)` ke `whereIn('level_position', ['Partner', 'Manager'])`
2. Pastikan notifikasi dikirim ke semua admin yang aktif
3. Test bahwa kedua admin menerima notifikasi

## Status
- [x] Analisis selesai
- [ ] Implementasi LeaveRequestController
- [ ] Implementasi AbsensiController
- [ ] Testing
