# CloudeDrive - File Management System

A comprehensive file upload and management system with admin controls, folder sharing, and permission management.

## Features

### Core Features
- **Multi-file Upload**: Support for multiple file types with drag & drop interface
- **Large File Support**: Chunked upload for files larger than 50MB (up to 1GB)
- **Folder Upload**: Upload entire folder structures
- **File Management**: View, download, delete files with permission controls
- **Folder Management**: Create, organize, and manage folder hierarchies

### Admin Features
- **User Management**: Create, edit, delete users
- **Role Management**: Admin and User roles with different permissions
- **Folder Sharing**: Share folders between users with granular permissions
- **Permission Control**: Lock download and delete features per shared folder
- **Activity Logging**: Track all user activities
- **System Statistics**: View usage statistics and recent activities

### Security Features
- **Authentication**: Secure login/logout system
- **Authorization**: Role-based access control
- **File Validation**: File type and size restrictions
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: Input sanitization

## Installation

### Prerequisites
- **XAMPP** (Apache, MySQL, PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)

### Setup Instructions

1. **Install XAMPP**
   - Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
   - Start Apache and MySQL services

2. **Database Setup**
   ```bash
   # Access MySQL through phpMyAdmin or command line
   # Navigate to http://localhost/phpmyadmin
   # Create database and run the schema
   ```
   
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `cloude_drive`
   - Import the SQL schema from `database/schema.sql` or run it directly

3. **File Setup**
   - The project is already in `/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive`
   - Ensure the `uploads/` directory has write permissions:
   ```bash
   chmod 755 /Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads
   ```

4. **Configuration**
   - Update database credentials in `config/database.php` if needed:
   ```php
   private $host = 'localhost';
   private $db_name = 'cloude_drive';
   private $username = 'root';
   private $password = '';
   ```

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/cloudeDrive`
   - Default admin credentials:
     - Username: `admin`
     - Password: `password`

## Usage

### For Users

1. **Login/Register**
   - Use the login page to access the system
   - Register new accounts (admin can also create accounts)

2. **File Management**
   - **Upload Files**: Click "Upload Files" button or drag & drop files
   - **Create Folders**: Use "New Folder" button to organize files
   - **Download Files**: Click download button on any file
   - **Delete Files**: Click delete button (if you have permission)

3. **Navigation**
   - Click folder names to navigate into them
   - Use breadcrumb navigation to go back
   - View file details in the table

### For Administrators

1. **Access Admin Panel**
   - Click "Admin" in the navigation (only visible to admins)
   - View system statistics and recent activities

2. **User Management**
   - Create new users with specific roles
   - Change user roles (Admin/User)
   - Delete users (except yourself)

3. **Folder Sharing**
   - Share folders with specific users
   - Set granular permissions:
     - **Can View**: See folder contents
     - **Can Download**: Download files from folder
     - **Can Upload**: Add files to folder
     - **Can Delete**: Remove files from folder

4. **Permission Management**
   - Lock download/delete features per shared folder
   - Remove user access to folders
   - Update permissions for existing shares

## File Upload Specifications

### Supported File Types
- **Documents**: txt, pdf, doc, docx, xls, xlsx, ppt, pptx
- **Images**: jpg, jpeg, png, gif, bmp, svg, webp
- **Videos**: mp4, avi, mov, wmv, flv, mkv, webm
- **Audio**: mp3, wav, flac, aac, ogg, wma
- **Archives**: zip, rar, 7z, tar, gz, bz2
- **Code**: html, css, js, php, py, java, cpp, c, h
- **Data**: json, xml, csv, sql

### File Size Limits
- **Regular Upload**: Up to 50MB per file
- **Chunked Upload**: 50MB to 1GB per file (automatic)
- **Total System**: No limit (depends on disk space)

### Upload Methods
1. **Single File**: Select individual files
2. **Multiple Files**: Select multiple files at once
3. **Folder Upload**: Upload entire folder structures
4. **Drag & Drop**: Drag files/folders directly to upload area

## API Endpoints

### File Operations
- `POST /api/upload.php` - Upload files
- `GET /api/download.php?id={file_id}` - Download file
- `DELETE /api/delete_file.php?id={file_id}` - Delete file

### Folder Operations
- `POST /api/create_folder.php` - Create folder
- `DELETE /api/delete_folder.php?id={folder_id}` - Delete folder
- `POST /api/share_folder.php` - Share folder with users

### Admin Operations
- `POST /api/admin/add_user.php` - Add new user
- `POST /api/admin/update_user_role.php` - Update user role
- `DELETE /api/admin/delete_user.php` - Delete user

### Chunked Upload (Large Files)
- `POST /api/create_upload_session.php` - Start upload session
- `POST /api/upload_chunk.php` - Upload file chunk

## Directory Structure

```
cloudeDrive/
├── index.php              # Main dashboard
├── login.php              # Authentication page
├── admin.php              # Admin panel
├── config/
│   └── database.php       # Database configuration
├── classes/
│   ├── User.php           # User management
│   ├── FileManager.php    # File operations
│   └── FolderManager.php  # Folder operations
├── api/                   # API endpoints
│   ├── upload.php
│   ├── download.php
│   ├── delete_file.php
│   ├── create_folder.php
│   ├── share_folder.php
│   └── admin/
├── assets/
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       ├── app.js         # Main JavaScript
│       └── admin.js       # Admin JavaScript
├── uploads/               # File storage directory
└── database/
    └── schema.sql         # Database schema
```

## Security Considerations

1. **File Upload Security**
   - File type validation
   - File size limits
   - Virus scanning (recommended to add)

2. **Database Security**
   - Prepared statements for SQL injection prevention
   - Password hashing with bcrypt

3. **Access Control**
   - Session-based authentication
   - Role-based permissions
   - File access validation

4. **Recommended Enhancements**
   - Add HTTPS in production
   - Implement rate limiting
   - Add virus scanning for uploads
   - Regular backup of uploads directory

## Troubleshooting

### Common Issues

1. **Upload Fails**
   - Check file size limits in PHP configuration
   - Ensure uploads directory has write permissions
   - Verify file type is allowed

2. **Database Connection Error**
   - Verify MySQL is running
   - Check database credentials in config/database.php
   - Ensure database exists and schema is imported

3. **Permission Denied**
   - Check user role and permissions
   - Verify folder sharing settings
   - Contact admin if needed

### PHP Configuration
Add these settings to php.ini for large file uploads:
```ini
upload_max_filesize = 1G
post_max_size = 1G
max_execution_time = 300
memory_limit = 256M
```

## License

This project is open source and available under the MIT License.

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review error logs in browser console
3. Check Apache/PHP error logs
4. Contact system administrator
