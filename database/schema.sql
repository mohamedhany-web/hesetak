-- ============================================
-- حصتك Platform - Complete Database Schema
-- ============================================
-- Auto-generated on: 2025-11-15 17:31:06
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
CREATE TABLE `academic_subjects` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `academic_year_id` integer not null, `name` varchar(255) not null, `code` varchar(255) not null, `description` text, `icon` varchar(255), `color` varchar(255) not null default '#3B82F6', `order` integer not null default '0', `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`academic_year_id`) references `academic_years`(`id`) on delete cascade);

CREATE TABLE `academic_years` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `code` varchar(255) not null, `description` text, `icon` varchar(255), `color` varchar(255) not null default '#3B82F6', `order` integer not null default '0', `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `achievements` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `code` varchar(255) not null, `name` varchar(255) not null, `description` text, `icon` varchar(255), `type` enum('course_completion','exam_score','streak','points','custom') not null default 'custom', `requirements` text, `points_reward` integer not null default '0', `is_active` tinyint(1) not null default '1', `sort_order` integer not null default '0', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `activity_logs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `action` varchar(255) not null, `model_type` varchar(255), `model_id` integer, `old_values` text, `new_values` text, `ip_address` varchar(255), `user_agent` varchar(255), `created_at` datetime, `updated_at` datetime, `description` text, `url` text, `method` varchar(255), `response_code` integer, `duration` integer, `session_id` varchar(255), foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `advanced_courses` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `instructor_id` integer, `title` varchar(255) not null, `programming_language` varchar(255), `framework` varchar(255), `category` varchar(255), `language` varchar(255) not null default ('ar'), `description` text, `objectives` text, `level` varchar(255) not null default ('beginner'), `duration_hours` integer not null default ('0'), `duration_minutes` integer not null default ('0'), `price` numeric not null default ('0'), `students_count` integer not null default ('0'), `rating` numeric not null default ('0'), `reviews_count` integer not null default ('0'), `thumbnail` varchar(255), `requirements` text, `prerequisites` text, `what_you_learn` text, `skills` text, `starts_at` datetime, `ends_at` datetime, `is_active` tinyint(1) not null default ('1'), `is_featured` tinyint(1) not null default ('0'), `created_at` datetime, `updated_at` datetime, foreign key(`instructor_id`) references users(`id`) on delete set null on update no action);

CREATE TABLE `advanced_notifications` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `message` text not null, `type` varchar(255) not null default 'info', `recipients` text not null, `is_broadcast` tinyint(1) not null default '0', `scheduled_at` datetime, `status` enum('draft','sent','scheduled') not null default 'draft', `created_by` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`created_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `assignment_submission_versions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `submission_id` integer not null, `version` integer not null, `content` text, `attachments` text, `github_link` varchar(255), `submitted_at` datetime not null, `created_at` datetime, `updated_at` datetime, foreign key(`submission_id`) references `assignment_submissions`(`id`) on delete cascade);

CREATE TABLE `assignment_submissions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `assignment_id` integer not null, `student_id` integer not null, `content` text, `attachments` text, `submitted_at` datetime not null default CURRENT_TIMESTAMP, `score` integer, `feedback` text, `graded_at` datetime, `graded_by` integer, `status` enum('submitted','graded','returned') not null default 'submitted', `created_at` datetime, `updated_at` datetime, `github_link` varchar(255), `version` integer not null default '1', `voice_feedback_path` varchar(255), `feedback_attachments` text, `code_test_results` text, foreign key(`assignment_id`) references `assignments`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`graded_by`) references `users`(`id`) on delete set null);

CREATE TABLE `assignments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `instructions` text, `course_id` integer not null, `lesson_id` integer, `teacher_id` integer not null, `due_date` datetime, `max_score` integer not null default '100', `allow_late_submission` tinyint(1) not null default '0', `status` enum('draft','published','archived') not null default 'draft', `created_at` datetime, `updated_at` datetime, `github_link_support` tinyint(1) not null default '1', `code_testing_api` varchar(255), `code_testing_config` text, foreign key(`course_id`) references `courses`(`id`) on delete cascade, foreign key(`lesson_id`) references `lessons`(`id`) on delete set null, foreign key(`teacher_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `attendance_records` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lecture_id` integer not null, `student_id` integer not null, `joined_at` datetime, `left_at` datetime, `attendance_minutes` integer not null default '0', `total_minutes` integer not null default '0', `attendance_percentage` numeric not null default '0', `status` enum('present','late','absent','partial') not null default 'absent', `source` varchar(255) not null default 'manual', `teams_data` text, `teams_file_path` varchar(255), `created_at` datetime, `updated_at` datetime, foreign key(`lecture_id`) references `lectures`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `attendance_statistics` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `student_id` integer not null, `course_id` integer not null, `total_lectures` integer not null default '0', `attended_lectures` integer not null default '0', `late_lectures` integer not null default '0', `absent_lectures` integer not null default '0', `attendance_rate` numeric not null default '0', `total_hours` integer not null default '0', `period_start` date, `period_end` date, `created_at` datetime, `updated_at` datetime, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`course_id`) references `advanced_courses`(`id`) on delete cascade);

CREATE TABLE `badges` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `code` varchar(255) not null, `name` varchar(255) not null, `description` text, `icon` varchar(255), `color` varchar(255) not null default '#3B82F6', `type` enum('skill','milestone','special','seasonal') not null default 'skill', `requirements` text, `is_active` tinyint(1) not null default '1', `sort_order` integer not null default '0', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `blog_posts` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `author_id` integer not null, `title` varchar(255) not null, `slug` varchar(255) not null, `excerpt` text, `content` text not null, `featured_image` varchar(255), `images` text, `status` enum('draft','published','archived') not null default 'draft', `is_featured` tinyint(1) not null default '0', `views_count` integer not null default '0', `tags` text, `meta_title` varchar(255), `meta_description` text, `published_at` datetime, `created_at` datetime, `updated_at` datetime, foreign key(`author_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `cache` (`key` varchar(255) not null, `value` text not null, `expiration` integer not null, primary key (`key`));

CREATE TABLE `cache_locks` (`key` varchar(255) not null, `owner` varchar(255) not null, `expiration` integer not null, primary key (`key`));

CREATE TABLE `calendar_event_attendees` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `created_at` datetime, `updated_at` datetime);

CREATE TABLE `calendar_events` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `start_date` datetime not null, `end_date` datetime, `is_all_day` tinyint(1) not null default '0', `type` enum('exam','lesson','assignment','meeting','holiday','deadline','review','personal','system') not null default 'personal', `priority` enum('low','medium','high','urgent') not null default 'medium', `color` varchar(255) not null default '#3B82F6', `location` varchar(255), `notes` text, `created_by` integer not null, `visibility` enum('public','private','course','year','subject') not null default 'private', `academic_year_id` integer, `academic_subject_id` integer, `advanced_course_id` integer, `has_reminder` tinyint(1) not null default '0', `reminder_minutes` integer, `email_reminder` tinyint(1) not null default '0', `status` enum('scheduled','completed','cancelled','postponed') not null default 'scheduled', `is_recurring` tinyint(1) not null default '0', `recurrence_type` enum('daily','weekly','monthly','yearly'), `recurrence_interval` integer not null default '1', `recurrence_end_date` date, `has_grade` tinyint(1) not null default '0', `max_grade` numeric, `grading_criteria` text, `created_at` datetime, `updated_at` datetime, foreign key(`created_by`) references `users`(`id`) on delete cascade, foreign key(`academic_year_id`) references `academic_years`(`id`) on delete cascade, foreign key(`academic_subject_id`) references `academic_subjects`(`id`) on delete cascade, foreign key(`advanced_course_id`) references `advanced_courses`(`id`) on delete cascade);

CREATE TABLE `certificates` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `course_id` integer not null, `user_id` integer not null, `certificate_number` varchar(255) not null, `issued_at` datetime not null, `template` varchar(255), `data` text, `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `courses`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `classroom_students` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `student_id` integer not null, `classroom_id` integer not null, `enrolled_at` datetime not null default CURRENT_TIMESTAMP, `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`classroom_id`) references `classrooms`(`id`) on delete cascade);

CREATE TABLE `classrooms` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `description` text, `school_id` integer not null, `teacher_id` integer, `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`school_id`) references `schools`(`id`) on delete cascade, foreign key(`teacher_id`) references `users`(`id`) on delete set null);

CREATE TABLE `contact_messages` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `email` varchar(255) not null, `phone` varchar(255), `subject` varchar(255) not null, `message` text not null, `status` enum('new','read','replied','archived') not null default 'new', `admin_notes` text, `replied_by` integer, `replied_at` datetime, `read_at` datetime, `created_at` datetime, `updated_at` datetime, foreign key(`replied_by`) references `users`(`id`) on delete set null);

CREATE TABLE `coupon_usages` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `coupon_id` integer not null, `user_id` integer not null, `invoice_id` integer, `discount_amount` numeric not null, `order_amount` numeric not null, `final_amount` numeric not null, `created_at` datetime, `updated_at` datetime, foreign key(`coupon_id`) references `coupons`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`invoice_id`) references `invoices`(`id`) on delete set null);

CREATE TABLE `coupons` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `code` varchar(255) not null, `name` varchar(255) not null, `description` text, `discount_type` enum('percentage','fixed') not null default 'percentage', `discount_value` numeric not null, `minimum_amount` numeric, `maximum_discount` numeric, `usage_limit` integer, `usage_limit_per_user` integer not null default '1', `used_count` integer not null default '0', `starts_at` date, `expires_at` date, `applicable_to` enum('all','courses','subscriptions','specific') not null default 'all', `applicable_course_ids` text, `applicable_user_ids` text, `is_active` tinyint(1) not null default '1', `is_public` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `course_enrollments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `student_id` integer not null, `course_id` integer not null, `enrolled_at` datetime not null default CURRENT_TIMESTAMP, `completed_at` datetime, `progress_percentage` integer not null default '0', `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`course_id`) references `courses`(`id`) on delete cascade);

CREATE TABLE `course_lessons` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `advanced_course_id` integer not null, `title` varchar(255) not null, `description` text, `type` varchar(255) not null default ('video'), `content` text, `video_url` varchar(255), `attachments` text, `duration_minutes` integer default '0', `order` integer not null default ('0'), `is_free` tinyint(1) not null default ('0'), `is_active` tinyint(1) not null default ('1'), `created_at` datetime, `updated_at` datetime, foreign key(`advanced_course_id`) references advanced_courses(`id`) on delete cascade on update no action);

CREATE TABLE `course_reviews` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `course_id` integer not null, `user_id` integer not null, `rating` integer not null, `review` text, `is_approved` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `courses`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `courses` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `content` text, `thumbnail` varchar(255), `subject_id` integer not null, `teacher_id` integer not null, `classroom_id` integer, `status` enum('draft','published','archived') not null default 'draft', `duration_minutes` integer, `is_free` tinyint(1) not null default '0', `price` numeric, `created_at` datetime, `updated_at` datetime, foreign key(`subject_id`) references `subjects`(`id`) on delete cascade, foreign key(`teacher_id`) references `users`(`id`) on delete cascade, foreign key(`classroom_id`) references `classrooms`(`id`) on delete set null);

CREATE TABLE `exam_activity_logs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `exam_id` integer not null, `attempt_id` integer not null, `student_id` integer not null, `activity_type` enum('focus','blur','visibility_change','mouse_move','keyboard','copy','paste','cut') not null default 'focus', `description` text, `metadata` text, `activity_at` datetime not null, `created_at` datetime, `updated_at` datetime, foreign key(`exam_id`) references `exams`(`id`) on delete cascade, foreign key(`attempt_id`) references `exam_attempts`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `exam_anti_cheat_logs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `exam_id` integer not null, `attempt_id` integer not null, `student_id` integer not null, `violation_type` enum('tab_switch','copy_paste','right_click','fullscreen_exit','window_blur','other') not null default 'other', `description` text, `metadata` text, `violation_at` datetime not null, `created_at` datetime, `updated_at` datetime, foreign key(`exam_id`) references `exams`(`id`) on delete cascade, foreign key(`attempt_id`) references `exam_attempts`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `exam_attempts` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `exam_id` integer not null, `user_id` integer not null, `started_at` datetime not null, `submitted_at` datetime, `score` integer, `answers` text not null, `status` varchar(255) not null default ('in_progress'), `time_taken` integer, `created_at` datetime, `updated_at` datetime, `ip_address` varchar(255), `user_agent` text, `tab_switches` integer not null default ('0'), `suspicious_activities` text, `percentage` numeric, `completed_at` datetime, `auto_submitted` tinyint(1) not null default '0', `reviewed_by` integer, `reviewed_at` datetime, `feedback` text, foreign key(`user_id`) references users(`id`) on delete cascade on update no action, foreign key(`exam_id`) references exams(`id`) on delete cascade on update no action, foreign key(`reviewed_by`) references `users`(`id`) on delete set null);

CREATE TABLE `exam_questions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `exam_id` integer not null, `question_id` integer not null, `order` integer not null, `marks` integer not null, `created_at` datetime, `updated_at` datetime, `time_limit` integer, `is_required` tinyint(1) not null default '0', foreign key(`exam_id`) references `exams`(`id`) on delete cascade, foreign key(`question_id`) references `questions`(`id`) on delete cascade);

CREATE TABLE `exam_tab_switch_logs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `exam_id` integer not null, `attempt_id` integer not null, `student_id` integer not null, `switch_count` integer not null default '0', `first_switch_at` datetime, `last_switch_at` datetime, `switch_details` text, `created_at` datetime, `updated_at` datetime, foreign key(`exam_id`) references `exams`(`id`) on delete cascade, foreign key(`attempt_id`) references `exam_attempts`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `exams` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `course_id` integer, `created_by` integer, `duration_minutes` integer not null, `total_marks` numeric default ('0'), `start_date` datetime, `end_date` datetime, `attempts_allowed` integer not null default ('1'), `shuffle_questions` tinyint(1) not null default ('1'), `show_results` tinyint(1) not null default ('1'), `status` varchar(255) not null default ('draft'), `settings` text, `created_at` datetime, `updated_at` datetime, `prevent_tab_switch` tinyint(1) not null default ('1'), `prevent_copy_paste` tinyint(1) not null default ('1'), `prevent_right_click` tinyint(1) not null default ('1'), `require_fullscreen` tinyint(1) not null default ('0'), `monitor_activity` tinyint(1) not null default ('1'), `passing_marks` numeric not null default ('50'), `start_time` datetime, `end_time` datetime, `show_results_immediately` tinyint(1) not null default ('0'), `show_correct_answers` tinyint(1) not null default ('0'), `show_explanations` tinyint(1) not null default ('0'), `allow_review` tinyint(1) not null default ('0'), `randomize_questions` tinyint(1) not null default ('0'), `randomize_options` tinyint(1) not null default ('0'), `require_camera` tinyint(1) not null default ('0'), `require_microphone` tinyint(1) not null default ('0'), `auto_submit` tinyint(1) not null default ('0'), `is_published` tinyint(1) not null default ('0'), `instructions` text, `is_active` tinyint(1) not null default ('1'), `advanced_course_id` integer, foreign key(`advanced_course_id`) references advanced_courses(`id`) on delete cascade on update no action, foreign key(`course_id`) references courses(`id`) on delete cascade on update no action, foreign key(`created_by`) references users(`id`) on delete cascade on update no action);

CREATE TABLE `failed_jobs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `uuid` varchar(255) not null, `connection` text not null, `queue` text not null, `payload` text not null, `exception` text not null, `failed_at` datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE `faqs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `question` varchar(255) not null, `answer` text not null, `category` varchar(255), `order` integer not null default '0', `is_active` tinyint(1) not null default '1', `views_count` integer not null default '0', `is_featured` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `files` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `original_name` varchar(255) not null, `path` varchar(255) not null, `mime_type` varchar(255) not null, `size` integer not null, `uploaded_by` integer not null, `fileable_type` varchar(255) not null, `fileable_id` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`uploaded_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `group_members` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `group_id` integer not null, `user_id` integer not null, `role` enum('leader','member') not null default 'member', `joined_at` datetime not null default CURRENT_TIMESTAMP, `created_at` datetime, `updated_at` datetime, foreign key(`group_id`) references `groups`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `groups` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `course_id` integer not null, `name` varchar(255) not null, `description` text, `leader_id` integer, `max_members` integer not null default '10', `status` enum('active','inactive','archived') not null default 'active', `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `advanced_courses`(`id`) on delete cascade, foreign key(`leader_id`) references `users`(`id`) on delete set null);

CREATE TABLE `installment_agreements` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `installment_plan_id` integer not null, `student_course_enrollment_id` integer not null, `user_id` integer not null, `advanced_course_id` integer, `total_amount` numeric not null, `deposit_amount` numeric not null default '0', `installments_count` integer not null, `start_date` date not null, `status` varchar(255) not null default 'active', `notes` text, `created_by` integer, `created_at` datetime, `updated_at` datetime, foreign key(`installment_plan_id`) references `installment_plans`(`id`) on delete cascade, foreign key(`student_course_enrollment_id`) references `student_course_enrollments`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`advanced_course_id`) references `advanced_courses`(`id`) on delete set null, foreign key(`created_by`) references `users`(`id`) on delete set null);

CREATE TABLE `installment_payments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `installment_agreement_id` integer not null, `sequence_number` integer not null, `due_date` date not null, `amount` numeric not null, `status` varchar(255) not null default 'pending', `paid_at` datetime, `payment_id` integer, `notes` text, `created_at` datetime, `updated_at` datetime, foreign key(`installment_agreement_id`) references `installment_agreements`(`id`) on delete cascade, foreign key(`payment_id`) references `payments`(`id`) on delete set null);

CREATE TABLE `installment_plans` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `slug` varchar(255) not null, `description` text, `advanced_course_id` integer, `total_amount` numeric, `deposit_amount` numeric not null default '0', `installments_count` integer not null, `frequency_unit` varchar(255) not null default 'month', `frequency_interval` integer not null default '1', `grace_period_days` integer not null default '0', `auto_generate_on_enrollment` tinyint(1) not null default '0', `is_active` tinyint(1) not null default '1', `metadata` text, `created_at` datetime, `updated_at` datetime, foreign key(`advanced_course_id`) references `advanced_courses`(`id`) on delete set null);

CREATE TABLE `invoices` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `invoice_number` varchar(255) not null, `user_id` integer not null, `type` enum('course','subscription','membership','other') not null default 'course', `description` varchar(255) not null, `subtotal` numeric not null, `tax_amount` numeric not null default '0', `discount_amount` numeric not null default '0', `total_amount` numeric not null, `status` enum('draft','pending','paid','partial','overdue','cancelled','refunded') not null default 'pending', `due_date` date, `paid_at` date, `notes` text, `items` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `job_batches` (`id` varchar(255) not null, `name` varchar(255) not null, `total_jobs` integer not null, `pending_jobs` integer not null, `failed_jobs` integer not null, `failed_job_ids` text not null, `options` text, `cancelled_at` integer, `created_at` integer not null, `finished_at` integer, primary key (`id`));

CREATE TABLE `jobs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `queue` varchar(255) not null, `payload` text not null, `attempts` integer not null, `reserved_at` integer, `available_at` integer not null, `created_at` integer not null);

CREATE TABLE `lecture_assignment_submissions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `assignment_id` integer not null, `student_id` integer not null, `content` text, `attachments` text, `github_link` varchar(255), `submitted_at` datetime not null default CURRENT_TIMESTAMP, `score` integer, `feedback` text, `voice_feedback_path` varchar(255), `feedback_attachments` text, `graded_at` datetime, `graded_by` integer, `status` enum('submitted','graded','returned') not null default 'submitted', `version` integer not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`assignment_id`) references `lecture_assignments`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`graded_by`) references `users`(`id`) on delete set null);

CREATE TABLE `lecture_assignments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lecture_id` integer not null, `title` varchar(255) not null, `description` text, `instructions` text, `due_date` datetime, `max_score` integer not null default '100', `allow_late_submission` tinyint(1) not null default '0', `status` enum('draft','published','archived') not null default 'draft', `created_at` datetime, `updated_at` datetime, foreign key(`lecture_id`) references `lectures`(`id`) on delete cascade);

CREATE TABLE `lecture_evaluations` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lecture_id` integer not null, `student_id` integer not null, `rating` integer not null default '5', `feedback` text, `evaluation_data` text, `created_at` datetime, `updated_at` datetime, foreign key(`lecture_id`) references `lectures`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `lectures` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `course_id` integer not null, `instructor_id` integer not null, `title` varchar(255) not null, `description` text, `teams_registration_link` varchar(255), `teams_meeting_link` varchar(255), `recording_url` varchar(255), `recording_file_path` varchar(255), `scheduled_at` datetime not null, `duration_minutes` integer not null default '60', `status` enum('scheduled','in_progress','completed','cancelled') not null default 'scheduled', `notes` text, `has_attendance_tracking` tinyint(1) not null default '1', `has_assignment` tinyint(1) not null default '0', `has_evaluation` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `advanced_courses`(`id`) on delete cascade, foreign key(`instructor_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `lessons` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `content` text, `video_url` varchar(255), `thumbnail` varchar(255), `course_id` integer not null, `order` integer not null default '0', `duration_minutes` integer, `is_free` tinyint(1) not null default '0', `status` enum('draft','published') not null default 'draft', `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `courses`(`id`) on delete cascade);

CREATE TABLE `loyalty_programs` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `description` text, `type` enum('points','tier','referral','volume') not null default 'points', `rules` text, `is_active` tinyint(1) not null default '1', `starts_at` date, `expires_at` date, `created_at` datetime, `updated_at` datetime);

CREATE TABLE `media_galleries` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `type` enum('image','video','document') not null default 'image', `file_path` varchar(255) not null, `thumbnail_path` varchar(255), `file_name` varchar(255) not null, `mime_type` varchar(255) not null, `file_size` integer not null, `category` varchar(255), `tags` text, `is_featured` tinyint(1) not null default '0', `is_active` tinyint(1) not null default '1', `views_count` integer not null default '0', `uploaded_by` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`uploaded_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `message_templates` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `title` varchar(255) not null, `content` text not null, `type` varchar(255) not null, `variables` text, `is_active` tinyint(1) not null default '1', `created_by` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`created_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `migrations` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` varchar(255) not null, `batch` integer not null);

CREATE TABLE `notification_reads` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `notification_id` integer not null, `user_id` integer not null, `read_at` datetime not null, `created_at` datetime, `updated_at` datetime, foreign key(`notification_id`) references `advanced_notifications`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `notifications` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `title` varchar(255) not null, `message` text not null, `type` varchar(255) not null default ('info'), `is_read` tinyint(1) not null default ('0'), `read_at` datetime, `data` text, `created_at` datetime, `updated_at` datetime, `sender_id` integer, `action_url` varchar(255), `action_text` varchar(255), `priority` enum('low','normal','high','urgent') not null default 'normal', `target_type` enum('all_students','course_students','year_students','subject_students','individual') not null default 'individual', `target_id` integer, `expires_at` datetime, foreign key(`user_id`) references users(`id`) on delete cascade on update no action, foreign key(`sender_id`) references `users`(`id`) on delete set null);

CREATE TABLE `orders` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `advanced_course_id` integer not null, `amount` numeric not null, `payment_method` enum('bank_transfer','cash','other') not null default 'bank_transfer', `payment_proof` varchar(255), `status` enum('pending','approved','rejected') not null default 'pending', `notes` text, `approved_at` datetime, `approved_by` integer, `created_at` datetime, `updated_at` datetime);

CREATE TABLE `parent_students` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `parent_id` integer not null, `student_id` integer not null, `relation` enum('father','mother','guardian') not null default 'father', `is_primary` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`parent_id`) references `users`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` datetime, primary key (`email`));

CREATE TABLE `payments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `payment_number` varchar(255) not null, `invoice_id` integer not null, `user_id` integer not null, `payment_method` enum('cash','card','bank_transfer','online','wallet','other') not null default 'cash', `payment_gateway` enum('manual','moyasar','stripe','paypal','other'), `amount` numeric not null, `currency` varchar(255) not null default 'EGP', `status` enum('pending','processing','completed','failed','cancelled','refunded') not null default 'pending', `transaction_id` varchar(255), `reference_number` varchar(255), `gateway_response` text, `notes` text, `paid_at` datetime, `processed_by` integer, `created_at` datetime, `updated_at` datetime, foreign key(`invoice_id`) references `invoices`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`processed_by`) references `users`(`id`) on delete set null);

CREATE TABLE `permissions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `display_name` varchar(255) not null, `description` varchar(255), `group` varchar(255), `created_at` datetime, `updated_at` datetime);

CREATE TABLE `platform_settings` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `key` varchar(255) not null, `value` text, `type` varchar(255) not null default 'string', `group` varchar(255), `description` varchar(255), `created_at` datetime, `updated_at` datetime);

CREATE TABLE `point_transactions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `user_points_id` integer not null, `type` enum('earned','redeemed','expired','adjusted') not null default 'earned', `points` integer not null, `points_before` integer not null, `points_after` integer not null, `description` text not null, `invoice_id` integer, `metadata` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`user_points_id`) references `user_points`(`id`) on delete cascade, foreign key(`invoice_id`) references `invoices`(`id`) on delete set null);

CREATE TABLE `progress_tracks` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `course_id` integer, `track_type` enum('course','lesson','exam','assignment','overall') not null default 'course', `item_id` integer, `progress_percentage` integer not null default '0', `status` enum('not_started','in_progress','completed','failed') not null default 'not_started', `started_at` datetime, `completed_at` datetime, `time_spent_minutes` integer not null default '0', `metadata` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`course_id`) references `advanced_courses`(`id`) on delete cascade);

CREATE TABLE `question_banks` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `subject_id` integer not null, `created_by` integer not null, `difficulty` enum('easy','medium','hard') not null, `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`subject_id`) references `subjects`(`id`) on delete cascade, foreign key(`created_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `question_categories` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `description` text, `academic_year_id` integer not null, `academic_subject_id` integer not null, `parent_id` integer, `order` integer not null default '0', `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`academic_year_id`) references `academic_years`(`id`) on delete cascade, foreign key(`academic_subject_id`) references `academic_subjects`(`id`) on delete cascade, foreign key(`parent_id`) references `question_categories`(`id`) on delete cascade);

CREATE TABLE `questions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `question_bank_id` integer, `question` text not null, `type` varchar(255) not null, `options` text, `correct_answer` text not null, `explanation` text, `points` numeric not null default ('1'), `image` varchar(255), `is_active` tinyint(1) not null default ('1'), `created_at` datetime, `updated_at` datetime, `category_id` integer, `difficulty_level` enum('easy','medium','hard') not null default ('medium'), `image_url` varchar(255), `audio_url` varchar(255), `video_url` varchar(255), `time_limit` integer, `tags` text, foreign key(`category_id`) references question_categories(`id`) on delete set null on update no action, foreign key(`question_bank_id`) references question_banks(`id`) on delete cascade on update no action);

CREATE TABLE `quiz_attempts` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `quiz_id` integer not null, `student_id` integer not null, `started_at` datetime not null default CURRENT_TIMESTAMP, `completed_at` datetime, `answers` text, `score` integer, `total_points` integer, `status` enum('in_progress','completed','timeout') not null default 'in_progress', `created_at` datetime, `updated_at` datetime, foreign key(`quiz_id`) references `quizzes`(`id`) on delete cascade, foreign key(`student_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `quiz_questions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `quiz_id` integer not null, `question` text not null, `type` enum('multiple_choice','true_false','short_answer','essay') not null, `options` text, `correct_answer` text not null, `points` integer not null default '1', `order` integer not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`quiz_id`) references `quizzes`(`id`) on delete cascade);

CREATE TABLE `quizzes` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) not null, `description` text, `course_id` integer not null, `lesson_id` integer, `teacher_id` integer not null, `duration_minutes` integer not null default '60', `max_attempts` integer not null default '1', `shuffle_questions` tinyint(1) not null default '0', `show_results_immediately` tinyint(1) not null default '0', `available_from` datetime, `available_until` datetime, `status` enum('draft','published','archived') not null default 'draft', `created_at` datetime, `updated_at` datetime, foreign key(`course_id`) references `courses`(`id`) on delete cascade, foreign key(`lesson_id`) references `lessons`(`id`) on delete set null, foreign key(`teacher_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `referrals` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `referrer_id` integer not null, `referred_id` integer not null, `referral_code` varchar(255) not null, `status` enum('pending','completed','cancelled') not null default 'pending', `completed_at` datetime, `reward_amount` numeric, `reward_points` integer, `invoice_id` integer, `created_at` datetime, `updated_at` datetime, foreign key(`referrer_id`) references `users`(`id`) on delete cascade, foreign key(`referred_id`) references `users`(`id`) on delete cascade, foreign key(`invoice_id`) references `invoices`(`id`) on delete set null);

CREATE TABLE `review_helpful` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `review_id` integer not null, `user_id` integer not null, `is_helpful` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`review_id`) references `course_reviews`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `role_permissions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `role_id` integer not null, `permission_id` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`role_id`) references `roles`(`id`) on delete cascade, foreign key(`permission_id`) references `permissions`(`id`) on delete cascade);

CREATE TABLE `roles` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `display_name` varchar(255) not null, `description` varchar(255), `is_system` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `schools` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `description` text, `logo` varchar(255), `address` varchar(255), `phone` varchar(255), `email` varchar(255), `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `sessions` (`id` varchar(255) not null, `user_id` integer, `ip_address` varchar(255), `user_agent` text, `payload` text not null, `last_activity` integer not null, primary key (`id`));

CREATE TABLE `settings` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `key` varchar(255) not null, `value` text not null, `type` varchar(255) not null default 'text', `description` text, `created_at` datetime, `updated_at` datetime);

CREATE TABLE `student_course_enrollments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `advanced_course_id` integer not null, `enrolled_at` datetime not null default (CURRENT_TIMESTAMP), `activated_at` datetime, `activated_by` integer, `status` varchar(255) not null default ('pending'), `progress` numeric not null default ('0'), `notes` text, `created_at` datetime, `updated_at` datetime, `invoice_id` integer, `payment_id` integer, `coupon_id` integer, `original_price` numeric not null default '0', `discount_amount` numeric not null default '0', `final_price` numeric not null default '0', `payment_method` enum('cash','card','bank_transfer','online','wallet','subscription','free'), `enrollment_type` enum('purchase','subscription','gift','trial','promotional') not null default 'purchase', `expires_at` datetime, `access_type` enum('lifetime','limited','subscription') not null default 'lifetime', `referral_code` varchar(255), foreign key(`invoice_id`) references `invoices`(`id`) on delete set null, foreign key(`payment_id`) references `payments`(`id`) on delete set null, foreign key(`coupon_id`) references `coupons`(`id`) on delete set null);

CREATE TABLE `student_reports` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `student_id` integer not null, `parent_id` integer, `report_month` varchar(255) not null, `report_type` enum('monthly','weekly','custom') not null default 'monthly', `report_data` text not null, `sent_via` enum('whatsapp','email','sms') not null default 'whatsapp', `sent_at` datetime, `status` enum('pending','sent','failed') not null default 'pending', `error_message` text, `generated_by` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`student_id`) references `users`(`id`) on delete cascade, foreign key(`parent_id`) references `users`(`id`) on delete set null, foreign key(`generated_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `subjects` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) not null, `description` text, `color` varchar(255) not null default '#3B82F6', `icon` varchar(255), `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime);

CREATE TABLE `subscriptions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `subscription_type` varchar(255) not null, `plan_name` varchar(255) not null, `price` numeric not null, `start_date` date not null, `end_date` date, `status` enum('active','expired','cancelled','suspended') not null default 'active', `auto_renew` tinyint(1) not null default '0', `billing_cycle` integer not null default '1', `invoice_id` integer, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`invoice_id`) references `invoices`(`id`) on delete set null);

CREATE TABLE `task_comments` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `task_id` integer not null, `user_id` integer not null, `comment` text not null, `voice_comment_path` varchar(255), `attachments` text, `created_at` datetime, `updated_at` datetime, foreign key(`task_id`) references `tasks`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `task_notifications` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `task_id` integer not null, `user_id` integer not null, `type` enum('reminder','due_soon','overdue','completed','comment') not null default 'reminder', `is_read` tinyint(1) not null default '0', `sent_at` datetime, `read_at` datetime, `created_at` datetime, `updated_at` datetime, foreign key(`task_id`) references `tasks`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `tasks` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `title` varchar(255) not null, `description` text, `priority` enum('low','medium','high','urgent') not null default 'medium', `status` enum('pending','in_progress','completed','cancelled') not null default 'pending', `due_date` datetime, `completed_at` datetime, `related_course_id` integer, `related_lecture_id` integer, `related_assignment_id` integer, `related_type` varchar(255), `related_id` integer, `is_reminder` tinyint(1) not null default '0', `reminder_at` datetime, `tags` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`related_course_id`) references `advanced_courses`(`id`) on delete set null, foreign key(`related_lecture_id`) references `lectures`(`id`) on delete set null, foreign key(`related_assignment_id`) references `assignments`(`id`) on delete set null);

CREATE TABLE `teams_attendance_files` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lecture_id` integer not null, `file_name` varchar(255) not null, `file_path` varchar(255) not null, `file_type` varchar(255) not null default 'csv', `total_records` integer not null default '0', `processed_records` integer not null default '0', `status` enum('uploaded','processing','completed','failed') not null default 'uploaded', `error_message` text, `uploaded_by` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`lecture_id`) references `lectures`(`id`) on delete cascade, foreign key(`uploaded_by`) references `users`(`id`) on delete cascade);

CREATE TABLE `transactions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `transaction_number` varchar(255) not null, `user_id` integer not null, `payment_id` integer, `type` enum('debit','credit') not null, `category` enum('course_payment','subscription','refund','commission','fee','other') not null default 'other', `amount` numeric not null, `currency` varchar(255) not null default 'EGP', `description` text not null, `status` enum('pending','completed','cancelled','reversed') not null default 'completed', `metadata` text, `created_by` integer, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`payment_id`) references `payments`(`id`) on delete set null, foreign key(`created_by`) references `users`(`id`) on delete set null);

CREATE TABLE `user_achievements` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `achievement_id` integer not null, `course_id` integer, `earned_at` datetime not null, `progress` integer not null default '100', `metadata` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`achievement_id`) references `achievements`(`id`) on delete cascade, foreign key(`course_id`) references `advanced_courses`(`id`) on delete set null);

CREATE TABLE `user_badges` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `badge_id` integer not null, `earned_at` datetime not null, `is_displayed` tinyint(1) not null default '1', `sort_order` integer not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`badge_id`) references `badges`(`id`) on delete cascade);

CREATE TABLE `user_permissions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `permission_id` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`permission_id`) references `permissions`(`id`) on delete cascade);

CREATE TABLE `user_points` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `loyalty_program_id` integer, `points` integer not null default '0', `total_earned` integer not null default '0', `total_redeemed` integer not null default '0', `tier` enum('bronze','silver','gold','platinum','diamond') not null default 'bronze', `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`loyalty_program_id`) references `loyalty_programs`(`id`) on delete set null);

CREATE TABLE `user_roles` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `role_id` integer not null, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade, foreign key(`role_id`) references `roles`(`id`) on delete cascade);

CREATE TABLE users (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT,
                phone varchar(255) UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role varchar(255) NOT NULL DEFAULT 'student',
                avatar TEXT,
                is_active INTEGER DEFAULT 1,
                bio TEXT,
                parent_id INTEGER,
                profile_image TEXT,
                birth_date TEXT,
                address TEXT,
                academic_year_id INTEGER,
                last_login_at TEXT,
                remember_token TEXT,
                created_at TEXT,
                updated_at TEXT
            );

CREATE TABLE `video_tokens` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lesson_id` integer not null, `user_id` integer not null, `token` varchar(255) not null, `expires_at` datetime not null, `ip_address` varchar(255), `is_used` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`lesson_id`) references `lessons`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `video_watches` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `lesson_id` integer not null, `user_id` integer not null, `watch_time` integer not null, `video_duration` integer not null, `progress_percentage` numeric not null, `completed` tinyint(1) not null default '0', `created_at` datetime, `updated_at` datetime, foreign key(`lesson_id`) references `lessons`(`id`) on delete cascade, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `wallet_reports` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `wallet_id` integer not null, `report_month` varchar(255) not null, `opening_balance` numeric not null default '0', `closing_balance` numeric not null default '0', `total_deposits` numeric not null default '0', `total_withdrawals` numeric not null default '0', `transactions_count` integer not null default '0', `expected_amounts` text, `actual_amounts` text, `difference` numeric not null default '0', `notes` text, `created_at` datetime, `updated_at` datetime, foreign key(`wallet_id`) references `wallets`(`id`) on delete cascade);

CREATE TABLE `wallet_transactions` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `wallet_id` integer not null, `transaction_id` integer, `type` enum('deposit','withdrawal','refund','commission','bonus','deduction') not null default 'deposit', `amount` numeric not null, `balance_before` numeric not null, `balance_after` numeric not null, `description` text not null, `status` enum('pending','completed','cancelled') not null default 'completed', `metadata` text, `created_at` datetime, `updated_at` datetime, foreign key(`wallet_id`) references `wallets`(`id`) on delete cascade, foreign key(`transaction_id`) references `transactions`(`id`) on delete set null);

CREATE TABLE `wallets` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `balance` numeric not null default '0', `pending_balance` numeric not null default '0', `currency` varchar(255) not null default 'EGP', `is_active` tinyint(1) not null default '1', `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade);

CREATE TABLE `whats_app_messages` (`id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer, `phone_number` varchar(255) not null, `message` text not null, `type` varchar(255) not null default 'text', `status` enum('pending','sent','delivered','read','failed') not null default 'pending', `response_data` text, `whatsapp_message_id` varchar(255), `sent_at` datetime, `delivered_at` datetime, `read_at` datetime, `template_name` varchar(255), `template_params` text, `error_message` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete set null);

CREATE INDEX `academic_subjects_academic_year_id_is_active_order_index` on `academic_subjects` (`academic_year_id`, `is_active`, `order`);

CREATE UNIQUE INDEX `academic_subjects_code_unique` on `academic_subjects` (`code`);

CREATE UNIQUE INDEX `academic_years_code_unique` on `academic_years` (`code`);

CREATE INDEX `academic_years_is_active_order_index` on `academic_years` (`is_active`, `order`);

CREATE UNIQUE INDEX `achievements_code_unique` on `achievements` (`code`);

CREATE INDEX `activity_logs_model_type_model_id_index` on `activity_logs` (`model_type`, `model_id`);

CREATE INDEX `advanced_courses_new_category_index` on `advanced_courses` (`category`);

CREATE INDEX `advanced_courses_new_instructor_id_index` on `advanced_courses` (`instructor_id`);

CREATE INDEX `advanced_courses_new_is_active_is_featured_index` on `advanced_courses` (`is_active`, `is_featured`);

CREATE INDEX `advanced_courses_new_programming_language_index` on `advanced_courses` (`programming_language`);

CREATE INDEX `assignment_submission_versions_submission_id_submitted_at_index` on `assignment_submission_versions` (`submission_id`, `submitted_at`);

CREATE UNIQUE INDEX `assignment_submission_versions_submission_id_version_unique` on `assignment_submission_versions` (`submission_id`, `version`);

CREATE UNIQUE INDEX `assignment_submissions_assignment_id_student_id_unique` on `assignment_submissions` (`assignment_id`, `student_id`);

CREATE INDEX `attendance_records_lecture_id_status_index` on `attendance_records` (`lecture_id`, `status`);

CREATE UNIQUE INDEX `attendance_records_lecture_id_student_id_unique` on `attendance_records` (`lecture_id`, `student_id`);

CREATE INDEX `attendance_records_student_id_status_index` on `attendance_records` (`student_id`, `status`);

CREATE INDEX `attendance_statistics_course_id_attendance_rate_index` on `attendance_statistics` (`course_id`, `attendance_rate`);

CREATE UNIQUE INDEX `attendance_statistics_student_id_course_id_unique` on `attendance_statistics` (`student_id`, `course_id`);

CREATE UNIQUE INDEX `badges_code_unique` on `badges` (`code`);

CREATE INDEX `blog_posts_is_featured_index` on `blog_posts` (`is_featured`);

CREATE INDEX `blog_posts_slug_index` on `blog_posts` (`slug`);

CREATE UNIQUE INDEX `blog_posts_slug_unique` on `blog_posts` (`slug`);

CREATE INDEX `blog_posts_status_published_at_index` on `blog_posts` (`status`, `published_at`);

CREATE INDEX `calendar_events_academic_year_id_academic_subject_id_index` on `calendar_events` (`academic_year_id`, `academic_subject_id`);

CREATE INDEX `calendar_events_created_by_type_index` on `calendar_events` (`created_by`, `type`);

CREATE INDEX `calendar_events_start_date_end_date_index` on `calendar_events` (`start_date`, `end_date`);

CREATE INDEX `calendar_events_visibility_status_index` on `calendar_events` (`visibility`, `status`);

CREATE UNIQUE INDEX `certificates_certificate_number_unique` on `certificates` (`certificate_number`);

CREATE UNIQUE INDEX `classroom_students_student_id_classroom_id_unique` on `classroom_students` (`student_id`, `classroom_id`);

CREATE INDEX `contact_messages_status_created_at_index` on `contact_messages` (`status`, `created_at`);

CREATE INDEX `coupon_usages_coupon_id_user_id_index` on `coupon_usages` (`coupon_id`, `user_id`);

CREATE INDEX `coupon_usages_invoice_id_index` on `coupon_usages` (`invoice_id`);

CREATE INDEX `coupons_code_is_active_index` on `coupons` (`code`, `is_active`);

CREATE UNIQUE INDEX `coupons_code_unique` on `coupons` (`code`);

CREATE INDEX `coupons_is_active_index` on `coupons` (`is_active`);

CREATE INDEX `coupons_starts_at_expires_at_index` on `coupons` (`starts_at`, `expires_at`);

CREATE UNIQUE INDEX `course_enrollments_student_id_course_id_unique` on `course_enrollments` (`student_id`, `course_id`);

CREATE INDEX `course_lessons_advanced_course_id_is_active_index` on `course_lessons` (`advanced_course_id`, `is_active`);

CREATE INDEX `course_lessons_advanced_course_id_order_index` on `course_lessons` (`advanced_course_id`, `order`);

CREATE UNIQUE INDEX `course_reviews_course_id_user_id_unique` on `course_reviews` (`course_id`, `user_id`);

CREATE INDEX `exam_activity_logs_exam_id_attempt_id_activity_type_index` on `exam_activity_logs` (`exam_id`, `attempt_id`, `activity_type`);

CREATE INDEX `exam_activity_logs_student_id_activity_at_index` on `exam_activity_logs` (`student_id`, `activity_at`);

CREATE INDEX `exam_anti_cheat_logs_exam_id_attempt_id_index` on `exam_anti_cheat_logs` (`exam_id`, `attempt_id`);

CREATE INDEX `exam_anti_cheat_logs_student_id_violation_type_index` on `exam_anti_cheat_logs` (`student_id`, `violation_type`);

CREATE UNIQUE INDEX `exam_tab_switch_logs_exam_id_attempt_id_unique` on `exam_tab_switch_logs` (`exam_id`, `attempt_id`);

CREATE INDEX `exam_tab_switch_logs_student_id_switch_count_index` on `exam_tab_switch_logs` (`student_id`, `switch_count`);

CREATE UNIQUE INDEX `failed_jobs_uuid_unique` on `failed_jobs` (`uuid`);

CREATE INDEX `faqs_category_index` on `faqs` (`category`);

CREATE INDEX `faqs_is_active_order_index` on `faqs` (`is_active`, `order`);

CREATE INDEX `files_fileable_type_fileable_id_index` on `files` (`fileable_type`, `fileable_id`);

CREATE UNIQUE INDEX `group_members_group_id_user_id_unique` on `group_members` (`group_id`, `user_id`);

CREATE INDEX `groups_course_id_status_index` on `groups` (`course_id`, `status`);

CREATE UNIQUE INDEX `installment_payments_agreement_seq_unique` on `installment_payments` (`installment_agreement_id`, `sequence_number`);

CREATE UNIQUE INDEX `installment_plans_slug_unique` on `installment_plans` (`slug`);

CREATE INDEX `invoices_due_date_index` on `invoices` (`due_date`);

CREATE INDEX `invoices_invoice_number_index` on `invoices` (`invoice_number`);

CREATE UNIQUE INDEX `invoices_invoice_number_unique` on `invoices` (`invoice_number`);

CREATE INDEX `invoices_user_id_status_index` on `invoices` (`user_id`, `status`);

CREATE INDEX `jobs_queue_index` on `jobs` (`queue`);

CREATE UNIQUE INDEX `lecture_evaluations_lecture_id_student_id_unique` on `lecture_evaluations` (`lecture_id`, `student_id`);

CREATE INDEX `lectures_course_id_scheduled_at_index` on `lectures` (`course_id`, `scheduled_at`);

CREATE INDEX `lectures_instructor_id_status_index` on `lectures` (`instructor_id`, `status`);

CREATE INDEX `media_galleries_category_is_featured_index` on `media_galleries` (`category`, `is_featured`);

CREATE INDEX `media_galleries_type_is_active_index` on `media_galleries` (`type`, `is_active`);

CREATE INDEX `message_templates_type_is_active_index` on `message_templates` (`type`, `is_active`);

CREATE UNIQUE INDEX `notification_reads_notification_id_user_id_unique` on `notification_reads` (`notification_id`, `user_id`);

CREATE INDEX `orders_status_created_at_index` on `orders` (`status`, `created_at`);

CREATE INDEX `orders_user_id_advanced_course_id_index` on `orders` (`user_id`, `advanced_course_id`);

CREATE UNIQUE INDEX `parent_students_parent_id_student_id_unique` on `parent_students` (`parent_id`, `student_id`);

CREATE INDEX `payments_invoice_id_status_index` on `payments` (`invoice_id`, `status`);

CREATE INDEX `payments_payment_number_index` on `payments` (`payment_number`);

CREATE UNIQUE INDEX `payments_payment_number_unique` on `payments` (`payment_number`);

CREATE INDEX `payments_transaction_id_index` on `payments` (`transaction_id`);

CREATE INDEX `payments_user_id_status_index` on `payments` (`user_id`, `status`);

CREATE UNIQUE INDEX `permissions_name_unique` on `permissions` (`name`);

CREATE UNIQUE INDEX `platform_settings_key_unique` on `platform_settings` (`key`);

CREATE INDEX `point_transactions_invoice_id_index` on `point_transactions` (`invoice_id`);

CREATE INDEX `point_transactions_user_id_type_index` on `point_transactions` (`user_id`, `type`);

CREATE INDEX `progress_tracks_status_progress_percentage_index` on `progress_tracks` (`status`, `progress_percentage`);

CREATE INDEX `progress_tracks_user_id_course_id_track_type_index` on `progress_tracks` (`user_id`, `course_id`, `track_type`);

CREATE INDEX `question_categories_academic_year_id_academic_subject_id_index` on `question_categories` (`academic_year_id`, `academic_subject_id`);

CREATE INDEX `question_categories_parent_id_order_index` on `question_categories` (`parent_id`, `order`);

CREATE INDEX `questions_category_id_type_index` on `questions` (`category_id`, `type`);

CREATE INDEX `questions_difficulty_level_is_active_index` on `questions` (`difficulty_level`, `is_active`);

CREATE INDEX `referrals_referral_code_index` on `referrals` (`referral_code`);

CREATE UNIQUE INDEX `referrals_referral_code_unique` on `referrals` (`referral_code`);

CREATE UNIQUE INDEX `referrals_referred_id_unique` on `referrals` (`referred_id`);

CREATE INDEX `referrals_referrer_id_status_index` on `referrals` (`referrer_id`, `status`);

CREATE UNIQUE INDEX `review_helpful_review_id_user_id_unique` on `review_helpful` (`review_id`, `user_id`);

CREATE UNIQUE INDEX `role_permissions_role_id_permission_id_unique` on `role_permissions` (`role_id`, `permission_id`);

CREATE UNIQUE INDEX `roles_name_unique` on `roles` (`name`);

CREATE INDEX `sessions_last_activity_index` on `sessions` (`last_activity`);

CREATE INDEX `sessions_user_id_index` on `sessions` (`user_id`);

CREATE UNIQUE INDEX `settings_key_unique` on `settings` (`key`);

CREATE INDEX `student_course_enrollments_status_enrolled_at_index` on `student_course_enrollments` (`status`, `enrolled_at`);

CREATE INDEX `student_reports_parent_id_sent_at_index` on `student_reports` (`parent_id`, `sent_at`);

CREATE INDEX `student_reports_student_id_report_month_index` on `student_reports` (`student_id`, `report_month`);

CREATE UNIQUE INDEX `student_reports_student_id_report_month_report_type_unique` on `student_reports` (`student_id`, `report_month`, `report_type`);

CREATE INDEX `subscriptions_end_date_index` on `subscriptions` (`end_date`);

CREATE INDEX `subscriptions_user_id_status_index` on `subscriptions` (`user_id`, `status`);

CREATE INDEX `task_comments_task_id_created_at_index` on `task_comments` (`task_id`, `created_at`);

CREATE INDEX `task_notifications_task_id_type_index` on `task_notifications` (`task_id`, `type`);

CREATE INDEX `task_notifications_user_id_is_read_index` on `task_notifications` (`user_id`, `is_read`);

CREATE INDEX `tasks_related_type_related_id_index` on `tasks` (`related_type`, `related_id`);

CREATE INDEX `tasks_user_id_due_date_index` on `tasks` (`user_id`, `due_date`);

CREATE INDEX `tasks_user_id_status_index` on `tasks` (`user_id`, `status`);

CREATE INDEX `teams_attendance_files_lecture_id_status_index` on `teams_attendance_files` (`lecture_id`, `status`);

CREATE INDEX `transactions_category_index` on `transactions` (`category`);

CREATE INDEX `transactions_transaction_number_index` on `transactions` (`transaction_number`);

CREATE UNIQUE INDEX `transactions_transaction_number_unique` on `transactions` (`transaction_number`);

CREATE INDEX `transactions_user_id_type_status_index` on `transactions` (`user_id`, `type`, `status`);

CREATE UNIQUE INDEX `unique_submission_version` on `lecture_assignment_submissions` (`assignment_id`, `student_id`, `version`);

CREATE UNIQUE INDEX `unique_user_achievement` on `user_achievements` (`user_id`, `achievement_id`, `course_id`);

CREATE UNIQUE INDEX `unique_user_course` on `student_course_enrollments` (`user_id`, `advanced_course_id`);

CREATE INDEX `user_achievements_user_id_earned_at_index` on `user_achievements` (`user_id`, `earned_at`);

CREATE UNIQUE INDEX `user_badges_user_id_badge_id_unique` on `user_badges` (`user_id`, `badge_id`);

CREATE INDEX `user_badges_user_id_is_displayed_index` on `user_badges` (`user_id`, `is_displayed`);

CREATE UNIQUE INDEX `user_permissions_user_id_permission_id_unique` on `user_permissions` (`user_id`, `permission_id`);

CREATE INDEX `user_points_tier_index` on `user_points` (`tier`);

CREATE UNIQUE INDEX `user_points_user_id_unique` on `user_points` (`user_id`);

CREATE UNIQUE INDEX `user_roles_user_id_role_id_unique` on `user_roles` (`user_id`, `role_id`);

CREATE UNIQUE INDEX `video_tokens_token_unique` on `video_tokens` (`token`);

CREATE UNIQUE INDEX `video_watches_lesson_id_user_id_unique` on `video_watches` (`lesson_id`, `user_id`);

CREATE INDEX `wallet_reports_report_month_index` on `wallet_reports` (`report_month`);

CREATE UNIQUE INDEX `wallet_reports_wallet_id_report_month_unique` on `wallet_reports` (`wallet_id`, `report_month`);

CREATE INDEX `wallet_transactions_wallet_id_type_status_index` on `wallet_transactions` (`wallet_id`, `type`, `status`);

CREATE INDEX `wallets_user_id_index` on `wallets` (`user_id`);

CREATE UNIQUE INDEX `wallets_user_id_unique` on `wallets` (`user_id`);

CREATE INDEX `whats_app_messages_phone_number_status_index` on `whats_app_messages` (`phone_number`, `status`);

CREATE INDEX `whats_app_messages_user_id_sent_at_index` on `whats_app_messages` (`user_id`, `sent_at`);

-- ============================================
-- Data Export
-- ============================================

-- Data for table `academic_subjects`
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (1, 1, 'الحصة الرقمية والأدوات الأساسية', 'TCH101', NULL, 'fa-laptop-house', '#10B981', 1, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (2, 1, 'التخطيط والأهداف التعليمية', 'TCH102', NULL, 'fa-bullseye', '#F59E0B', 2, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (3, 1, 'التقييم والتغذية الراجعة', 'TCH103', NULL, 'fa-clipboard-check', '#8B5CF6', 3, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (4, 2, 'أدوات التواصل والتعاون الصفي', 'TCH201', NULL, 'fa-users', '#EF4444', 1, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (5, 2, 'الخصوصية والسلوك الرقمي في المدرسة', 'TCH202', NULL, 'fa-shield-halved', '#06B6D4', 2, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (6, 3, 'تصميم البرنامج التدريبي', 'TCH301', NULL, 'fa-diagram-project', '#EC4899', 1, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');
INSERT INTO `academic_subjects` (`id`,`academic_year_id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (7, 3, 'التواصل مع المتعلمين وخدمة ما بعد التدريب', 'TCH302', NULL, 'fa-comments', '#F59E0B', 2, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');

-- Data for table `academic_years`
INSERT INTO `academic_years` (`id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (1, 'مسار التمهيد للتدريس أونلاين', 'TCH-L1', 'المستوى الأول: أساسيات الحصة الرقمية والتفاعل مع المتعلمين', 'fa-chalkboard-teacher', '#3B82F6', 1, 1, '2025-11-01 23:31:15', '2025-11-12 12:22:38');
INSERT INTO `academic_years` (`id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (2, 'مسار المهارات الرقمية للمعلم', 'TCH-L2', 'المستوى الثاني: دمج أدوات رقمية آمنة وفعّالة في التعليم', 'fa-laptop-code', '#10B981', 2, 1, '2025-11-01 23:31:15', '2025-11-12 12:17:48');
INSERT INTO `academic_years` (`id`,`name`,`code`,`description`,`icon`,`color`,`order`,`is_active`,`created_at`,`updated_at`) VALUES (3, 'مسار التطوير المهني للمدرّب', 'TCH-L3', 'المستوى الثالث: بناء حضور مهني وتقديم برامج تدريبية أونلاين', 'fa-user-tie', '#8B5CF6', 3, 1, '2025-11-01 23:31:15', '2025-11-01 23:31:15');

-- Data for table `blog_posts`
INSERT INTO `blog_posts` (`id`,`author_id`,`title`,`slug`,`excerpt`,`content`,`featured_image`,`images`,`status`,`is_featured`,`views_count`,`tags`,`meta_title`,`meta_description`,`published_at`,`created_at`,`updated_at`) VALUES (1, 1, 'ااااااااا', 'الصف الأول الثانوي', 'ااااااااااااااااا', 'ااااااااااااااااااا', 'images/blog/1762386336_690be1a08b718.png', NULL, 'published', 0, 8, '[]', 'ىى', 'ىى', '2025-11-05 23:09:23', '2025-11-05 23:09:23', '2025-11-14 01:00:37');

-- Data for table `contact_messages`
INSERT INTO `contact_messages` (`id`,`name`,`email`,`phone`,`subject`,`message`,`status`,`admin_notes`,`replied_by`,`replied_at`,`created_at`,`updated_at`) VALUES (1, 'زائر تجريبي', 'visitor@example.local', '0100000000', 'استفسار', 'نص تجريبي', 'new', NULL, NULL, NULL, '2025-11-05 23:49:40', '2025-11-05 23:49:40');

-- Data for table `installment_plans`
INSERT INTO `installment_plans` (`id`,`name`,`slug`,`description`,`advanced_course_id`,`total_amount`,`deposit_amount`,`installments_count`,`frequency_unit`,`frequency_interval`,`grace_period_days`,`auto_generate_on_enrollment`,`is_active`,`metadata`,`created_at`,`updated_at`) VALUES (1, 'mohamed hany', 'mohamed-hany-isvu', 'لالالالالالالالالالالالالالالالالالالالا', NULL, 1500, 500, 2, 'month', 1, 0, 1, 1, NULL, '2025-11-13 14:40:32', '2025-11-13 14:40:32');

-- Data for table `media_galleries`
INSERT INTO `media_galleries` (`id`,`title`,`description`,`type`,`file_path`,`thumbnail_path`,`file_name`,`mime_type`,`file_size`,`category`,`tags`,`is_featured`,`is_active`,`views_count`,`uploaded_by`,`created_at`,`updated_at`) VALUES (1, 'ا', 'ا', 'image', 'images/media/images/1762386926_690be3eec2525.png', NULL, 'Screenshot 2025-06-11 194445.png', 'image/png', 233747, 'ا', NULL, 1, 1, 1, 1, '2025-11-05 23:55:26', '2025-11-14 01:01:50');

-- Data for table `migrations`
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (4, '2025_08_30_011316_create_learning_platform_tables', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (5, '2025_08_30_013755_create_advanced_platform_tables', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (6, '2025_08_30_124816_create_orders_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (7, '2025_08_30_130813_add_advanced_course_id_to_course_enrollments_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (8, '2025_08_31_012310_create_student_course_enrollments_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (9, '2025_08_31_154304_add_video_and_attachments_to_course_lessons_table', 1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (10, '2025_09_01_024049_add_activity_log_columns', 2);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (11, '2025_11_01_233032_create_academic_years_and_subjects_tables', 3);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (12, '2025_11_01_234139_create_advanced_courses_table', 4);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (14, '2025_11_01_234339_create_coupons_and_discounts_system', 6);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (15, '2025_11_01_234342_create_certificates_and_achievements_system', 7);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (16, '2025_11_01_234346_update_course_enrollments_to_academy_system', 8);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (17, '2025_11_02_000001_remove_academic_fields_from_advanced_courses', 9);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (18, '2025_11_04_191812_create_course_lessons_table', 10);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (19, '2025_08_31_154500_update_course_lessons_duration_minutes_nullable', 11);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (20, '2025_08_31_160100_update_questions_table', 12);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (21, '2025_08_31_170421_create_question_categories_table_if_not_exists', 12);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (22, '2025_08_31_175753_create_notifications_table_enhanced', 12);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (23, '2025_08_31_190812_create_calendar_events_table', 12);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (24, '2025_08_31_191013_create_calendar_event_attendees_table', 12);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (25, '2025_11_04_191254_create_lectures_and_groups_system', 13);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (26, '2025_11_04_191257_create_tasks_system', 14);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (27, '2025_11_04_191301_create_attendance_system', 15);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (28, '2025_11_04_192343_create_public_pages_tables', 16);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (29, '2025_08_31_231829_add_parent_id_to_question_categories_table', 17);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (30, '2025_11_04_191304_enhance_assignments_system', 18);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (31, '2025_11_04_191307_enhance_exams_anti_cheat_system', 18);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (32, '2025_09_01_024744_create_whats_app_messages_table', 19);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (33, '2025_09_01_024811_create_student_reports_table', 20);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (34, '2025_11_01_235808_create_wallet_transactions_table', 21);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (35, '2025_08_31_232138_make_question_bank_id_nullable_in_questions_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (36, '2025_08_31_233317_add_passing_marks_to_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (37, '2025_08_31_233345_make_description_nullable_in_advanced_courses_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (38, '2025_08_31_233454_add_missing_columns_to_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (39, '2025_08_31_233702_add_is_active_to_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (40, '2025_08_31_233756_fix_course_id_in_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (41, '2025_08_31_233851_fix_created_by_in_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (42, '2025_08_31_234019_make_total_marks_nullable_in_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (43, '2025_08_31_234417_fix_date_time_columns_in_exams_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (44, '2025_09_01_003807_fix_exams_date_columns', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (45, '2025_09_01_021922_add_time_limit_and_is_required_to_exam_questions_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (46, '2025_09_01_022319_add_missing_columns_to_exam_attempts_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (47, '2025_09_01_022502_add_remaining_columns_to_exam_attempts_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (48, '2025_09_01_024754_create_message_templates_table', 22);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (49, '2025_11_06_175225_create_user_permissions_table', 23);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (50, '2025_11_01_232031_update_user_roles_to_new_system', 24);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (51, '2025_11_01_234336_create_accounting_system_tables', 25);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (52, '2025_11_06_190000_create_installment_tables', 26);

-- Data for table `permissions`
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (1, 'view.dashboard', 'عرض لوحة التحكم', 'إمكانية الوصول إلى لوحة التحكم', 'إدارة النظام', '2025-11-06 17:11:37', '2025-11-06 17:11:37');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (2, 'manage.users', 'إدارة المستخدمين', 'إدارة المستخدمين (عرض، إضافة، تعديل، حذف)', 'إدارة النظام', '2025-11-06 17:11:37', '2025-11-06 17:11:37');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (3, 'manage.orders', 'إدارة الطلبات', 'إدارة طلبات التسجيل في الكورسات', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (4, 'manage.notifications', 'إدارة الإشعارات', 'إرسال وإدارة الإشعارات', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (5, 'view.activity-log', 'عرض سجل النشاطات', 'عرض سجل نشاطات المستخدمين', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (6, 'view.statistics', 'عرض الإحصائيات', 'عرض إحصائيات المنصة', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (7, 'manage.roles', 'إدارة الأدوار', 'إدارة الأدوار والصلاحيات', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (8, 'manage.permissions', 'إدارة الصلاحيات', 'إدارة الصلاحيات', 'إدارة النظام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (9, 'manage.invoices', 'إدارة الفواتير', 'إدارة الفواتير', 'إدارة المحاسبة', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (10, 'manage.payments', 'إدارة المدفوعات', 'إدارة المدفوعات', 'إدارة المحاسبة', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (11, 'manage.transactions', 'إدارة المعاملات المالية', 'إدارة المعاملات المالية', 'إدارة المحاسبة', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (12, 'manage.wallets', 'إدارة المحافظ', 'إدارة محافظ المستخدمين', 'إدارة المحاسبة', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (13, 'manage.subscriptions', 'إدارة الاشتراكات', 'إدارة الاشتراكات', 'إدارة المحاسبة', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (14, 'manage.coupons', 'إدارة الكوبونات', 'إدارة الكوبونات والخصومات', 'إدارة التسويق', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (15, 'manage.referrals', 'إدارة برنامج الإحالات', 'إدارة برنامج الإحالات', 'إدارة التسويق', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (16, 'manage.loyalty', 'إدارة برامج الولاء', 'إدارة برامج الولاء', 'إدارة التسويق', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (17, 'manage.certificates', 'إدارة الشهادات', 'إدارة الشهادات', 'الشهادات والإنجازات', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (18, 'manage.achievements', 'إدارة الإنجازات', 'إدارة الإنجازات', 'الشهادات والإنجازات', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (19, 'manage.badges', 'إدارة الشارات', 'إدارة الشارات', 'الشهادات والإنجازات', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (20, 'manage.reviews', 'إدارة التقييمات', 'إدارة التقييمات والمراجعات', 'الشهادات والإنجازات', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (21, 'manage.academic-years', 'إدارة السنوات الدراسية', 'إدارة السنوات الدراسية', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (22, 'manage.academic-subjects', 'إدارة المواد الدراسية', 'إدارة المواد الدراسية', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (23, 'manage.courses', 'إدارة الكورسات', 'إدارة الكورسات والدروس', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (24, 'manage.enrollments', 'إدارة تسجيل الطلاب', 'إدارة تسجيل الطلاب في الكورسات', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (25, 'manage.lectures', 'إدارة المحاضرات', 'إدارة المحاضرات', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (26, 'manage.groups', 'إدارة المجموعات', 'إدارة المجموعات الدراسية', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (27, 'manage.assignments', 'إدارة الواجبات', 'إدارة الواجبات والمشاريع', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (28, 'manage.exams', 'إدارة الامتحانات', 'إدارة الامتحانات', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (29, 'manage.question-bank', 'إدارة بنك الأسئلة', 'إدارة بنك الأسئلة', 'إدارة المحتوى', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (30, 'manage.blog', 'إدارة المدونة', 'إدارة مقالات المدونة', 'إدارة الصفحات الخارجية', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (31, 'manage.faq', 'إدارة الأسئلة الشائعة', 'إدارة الأسئلة الشائعة', 'إدارة الصفحات الخارجية', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (32, 'manage.contact-messages', 'إدارة رسائل التواصل', 'إدارة رسائل التواصل', 'إدارة الصفحات الخارجية', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (33, 'manage.media', 'إدارة معرض الصور', 'إدارة معرض الصور والفيديوهات', 'إدارة الصفحات الخارجية', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (34, 'manage.tasks', 'إدارة المهام', 'إدارة المهام', 'المهام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (35, 'view.tasks', 'عرض المهام', 'عرض المهام المخصصة', 'المهام', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (36, 'view.wallets', 'عرض المحافظ', 'عرض المحافظ الذكية', 'المحافظ الذكية', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (37, 'manage.messages', 'إدارة الرسائل', 'إدارة الرسائل والتقارير', 'الرسائل والتقارير', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (38, 'view.calendar', 'عرض التقويم', 'عرض التقويم', 'التقويم', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (39, 'instructor.view.courses', 'عرض كورساتي', 'عرض الكورسات الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (40, 'instructor.manage.lectures', 'إدارة محاضراتي', 'إدارة المحاضرات الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (41, 'instructor.manage.groups', 'إدارة مجموعاتي', 'إدارة المجموعات الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (42, 'instructor.manage.assignments', 'إدارة واجباتي', 'إدارة الواجبات الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (43, 'instructor.manage.exams', 'إدارة اختباراتي', 'إدارة الامتحانات الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (44, 'instructor.manage.attendance', 'إدارة الحضور', 'إدارة الحضور والانصراف', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (45, 'instructor.view.tasks', 'عرض مهامي', 'عرض المهام الخاصة بالمدرب', 'صلاحيات المدرب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (46, 'student.view.courses', 'تصفح الكورسات', 'تصفح الكورسات المتاحة', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (47, 'student.view.my-courses', 'عرض كورساتي', 'عرض الكورسات المسجل فيها', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (48, 'student.view.orders', 'عرض طلباتي', 'عرض طلبات التسجيل', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (49, 'student.view.invoices', 'عرض فواتيري', 'عرض الفواتير', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (50, 'student.view.wallet', 'عرض محفظتي', 'عرض المحفظة', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (51, 'student.view.certificates', 'عرض شهاداتي', 'عرض الشهادات', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (52, 'student.view.achievements', 'عرض إنجازاتي', 'عرض الإنجازات', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (53, 'student.view.exams', 'عرض الامتحانات', 'عرض الامتحانات المتاحة', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (54, 'student.view.notifications', 'عرض الإشعارات', 'عرض الإشعارات', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (55, 'student.view.profile', 'عرض البروفايل', 'عرض وتعديل البروفايل', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (56, 'student.view.settings', 'عرض الإعدادات', 'عرض الإعدادات', 'صلاحيات الطالب', '2025-11-06 17:11:38', '2025-11-06 17:11:38');
INSERT INTO `permissions` (`id`,`name`,`display_name`,`description`,`group`,`created_at`,`updated_at`) VALUES (57, 'manage.user-permissions', 'إدارة صلاحيات المستخدمين', 'إدارة صلاحيات المستخدمين مباشرة', 'إدارة النظام', '2025-11-06 17:54:57', '2025-11-06 17:54:57');

-- Data for table `role_permissions`
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (1, 1, 1, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (2, 1, 2, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (3, 1, 3, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (4, 1, 4, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (5, 1, 5, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (6, 1, 6, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (7, 1, 7, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (8, 1, 8, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (9, 1, 9, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (10, 1, 10, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (11, 1, 11, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (12, 1, 12, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (13, 1, 13, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (14, 1, 14, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (15, 1, 15, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (16, 1, 16, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (17, 1, 17, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (18, 1, 18, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (19, 1, 19, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (20, 1, 20, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (21, 1, 21, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (22, 1, 22, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (23, 1, 23, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (24, 1, 24, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (25, 1, 25, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (26, 1, 26, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (27, 1, 27, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (28, 1, 28, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (29, 1, 29, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (30, 1, 30, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (31, 1, 31, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (32, 1, 32, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (33, 1, 33, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (34, 1, 34, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (35, 1, 35, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (36, 1, 36, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (37, 1, 37, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (38, 1, 38, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (39, 1, 39, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (40, 1, 40, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (41, 1, 41, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (42, 1, 42, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (43, 1, 43, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (44, 1, 44, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (45, 1, 45, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (46, 1, 46, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (47, 1, 47, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (48, 1, 48, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (49, 1, 49, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (50, 1, 50, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (51, 1, 51, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (52, 1, 52, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (53, 1, 53, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (54, 1, 54, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (55, 1, 55, NULL, NULL);
INSERT INTO `role_permissions` (`id`,`role_id`,`permission_id`,`created_at`,`updated_at`) VALUES (56, 1, 56, NULL, NULL);

-- Data for table `roles`
INSERT INTO `roles` (`id`,`name`,`display_name`,`description`,`is_system`,`created_at`,`updated_at`) VALUES (1, 'Super Admin', 'مدير عام', 'مدير عام للنظام - يمتلك جميع الصلاحيات', 0, '2025-11-06 17:17:17', '2025-11-06 17:17:17');

-- Data for table `schools`
INSERT INTO `schools` (`id`,`name`,`description`,`logo`,`address`,`phone`,`email`,`is_active`,`created_at`,`updated_at`) VALUES (1, 'مدرسة النور الابتدائية', 'مدرسة ابتدائية متميزة تهتم بتطوير قدرات الطلاب', NULL, 'الرياض، المملكة العربية السعودية', '0112345678', 'info@alnoor.edu.sa', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');

-- Data for table `sessions`
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('6TUpg5VCOzYKdBP0OX6jRNM57nM5EY44F2PzikQo', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibzM5UkIzVlE4S09JZGlKS0JwSnJkSExYa05JeDBqNnRFMjBCeWVNNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1763083238);
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('vWOPL3GE1McDJ1oFcKE2VVo2uEBfICHaGRa4MmQK', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiVEFWVzJ0QVdIT3BVU1NManNqNGcyanVGbHhHTm4wTVZ6bjV0QUpVbSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjUyOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL25vdGlmaWNhdGlvbnMvdW5yZWFkLWNvdW50Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTt9', 1763083233);
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('FlLSTTfrBzcUPXGFS32g02Aa2XyMDApjyrcNLzBo', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSHl4UVA3OXdDSEJNSmtSVkRCQUxMZTB0NlNyYlIzQTlJY3Z0TE9hZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbm90aWZpY2F0aW9ucy91bnJlYWQtY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1763227849);

-- Data for table `subjects`
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (1, 'الرياضيات', 'تعلم الأرقام والعمليات الحسابية والهندسة', '#3B82F6', 'fas fa-calculator', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (2, 'العلوم', 'استكشاف الطبيعة والكيمياء والفيزياء', '#10B981', 'fas fa-flask', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (3, 'اللغة العربية', 'تطوير مهارات القراءة والكتابة والتعبير', '#8B5CF6', 'fas fa-book', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (4, 'اللغة الإنجليزية', 'تعلم اللغة الإنجليزية من الأساسيات إلى المستوى المتقدم', '#F59E0B', 'fas fa-globe', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (5, 'التاريخ', 'دراسة الأحداث التاريخية والحضارات', '#EF4444', 'fas fa-landmark', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');
INSERT INTO `subjects` (`id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`) VALUES (6, 'الجغرافيا', 'دراسة الأرض والبيئة والمناخ', '#06B6D4', 'fas fa-map', 1, '2025-11-05 18:01:24', '2025-11-05 18:01:24');

-- Data for table `user_permissions`
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (4, 5, 56, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (5, 5, 53, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (6, 5, 55, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (7, 5, 49, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (8, 5, 48, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (9, 5, 51, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (10, 5, 47, NULL, NULL);
INSERT INTO `user_permissions` (`id`,`user_id`,`permission_id`,`created_at`,`updated_at`) VALUES (11, 5, 50, NULL, NULL);

-- Data for table `users`
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`avatar`,`is_active`,`bio`,`parent_id`,`profile_image`,`birth_date`,`address`,`academic_year_id`,`last_login_at`,`remember_token`,`created_at`,`updated_at`) VALUES (1, 'مدير المنصة', 'admin@example.local', '0500000000', '$2y$12$ZbX7Xe8mT0gNtZ3PCYhAoOjVNOalyvaaMSw56mDqKPlBNK1PM4P46', 'super_admin', NULL, 1, NULL, NULL, 'profile-photos/7dd52445-58f4-4be9-aa36-4d76c99b09be.jpg', NULL, NULL, NULL, '2025-11-15 17:18:08', NULL, '2025-11-01 23:29:48', '2025-11-09 20:28:28');
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password`,`role`,`avatar`,`is_active`,`bio`,`parent_id`,`profile_image`,`birth_date`,`address`,`academic_year_id`,`last_login_at`,`remember_token`,`created_at`,`updated_at`) VALUES (5, 'طالب تجريبي', 'student@example.local', '01203679764', '$2y$12$IozkiILUSekgrtBA73HkNej3HAtOTLpGX61gfWuZRK8JURfhXs/0i', 'student', NULL, 1, NULL, NULL, 'profile-photos/29fd3b5f-ba40-4271-96dc-6a1d53be928a.png', NULL, NULL, NULL, '2025-11-14 01:09:49', NULL, '2025-11-09 18:16:45', '2025-11-12 12:33:20');

-- Data for table `wallets`
INSERT INTO `wallets` (`id`,`user_id`,`balance`,`pending_balance`,`currency`,`is_active`,`created_at`,`updated_at`) VALUES (1, 5, 0, 0, 'EGP', 1, '2025-11-14 01:10:47', '2025-11-14 01:10:47');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
