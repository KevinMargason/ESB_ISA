# Laporan Project

## Secure Supply Chain Tracking System

### Program Studi Informatika IF-UBAYA

---

## 1. Identitas Project

- Nama aplikasi: Secure Supply Chain Tracking System (S2CTS)
- Tema: Tema 9 - Secure Supply Chain Tracking System
- Platform: Web Application
- Framework: Laravel 12 (PHP 8.2)
- Database: MySQL
- Target pengguna: Admin, Supplier, Kurir

## 2. Latar Belakang

Rantai pasok merupakan komponen kritis dalam distribusi barang dari pemasok hingga pelanggan. Pada banyak organisasi, proses pencatatan pergerakan barang masih rentan terhadap manipulasi data, kehilangan jejak transaksi, dan akses tanpa otorisasi. Risiko ini meningkat ketika proses melibatkan banyak peran operasional, seperti admin gudang, supplier, dan kurir.

Untuk menjawab tantangan tersebut, project ini merancang Secure Supply Chain Tracking System yang menekankan ketertelusuran (traceability), integritas data, dan auditability. Sistem tidak hanya mencatat status barang dari gudang menuju distribusi dan customer, tetapi juga menerapkan desain keamanan data yang terstruktur: enkripsi data penting, hash chain untuk log transaksi, verifikasi integritas, dan audit trail lengkap.

## 3. Rumusan Masalah

1. Bagaimana merancang sistem tracking supply chain yang dapat memantau status barang secara real-time dan akurat?
2. Bagaimana mencegah pemalsuan atau perubahan data transaksi oleh pihak yang tidak berwenang?
3. Bagaimana menerapkan kontrol akses berbasis peran pada proses operasional multi-user?
4. Bagaimana membuktikan bahwa data yang tersimpan memiliki integritas tinggi dan dapat diaudit?

## 4. Tujuan Project

1. Membangun aplikasi tracking barang dari gudang sampai customer dengan alur status yang jelas.
2. Menerapkan autentikasi, otorisasi, dan pemisahan hak akses untuk peran admin, supplier, dan kurir.
3. Mengimplementasikan enkripsi dan dekripsi data menggunakan minimal dua cipher.
4. Membangun mekanisme hash chain untuk memastikan integritas log transaksi.
5. Menyediakan audit trail lengkap dan fitur keluaran dokumen (PDF).
6. Melakukan security testing melalui simulasi serangan terhadap desain keamanan yang dibangun.

## 5. Ruang Lingkup

### 5.1 In Scope

1. Registrasi dan login pengguna.
2. Role-based access control (admin, supplier, kurir).
3. Input data barang dan metadata pengiriman.
4. Tracking status: gudang -> distribusi -> customer.
5. Enkripsi data sensitif pada level aplikasi.
6. Hash chain pada log transaksi.
7. Verifikasi integritas data.
8. Audit trail terstruktur.
9. Export laporan transaksi dalam format PDF.

### 5.2 Out of Scope

1. Integrasi perangkat IoT untuk sensor suhu/lokasi.
2. Integrasi langsung dengan sistem ERP eksternal.
3. Integrasi pembayaran online.

## 6. Metodologi Pengembangan

Metodologi yang digunakan adalah Agile Iterative Delivery dengan tahapan:

1. Requirement elicitation dan analisis ancaman.
2. Perancangan arsitektur, database, dan kontrol keamanan.
3. Implementasi fitur inti dan modul security.
4. Pengujian fungsional, pengujian keamanan, dan perbaikan.
5. Dokumentasi, finalisasi laporan, dan persiapan presentasi.

## 7. Gambaran Arsitektur Sistem

Arsitektur aplikasi menggunakan model 3 lapisan:

1. Presentation Layer: UI web untuk admin, supplier, kurir.
2. Application Layer: Laravel controllers, services, policy, dan middleware keamanan.
3. Data Layer: MySQL untuk data operasional, tabel log untuk hash chain dan audit.

### 7.1 Komponen Keamanan Utama

1. Authentication + session management.
2. Role-based authorization.
3. Hybrid encryption untuk data sensitif.
4. Hash chain per log transaksi.
5. Integrity verification engine.
6. Audit trail immutable style.

## 8. Fitur Wajib dan Implementasi

### 8.1 Input Data Barang

Data yang diinput:

1. Kode barang (unik)
2. Nama barang
3. Kategori
4. Jumlah
5. Supplier pemilik
6. Lokasi awal gudang
7. Catatan keamanan (opsional)

Validasi utama:

1. Kode barang unik.
2. Jumlah > 0.
3. Supplier aktif.
4. Semua transaksi input membentuk log chain baru.

### 8.2 Tracking Status

Alur status standar:

1. `WAREHOUSE`
2. `DISTRIBUTION`
3. `CUSTOMER_RECEIVED`

Setiap perubahan status menghasilkan:

1. Rekam event tracking.
2. Hash log baru yang mengacu hash sebelumnya.
3. Catatan siapa pelaku perubahan dan timestamp.

### 8.3 Multi-role

1. Admin:
    - Kelola user, barang, verifikasi integritas, audit, export laporan.
2. Supplier:
    - Input barang, lihat status barang miliknya, update metadata tertentu.
3. Kurir:
    - Update status pengiriman, bukti serah terima, catatan distribusi.

## 9. Desain Database MySQL

### 9.1 Entitas Utama

1. `users`
2. `roles`
3. `user_roles`
4. `items`
5. `shipments`
6. `tracking_events`
7. `transaction_logs`
8. `audit_trails`
9. `integrity_checks`

### 9.2 Contoh Struktur Tabel Inti

```sql
CREATE TABLE items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  item_code VARCHAR(64) NOT NULL UNIQUE,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  supplier_id BIGINT NOT NULL,
  sensitive_blob TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (supplier_id) REFERENCES users(id)
);

CREATE TABLE tracking_events (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  item_id BIGINT NOT NULL,
  shipment_id BIGINT NOT NULL,
  status ENUM('WAREHOUSE','DISTRIBUTION','CUSTOMER_RECEIVED') NOT NULL,
  actor_id BIGINT NOT NULL,
  event_time TIMESTAMP NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (actor_id) REFERENCES users(id)
);

CREATE TABLE transaction_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ref_type VARCHAR(50) NOT NULL,
  ref_id BIGINT NOT NULL,
  actor_id BIGINT NOT NULL,
  event_name VARCHAR(100) NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  prev_hash CHAR(64) NULL,
  current_hash CHAR(64) NOT NULL,
  chain_index BIGINT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (actor_id) REFERENCES users(id)
);
```

## 10. Desain Keamanan Data

### 10.1 Klasifikasi Data

1. Public: status umum pengiriman.
2. Internal: data operasional item dan shipment.
3. Sensitive: detail identitas pelanggan, catatan internal, dokumen bukti.

### 10.2 Encryption dan Decryption (Minimal 2 Cipher)

Project menggunakan pendekatan hybrid encryption:

1. Cipher 1: AES-256-GCM
    - Untuk mengenkripsi payload data sensitif.
    - Memberikan confidentiality + integrity melalui authentication tag.
2. Cipher 2: RSA-2048 OAEP
    - Untuk enkripsi Data Encryption Key (DEK).
    - DEK dibungkus (wrapped) dengan public key sesuai role/layanan.

Alur enkripsi:

1. Generate DEK acak 256-bit untuk setiap record sensitif.
2. Encrypt payload sensitif menggunakan AES-256-GCM (DEK + nonce).
3. Encrypt DEK menggunakan RSA public key.
4. Simpan ciphertext payload, nonce, tag, dan wrapped DEK.

Alur dekripsi:

1. Ambil wrapped DEK dan decrypt dengan RSA private key.
2. Gunakan DEK untuk decrypt AES-256-GCM payload.
3. Validasi tag GCM untuk memastikan data tidak dimodifikasi.

### 10.3 Hash Chain untuk Transaksi/Log

Rumus hash chain log:

$$
H_n = SHA256(ref\_type_n || ref\_id_n || payload\_hash_n || actor\_id_n || timestamp_n || H_{n-1})
$$

Keterangan:

1. $H_n$ adalah hash log ke-$n$.
2. $H_{n-1}$ adalah hash log sebelumnya.
3. Jika satu log dimodifikasi, hash berikutnya menjadi tidak valid.

### 10.4 Integrity Verification

Prosedur verifikasi:

1. Ambil seluruh log berdasarkan chain index.
2. Rekalkulasi hash satu per satu.
3. Cocokkan hasil rekalkulasi dengan `current_hash` tersimpan.
4. Laporkan mismatch beserta lokasi chain index.

Output verifikasi:

1. Status: VALID / TAMPERED
2. Jumlah log diperiksa
3. Posisi mismatch (jika ada)
4. Timestamp pemeriksaan dan aktor pemeriksa

### 10.5 Audit Trail Lengkap

Audit trail mencatat:

1. Siapa (user id, role)
2. Melakukan apa (aksi)
3. Pada data mana (entity id)
4. Kapan (timestamp)
5. Dari mana (IP, user-agent)
6. Sebelum dan sesudah (old/new values untuk field kritikal)

## 11. BPMN dan Alur Proses Keamanan

Diagram BPMN dan flow keamanan disertakan pada file:

- `docs/diagram-keamanan-mermaid.md`

Ringkasan alur:

1. Supplier input barang.
2. Sistem validasi dan enkripsi data sensitif.
3. Sistem membuat transaction log hash chain.
4. Kurir update status distribusi.
5. Sistem append log chain.
6. Admin menjalankan integrity verification.
7. Sistem menghasilkan laporan dan audit trail.

## 12. Register, Login, dan Hak Akses

### 12.1 Register/Login

1. Register akun oleh admin atau self-registration terkontrol.
2. Login menggunakan email dan password hashed.
3. Session timeout dan rotasi token session.

### 12.2 Authorization

1. Middleware role-based access.
2. Policy pada resource penting (`items`, `shipments`, `logs`).
3. Endpoint audit dan integrity hanya untuk admin.

## 13. Mencetak Output

Sistem menyediakan export:

1. Laporan tracking per barang (PDF)
2. Ringkasan audit trail (PDF)
3. Hasil integrity verification (PDF)

Konten PDF minimal:

1. Identitas item dan shipment
2. Timeline status
3. Ringkasan hash chain
4. Status integritas
5. Tanda waktu cetak dan pencetak

## 14. Security Testing

### 14.1 Tujuan

Menguji ketahanan implementasi cryptography, integritas log, dan desain akses.

### 14.2 Skenario Simulasi Attack

1. Log tampering test
    - Ubah 1 record `transaction_logs.current_hash` secara manual.
    - Jalankan integrity verification.
    - Expected: terdeteksi mismatch.

2. Payload tampering test
    - Modifikasi ciphertext payload sensitif.
    - Jalankan proses dekripsi.
    - Expected: gagal validasi tag GCM.

3. SQL injection test
    - Uji input field pencarian dan filter.
    - Expected: query aman karena parameter binding.

4. Broken access control test
    - Kurir mencoba akses endpoint admin.
    - Expected: HTTP 403 dan audit event unauthorized.

5. Brute force login simulation
    - Uji percobaan login gagal berulang.
    - Expected: rate limiting / lockout sementara.

### 14.3 Hasil yang Diharapkan

1. Integrity mismatch terdeteksi konsisten.
2. Data sensitif tidak dapat dibaca tanpa kunci sah.
3. Endpoint terlindung dari akses role tidak sesuai.
4. Audit trail memuat jejak semua percobaan penting.

## 15. Fitur Tambahan (Opsional)

1. OTP / 2FA untuk admin.
2. Data masking pada halaman daftar pelanggan.
3. Secure session token + device binding sederhana.
4. Privacy by design (minimasi data personal).

## 16. Mapping Terhadap Rubrik Penilaian

1. Fitur dasar aplikasi: terpenuhi (input, tracking, role, auth, output).
2. Database MySQL: terpenuhi (desain tabel relasional).
3. Register/login: terpenuhi.
4. Pemilahan hak akses: terpenuhi melalui RBAC.
5. Cetak output file: terpenuhi (PDF).
6. Encryption/decryption 2 cipher: terpenuhi (AES-256-GCM + RSA-2048 OAEP).
7. Desain keamanan + BPMN/alur: terpenuhi.
8. Security testing dengan simulasi attack: terpenuhi.
9. Fitur tambahan keamanan: disiapkan (2FA, masking, secure session).

## 17. Rencana Implementasi Tahapan

1. Sprint 1: setup auth + role + model data inti.
2. Sprint 2: fitur input barang + tracking status + audit dasar.
3. Sprint 3: encryption layer + hash chain + integrity check.
4. Sprint 4: export PDF + security testing + hardening.
5. Sprint 5: dokumentasi final + persiapan presentasi.

## 18. Kesimpulan

Secure Supply Chain Tracking System dirancang untuk menjawab kebutuhan tracking rantai pasok yang aman, dapat diverifikasi, dan siap audit. Penggabungan mekanisme RBAC, hybrid encryption, hash chain, dan integrity verification memastikan bahwa data operasional tidak hanya tersedia, tetapi juga terlindungi dari manipulasi. Dengan pengujian keamanan terstruktur dan dokumentasi formal, project ini memenuhi kebutuhan akademik sekaligus relevan untuk implementasi nyata.

## 19. Daftar Referensi

1. NIST SP 800-38D, Recommendation for Block Cipher Modes of Operation: GCM and GMAC.
2. NIST SP 800-56B Rev. 2, Recommendation for Pair-Wise Key Establishment Using Integer Factorization Cryptography.
3. OWASP Top 10:2021.
4. OWASP ASVS 4.0.3.
5. Laravel Documentation 12.x (Authentication, Authorization, Encryption, Validation).
6. MySQL 8.0 Reference Manual.
7. RFC 8017: PKCS #1 v2.2 (RSA Cryptography Specifications).
8. ISO/IEC 27001:2022 Information security, cybersecurity and privacy protection.
9. BPMN 2.0 Specification, OMG.
10. Schneier, B. Applied Cryptography, 2nd Edition.

## 20. Lampiran

1. Lampiran A: Diagram BPMN dan alur keamanan (`docs/diagram-keamanan-mermaid.md`).
2. Lampiran B: Draft pembagian tugas kelompok (`docs/pembagian-tugas-kelompok.md`).
3. Lampiran C: Draft materi presentasi (`docs/presentasi-secure-supply-chain.pptx`).
