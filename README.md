# 🎓 SEGi CPRS - Course Performance Report System

A database-driven course performance reporting system developed as part of my **Diploma Final Year Project**.

## 🌐 Running the System

This project runs locally using XAMPP. After setup, open [SEGi CPRS](http://localhost/segi_cprs/).

## 📖 Overview

SEGi CPRS helps administrators manage courses and student enrollment, while lecturers record and review performance for their assigned courses.

The system combines Course Learning Outcome (CLO) assessment, automatic result calculations, visual summaries, and downloadable reports in one interface.

## ✨ Features

- Separate administrator and lecturer access
- Course creation, editing, and lecturer assignment
- Student enrollment and disenrollment
- Three CLOs with configurable descriptions and assessment weights
- Attendance, marks, remarks, and CLO comment entry
- Automatic weighted marks, grades, and Pass/Resit/Retake results
- Dashboard summaries, grade distribution, and CLO attainment charts
- Live search, filtering, and sorting
- Printable reports and CSV export
- Responsive layouts with scrollable results tables
- Session inactivity warnings and automatic logout

## 🛠️ Built With

- HTML
- CSS
- JavaScript
- PHP
- MySQL / MariaDB
- XAMPP (Apache and database server)

## ⚙️ Local Setup

1. Install XAMPP with PHP 8.0 or later, including the `mysqli`, `mysqlnd`, and `mbstring` extensions.
2. Place the project in `C:\xampp\htdocs\segi_cprs`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open [phpMyAdmin](http://localhost/phpmyadmin/) and select the **Import** tab on the home page.
5. Click **Choose File**, select [`sql/segi_cprs.sql`](sql/segi_cprs.sql) from the project folder, keep the format as **SQL**, and click **Import** (or **Go**, depending on the version).
6. Wait for the success message. The file creates the `segi_cprs` database, its tables, and the sample data automatically.
7. Open [http://localhost/segi_cprs/](http://localhost/segi_cprs/) and sign in using a starter account below.

Import the SQL file for a fresh installation.

The database connection uses the default local XAMPP settings: host `localhost`, user `root`, an empty password, and database `segi_cprs`. 

### Starter Accounts

| Role          | Username  | Password     |
| ------------- | --------- | ------------ |
| Administrator | `admin`   | `admin123`   |
| Lecturer      | `amizar`  | `amizar123`  |
| Lecturer      | `aqilah`  | `aqilah123`  |
| Lecturer      | `rosmah`  | `rosmah123`  |
| Lecturer      | `steven`  | `steven123`  |
| Lecturer      | `shahidh` | `shahidh123` |

The starter database includes **20 students, 8 courses, and 49 enrollments with marks**. 

Programme and study-mode options are also included. Additional students can be added through phpMyAdmin; there is no student-registration page. Course titles, assessments, and results are sample data.

## 📌 Notes

- This system was developed as part of my Diploma Final Year Project for academic and learning purposes.
- Records are saved in a MySQL/MariaDB database, rather than browser storage.
- The included accounts are for local demonstrations. Passwords are currently stored in plain text; password hashing and a security review are needed before public deployment.
- Lecturers can access only their assigned courses.
- Dashboard reports, printouts, and CSV exports use saved marks. Live previews must be saved to persist changes.
- Printing can produce a PDF through the browser's **Save as PDF** option.
