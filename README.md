# Adolescent Risk Analytics System

A comprehensive web-based system for assessing and analyzing adolescent risk factors with advanced reporting and management capabilities.

## 🚀 Features

### Core Functionality
- **Risk Assessment**: Comprehensive questionnaire-based risk evaluation
- **Interactive Dashboard**: Real-time analytics with SVG map visualization
- **Advanced Reporting**: Detailed reports with filtering and export capabilities
- **Activity Management**: Track and manage assessment activities
- **User Management**: Multi-user system with role-based access

### Enhanced Features
- **Data Visualization**: Interactive charts using Chart.js
- **Export Capabilities**: Excel export with multiple report types
- **Security**: Enhanced authentication with session management
- **Audit Trail**: Complete logging of user actions
- **Responsive Design**: Mobile-friendly interface

## 📋 Requirements

- **Web Server**: Apache/Nginx with PHP 7.4+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **PHP Extensions**: PDO, JSON, OpenSSL
- **Composer**: For dependency management
- **PhpSpreadsheet**: For Excel export functionality

## 🛠️ Installation

### 1. Clone/Download the Project
```bash
git clone [repository-url]
cd Capstone
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Database Setup
1. Create a MySQL database named `capstone`
2. Import the base structure:
   ```sql
   mysql -u root -p capstone < includes/capstone.sql
   ```
3. Run the upgrade script for enhanced features:
   ```sql
   mysql -u root -p capstone < includes/database_upgrade.sql
   ```

### 4. Configuration
1. Update database connection in `includes/db.php`:
   ```php
   $host = 'localhost';
   $dbname = 'capstone';
   $username = 'your_username';
   $password = 'your_password';
   ```

### 5. Initial Admin Setup
1. Access `register.php` to create the first admin account
2. Use the admin token: `ADMIN-2025-[generated-token]`

## 📁 Project Structure

```
Capstone/
├── includes/
│   ├── auth.php              # Authentication functions
│   ├── db.php                # Database connection
│   ├── export_data.php       # Data export functionality
│   ├── login_process.php     # Login handling
│   ├── logout.php            # Logout handling
│   ├── proc_assessment.php   # Assessment processing
│   ├── register_admin.php    # Admin registration
│   ├── sidebar.php           # Navigation sidebar
│   └── database_upgrade.sql  # Database enhancements
├── css/
│   └── sidebar.css           # Sidebar styling
├── js/
│   └── sidebar-highlight.js  # Navigation highlighting
├── admin_dashboard.php       # Main dashboard
├── admin_login.php           # Login page
├── take_assessment.php       # Assessment form
├── reports.php               # Original reports
├── enhanced_reports.php      # Advanced reporting
├── activity_management.php   # Activity management
├── user_management.php       # User administration
├── settings.php              # System settings
├── register.php              # Admin registration
└── index.php                 # Landing page
```

## 🔐 Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Session Management**: Secure session handling with timeout
- **CSRF Protection**: Cross-site request forgery prevention
- **Input Sanitization**: XSS prevention and data validation
- **Audit Logging**: Complete activity tracking
- **Role-based Access**: Admin-only access to sensitive features

## 📊 Database Schema

### Core Tables
- `admin`: System administrators
- `assessment`: Risk assessment records
- `school`: Educational institutions
- `activity_info`: Assessment activities

### Enhanced Tables
- `admin_logs`: Audit trail
- `system_settings`: Configurable settings
- `backup_history`: Backup tracking
- `notifications`: System notifications
- `assessment_history`: Change tracking

## 🎯 Usage Guide

### For Administrators

1. **Dashboard**: View system overview and statistics
2. **Take Assessment**: Conduct risk assessments
3. **Enhanced Reports**: Generate detailed analytics
4. **Activity Management**: Create and manage activities
5. **User Management**: Add/remove system users
6. **Settings**: Configure system preferences

### Assessment Process

1. Navigate to "Take Assessment"
2. Fill out the comprehensive questionnaire
3. System automatically calculates risk score
4. Results are categorized as Low/Medium/High Risk
5. Data is stored for reporting and analysis

### Reporting Features

- **Filter Options**: By school, risk level, date range, activity
- **Export Formats**: Excel with multiple report types
- **Visualizations**: Charts and graphs for data analysis
- **Pagination**: Efficient handling of large datasets

## 🔧 Configuration

### System Settings
Access via Settings page to configure:
- Risk thresholds
- Session timeout
- User registration permissions
- Backup retention
- System display name

### Risk Calculation
The system uses a weighted scoring algorithm based on:
- Problem indicators
- Age factors
- Social circumstances
- Educational status
- Family situation

## 📈 Analytics & Reporting

### Dashboard Metrics
- Total assessments
- Risk distribution
- Recent activity
- Top problems identified

### Report Types
1. **Assessment Export**: Complete assessment data
2. **Summary Report**: Statistical overview
3. **Risk Analysis**: School-by-school breakdown

### Data Visualization
- Risk distribution pie charts
- Problem frequency analysis
- Trend analysis over time
- Geographic distribution (SVG map)

## 🛡️ Backup & Maintenance

### Automated Features
- Database optimization queries
- Index management
- Performance monitoring

### Manual Operations
- Database backup via Settings
- User account management
- System log review

## 🚨 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check credentials in `includes/db.php`
   - Verify MySQL service is running

2. **Export Not Working**
   - Ensure PhpSpreadsheet is installed via Composer
   - Check file permissions

3. **Login Issues**
   - Verify admin account exists
   - Check session configuration

4. **Chart Not Loading**
   - Ensure internet connection for Chart.js CDN
   - Check browser console for errors

## 📝 Development Notes

### Code Standards
- PSR-4 autoloading
- Prepared statements for database queries
- Input validation and sanitization
- Error handling and logging

### Future Enhancements
- API endpoints for mobile app
- Advanced analytics with ML
- Multi-language support
- Email notifications
- PDF report generation

## 📞 Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review system logs in admin panel
3. Contact system administrator

## 📄 License

This project is developed for educational and research purposes in adolescent risk assessment and analytics.

---

**Version**: 2.0  
**Last Updated**: 2025  
**Developed by**: Capstone Project Team
