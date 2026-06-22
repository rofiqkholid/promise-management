Karena Anda ingin menggunakan ini sebagai dasar **vibe coding**, maka PRD sebaiknya tidak hanya menjelaskan tabel, tetapi juga:

1. Tujuan bisnis modul
2. Fungsi setiap tabel
3. Relasi antar tabel
4. Fitur yang harus dibuat
5. Alur proses bisnis

---

# 1. Modul Master User & Organization

## Tabel: departments

### Fungsi

Master data departemen perusahaan.

Contoh:

```text
Sales
Marketing
Engineering
Purchasing
QA
Production
Tooling
```

### Digunakan untuk

* Owner SPK
* Support Department SPK
* Approval SPK
* Hak akses user

---

## Tabel: users

### Fungsi

Menyimpan user aplikasi.

### Relasi

```text
Department 1 --- N Users
```

### Digunakan untuk

* Login
* Approval
* Audit Log
* Tracking aktivitas

---

## Fitur

### Department Management

* Create Department
* Edit Department
* Nonaktifkan Department

### User Management

* Create User
* Edit User
* Reset Password
* Assign Department
* Assign Role
* Activate / Deactivate User

---

# 2. Modul Inquiry Management

---

## Tabel: project_inquiries

### Fungsi

Header project yang masuk dari customer.

Contoh:

```text
INQ-2026-001

Customer :
PT HPM

Project :
T9A Cover Comp Heat
```

### Relasi

```text
Project Inquiry
    |
    +--- Inquiry Products
```

---

## Tabel: inquiry_products

### Fungsi

Menyimpan daftar finish good yang sedang ditawarkan customer.

### Relasi

```text
Project Inquiry 1
    |
    +--- N Inquiry Products
```

---

### Contoh

```text
Customer :
PT HPM

Project :
T9A

Part No :
44517T9A0000

Part Name :
Cover Comp Heat
```

---

## Fitur

### Inquiry Header

* Create Inquiry
* Edit Inquiry
* Cancel Inquiry
* Inquiry History

### Import Excel

Upload file:

```text
Order Review Scoring
```

Import:

```text
Model
Part Number
Part Name
SOP
EOL
Volume
Destination
```

---

### Inquiry Product

* Add Product Manual
* Import Product Excel
* Edit Product
* Delete Product

---

# 3. Modul Feasibility Assessment

---

## Tabel: score_categories

### Fungsi

Master kategori penilaian.

Contoh:

```text
Customer Priority
Volume Potential
Type Product
Technical Capability
Investment
```

---

## Tabel: score_options

### Fungsi

Pilihan nilai setiap kategori.

Contoh:

```text
Customer Priority

Strategis = 175
Existing = 105
Baru = 35
```

---

## Tabel: assessment_rankings

### Fungsi

Konversi total score menjadi ranking.

Contoh:

```text
A = >=400
B = 300-399
C = 200-299
D = <200
```

---

## Tabel: priority_assessments

### Fungsi

Header hasil penilaian suatu finish good.

---

## Tabel: priority_assessment_details

### Fungsi

Detail pilihan score.

Contoh:

```text
Customer Priority = Strategis

Volume Potential = Tinggi

Investment = Rendah
```

---

## Relasi

```text
Inquiry Product
      |
      1
      |
      N
Priority Assessment
      |
      1
      |
      N
Assessment Detail
```

---

## Fitur

### Assessment Form

User memilih:

```text
Customer Priority
Volume Potential
Type Product
Technical Capability
Investment
```

---

### Auto Calculation

System menghitung:

```text
175
+125
+50
+100
+50
------
500
```

---

### Auto Ranking

System menentukan:

```text
500

=> Rank A
=> Review Now
```

---

### Dashboard Assessment

Filter:

```text
Customer
Project
Rank
Action
Status
```

---

# 4. Modul SPK (Work Order)

---

## Tujuan

Mengubah hasil feasibility menjadi:

```text
Surat Perintah Kerja
```

untuk department terkait.

---

# 5. Tabel work_orders

### Fungsi

Header SPK.

Contoh:

```text
SPK-2026-001

Priority :
Urgent

Department :
Purchasing
```

---

## Relasi

```text
Inquiry
   |
   1
   |
   N
Work Order
```

Karena inquiry dapat menghasilkan beberapa SPK.

---

## Fitur

### SPK Header

* Create SPK
* Select Inquiry
* Generate Nomor SPK
* Revision SPK
* Print SPK PDF

---

# 6. Tabel work_order_departments

### Fungsi

Support Department.

Contoh:

```text
Owner :
Purchasing

Support :
QA
Engineering
Production
```

---

## Fitur

* Add Support Department
* Remove Support Department

---

# 7. Tabel work_order_processes

### Fungsi

Master proses SPK / Tipe Permintaan beserta departemen penanggung jawabnya (Owner).

Contoh Data Master:

* `mpp` (MPP - Manufacturing Planing Proses) -> Owner: Engineering
* `kalkulasi_dies` (Kalkulasi Dies) -> Owner: Tooling
* `lifetime_tooling` (Life Time Manufacturing Tooling) -> Owner: Tooling
* `kalkulasi_cf` (Kalkulasi CF) -> Owner: Engineering
* `modifikasi_tools` (Modifikasi Tools) -> Owner: Tooling
* `sample_part` (Sample Part) -> Owner: Quality Assurance
* `design` (Design) -> Owner: Engineering
* `start_dev_tooling` (Start development tooling) -> Owner: Tooling
* `other_sourcing` (Other - Sourcing Supplier) -> Owner: Purchasing

---

# 8. Tabel work_order_process_details

### Fungsi

Checklist tipe permintaan / proses kerja yang dipilih dalam dokumen SPK.

Contoh Terpilih:

* ✓ MPP (Manufacturing Planing Proses)
* ✓ Design
* ✓ Kalkulasi Dies

---

## Fitur

* Select Process
* Unselect Process

---

# 9. Tabel work_order_products

### Fungsi

Snapshot finish good yang masuk SPK.

### Kenapa Snapshot?

Karena setelah SPK dibuat:

```text
Inquiry boleh berubah

SPK tidak boleh berubah
```

Sehingga data dicopy.

---

## Menyimpan

```text
Customer
Model
Part Number
Part Name
SOP
EOL
Volume
```

---

## Fitur

* Select Product dari Inquiry
* Clone ke SPK
* Edit data SPK

---

# 10. Tabel work_order_parts

### Fungsi

BOM atau komponen pembentuk finish good.

Contoh:

```text
FG
Cover Comp Heat
```

memiliki:

```text
RM
Stay A Cover

RM
Stay B Cover

RM
Stay C Cover
```

---

## Fitur

* Add Part
* Import Part Excel
* Edit Part
* Delete Part

---

# 11. Tabel work_order_attachments

### Fungsi

Lampiran SPK.

Contoh:

```text
Drawing
3D Model
Customer Spec
PDF RFQ
```

---

## Fitur

* Upload File
* Download File
* Preview File
* Delete File

---

# 12. Tabel work_order_approvals

### Fungsi

Workflow approval SPK.

---

### Approval Flow

```text
Marketing Staff
        ↓
Marketing GM
        ↓
Purchasing
```

---

## Fitur

### Approval

* Approve
* Reject
* Return Revision

### Tracking

Lihat:

```text
Siapa approve
Kapan approve
Komentar approve
```

---

# 13. Modul Audit Trail

---

## Tabel: audit_logs

### Fungsi

Mencatat seluruh aktivitas user.

---

### Contoh

```text
User :
Reza

Action :
UPDATE

Module :
SPK

Record :
SPK-2026-001
```

---

## Fitur

### Audit Viewer

Filter:

```text
User
Module
Tanggal
Action
```

---

# Ringkasan Modul yang Harus Dibuat

### Master

* Department Management
* User Management
* Role & Permission

### Inquiry

* Inquiry Header
* Inquiry Product
* Import Excel
* Inquiry Dashboard

### Feasibility

* Score Category
* Score Option
* Ranking Master
* Assessment Form
* Assessment Dashboard

### SPK

* SPK Header
* SPK Product
* SPK Part List
* Process Checklist
* Support Department
* Attachment
* Approval Workflow
* Revision Management
* PDF Generator

### System

* Notification
* Audit Trail
* Activity Log
* Soft Delete Management

Dengan struktur ini, alur bisnis menjadi:

```text
Inquiry
    ↓
Import Product
    ↓
Feasibility Assessment
    ↓
Ranking (A/B/C/D)
    ↓
Create SPK
    ↓
Select Product
    ↓
Input Part List
    ↓
Approval
    ↓
Issued SPK
```
