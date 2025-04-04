# Friends Gym Management System

A comprehensive gym management system with separate interfaces for Admin, Staff, and Members.

## Getting Started

Follow these instructions to set up the Friends Gym Management System on your local machine for development and testing purposes.

### Prerequisites

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge, etc.)

### Installation

1. **Clone or download the project** to your web server's document root folder.

2. **Create a MySQL database** named `gym_db`.

3. **Import the database schema**:
   - Open phpMyAdmin or any MySQL client
   - Select the `gym_db` database
   - Import the SQL file from `database/gym_mng.sql`

4. **Configure database connection**:
   - Open `dbcon.php` in the root directory
   - Update the database credentials if needed (default values are set for localhost)

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'gym_db');
   ```

5. **Set up an admin account**:
   - Add an admin user to the database by executing the following SQL query:

   ```sql
   INSERT INTO admin (username, password, name) 
   VALUES ('admin', 'admin123', 'Administrator');
   ```

6. **Access the system**:
   - Open your web browser and navigate to: `http://localhost/gym_mng/` (or the appropriate URL based on your setup)
   - Login with the admin credentials:
     - Username: admin
     - Password: admin123

## System Access

The system has three user roles:

1. **Admin Access**:
   - URL: `/admin/pages/dashboard.php`
   - Full control of the system
   - Manage staff, members, equipment, and announcements
   - View reports and statistics

2. **Staff Access**:
   - URL: `/staff/pages/dashboard.php`
   - Manage members
   - Track attendance
   - Manage equipment
   - Post announcements

3. **Member Access**:
   - URL: `/member/pages/dashboard.php`
   - View personal profile
   - Track attendance
   - Monitor progress
   - View payment history
   - Access shop
   - View announcements

## Features

- **Role-based Access Control**: Different interfaces for Admin, Staff, and Members
- **Member Management**: Add, edit, and delete members
- **Staff Management**: Add, edit, and delete staff
- **Equipment Management**: Track gym equipment inventory
- **Attendance Tracking**: Record and monitor member attendance
- **Progress Monitoring**: Track member fitness progress
- **Payment Management**: Record and track membership payments
- **Announcement System**: Post and view announcements
- **Shop System**: Purchase products and supplements
- **Responsive Design**: Works on all devices

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- jQuery
- Font Awesome
- DataTables
- Select2
- Flatpickr

## File Structure

```
gym_mng/
├── admin/             # Admin section
│   ├── include/       # Header and footer files
│   ├── css/           # Admin-specific CSS
│   ├── js/            # Admin-specific JavaScript
│   └── pages/         # Admin pages (dashboard, members, staff, etc.)
├── member/            # Member section
│   ├── include/       # Header and footer files
│   ├── css/           # Member-specific CSS
│   ├── js/            # Member-specific JavaScript
│   └── pages/         # Member pages (dashboard, profile, etc.)
├── staff/             # Staff section
│   ├── include/       # Header and footer files
│   ├── css/           # Staff-specific CSS
│   ├── js/            # Staff-specific JavaScript
│   └── pages/         # Staff pages (dashboard, members, etc.)
├── database/          # Database SQL file
├── dbcon.php          # Database connection
├── index.php          # Login page
├── logout.php         # Logout script
└── session.php        # Session management
```

## Security Notes

This is a development version and includes basic security measures. For production use, consider implementing:

1. Password hashing
2. Additional input validation
3. CSRF protection
4. SSL/TLS encryption
5. Regular security updates

## License

This project is licensed under the MIT License. 