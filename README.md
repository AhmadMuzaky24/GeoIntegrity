# Setup GeoIntegrity

Panduan menjalankan proyek ini dari nol setelah clone dari GitHub.

Aplikasi ini terdiri dari dua bagian:

| Bagian | Isi | Butuh |
|---|---|---|
| `geo-integrity/` (root) | Laravel 13 — peta Leaflet, CRUD titik, halaman detail & verifikasi | PHP + Composer |
| `geo-integrity/chain/` | Kontrak Solidity `GeoAnchor` + Hardhat (compile, test, local node) | Node.js + npm |

Data titik disimpan di SQLite. Anchor (hash credential) disimpan di smart contract
saat `CHAIN_DRIVER=evm`; kalau node tidak jalan, aplikasi bisa memakai hash-chain
lokal di tabel `blocks` (`CHAIN_DRIVER=database`).

---

## 1. Dependencies yang harus di-install

### Wajib

| Dependency | Versi minimum | Cek dengan | Catatan |
|---|---|---|---|
| PHP | 8.3 (diuji di 8.5) | `php -v` | ekstensi `pdo_sqlite` dan `sqlite3` harus aktif |
| Composer | 2.x | `composer -V` | dependency manager PHP |
| Node.js | 20 LTS (diuji di 24) | `node -v` | untuk Hardhat dan skrip anchor |
| npm | 10.x | `npm -v` | ikut Node.js |
| Git | bebas | `git --version` | untuk clone repo |

Cek ekstensi PHP:

```bash
php -m | grep -i sqlite      # Windows PowerShell: php -m | Select-String sqlite
```

Harus muncul `pdo_sqlite` dan `sqlite3`. Kalau tidak ada, aktifkan di `php.ini`
(hapus `;` di depan `extension=pdo_sqlite` dan `extension=sqlite3`).

Tidak perlu MySQL/MariaDB, tidak perlu Redis, tidak perlu Docker.

### Opsional

| Dependency | Untuk apa |
|---|---|
| `npm install` di root proyek | hanya kalau ingin memakai `resources/views/welcome.blade.php` (butuh Vite/Tailwind). Aplikasi utama tidak memakainya, jadi boleh dilewati |

---

## 2. Menjalankan aplikasi (mode offline, paling cepat)

Mode ini tidak butuh Node/Hardhat sama sekali — anchor disimpan di tabel `blocks`.

```bash
git clone <URL-REPO> geo-integrity
cd geo-integrity

composer install

cp .env.example .env
# Windows PowerShell: Copy-Item .env.example .env

php artisan key:generate

# buat file database SQLite kosong
touch database/database.sqlite
# Windows PowerShell: New-Item database/database.sqlite

php artisan migrate --seed
```

Pastikan di `.env` tertulis:

```
CHAIN_DRIVER=database
```

Lalu jalankan:

```bash
php artisan serve
```

Buka http://127.0.0.1:8000

`migrate --seed` membuat 4 user demo: `User A`, `User B`, `User C` (email
`a@demo.test`, `b@demo.test`, `c@demo.test`, password `demo`) plus satu user
test bawaan Laravel.

---

## 3. Menjalankan dengan blockchain Solidity (Hardhat)

Dibutuhkan tiga terminal. Urutannya penting.

### Terminal 1 — install & test kontrak

```bash
cd chain
npm install
npx hardhat test
```

Hasil yang diharapkan: `6 passing`. Langkah ini meng-compile kontrak dan
mengunduh compiler Solidity pada run pertama (butuh internet, ± 1–2 menit).

### Terminal 1 — nyalakan local chain

```bash
npx hardhat node
```

Biarkan terminal ini berjalan. Node mendengarkan di `http://127.0.0.1:8545`
(chain id 31337) dan mencetak 20 akun tes.

### Terminal 2 — deploy kontrak

```bash
cd chain
npx hardhat run scripts/deploy.js --network localhost
```

Keluarannya berupa JSON; catat `address`, misalnya:

```json
{ "address": "0x5FbDB2315678afecb367f032d93F642f64180aa3", ... }
```

### Terminal 2 — arahkan Laravel ke kontrak

Edit `.env` di root proyek:

```
CHAIN_DRIVER=evm
CHAIN_RPC_URL=http://127.0.0.1:8545
CHAIN_CONTRACT_ADDRESS=0x5FbDB2315678afecb367f032d93F642f64180aa3
CHAIN_DEPLOYER_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
```

> **Peringatan:** `CHAIN_DEPLOYER_KEY` di atas adalah private key akun #0 Hardhat
> yang publik diketahui. Hanya untuk node lokal di laptop sendiri. Jangan pernah
> dipakai di testnet atau mainnet, dan jangan pernah commit `.env`.

```bash
php artisan config:clear
```

### Terminal 3 — jalankan web

```bash
php artisan serve
```

Buka http://127.0.0.1:8000, lalu:

1. pilih **User A** di header, isi form, klik peta untuk memilih koordinat, simpan;
2. ganti ke **User B**, edit titik tersebut (ubah longitude), simpan;
3. ganti ke **User C**, buka detail titik — muncul badge *Hash tidak valid*,
   *Diubah oleh pihak lain*, dan teks *Credential tidak dapat di-anchor ulang*.

Halaman `/chain` menampilkan semua anchor dari kontrak beserta driver yang aktif.

---

## 4. Verifikasi bahwa semuanya sehat

```bash
# root proyek
php artisan test              # 28 test (feature + unit)

# folder chain/
npx hardhat test              # 6 test kontrak

# dengan server jalan
curl http://127.0.0.1:8000/chain/verify
```

Contoh jawaban `/chain/verify` saat mode EVM dan semua data cocok:

```json
{"valid":true,"blocks":22,"driver":"evm","data":{"checked":4,"tampered":[]}}
```

Arti field: `valid` = rantai/kontrak terjangkau dan konsisten, `blocks` = jumlah
anchor, `data.tampered` = daftar titik yang isinya menyimpang dari anchor
(`reason: hash_mismatch` atau `unregistered`).

Bukti per titik:

```bash
curl http://127.0.0.1:8000/chain/proof/1
```

---

## 5. Kalau ada masalah

| Gejala | Penyebab & solusi |
|---|---|
| `could not find driver (sqlite)` | ekstensi `pdo_sqlite` belum aktif → edit `php.ini`, restart terminal |
| `No application encryption key has been specified` | `.env` belum dibuat / `php artisan key:generate` belum dijalankan |
| `SQLSTATE[HY000]: unable to open database file` | file `database/database.sqlite` belum ada → `touch database/database.sqlite` lalu `php artisan migrate --seed` |
| `npm install` ditolak PowerShell | `Set-ExecutionPolicy -Scope Process -Bypass` lalu ulangi |
| `npx hardhat test` gagal unduh compiler | tidak ada internet saat run pertama; ulangi saat online |
| Port 8000 dipakai | `php artisan serve --port=8001` |
| Port 8545 dipakai (node lama masih hidup) | tutup proses Hardhat sebelumnya, atau `CHAIN_RPC_URL` ke port lain |
| Halaman `/chain` menampilkan *Kontrak tidak terjangkau* | `npx hardhat node` belum jalan, atau `CHAIN_CONTRACT_ADDRESS` salah → jalankan node, deploy, `php artisan config:clear` |
| Anchor hilang setelah restart | node Hardhat menyimpan state di memori. Deploy ulang, lalu replay dari tabel `blocks`: `php artisan tinker --execute="foreach (App\Models\Block::orderBy('height')->get() as \$b) { app(App\Contracts\AnchorStore::class)->anchor(\$b->geo_location_id, \$b->data_hash, \$b->actor); }"` |
| Ingin kembali offline tanpa Node | set `CHAIN_DRIVER=database` di `.env`, lalu `php artisan config:clear` |

---

## 6. Yang tidak ikut ter-commit

Repo tidak menyertakan file berikut, jadi harus dibuat/di-install sendiri:

- `.env` → salin dari `.env.example`
- `vendor/` → `composer install`
- `chain/node_modules/`, `chain/artifacts/`, `chain/cache/` → `npm install`
- `database/database.sqlite` → buat file kosong + `php artisan migrate --seed`

---

## 7. Peta file penting

```
app/Contracts/AnchorStore.php        kontrak antarmuka penyimpanan anchor
app/Services/EvmAnchorStore.php      driver EVM (JSON-RPC + script Node)
app/Services/DatabaseAnchorStore.php driver hash-chain lokal (fallback)
app/Services/GeoIntegrity.php        aturan validasi: data cocok dengan anchor?
app/Services/GeoLocationHasher.php   SHA-256 konten titik
app/Support/Actor.php                identitas demo (A/B/C) dari session
chain/contracts/GeoAnchor.sol        kontrak Solidity
chain/scripts/deploy.js              deploy ke node lokal
chain/scripts/anchor.js              kirim transaksi anchor (dipanggil Laravel)
chain/README.md                      catatan khusus folder chain
tests/Feature/BlockchainTest.php     test alur anchor & deteksi perubahan
tests/Feature/DemoTraceabilityTest.php  skenario A buat, B ubah, C mendeteksi
tests/Unit/EvmAnchorStoreTest.php    test encode/decode ABI manual
```
