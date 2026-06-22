Table users {
  id int [pk, increment]
  name varchar
  email varchar
  nik varchar
  id_dept int [ref: > departments.id]
  password varchar
  remember_token varchar
  email_verification_at datetime
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

Table project_inquiries {
  inquiry_id int [pk, increment]
  inquiry_no varchar [unique]
  customer_name varchar
  project_name varchar
  inquiry_date date
  status varchar
  remarks text
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table inquiry_products {
  inquiry_product_id int [pk, increment]
  inquiry_id int [ref: > project_inquiries.inquiry_id]
  model_name varchar
  customer_part_no varchar
  customer_part_name varchar
  part_category varchar
  destination varchar
  sop_date date
  eol_date date
  model_life int
  annual_volume int
  has_2d_data boolean
  has_3d_data boolean
  has_tech_doc boolean
  remarks text
  created_at datetime
  updated_at datetime
  deleted_at datetime
}

Table score_categories {
  category_id int [pk, increment]
  category_code varchar [unique]
  category_name varchar
  sort_order int
  is_active boolean
  created_at datetime
}

Table score_options {
  option_id int [pk, increment]
  category_id int [ref: > score_categories.category_id]
  option_name varchar
  score_value int
  description text
  sort_order int
  created_at datetime
}

Table assessment_rankings {
  ranking_id int [pk, increment]
  rank_code varchar
  min_score int
  max_score int
  priority_label varchar
  recommendation text
  sort_order int
  is_active boolean
  created_at datetime
}

Table priority_assessments {
  assessment_id int [pk, increment]
  inquiry_product_id int [ref: > inquiry_products.inquiry_product_id]
  total_score int
  ranking_id int [ref: > assessment_rankings.ranking_id]
  action varchar
  action_override varchar
  remarks text
  assessed_by varchar
  assessed_at datetime
  created_at datetime
  deleted_at datetime
}

Table priority_assessment_details {
  detail_id int [pk, increment]
  assessment_id int [ref: > priority_assessments.assessment_id]
  category_id int [ref: > score_categories.category_id]
  option_id int [ref: > score_options.option_id]
  score_snapshot int
  remarks text
}

Table work_orders {
  work_order_id int [pk, increment]
  inquiry_id int [ref: > project_inquiries.inquiry_id]
  work_order_no varchar
  revision_no int [default: 0]
  revised_from_id int [ref: > work_orders.work_order_id, null]
  is_latest boolean [default: true]
  department_id int [ref: > departments.id] // Owner Department / Tujuan Utama SPK
  priority varchar
  subject varchar
  status varchar
  remarks text
  created_by varchar
  created_at datetime
  updated_at datetime
  deleted_at datetime

  indexes {
    (work_order_no, revision_no) [unique]
  }
}

Table work_order_departments {
  work_order_department_id int [pk, increment]
  work_order_id int [ref: > work_orders.work_order_id]
  department_id int [ref: > departments.id] // Support Department
  remarks text
}

Table work_order_processes {
  process_id int [pk, increment]
  process_code varchar [unique]
  process_name varchar
  owner_department_id int [ref: > departments.id]
  sort_order int
  is_active boolean
  created_at datetime
}

Table work_order_process_details {
  process_detail_id int [pk, increment]
  work_order_id int [ref: > work_orders.work_order_id]
  process_id int [ref: > work_order_processes.process_id]
  remarks text
}

Table work_order_products {
  work_order_product_id int [pk, increment]
  work_order_id int [ref: > work_orders.work_order_id]
  inquiry_product_id int [ref: > inquiry_products.inquiry_product_id]
  customer_name varchar
  model_name varchar
  customer_part_no varchar
  customer_part_name varchar
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
  deleted_at datetime
}

Table work_order_parts {
  work_order_part_id int [pk, increment]
  work_order_product_id int [ref: > work_order_products.work_order_product_id]
  eo varchar
  part_no varchar
  part_name varchar
  class_id varchar
  uom varchar
  remarks text
  created_at datetime
  deleted_at datetime
}

Table work_order_attachments {
  attachment_id int [pk, increment]
  work_order_id int [ref: > work_orders.work_order_id]
  file_name varchar
  file_path varchar
  uploaded_by varchar
  uploaded_at datetime
}

Table work_order_approvals {
  approval_id int [pk, increment]
  work_order_id int [ref: > work_orders.work_order_id]
  approval_level int
  department_id int [ref: > departments.id]
  approver_name varchar
  approver_position varchar
  status varchar
  approved_at datetime
  remarks text
}

Table audit_logs {
  audit_log_id int [pk, increment]
  user_id int [ref: > users.id]
  module_name varchar
  action varchar
  record_id int
  old_values text
  new_values text
  ip_address varchar
  created_at datetime
}