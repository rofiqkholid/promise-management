Table users {
  id int [pk, increment]
  name varchar
  email varchar
  nik varchar
  id_dept int [ref: > departments.id]
  password varchar
  remember_token varchar
  email_verified_at datetime
  created_at datetime
  updated_at datetime
  is_active boolean
}

Table departments {
  id int [pk, increment]
  name varchar
  code varchar [unique]
  created_at datetime
  updated_at datetime
}

Table mng_inquiries {
  id int [pk, increment]
  inquiry_no varchar [unique]
  customer_id int [null]
  project_name varchar
  model_id int [null]
  inquiry_date date
  status varchar
  remarks text
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table mng_inquiry_products {
  id int [pk, increment]
  inquiry_id int [ref: > mng_inquiries.id]
  variant varchar
  customer_part_no varchar
  customer_part_name varchar
  part_category varchar
  destination varchar
  sop_date date
  eol_date date
  model_life int
  annual_volume int
  sort_order int
  has_2d_data boolean
  has_3d_data boolean
  has_tech_doc boolean
  remarks text
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table mng_inq_score_categories {
  id int [pk, increment]
  category_code varchar [unique]
  category_name varchar
  sort_order int
  is_active boolean
  created_at datetime
  updated_at datetime
}

Table mng_inq_score_options {
  id int [pk, increment]
  category_id int [ref: > mng_inq_score_categories.id]
  option_name varchar
  score_value int
  description text
  sort_order int
  created_at datetime
  updated_at datetime
}

Table mng_inq_rankings {
  id int [pk, increment]
  rank_code varchar
  min_score int
  max_score int
  priority_label varchar
  recommendation text
  sort_order int
  is_active boolean
  created_at datetime
  updated_at datetime
}

Table mng_inq_assessments {
  id int [pk, increment]
  inquiry_product_id int [ref: > mng_inquiry_products.id]
  total_score int
  ranking_id int [ref: > mng_inq_rankings.id]
  action varchar
  remarks text
  assessed_by varchar
  assessed_at datetime
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table mng_inq_assessment_details {
  id int [pk, increment]
  assessment_id int [ref: > mng_inq_assessments.id]
  category_id int [ref: > mng_inq_score_categories.id]
  option_id int [ref: > mng_inq_score_options.id]
  score_snapshot int
  remarks text
}

Table mng_work_orders {
  id int [pk, increment]
  inquiry_id int [ref: > mng_inquiries.id]
  wo_number varchar
  revision_no int [default: 0]
  revised_from_id int [ref: > mng_work_orders.id, null]
  is_latest boolean [default: true]
  header_id int [ref: > mng_wo_doc_format.id] // Link to QEMS Document Header template (wo_doc_format)
  department_id int [ref: > departments.id] // Owner Department / Tujuan Utama SPK
  priority varchar
  subject varchar
  request_types text // JSON Array of request types
  status varchar
  remarks text
  created_by varchar
  released_at datetime
  created_at datetime
  updated_at datetime
  deleted_at datetime

  indexes {
    (wo_number, revision_no) [unique]
  }
}

Table mng_wo_processes {
  id int [pk, increment]
  process_code varchar [unique]
  process_name varchar
  default_assigned_departments text // JSON Array of Department IDs
  sort_order int
  is_active boolean
  created_at datetime
  updated_at datetime
}

Table mng_wo_process_details {
  id int [pk, increment]
  work_order_id int [ref: > mng_work_orders.id]
  process_id int [ref: > mng_wo_processes.id]
  assigned_departments text // JSON Array of Department IDs
  remarks text
}

Table mng_wo_products {
  id int [pk, increment]
  work_order_id int [ref: > mng_work_orders.id]
  inquiry_product_id int [ref: > mng_inquiry_products.id]
  customer_name varchar
  model_name varchar
  variant varchar
  customer_part_no varchar
  customer_part_name varchar
  eo varchar
  class_id varchar
  uom varchar
  destination varchar
  sop_date date
  eol_date date
  model_life int
  annual_volume int
  first_sample_date date
  due_date_approval date
  due_date_closed date
  remarks text
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table mng_wo_attachments {
  id int [pk, increment]
  work_order_id int [ref: > mng_work_orders.id]
  file_name varchar
  file_path varchar
  uploaded_by varchar
  uploaded_at datetime
  created_at datetime
  updated_at datetime
}

Table mng_wo_approvals {
  id int [pk, increment]
  work_order_id int [ref: > mng_work_orders.id]
  approval_level int
  department_id int [ref: > departments.id]
  approver_name varchar
  approver_position varchar
  status varchar
  approved_at datetime
  remarks text
  created_at datetime
  updated_at datetime
}

Table mng_wo_doc_format {
  id int [pk, increment]
  document_no varchar
  doc_department varchar
  doc_publish_date date
  page_hal varchar
  is_current boolean [default: true]
  created_at datetime
  updated_at datetime
}

Table mng_approval_rules {
  id int [pk, increment]
  document_type varchar
  approval_level int
  department_id int [ref: > departments.id]
  approver_user_id int [ref: > users.id, null]
  position_label varchar
  is_active boolean
  sort_order int
  created_at datetime
  updated_at datetime
}

Table mng_calendar_events {
  id int [pk, increment]
  title varchar
  start_date date
  end_date date
  is_holiday boolean
  description text
  color varchar
  created_at datetime
  updated_at datetime
}

Table mng_audit_logs {
  id int [pk, increment]
  user_id int [ref: > users.id]
  module_name varchar
  action varchar
  record_id int
  old_values text
  new_values text
  ip_address varchar
  created_at datetime
  updated_at datetime
}