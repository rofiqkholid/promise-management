# **Product Requirement Document (PRD)**

## **Dynamic Excel Template Engine & Visual Mapping System (Core Reusable Module)**

| Attribute | Detail |
| :---- | :---- |
| **Module Name** | Core Dynamic Excel Engine Module |
| **Document Version** | 2.0.0 |
| **Target Framework** | Laravel 11 (PHP 8.2+) |
| **Core Dependency** | phpoffice/phpspreadsheet, Web Spreadsheet UI Component (Univer / Luckysheet) |
| **Document Status** | Approved for Development |

## **1\. Executive Summary**

Dalam sistem enterprise, pengelolaan dokumen berbasis Microsoft Excel (seperti *Quotation, Purchase Order, Invoice, Delivery Order, Costing Sheet*, dll.) sering menghadapi kendala skalabilitas (*scalability blocker*). Setiap kali terdapat format bawaan baru dari partner/customer, tim developer harus melakukan koding ulang (*hardcoded layout*) untuk menyesuaikan koordinat sel.

Dokumen ini mendefinisikan pembuatan **Dynamic Excel Template Engine dengan Visual Mapping Interface** sebagai **Core Reusable Module**. Modul ini dirancang agar dapat digunakan oleh seluruh fitur/modul bisnis dalam aplikasi tanpa perlu menyentuh kode program (*zero-code onboarding*).

Sistem memungkinkan Admin mengunggah template Excel bawaan pihak eksternal (sekompleks apa pun layout, border, merged cell, dan rumusnya), **menampilkan preview spreadsheet secara interaktif di browser Web UI**, lalu memetakan (*mapping*) koordinat sel secara visual ke dalam variabel sistem (mng\_cfg\_system\_fields).

## **2\. Objectives & Success Metrics**

### **2.1 Objectives**

1. **Reusability & Multi-Module Support:** Menyediakan *engine* terpusat yang dapat melayani berbagai domain bisnis (*Quotation, Purchase Order, Invoice, Goods Receipt*, dll.) melalui parameter template\_type.  
2. **Zero Developer Intervention:** Menghilangkan ketergantungan pada *programmer* saat ada penambahan format/template dokumen baru.  
3. **100% Layout Preservation:** Mempertahankan keutuhan *formatting*, logo, *merged cell*, garis *border*, warna, serta *formula* Excel bawaan partner saat proses ekspor dan impor.  
4. **Interactive UI Mapping:** Menggantikan input koordinat manual (misal mengetik "C9" di form) dengan **Web UI Preview Excel** yang interaktif (klik sel di layar untuk memetakan field).  
5. **Bidirectional Data Flow:** Mendukung proses **Export** (Sistem ![][image1] File Excel) dan **Import/Re-scan** (File Excel ![][image1] Sistem) secara presisi.

### **2.2 Key Performance Indicators (KPIs)**

* **Onboarding Speed:** Waktu penyiapan template baru berkurang dari 1–2 hari kerja menjadi \< 10 menit melalui Interactive UI Web Admin.  
* **Format Accuracy Rate:** 100% preservasi elemen visual Excel tanpa ada *broken layout* atau *formula corruption*.  
* **Parse Success Rate:** \> 99% keakuratan ekstraksi data saat proses impor file dari pengguna eksternal.

## **3\. User Personas & Use Cases**

### **Personas**

1. **System Administrator (Internal):** Mengelola Master Data Field (mng\_cfg\_system\_fields) dan melakukan *visual mapping* sel Excel di Web UI saat ada template baru.  
2. **Operational / Business Staff (Internal):** Mengisi data transaksi pada modul terkait (Sales, Purchasing, Finance) dan men-generate file Excel berformat khusus partner secara otomatis.  
3. **External Partner / Customer / Vendor:** Menerima file Excel, mengedit/mengisi data penyesuaian, lalu mengunggah kembali file tersebut ke sistem.

### **Primary Use Cases**

* **UC-01:** Admin mendaftarkan variabel/field sistem baru berdasarkan modulnya (module\_type: quotation, po, invoice).  
* **UC-02 (Interactive Mapping):** Admin mengunggah .xlsx template baru, sistem menampilkan preview Excel di Web UI, Admin mengklik sel untuk memasangkan *system field*, dan menentukan aturan *looping* tabel secara visual.  
* **UC-03:** Sistem memanggil *Export Engine* untuk menginjeksi data transaksi ke dalam template Excel dan menghasilkan file .xlsx siap unduh.  
* **UC-04:** User mengunggah file balasan dari partner, sistem memanggil *Import Engine* untuk mengekstrak data dari koordinat terpetakan menjadi *payload record* database.

## **4\. System Architecture & Workflows**

### **4.1 Architectural Pattern**

Sistem menggunakan pendekatan **Template Overlay & Visual Coordinate Mapping**:

* File .xlsx asli disimpan sebagai **Master Template Artifact**.  
* Sistem **TIDAK MEMBUAT** file spreadsheet dari awal (*scratch*).  
* **UI Sheet Previewer Engine**: Mengonversi/menampilkan file .xlsx ke dalam Web Canvas Grid di Browser.  
* Untuk **Export**: Engine membaca Master Template, lalu *overwrites* nilai sel sesuai JSON Map hasil penentuan UI.  
* Untuk **Import**: Engine membaca koordinat sel dari file yang diunggah berdasarkan JSON Map, lalu mengonversinya menjadi *payload JSON/Array*.

### **4.2 Data Flow Diagram (DFD)**

\+--------------------------+        \+-----------------------------------+        \+-----------------------+  
|  mng\_cfg\_system\_fields   |  \---\>  | Web UI Interactive Sheet Preview  |  \---\>  |   mng\_cfg\_templates   |  
|  (Dictionary per Module) |        | (Click Cell & Visual Loop Rule)   |        | (JSON Configuration)  |  
\+--------------------------+        \+-----------------------------------+        \+-----------------------+  
                                                      |  
                                                      v  
                                    \+-----------------------------------+  
                                    |     CORE ENGINE PROCESSOR         |  
                                    \+-----------------------------------+  
                                    |                                   |  
                                    |  \[EXPORT MODE\]                    |  \[IMPORT MODE\]  
                                    |  1\. Load Master .xlsx             |  1\. Parse Uploaded .xlsx  
                                    |  2\. Inject DB Values              |  2\. Extract Cells by Map  
                                    |  3\. Output Final .xlsx            |  3\. Transform Array/JSON  
                                    |                                   |  4\. Pass Payload to Modul  
                                    \+-----------------------------------+

## **5\. Detailed Functional Specifications**

### **5.1 Master System Fields Management (mng\_cfg\_system\_fields)**

* Menjadi *single source of truth* untuk seluruh variabel yang didukung oleh modul-modul di aplikasi.  
* Atribut Field: field\_key (unik), label, module\_type (quotation, po, invoice, dll.), group (Header, Material, Process, Tooling, Item), dan data\_type.  
* *Validation Rule:* Kunci field wajib menggunakan format *snake\_case*.

### **5.2 Interactive Web UI Excel Preview & Visual Mapping System**

Fitur utama untuk Admin dalam melakukan konfigurasi template tanpa perlu mengetik koordinat sel secara manual.

#### **A. Interactive Sheet Viewer Components**

* Sistem mengintegrasikan pustaka Web Spreadsheet Viewer (seperti *Univer JS* atau *Luckysheet*) di halaman Admin Web.  
* Saat file .xlsx diunggah, viewer langsung merender struktur sheet, warna, border, merged cells, dan font secara *real-time* di browser.

#### **B. Visual Mapping Interaction Workflow**

1. **Single Field Mapping (Header Data):**  
   * Admin mengklik salah satu sel pada preview Excel (contoh: Klik Sel C9).  
   * Popover / Sidebar Form akan muncul di sisi kanan layar.  
   * Admin memilih **System Field** dari dropdown list (terfilter berdasarkan module\_type).  
   * Sel yang berhasil di-map akan mendapat highlight warna khas (misal: *Green Highlight*) beserta badge nama field (part\_number).  
2. **Table Loops Mapping (Data Bertingkat / Dynamic Array):**  
   * Admin menandai/menyeleksi area baris tabel (contoh: Baris 11).  
   * Admin memilih mode **"Create Table Loop"** dan menentukan nama grup (misal: material\_table).  
   * Admin menandai kolom-kolom terkait secara visual (Klik Kolom B ![][image1] set ke material\_name, Klik Kolom E ![][image1] set ke input\_wt).  
   * **Visual Stop Condition Builder:** Admin mengklik sel penutup loop (misal Sel B17 berisi teks "MATERIAL COST TOTAL"), lalu sistem otomatis menyimpan aturan *stop condition* berdasarkan kolom dan isi nilai teks sel tersebut.  
   * Baris/area loop akan diberi highlight warna berbeda (misal: *Blue Highlight*).  
3. **Formula & Static Cell Protection:**  
   * Sel yang mengandung Formula Excel (seperti \=SUM(...)) akan diberi indikator khusus (*Yellow Highlight / Lock Icon*) untuk mencegah Admin tidak sengaja memetakan sel tersebut sebagai *input field*.  
4. **Config JSON Auto-Generator:**  
   * Setelah selesai melakukan mapping secara visual, Admin mengklik **"Save Mapping Configuration"**. Sistem otomatis mengekstrak seluruh koordinat visual tersebut menjadi dokumen JSON mapping\_config.

### **5.3 Dynamic Mapping Configuration (mng\_cfg\_templates)**

Sistem menyimpan konfigurasi tergenerasi dari UI ke dalam kolom JSON mapping\_config dengan struktur:

1. **Single Fields:** Peta 1-to-1 koordinat sel dengan field\_key.  
2. **Table Loops:** Peta multi-baris mencakup start\_row, stop\_condition, dan daftar columns.

### **5.4 Export Engine Requirements**

* Menggunakan template master berbasis file .xlsx.  
* Menimpa (*overwrites*) nilai sel berdasarkan mapping\_config tanpa merusak styling/formula sel lain yang tidak di-map.  
* Menghasilkan file output .xlsx yang siap diunduh atau dikirim via email.

### **5.5 Import Engine Requirements**

* Membaca file balasan dari partner.  
* Mengekstrak nilai sel menggunakan getFormattedValue() atau getValue().  
* Merged Cell handling: Jika area sel di-merge (misal A11:A16), ekstraktor membaca data dari sel paling kiri-atas (A11).  
* Mengembalikan *payload array/JSON* yang siap diproses oleh logika transaksi modul bersangkutan.

## **6\. Database Schema Design**

### **6.1 Entity Relationship Diagram (ERD)**

customers / entities (1) \<--- (N) mng\_cfg\_templates (1) \<--- (N) \[Existing Modul Tables (e.g., quotations)\]  
                                          ^  
mng\_cfg\_system\_fields (1) \----------------+ (Referenced via field\_key inside JSON Mapping)

### **6.2 Table Definitions (Laravel Migrations)**

#### **Table 1: mng\_cfg\_system\_fields**

Schema::create('mng\_cfg\_system\_fields', function (Blueprint $table) {  
    $table-\>id();  
    $table-\>string('module\_type')-\>index(); // e.g., 'quotation', 'purchase\_order', 'invoice'  
    $table-\>string('field\_key')-\>unique();  // e.g., 'part\_number', 'cycle\_time', 'po\_date'  
    $table-\>string('label');                // Human-readable label for UI Dropdown  
    $table-\>string('group');                // e.g., 'header', 'material', 'process', 'item'  
    $table-\>enum('data\_type', \['string', 'numeric', 'decimal', 'date', 'boolean'\])-\>default('string');  
    $table-\>boolean('is\_required')-\>default(false);  
    $table-\>timestamps();  
});

#### **Table 2: mng\_cfg\_templates**

Schema::create('mng\_cfg\_templates', function (Blueprint $table) {  
    $table-\>id();  
    $table-\>string('template\_type')-\>index();       // e.g., 'quotation', 'purchase\_order', 'invoice'  
    $table-\>foreignId('customer\_id')  
          \-\>nullable()  
          \-\>constrained('customers')  
          \-\>nullOnDelete();                         // Nullable jika template bertipe umum/vendor  
    $table-\>string('template\_name');                // e.g., "Quotation Fabricated Part Honda"  
    $table-\>string('file\_path');                    // Storage path master .xlsx  
    $table-\>json('mapping\_config');                 // Generated JSON from Interactive Web UI Mapper  
    $table-\>boolean('is\_active')-\>default(true);  
    $table-\>timestamps();  
});

#### **Migration Integration pada Tabel Transaksi Existing (Contoh: quotations)**

Schema::table('quotations', function (Blueprint $table) {  
    $table-\>foreignId('excel\_template\_id')  
          \-\>nullable()  
          \-\>after('customer\_id')  
          \-\>constrained('mng\_cfg\_templates')  
          \-\>nullOnDelete();  
});

## **7\. Sample JSON Structure (mapping\_config)**

Struktur JSON yang dihasilkan secara otomatis oleh Web UI Preview & Visual Mapping System:

{  
  "template\_type": "quotation",  
  "single\_fields": \[  
    {  
      "field\_key": "part\_number",  
      "cell": "C9"  
    },  
    {  
      "field\_key": "part\_name",  
      "cell": "C10"  
    },  
    {  
      "field\_key": "supplier\_name",  
      "cell": "F7"  
    },  
    {  
      "field\_key": "usd\_rate",  
      "cell": "H8"  
    }  
  \],  
  "table\_loops": \[  
    {  
      "group": "material",  
      "start\_row": 11,  
      "stop\_condition": {  
        "type": "cell\_value\_contains",  
        "column": "B",  
        "value": "MATERIAL COST TOTAL"  
      },  
      "columns": {  
        "material\_name": "B",  
        "input\_wt": "E",  
        "output\_wt": "F",  
        "scrap\_wt": "H"  
      }  
    },  
    {  
      "group": "process",  
      "start\_row": 28,  
      "stop\_condition": {  
        "type": "cell\_value\_contains",  
        "column": "B",  
        "value": "ASSEMBLY PROCESS COST TOTAL"  
      },  
      "columns": {  
        "process\_name": "B",  
        "machinery": "C",  
        "cycle\_time": "F",  
        "cost\_minute": "G"  
      }  
    }  
  \]  
}

## **8\. Non-Functional Requirements (NFRs)**

1. **Performance:**  
   * Render UI Preview file Excel di browser harus selesai dalam **\< 2 detik** untuk file \< 5MB.  
   * Engine Export/Import di backend harus selesai dalam **\< 3 detik** per file.  
   * Peak Memory PHP limit diset maksimal 128MB.  
2. **Security & Validation:**  
   * Upload file divalidasi MIME Type: .xlsx.  
   * Sanitasi input wajib diterapkan sebelum mengekstrak data ke database.  
3. **UI / UX Usability:**  
   * Antarmuka visual mapper harus menyediakan fitur *Reset Mapping*, *Undo*, dan *Real-time Visual Highlight Badges*.

## **9\. Implementation Roadmap & Milestones**

| Phase | Milestone | Deliverables | Estimated Time |
| :---- | :---- | :---- | :---- |
| **Phase 1** | **Database & Core Dictionary** | Migrations mng\_cfg\_\*, Seeders, CRUD Master System Fields per Modul | Sprint 1 (3 Hari) |
| **Phase 2** | **Interactive Web UI Mapper** | Integrasi Web Sheet Viewer (Univer/Luckysheet) \+ Click & Map UI \+ Color Highlight \+ JSON Auto Generator | Sprint 1 (5 Hari) |
| **Phase 3** | **Export Engine Module** | Reusable ExcelExportEngineService berbasis PhpSpreadsheet Overlay | Sprint 2 (3 Hari) |
| **Phase 4** | **Import Engine Module** | Reusable ExcelImportEngineService \+ Dynamic Loop Parser \+ Stop Condition Detector | Sprint 2 (4 Hari) |
| **Phase 5** | **Module Integration & Testing** | Integrasi ke modul Quotation (dan modul lain) \+ UAT Template Kompleks | Sprint 3 (3 Hari) |

## **10\. Risk Management & Mitigations**

| Identified Risk | Risk Level | Mitigation Strategy |
| :---- | :---- | :---- |
| Partner mengubah posisi sel/layout tanpa pemberitahuan. | **Medium** | Admin tidak perlu koding ulang; cukup buka UI Visual Mapper, klik sel lokasi baru, dan simpan ulang dalam \< 5 menit. |
| Render UI Preview berat untuk file Excel ukuran besar. | **Medium** | Terapkan server-side HTML/JSON conversion atau canvas rendering terbatas pada sheet aktif. |
| Formula Excel \#REF\! atau \#VALUE\!. | **Low** | Biarkan formula dievaluasi oleh aplikasi Microsoft Excel milik partner. Backend hanya menginjeksi sel variabel input. |

[image1]: <data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABUAAAAZCAYAAADe1WXtAAAAmklEQVR4XmNgGAWjYOCBvLx8FBA7oYtTBOTk5FyAuB/IZESXIxuIioryAF26VEZGRgVdjiIAdKkr0OA16OKUAkagoTlA1wqhS4AAC9DWPKCCWWTg2UD8DYirQeagG0wyUFJS4gcatgpdnBIA8nq3goJCOLoE2QBooCIQLwalAnQ5sgHQwGhgPJSji1MEQC4Eep0DXXwUjAIaAQBLkx8bCNIqXAAAAABJRU5ErkJggg==>