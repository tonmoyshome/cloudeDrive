-- Sample data for CloudeDrive

-- Insert Users (1 admin + 4 regular users)
INSERT INTO users (username, email, password, role, storage_limit, created_at) VALUES
('admin', 'admin@clouddrive.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'admin', 10737418240, NOW()), -- password: admin123
('john_doe', 'john@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, NOW()), -- password: admin123
('sarah_wilson', 'sarah@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, NOW()), -- password: admin123
('mike_chen', 'mike@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, NOW()), -- password: admin123
('emma_brown', 'emma@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, NOW()); -- password: admin123

-- Insert Folders with different ownership and purposes
INSERT INTO folders (name, parent_id, owner_id, created_at) VALUES
-- User home folders
('admin', NULL, 1, NOW()),
('john_doe', NULL, 2, NOW()),
('sarah_wilson', NULL, 3, NOW()),
('mike_chen', NULL, 4, NOW()),
('emma_brown', NULL, 5, NOW()),

-- Shared project folders
('Company Projects', NULL, 1, NOW()),
('Marketing Materials', 6, 1, NOW()),
('Development', 6, 1, NOW()),
('HR Documents', 6, 1, NOW()),
('Public Resources', NULL, 1, NOW()),

-- User personal folders
('Documents', 2, 2, NOW()),
('Photos', 2, 2, NOW()),
('Work Files', 3, 3, NOW()),
('Personal', 4, 4, NOW()),
('Archive', 5, 5, NOW());

-- Insert sample files in different folders
INSERT INTO files (original_name, file_name, file_path, file_size, file_type, mime_type, folder_id, uploaded_by, upload_date) VALUES
-- Admin files
('company_policy.pdf', 'file_admin_001.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/admin/file_admin_001.pdf', 256000, 'pdf', 'application/pdf', 1, 1, NOW()),
('employee_handbook.docx', 'file_admin_002.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/admin/file_admin_002.docx', 512000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 1, NOW()),

-- Marketing Materials
('logo_v1.png', 'file_marketing_001.png', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_001.png', 128000, 'png', 'image/png', 7, 1, NOW()),
('brochure_draft.pdf', 'file_marketing_002.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_002.pdf', 1024000, 'pdf', 'application/pdf', 7, 3, NOW()),
('social_media_assets.zip', 'file_marketing_003.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_003.zip', 2048000, 'zip', 'application/zip', 7, 3, NOW()),

-- Development files
('source_code_v2.zip', 'file_dev_001.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_001.zip', 5120000, 'zip', 'application/zip', 8, 4, NOW()),
('database_schema.sql', 'file_dev_002.sql', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_002.sql', 64000, 'sql', 'text/sql', 8, 4, NOW()),
('api_documentation.md', 'file_dev_003.md', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_003.md', 32000, 'md', 'text/markdown', 8, 2, NOW()),

-- HR Documents
('job_descriptions.docx', 'file_hr_001.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/HR Documents/file_hr_001.docx', 256000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 9, 1, NOW()),
('salary_structure.xlsx', 'file_hr_002.xlsx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/HR Documents/file_hr_002.xlsx', 128000, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 9, 1, NOW()),

-- Public Resources
('training_video.mp4', 'file_public_001.mp4', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Public Resources/file_public_001.mp4', 10485760, 'mp4', 'video/mp4', 10, 1, NOW()),
('company_presentation.pptx', 'file_public_002.pptx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Public Resources/file_public_002.pptx', 2048000, 'pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 10, 2, NOW()),

-- John's personal files
('resume.pdf', 'file_john_001.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Documents/file_john_001.pdf', 200000, 'pdf', 'application/pdf', 11, 2, NOW()),
('project_notes.txt', 'file_john_002.txt', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Documents/file_john_002.txt', 8000, 'txt', 'text/plain', 11, 2, NOW()),
('vacation_photo.jpg', 'file_john_003.jpg', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Photos/file_john_003.jpg', 1024000, 'jpg', 'image/jpeg', 12, 2, NOW()),

-- Sarah's work files
('client_proposal.docx', 'file_sarah_001.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/sarah_wilson/Work Files/file_sarah_001.docx', 512000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 13, 3, NOW()),
('budget_analysis.xlsx', 'file_sarah_002.xlsx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/sarah_wilson/Work Files/file_sarah_002.xlsx', 256000, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 13, 3, NOW()),

-- Mike's personal files
('code_snippets.txt', 'file_mike_001.txt', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/mike_chen/Personal/file_mike_001.txt', 16000, 'txt', 'text/plain', 14, 4, NOW()),
('family_photo.png', 'file_mike_002.png', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/mike_chen/Personal/file_mike_002.png', 2048000, 'png', 'image/png', 14, 4, NOW()),

-- Emma's archive
('old_documents.zip', 'file_emma_001.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/emma_brown/Archive/file_emma_001.zip', 1024000, 'zip', 'application/zip', 15, 5, NOW()),
('backup_data.tar.gz', 'file_emma_002.tar.gz', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/emma_brown/Archive/file_emma_002.tar.gz', 5120000, 'tar.gz', 'application/gzip', 15, 5, NOW());

-- Insert folder permissions for shared access
INSERT INTO folder_permissions (folder_id, user_id, can_view, can_download, can_upload, can_delete, granted_by, granted_at) VALUES
-- Marketing Materials permissions
(7, 2, 1, 1, 1, 0, 1, NOW()), -- john_doe: view, download, upload
(7, 3, 1, 1, 1, 1, 1, NOW()), -- sarah_wilson: full access
(7, 4, 1, 1, 0, 0, 1, NOW()), -- mike_chen: view, download only
(7, 5, 1, 1, 0, 0, 1, NOW()), -- emma_brown: view, download only

-- Development folder permissions
(8, 2, 1, 1, 1, 1, 1, NOW()), -- john_doe: full access
(8, 4, 1, 1, 1, 1, 1, NOW()), -- mike_chen: full access (lead developer)
(8, 3, 1, 1, 0, 0, 1, NOW()), -- sarah_wilson: view, download only
(8, 5, 0, 0, 0, 0, 1, NOW()), -- emma_brown: no access

-- HR Documents permissions (sensitive)
(9, 3, 1, 1, 0, 0, 1, NOW()), -- sarah_wilson: view, download only
(9, 2, 0, 0, 0, 0, 1, NOW()), -- john_doe: no access
(9, 4, 0, 0, 0, 0, 1, NOW()), -- mike_chen: no access
(9, 5, 0, 0, 0, 0, 1, NOW()), -- emma_brown: no access

-- Public Resources permissions (everyone can read)
(10, 2, 1, 1, 0, 0, 1, NOW()), -- john_doe: view, download only
(10, 3, 1, 1, 0, 0, 1, NOW()), -- sarah_wilson: view, download only
(10, 4, 1, 1, 0, 0, 1, NOW()), -- mike_chen: view, download only
(10, 5, 1, 1, 0, 0, 1, NOW()); -- emma_brown: view, download only

-- Insert some activity logs
INSERT INTO activity_logs (user_id, action, resource_type, resource_id, details, created_at) VALUES
(1, 'create', 'folder', 6, '{"folder_name":"Company Projects"}', NOW() - INTERVAL 7 DAY),
(1, 'create', 'folder', 7, '{"folder_name":"Marketing Materials"}', NOW() - INTERVAL 6 DAY),
(3, 'upload', 'file', 4, '{"filename":"brochure_draft.pdf"}', NOW() - INTERVAL 5 DAY),
(4, 'upload', 'file', 6, '{"filename":"source_code_v2.zip"}', NOW() - INTERVAL 4 DAY),
(2, 'download', 'file', 1, '{"filename":"company_policy.pdf"}', NOW() - INTERVAL 3 DAY),
(3, 'share', 'folder', 7, '{"shared_with":"john_doe","permissions":"read,write"}', NOW() - INTERVAL 2 DAY),
(5, 'upload', 'file', 18, '{"filename":"old_documents.zip"}', NOW() - INTERVAL 1 DAY),
(4, 'delete', 'file', 0, '{"filename":"temp_file.txt"}', NOW());
