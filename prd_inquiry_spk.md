# PRODUCT REQUIREMENT DOCUMENT (PRD)

## Project Name

Project Feasibility Study & Work Order Management System

Version: 1.1

---

# 1. Background

Saat ini proses pengelolaan RFQ (Request For Quotation), Feasibility Study, Priority Assessment, dan SPK (Surat Perintah Kerja) masih dilakukan menggunakan dokumen Excel dan form manual.

Permasalahan yang terjadi:

* Data RFQ tersebar pada banyak file Excel.
* Sulit melakukan tracking status inquiry.
* Penilaian feasibility dilakukan secara manual.
* Pembuatan SPK masih menggunakan dokumen terpisah.
* Tidak ada histori revisi SPK yang terstruktur.
* Sulit melakukan monitoring progress project.

Sistem ini dibuat untuk mengelola seluruh proses mulai dari Inquiry hingga Work Order (SPK) dalam satu platform terintegrasi berbasis web.

---

# 2. Objectives

## Primary Objectives

* Digitalisasi proses inquiry project.
* Mempermudah feasibility assessment.
* Menghasilkan SPK secara terstruktur.
* Mengelola approval workflow.
* Menyediakan histori revisi SPK.
* Menjadi single source of truth untuk project baru.

## Success Criteria

* Inquiry dapat dibuat dan diimport dari Excel.
* Assessment dapat dihitung otomatis berdasarkan master rangking.
* SPK dapat dibuat dari inquiry.
* Approval dapat dilakukan secara digital.
* Revisi SPK dapat ditelusuri dengan mekanisme full clone data.
* Semua aktivitas tercatat dalam audit log.
* Sistem dapat diakses melalui browser desktop maupun perangkat mobile dengan tampilan responsif.

---

# 3. User Roles & Department Assignment

## User Department Assignment

* Setiap User terikat pada tepat 1 Department melalui kolom `id_dept` (seperti `Engineering`, `QA`, `Sales`, `Purchasing`, dll.).

## Role Management

* Pembagian role mengikuti aturan dan struktur RBAC yang terintegrasi dengan `promise-admin`.

## Roles & Hak Akses

### Sales

* Create Inquiry
* Edit Inquiry
* Import Excel
* Create SPK
* Revise SPK
* View Dashboard

### Approver

* Review SPK
* Approve / Reject SPK
* Request Revision
* View Dashboard

### User

* View Released SPK
* View Related Project Information

### Administrator

* Master Data Management (termasuk Assessment Rankings)
* User Management
* Role Management
* Assessment Configuration
* System Configuration

---

# 4. Business Process

Customer RFQ

↓

Sales Create Inquiry

↓

Import Finish Good List

↓

Feasibility Assessment

↓

Priority Ranking

↓

Create SPK

↓

Approval Workflow (Marketing Staff -> Marketing GM -> Purchasing)

↓

SPK Released

↓

Project Execution

---

# 5. Functional Requirements

## Module 1 – Inquiry Management

### Create Inquiry

Fields:

* Inquiry No (Auto Generate)
* Customer Name
* Project Name
* Inquiry Date
* Status
* Remarks
* Deleted At (Soft Delete)

Actions:

* Create
* Edit
* Cancel
* Close

---

### Inquiry Product Import

Upload Excel

Columns:

* Model Name
* Customer Part No
* Customer Part Name
* Part Category
* Destination
* SOP Date
* EOL Date
* Model Life
* Annual Volume
* Has 2D Data
* Has 3D Data
* Has Technical Document

Features:

* Preview Import
* Validation
* Duplicate Check
* Error Log Download

---

### Inquiry Product List

Features:

* Search
* Filter
* Pagination
* Export Excel

---

## Module 2 – Feasibility Assessment

### Score Category Management

Master Data

Examples:

* Customer Priority
* Volume Potential
* Product Type
* Technical Capability
* Investment Requirement

---

### Score Option Management

Pilihan skor bersifat baku dan tetap (fixed) sesuai Excel perusahaan. Menggunakan `score_value` pada `score_options` tanpa engine pembobotan yang kompleks.

Contoh data:
* Customer Priority: Strategis (175), Existing (105), Baru (35)
* Volume Potential: Tinggi (125), Sedang (75), Rendah (25)

---

### Assessment Rankings (Master Data)

Tabel master `assessment_rankings` menyimpan batas skor untuk merangkum ranking secara otomatis.

Contoh konfigurasi:
* Rank A (Min: 400, Max: 9999) -> Priority: Review Now -> Action: Review Now
* Rank B (Min: 300, Max: 399) -> Priority: Review Next -> Action: Review Next
* Rank C (Min: 200, Max: 299) -> Priority: Pending -> Action: Pending
* Rank D (Min: 0, Max: 199) -> Priority: Hold -> Action: Hold

---

### Assessment Form

Per Inquiry Product.

User memilih opsi skor untuk setiap kategori.

Sistem menghitung otomatis:
* Total Score = SUM(score_snapshot)
* Ranking (berdasarkan `assessment_rankings`)
* Action Recommendation (default dari `assessment_rankings`)

Assessor dapat mengubah/meng-override rekomendasi tindakan melalui kolom `action_override`.

---

## Module 3 – Work Order (SPK)

### Create SPK

Source:

Inquiry

Fields:

* Work Order No
* Revision No
* Subject
* Department ID (Owner Department / Tujuan Utama SPK, misal: Engineering)
* Priority
* Remarks
* Deleted At (Soft Delete)

---

### Select Finish Good

User memilih satu atau beberapa Inquiry Products.

Selected products disalin ke dalam `work_order_products` menggunakan mekanisme snapshot.

Perubahan pada inquiry setelah SPK dibuat tidak akan memengaruhi data SPK.

---

### Work Order Product

Fields:

* Customer Name
* Model Name
* Customer Part No
* Customer Part Name
* Destination
* SOP
* EOL
* Model Life
* Annual Volume
* First Sample Date
* Due Date Approval
* Due Date Closed
* Deleted At (Soft Delete)

---

### Work Order Part List

Per Finish Good (Work Order Product).

Tabel `work_order_parts` berelasi langsung ke `work_order_products.work_order_product_id`.

Fields:

* EO
* Part Number
* Part Name
* Class ID
* UOM
* Remarks
* Deleted At (Soft Delete)

Features:

* Add Row
* Edit Row
* Delete Row
* Excel Import

---

### Process Assignment

Master Process

Examples:

* Design Review
* Costing
* Material Preparation
* Trial
* Quality Preparation
* Other Supporting Activities

User dapat memilih beberapa proses pelaksana.

---

### Related Team Assignment (Support Department)

Disimpan pada `work_order_departments`. User dapat menentukan beberapa departemen pendukung (Support Department, misal: QA, Production, Purchasing) yang terlibat selain Owner Department utama SPK.

---

## Module 4 – Approval Workflow

### Approval Sequence (V1)

Untuk V1, urutan tingkat persetujuan bersifat tetap (Fixed Sequence) dan tidak dinamis:

Marketing Staff
↓
Marketing GM
↓
Purchasing

Workflow dinamis (`approval_workflows` & `approval_workflow_details`) ditunda untuk pengembangan masa mendatang. Pelacakan urutan approval aktif dilakukan dengan kolom `approval_level` di tabel `work_order_approvals`.

---

### Actions

* Approve
* Reject
* Request Revision
* Add Remarks

Sistem mencatat:
* Approval Timestamp
* Approval History
* User Information

---

### Status SPK

* Draft
* Pending Approval
* Rejected
* Approved
* Released
* Closed

---

## Module 5 – SPK Revision

User dapat merevisi SPK yang aktif. Ketika revisi baru dibuat (misal dari Rev.0 menjadi Rev.1):
* Sistem melakukan **Full Clone** (penyalinan penuh) untuk seluruh data child terkait:
  * `work_order_products`
  * `work_order_parts`
  * `work_order_departments`
  * `work_order_process_details`
  * `work_order_approvals`
  * `work_order_attachments`
* Versi sebelumnya (Rev.0) diubah menjadi **Read-Only**.
* Versi baru (Rev.1) menjadi **Active Version** yang dapat diedit.
* Seluruh histori revisi tetap dipertahankan dan dapat diakses untuk kebutuhan audit.

---

## Module 6 – Attachments

Allowed Files:

* PDF
* XLSX
* XLS
* DOCX
* JPG
* PNG
* ZIP

Attachments:

* Drawing
* Technical Document
* RFQ
* Customer Requirement
* Supporting Documents

### Features:

* Upload
* Download
* Preview (where applicable)

*Catatan: Attachment melekat langsung pada revisi SPK. Tidak perlu tabel khusus untuk versi file (`attachment_versions`), karena pelacakan versi file terwakili oleh revisi SPK.*

---

## Module 7 – Dashboard

Widgets:

* Inquiry Summary
* SPK Summary
* Approval Pending
* Released SPK
* Project Status
* Priority Distribution
* Top Customers
* Recent Activities

Dashboard mendukung penyaringan (filter) berdasarkan:

* Date Range
* Customer
* Status
* Priority

---

# 6. Non Functional Requirements

## Platform

Web Application Only

Responsive Design

Desktop First

Mobile Friendly

No Mobile Application Scope in Current Phase

---

## Technology Stack

### Backend

* Laravel 12
* PHP 8.2
* Laravel Blade
* Laravel Queue
* Laravel Policy
* Laravel Scheduler
* Service Layer Pattern
* Repository Pattern
* Audit Trail

### Frontend

* Laravel Blade
* Bootstrap 5 atau Tailwind CSS
* Alpine.js (optional)
* Responsive Layout

### Database

* SQL Server
* Soft Delete (`deleted_at` untuk tabel transaksi)
* Created By
* Updated By
* Audit Log (`audit_logs`)

---

## Security

* Authentication
* Role Based Access Control (RBAC) terintegrasi dengan `promise-admin`
* CSRF Protection
* Activity Logging (Audit Trail)
* Secure File Upload Validation

---

## Performance

List Page

< 2 seconds

Import 5,000 rows

< 30 seconds

Concurrent User

100+

---

# 7. Audit Trail & Soft Delete

## Audit Trail

Sistem mencatat setiap aktivitas berikut pada tabel `audit_logs`:
* Create, Update, Delete, Approval, Rejection, Revision, Login Activity.

Data yang disimpan:
* User ID
* Module Name
* Action
* Record ID
* Old Values (text format)
* New Values (text format)
* IP Address
* Timestamp

Data audit harus dapat dicari dan diexport.

## Soft Delete

Soft delete (`deleted_at`) diterapkan secara wajib pada tabel transaksi berikut:
* `project_inquiries`
* `inquiry_products`
* `priority_assessments`
* `work_orders`
* `work_order_products`
* `work_order_parts`

---

# 8. Future Roadmap

Phase 2

* Quotation Module
* Costing Module
* RFQ Tracking
* Project Timeline

Phase 3

* Project Progress Tracking
* Trial Tracking
* PPAP Tracking
* Notification Center

Phase 4

* Customer Portal
* Vendor Portal
* Advanced Reporting
* Integration with ERP System
