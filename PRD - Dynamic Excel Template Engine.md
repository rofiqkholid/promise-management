# Product Requirement Document (PRD) & Technical Architecture
## Dynamic Excel Template Engine (Export & Import DSL)

---

## 1. Executive Summary & Objective

### 1.1 Latar Belakang
Aplikasi PROMISE Management melayani berbagai customer manufaktur/otomotif (Toyota, Honda, MMKI, ADM, dll.) yang masing-masing memiliki format template Excel resmi (Quotation Tooling, Part Cost Breakdown, Feasibility Study) dengan layout, styling, header, merged cells, dan formula yang berbeda-beda.

### 1.2 Masalah (Problem Statement)
* Pembuatan class export/import hardcoded per customer (`ToyotaQuotationExport`, `HondaQuotationExport`) melanggar prinsip *Open-Closed Principle*, menyebabkan duplikasi kode, tingginya resiko regresi, dan ketergantungan pada developer setiap kali ada perubahan revisi template customer.

### 1.3 Solusi (The Solution)
Membangun **Generic Dynamic Excel Template Engine** berbasis **DSL (Domain Specific Language) JSON Definition**.
* **Template Fisik (`.xlsx`)**: Menyimpan seluruh layout visual, logo, border, font, dan format visual resmi customer.
* **Definition JSON**: Menyimpan aturan pemetaan (*mapping rules*), perulangan (*repeating sections*), nesting data, formula dinamis, dan kondisi render.
* **Laravel Engine**: Bertugas mengambil data via `DataResolver`, mem-parsing JSON, dan merender data ke file Excel menggunakan PhpSpreadsheet secara otomatis tanpa perlu coding baru untuk template baru.

---

## 2. Core Architectural Principles & Separation of Concerns

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            PROMISE EXCEL ARCHITECTURE                       │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
         ┌────────────────────────────┼────────────────────────────┐
         ▼                            ▼                            ▼
┌──────────────────┐        ┌──────────────────┐        ┌──────────────────┐
│  EXCEL TEMPLATE  │        │ DEFINITION JSON  │        │  DATA RESOLVER   │
│     (.xlsx)      │        │      (DSL)       │        │    (Laravel)     │
├──────────────────┤        ├──────────────────┤        ├──────────────────┤
│ Visual Layout    │        │ Cell Coordinates │        │ Database Queries │
│ Corporate Styles │        │ Loop Definitions │        │ System Fields    │
│ Static Texts     │        │ Formula Patterns │        │ Business Rules   │
│ Native Formulas  │        │ Value Formatting │        │ Entity Relations │
└──────────────────┘        └──────────────────┘        └──────────────────┘
         │                            │                            │
         └────────────────────────────┼────────────────────────────┘
                                      ▼
                       ┌──────────────────────────────┐
                       │    DYNAMIC TEMPLATE ENGINE   │
                       ├──────────────────────────────┤
                       │ • TemplateParser             │
                       │ • RowShiftTracker            │
                       │ • SingleFieldRenderer        │
                       │ • TableLoopRenderer          │
                       │ • FormulaCompiler            │
                       └──────────────────────────────┘
                                      │
                                      ▼
                       ┌──────────────────────────────┐
                       │   PhpSpreadsheet Renderer    │
                       └──────────────────────────────┘
                                      │
                                      ▼
                       ┌──────────────────────────────┐
                       │    Customer Final Output     │
                       └──────────────────────────────┘
```

### 2.1 Pembagian Tanggung Jawab (Matrix Responsibility)

| Komponen | Tanggung Jawab | Yang Boleh Dilakukan | Yang DILARANG Dilakukan |
| :--- | :--- | :--- | :--- |
| **Excel Master (`.xlsx`)** | Layout & Presentasi Visual | Styling, border, warna, logo, header statis | Tidak menyimpan data dinamis |
| **Definition JSON** | Aturan Pemetaan (Contract) | Koordinat cell, field mapping, format, formula dinamis | Tidak boleh memuat SQL / query / business logic |
| **System Fields Master** | Kamus Data Global | Definisi `field_key`, label, relasi tabel database | Tidak mengurusi layout cell Excel |
| **DataResolver** | Domain Provider | Eloquent query, kalkulasi bisnis, normalisasi payload | Tidak mengurusi koordinat cell / sheet |
| **TemplateEngine** | Eksekutor & Kompilator | Manipulasi baris, copy style, inject data, compile formula | Tidak mengubah business logic data domain |

---

## 3. Integrasi Master Data Model

### 3.1 Model `mng_cfg_templates`
Menyimpan file master Excel dan konfigurasi JSON untuk setiap tipe dokumen dan customer:
```text
mng_cfg_templates
├── id (PK)
├── template_name (VARCHAR)
├── template_type (VARCHAR)   --> 'tooling_quotation', 'part_cost', 'feasibility_study'
├── direction (ENUM)          --> 'export', 'import'
├── customer_id (FK nullable) --> Relasi ke tabel customers (null = template global)
├── revision (VARCHAR)        --> '0', '1.1', 'Rev-A'
├── file_path (VARCHAR)       --> Path storage file .xlsx master
├── mapping_config (JSON)     --> Schema Definition JSON (DSL)
├── is_active (BOOLEAN)
└── timestamps
```

### 3.2 Model `mng_cfg_system_fields`
Kamus variabel global yang menjadi jembatan antara kolom database dengan `field_key` pada JSON:
```text
mng_cfg_system_fields
├── id (PK)
├── field_key (VARCHAR, Unique) --> 'ebd_part_no', 'cust_name', 'tooling_total_cost'
├── label (VARCHAR)             --> 'Part Number', 'Customer Name', 'Total Tooling Cost'
├── group (VARCHAR)             --> 'header', 'ebd_item', 'process', 'cost'
├── data_type (VARCHAR)         --> 'string', 'number', 'currency', 'date', 'percentage'
├── target_table (VARCHAR)      --> 'tooling_quotations', 'ebd_items', 'processes'
├── target_column (VARCHAR)     --> 'part_number', 'total_cost'
├── is_required (BOOLEAN)
└── timestamps
```

---

## 4. Standardized Definition JSON Schema (DSL Specification)

Struktur JSON definition dirancang rapi, deklaratif, dan modular:

```json
{
  "version": "2.0",
  "template_type": "tooling_quotation",
  "direction": "export",
  "sheets": [
    {
      "key": "main_sheet",
      "name": "1. MMKI",
      "required": true
    },
    {
      "key": "breakdown_sheet",
      "name": "Cost Breakdown",
      "required": false
    }
  ],

  "single_fields": [
    {
      "key": "doc_no",
      "sheet": "1. MMKI",
      "cell": "E2",
      "type": "variable",
      "format": "string",
      "fallback": "-"
    },
    {
      "key": "ebd_part_no",
      "sheet": "1. MMKI",
      "cell": "E3",
      "type": "variable",
      "format": "string"
    },
    {
      "key": "quotation_date",
      "sheet": "1. MMKI",
      "cell": "E4",
      "type": "variable",
      "format": "date",
      "date_format": "YYYY-MM-DD"
    }
  ],

  "table_loops": [
    {
      "key": "ebd_items_section",
      "sheet": "1. MMKI",
      "type": "repeat",
      "data_source": "ebd_items",
      "start_row": 13,

      "row_behavior": {
        "mode": "insert_and_copy_style",
        "copy_merged_cells": true,
        "copy_row_height": true
      },

      "stop_condition": {
        "type": "empty",
        "column": "C"
      },

      "mappings": {
        "idx": {
          "column": "B",
          "type": "auto_increment",
          "start_from": 1
        },
        "ebd_part_no": {
          "column": "C",
          "type": "variable",
          "format": "string"
        },
        "ebd_part_name": {
          "column": "E",
          "type": "variable",
          "format": "string"
        },
        "ebd_mat_spec": {
          "column": "H",
          "type": "variable",
          "format": "string"
        },
        "ebd_mat_thick": {
          "column": "I",
          "type": "variable",
          "format": "number",
          "decimal_places": 2
        },
        "unit_price": {
          "column": "N",
          "type": "variable",
          "format": "currency"
        },
        "discount_amount": {
          "column": "O",
          "type": "variable",
          "format": "currency"
        }
      },

      "row_formulas": {
        "P": {
          "formula": "=N{row}-O{row}",
          "format": "currency"
        }
      },

      "footer_formulas": {
        "total_amount": {
          "target_cell": "P{end_row+1}",
          "formula": "=SUM(P{start_row}:P{end_row})",
          "format": "currency"
        }
      },

      "nested_loops": [
        {
          "key": "item_processes",
          "data_source": "processes",
          "layout_direction": "vertical",
          "mappings": {
            "process_name": {
              "column": "W",
              "type": "variable"
            },
            "tonnage": {
              "column": "X",
              "type": "variable",
              "format": "number"
            }
          }
        }
      ]
    }
  ],

  "conditional_renders": [
    {
      "type": "hide_sheet",
      "sheet": "Cost Breakdown",
      "when": "is_confidential == true"
    }
  ]
}
```

---

## 5. Standardized Placeholder & Formula Compiler Engine

Formula Excel di-compile secara dinamis dengan menyelesaikan placeholder posisi baris berikut:

| Token Placeholder | Penjelasan | Contoh Input | Contoh Output (Baris 13-18) |
| :--- | :--- | :--- | :--- |
| `{row}` | Nomor baris aktif saat ini | `=N{row}*Q{row}` | `=N13*Q13` |
| `{start_row}` | Nomor baris pertama data di-render | `=SUM(P{start_row}:P{end_row})` | `=SUM(P13:P18)` |
| `{end_row}` | Nomor baris terakhir data setelah loop selesai | `=AVERAGE(I{start_row}:I{end_row})` | `=AVERAGE(I13:I18)` |
| `{end_row+N}` | Offset baris setelah loop selesai (Footer) | `P{end_row+1}` | Target cell: `P19` |
| `{parent_row}` | Nomor baris parent pada nested loop vertikal | `=W{row}/V{parent_row}` | `=W15/V13` |
| `{sheet:key}` | Nama sheet aktual yang terdaftar pada sheets | `='{sheet:main_sheet}'!E3` | `='1. MMKI'!E3` |

---

## 6. Detailed Logic Flow & Execution Pipeline

```
[Request Export Quotation (ID: 101, Customer: MMKI)]
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 1. Load MngCfgTemplate & Master File   │
   │    • Ambil definition_json             │
   │    • Load master .xlsx via IOFactory   │
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 2. DataResolver::resolve($quotationId) │
   │    • Query Eloquent dengan eager load  │
   │    • Bentuk Normalized Data Payload    │
   │      - payload['fields'][...]          │
   │      - payload['ebd_items'][...]       │
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 3. RowShiftTracker Initialization      │
   │    • Inisialisasi offset per sheet     │
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 4. SingleFieldRenderer Execution       │
   │    • Render header/scalar single cells │
   │    • Set formatting & data type        │
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 5. TableLoopRenderer Execution         │
   │    • Iterasi setiap baris data source  │
   │    • Row > start_row: insertNewRow     │
   │    • Clone styling, height, merges     │
   │    • Inject cell mapping & row_formula │
   │    • Render nested loop (jika ada)     │
   │    • Update RowShiftTracker (delta row)│
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 6. Footer & Aggregate Formula Compile  │
   │    • Hitung target cell + offset       │
   │    • Substitusi {start_row} & {end_row}│
   │    • Tulis formula ke cell footer      │
   └────────────────────────────────────────┘
                       │
                       ▼
   ┌────────────────────────────────────────┐
   │ 7. ConditionEvaluator & Sheet Cleanup  │
   │    • Evaluasi hide/show sheet/row      │
   │    • Recalculate Pre-calculated Values │
   └────────────────────────────────────────┘
                       │
                       ▼
   [Download Response / Stream ke Browser]
```

---

## 7. Class Design & Service Architecture di Laravel

Struktur modular di folder `app/Services/ExcelTemplate/`:

```text
app/Services/ExcelTemplate/
├── Contracts/
│   ├── DataResolverInterface.php
│   └── TemplateEngineInterface.php
│
├── Core/
│   ├── TemplateEngine.php          --> Orchestrator utama workflow render
│   ├── TemplateParser.php          --> Validasi & parsing schema JSON
│   ├── RowShiftTracker.php         --> Melacak pergeseran baris antar-section
│   └── StyleCloner.php             --> Helper cloning border, fill, alignment, merges
│
├── Renderers/
│   ├── SingleFieldRenderer.php     --> Menangani rendering koordinat single cell
│   ├── TableLoopRenderer.php       --> Menangani rendering repeat baris & nested data
│   ├── FormulaCompiler.php         --> Parser formula string & token replacer
│   └── ConditionEvaluator.php      --> Evaluasi rule conditional show/hide
│
└── Resolvers/
    ├── ToolingQuotationDataResolver.php  --> Domain resolver untuk Tooling Quotation
    ├── PartCostDataResolver.php          --> Domain resolver untuk Part Cost
    └── FeasibilityDataResolver.php       --> Domain resolver untuk Feasibility Study
```

### 7.1 Contract DataResolver (`DataResolverInterface.php`)
Setiap modul bisnis wajib mengimplementasikan interface ini:
```php
namespace App\Services\ExcelTemplate\Contracts;

interface DataResolverInterface
{
    /**
     * Resolve domain entity into normalized array for TemplateEngine
     *
     * @param int|string $entityId
     * @return array{
     *    fields: array<string, mixed>,
     *    sections: array<string, array<int, array<string, mixed>>>
     * }
     */
    public function resolve($entityId): array;
}
```

---

## 8. Penanganan Kompleksitas PhpSpreadsheet (Edge Cases)

### 8.1 Row Insertion & Style Cloning
Ketika PhpSpreadsheet melakukan `insertNewRowBefore($row, 1)`:
1. Formula di bawah baris tersebut secara otomatis digeser oleh PhpSpreadsheet.
2. Namun **style (border, fill, alignment) dan merged cell tidak otomatis terduplikasi** ke baris baru.
3. **Solusi di `StyleCloner`**: Engine membaca `getStyle($templateRow)` dan `getMergeCells()` pada baris template, lalu menerapkannya secara eksplisit ke baris yang baru disisipkan.

### 8.2 Dynamic Merged Cells
Jika baris template memiliki merge cell (misal `E13:G13`):
* Saat baris 14 dibuat, engine meregistrasikan `mergeCells("E14:G14")`.

### 8.3 Number & Date Formatting
Engine menerapkan format mask asli Excel melalui PhpSpreadsheet:
* `currency` $\rightarrow$ `_($* #,##0_);_($* (#,##0);_($* "-"_);_(@_)` atau format rupiah `Rp #,##0`
* `percentage` $\rightarrow$ `0.00%`
* `number` $\rightarrow$ `#,##0.00`
* `date` $\rightarrow$ `yyyy-mm-dd`

---

## 9. Error Handling, Safety & Constraints

1. **Schema JSON Validation:**
   * Sebelum disimpan di `mng_cfg_templates.mapping_config`, request wajib divalidasi dengan JSON Schema validator di Laravel.
2. **Missing System Fields:**
   * Jika `field_key` tidak ditemukan pada data payload, engine mengisi dengan `fallback` value (default: string kosong / `null`) tanpa membuat proses export error (*graceful degradation*).
3. **Missing Sheet Name:**
   * Jika sheet dengan nama pada JSON tidak ditemukan di file Excel, engine memberikan exception deskriptif: `Sheet '[Name]' not found in master template [Template_Name]`.
4. **Memory Management:**
   * Untuk export data besar, konfigurasi PhpSpreadsheet memory cache aktif:
     ```php
     \PhpOffice\PhpSpreadsheet\Settings::setCache(
         new \Symfony\Component\Cache\Adapter\ApcuAdapter()
     );
     ```

---

## 10. Implementation Roadmap

| Phase | Modul / Fitur | Deliverable |
| :--- | :--- | :--- |
| **Phase 1** | **Core Engine & Architecture** | Pembuatan `TemplateParser`, `RowShiftTracker`, `SingleFieldRenderer`, `FormulaCompiler`, dan `TemplateEngine`. |
| **Phase 2** | **Table Loop & Nested Repeat** | Pembuatan `TableLoopRenderer`, `StyleCloner`, penanganan duplicate row, merge cells, dan footer formulas. |
| **Phase 3** | **Domain Data Resolvers** | Implementasi `ToolingQuotationDataResolver` menghubungkan database PROMISE dengan payload standar engine. |
| **Phase 4** | **Visual Studio Builder Integration** | Integrasi output JSON dari [Visual Mapping Studio](file:///c:/WebSource/PROMISE-APPS/promise-management/resources/views/management/excel-templates/builder.blade.php) agar 100% kompatibel dengan schema DSL. |
| **Phase 5** | **Testing, Validation & Dry-Run** | Automated Unit Tests, Stress test formula complex, dan uji coba export template Toyota, MMKI, Honda. |
