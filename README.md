# KasirApp
## Intruduction
This is a cashier website project using PHP and Oracle21c services

## What features are available?
- Dashboard
- Item Data
- Transaction
- History

## Koneksi.php
Modify this section, according to your connection
```php
<?php
$conn = oci_connect('user oracle', 'password', '(DESCRIPTION=(ADDRESS=(PROTOCOL=....)(HOST=......)
                        (PORT=.....))(CONNECT_DATA=(SERVICE_NAME=.....)))');
if (!$conn) {
    $e = oci_error();
    error_log("Koneksi Oracle gagal: " . $e['message']);
    die("Koneksi database gagal.");
}
?>
```

## 📦 Struktur Tabel (Oracle SQL)

### 1️⃣ Tabel TB_BARANG
```sql
CREATE TABLE TBL_BARANG (
  KODE_BARANG VARCHAR2(10) PRIMARY KEY,
  NAMA_BARANG VARCHAR2(100),
  KATEGORI VARCHAR2(50),
  STOK NUMBER,
  SATUAN VARCHAR2(20),
  HARGA NUMBER
);
```
### 2️⃣ Tabel TBL_TRANSAKSI
```sql
CREATE TABLE TBL_TRANSAKSI (
  ID_TRANSAKSI VARCHAR2(10) PRIMARY KEY,
  TANGGAL DATE DEFAULT SYSDATE,
  TOTAL NUMBER,
  KASIR VARCHAR2(50)
);
ALTER TABLE TBL_TRANSAKSI ADD TOTAL_BAYAR NUMBER;
ALTER TABLE TBL_TRANSAKSI ADD (KEMBALIAN NUMBER(12,2));
```
### 3️⃣ Tabel TBL_DETAIL_TRANSAKSI
```sql
CREATE TABLE TBL_DETAIL_TRANSAKSI (
  ID_DETAIL VARCHAR2(10) PRIMARY KEY,
  ID_TRANSAKSI VARCHAR2(10),
  KODE_BARANG VARCHAR2(10),
  JUMLAH NUMBER,
  SUBTOTAL NUMBER,
  FOREIGN KEY (ID_TRANSAKSI) REFERENCES TBL_TRANSAKSI(ID_TRANSAKSI),
  FOREIGN KEY (KODE_BARANG) REFERENCES TBL_BARANG(KODE_BARANG)
);
```
### 4️⃣ Sequence
```sql

CREATE SEQUENCE SEQ_TRANSAKSI
START WITH 1
INCREMENT BY 1;

CREATE SEQUENCE SEQ_DETAIL
START WITH 1
INCREMENT BY 1;
```