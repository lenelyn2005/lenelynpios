# College Automated Scheduling System

A comprehensive web-based scheduling system for colleges that uses Genetic Algorithm (GA) to automatically generate conflict-free schedules for teachers, students, and rooms.

## 🚀 Features

### 🔐 Authentication & Login System
- **Admin Login**: Secure username/password authentication with database storage
- **Teacher Login**: Admin-created accounts with username/password
- **Student Login**: Simple Student ID authentication (no password required)

### 📊 System Dashboards

#### 1. Admin Dashboard
- **Department Management**: Add and manage departments
- **Course Management**: Create courses linked to departments
- **Year Level & Section Management**: Organize students by year and section
- **Subject Management**: Define subjects with workflow (Department → Course → Year Level → Subject)
- **Teacher Management**: 
  - Create teacher accounts with username/password
  - Assign departments, courses, year levels, and subjects
  - Set employment type (Full-time/Part-time)
  - Define workload constraints (monthly hours for full-time, daily hours for part-time)
- **Room Management**: Add and manage classrooms, labs, and lecture halls
- **Student Management**: Add students with section assignments
- **Teacher-Subject Assignment**: Link teachers to subjects they can teach
- **Schedule Generation**: One-click GA-powered schedule generation

#### 2. Teacher Dashboard
- **Personal Schedule View**: View auto-generated weekly schedule
- **Room Availability Check**: Check room availability for any time slot
- **Schedule Change Requests**: Request schedule modifications
- **Subject Information**: View assigned subjects and details
- **Workload Tracking**: Monitor teaching hours and constraints

#### 3. Student Dashboard
- **Personal Information**: View student details and academic info
- **Class Schedule**: View weekly class schedule with teachers and rooms
- **Subject Information**: Detailed view of all subjects with scheduling status
- **Daily Schedule View**: Visual daily schedule layout
- **Academic Statistics**: Track units, hours, and progress

### 🧬 Genetic Algorithm Scheduler
- **Advanced GA Implementation**: Population-based optimization with selection, crossover, and mutation
- **Conflict Resolution**: Automatically resolves teacher, room, and section conflicts
- **Workload Constraints**: Respects full-time/part-time teacher limits
- **Fair Distribution**: Spreads classes evenly across the week
- **Individual Teacher Regeneration**: Regenerate schedules for specific teachers without affecting others

### 🏢 Room Availability System
- **Real-time Availability**: Check room availability for any time slot
- **Conflict Detection**: Identifies and logs scheduling conflicts
- **Room Information**: Detailed room specifications and capacity
- **Weekly Overview**: Visual room availability across the week

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Scheduling Algorithm**: Genetic Algorithm (GA)
- **UI Framework**: Custom CSS with Font Awesome icons

## 📋 Prerequisites

- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation & Setup

### 1. Database Setup
1. Start your XAMPP/WAMP server
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the `database_schema.sql` file to create the database and tables
4. The system will automatically create sample data including:
   - Default admin account (username: `admin`, password: `password`)
   - Sample departments, courses, year levels, sections
   - Sample teachers, students, subjects, and rooms

### 2. File Setup
1. Copy all PHP files to your web server directory (e.g., `htdocs/automated/`)
2. Ensure the `config.php` file has correct database credentials:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'college_scheduling');
   ```

### 3. Access the System
1. Open your web browser
2. Navigate to `http://localhost/automated/`
3. Use the login options to access different dashboards

## 🔑 Default Login Credentials

### Admin
- **Username**: `admin`
- **Password**: `password`

### Sample Teachers
- **Username**: `teacher1` | **Password**: `password`
- **Username**: `teacher2` | **Password**: `password`
- **Username**: `teacher3` | **Password**: `password`
- **Username**: `teacher4` | **Password**: `password`

### Sample Students
- **Student ID**: `2024-0001`
- **Student ID**: `2024-0002`
- **Student ID**: `2024-0003`
- **Student ID**: `2024-0004`
- **Student ID**: `2024-0005`

## 📁 File Structure

```
automated/
├── index.php                 # Main landing page
├── config.php               # Database configuration
├── database_schema.sql      # Database schema and sample data
├── styles.css              # Modern CSS styling
├── ga_scheduler.php        # Genetic Algorithm implementation
├── login_admin.php         # Admin login page
├── login_teacher.php       # Teacher login page
├── login_student.php       # Student login page
├── log_out.php            # Logout functionality
├── admin_dashboard.php    # Admin management dashboard
├── teacher_dashboard.php  # Teacher dashboard
├── student_dashboard.php  # Student dashboard
└── README.md             # This file
```

## 🎯 How to Use

### For Administrators
1. **Login** with admin credentials
2. **Add Data**: Create departments, courses, sections, subjects, teachers, rooms, and students
3. **Assign Teachers**: Link teachers to subjects they can teach
4. **Generate Schedule**: Click "Generate Schedule with GA" to create optimal schedules
5. **Monitor**: View generated schedules and resolve any conflicts

### For Teachers
1. **Login** with teacher credentials
2. **View Schedule**: Check your weekly teaching schedule
3. **Check Rooms**: Verify room availability for any time slot
4. **Request Changes**: Request schedule modifications if needed

### For Students
1. **Login** with Student ID
2. **View Schedule**: Check your weekly class schedule
3. **Subject Info**: View detailed information about your subjects
4. **Academic Progress**: Monitor your academic statistics

## 🧬 Genetic Algorithm Details

The GA scheduler implements:
- **Population Size**: 200 chromosomes
- **Generations**: Up to 1000 iterations
- **Selection**: Tournament selection
- **Crossover**: Single-point crossover (80% rate)
- **Mutation**: Random mutation (5% rate)
- **Elitism**: Preserves top 20 solutions
- **Fitness Function**: Minimizes conflicts and optimizes distribution

### Scheduling Rules
- Teachers cannot teach two classes simultaneously
- Rooms cannot be double-booked
- Sections cannot have overlapping classes
- Teacher workload respects employment type constraints
- Classes are distributed fairly across the week

## 🔧 Customization

### Adding New Time Slots
Edit the `$timeSlots` array in `ga_scheduler.php`:
```php
private $timeSlots = [
    ['08:00:00', '09:00:00'],
    ['09:00:00', '10:00:00'],
    // Add more slots as needed
];
```

### Modifying GA Parameters
Adjust parameters in the `GAScheduler` class:
```php
private $populationSize = 200;    // Increase for better solutions
private $generations = 1000;      // More generations for complex schedules
private $mutationRate = 0.05;     // Higher rate for more exploration
```

### Adding New Days
Modify the `$days` array:
```php
private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
```

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**: Check database credentials in `config.php`
2. **Login Issues**: Verify user accounts exist in the database
3. **Schedule Generation Fails**: Ensure sufficient data (teachers, subjects, rooms)
4. **Permission Errors**: Check file permissions on web server

### Debug Mode
Enable error reporting by adding to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Performance Optimization

- **Database Indexing**: Add indexes on frequently queried columns
- **Caching**: Implement Redis/Memcached for session management
- **Load Balancing**: Use multiple web servers for high traffic
- **Database Optimization**: Regular maintenance and query optimization

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Prevention**: Prepared statements throughout
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation for all inputs
- **XSS Protection**: HTML escaping for user inputs

## 🚀 Future Enhancements

- **Mobile App**: React Native or Flutter mobile application
- **Real-time Notifications**: WebSocket-based notifications
- **Advanced Analytics**: Schedule optimization metrics
- **Integration**: LMS and student information system integration
- **Multi-language Support**: Internationalization
- **API Development**: RESTful API for third-party integrations

## 📞 Support

For technical support or feature requests, please contact the development team.

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**College Automated Scheduling System** - Intelligent scheduling powered by Genetic Algorithm

