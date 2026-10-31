# S-PEACE
### Web-Based Student Complaint and Concern Management System
**Polytechnic College of Botolan (PCB)**

---

## 📌 Project Overview
**S-PEACE** is a web-based facility reporting and concern management system designed to connect students and campus administration at the **Polytechnic College of Botolan**. 

Many students encounter facility and maintenance issues (damaged furniture, broken armchairs, leaking restrooms, lack of water, electrical hazards) but are hesitant, shy, or afraid to complain in person. S-PEACE solves this by offering a transparent, mobile-optimized online reporting platform where students can submit concerns with descriptions and photos, opt for **100% anonymous reporting**, and track resolution in real-time. Campus facilities staff and administrators utilize a centralized dashboard to review, categorize, assign maintenance crew, and verify resolutions.

---

## 🎯 Objectives & Scope

### General Objective
To design a web-based complaint system that simplifies campus facility issues reporting at the Polytechnic College of Botolan.

### Specific Objectives
1. **Student Reporting Portal**: Mobile-first design allowing students to submit categorized maintenance issues with photo uploads (up to 5MB) and location specifications.
2. **Real-Time Status Transparency**: Interactive tracking timeline showing stages: `Submitted` &rarr; `In progress` &rarr; `Resolved`.
3. **Admin Central Dashboard**: Categorized review system for campus facilities staff to manage, sort, assign maintenance personnel, and record proof of resolution.

### Scope & Limitations
- **Scope**: Physical campus facilities, comfort rooms, classrooms, equipment, cleanliness, and safety hazards.
- **Strict Limitations**: Strictly exclusive to campus physical infrastructure and maintenance. Does **not** handle academic grievances, grading disputes, or personal misconduct reports (these are flagged and redirected to Department Deans). Requires internet access for report submission.

---

## 🖼️ UI Wireframe Alignment

| Screen | Wireframe Reference | Implementation Feature |
| :--- | :--- | :--- |
| **Welcome & Onboarding** | **Frame 1** | Campus building backdrop, "Welcome to S-PEACE", `Report an issue`, `Track a report`, "How it works: From report to resolution in three steps" |
| **Authentication** | **Frame 2 (Top)** | "Create your account" & "Welcome back" (Sign in / Sign up) with student email validation |
| **Issue Submission** | **Frame 2 (Middle)** | Category selector (Comfort rooms, Facilities, Classrooms, Equipment, Cleanliness, Safety, Other), Problem, Location, Description (0/500), Photo upload card (up to 5MB), and Anonymous checkbox |
| **Status Tracker** | **Frame 2 (Right)** | Olive card with category tag, status badge, title, location pin, description, upvote button, and 3-step vertical stepper (`Submitted`, `In progress`, `Resolved`) |
| **Admin Central Portal** | **Central Dashboard** | KPI metrics, category filter ribbon, status tabs, search bar, staff assignment dropdown, resolution proof photo uploader, and CSV report exporter |

---

## 🗄️ Database Architecture (MySQL)

- **Database Name**: `speace_db`
- **Tables**:
  - `users`: Administrator and student accounts
  - `categories`: 7 categories (`Comfort rooms`, `Facilities`, `Classrooms`, `Equipment`, `Cleanliness`, `Safety`, `Other`)
  - `staff`: Campus maintenance personnel (Plumbers, Electricians, Carpenters, Janitorial leads)
  - `complaints`: Facility reports with tracking codes (`SP-YYYY-XXXX`), photos, status, and resolution details
  - `complaint_logs`: Full audit trail of status changes and personnel assignments
  - `complaint_upvotes`: Student corroboration records to prevent double-voting

---

## 🚀 How to Run the System

### Option 1: Quick Launch (Built-in Web Server)
1. Double-click `run_server.bat` in the project root directory.
2. Open your browser and navigate to:
   - **Student Web Portal**: [http://localhost:8000](http://localhost:8000)
   - **Student Log In / Validation**: [http://localhost:8000/login.php](http://localhost:8000/login.php)
   - **Admin Central Dashboard**: [http://localhost:8000/admin/index.php](http://localhost:8000/admin/index.php)

### Option 2: Running via XAMPP
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache** and **MySQL**.
3. Copy or move this `S-PEACE` folder to `C:\xampp\htdocs\S-PEACE`.
4. Open your browser and visit:
   - [http://localhost/S-PEACE/](http://localhost/S-PEACE/)
   - [http://localhost/S-PEACE/admin/index.php](http://localhost/S-PEACE/admin/index.php)
5. To import the database via phpMyAdmin:
   - Go to [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
   - Import `database/speace_db.sql` or visit [http://localhost/S-PEACE/install.php](http://localhost/S-PEACE/install.php) for 1-click automatic setup.

---

## 🔑 Default Credentials

### Administrator Account
- **Email**: `admin@pcb.edu.ph`
- **Password**: `admin123`
- **Role**: Facilities and General Services Administrator

### Sample Student Account
- **Email**: `student@pcb.edu.ph`
- **Password**: `student123`
- *(Students may also submit reports without logging in by ticking "Submit anonymously")*

---

## 📂 Project Directory Structure

```
S-PEACE/
├── assets/
│   ├── css/
│   │   └── style.css                 # Custom styling matching Frame 1 & 2 palettes
│   ├── js/
│   │   ├── app.js                    # Student portal client logic & tracking
│   │   └── admin.js                  # Admin dashboard & workflow management
│   └── images/
│       ├── pcb-campus.svg            # Campus facade illustration (Frame 1 & 2)
│       ├── pcb-logo.svg              # Polytechnic College of Botolan seal
│       └── samples/                  # Sample facility photos (leaks, chairs, fan)
├── uploads/                          # Student & Admin uploaded issue photos
├── config/
│   ├── config.php                    # Application settings & credentials
│   └── db.php                        # MySQL connector with auto-setup & fallback
├── database/
│   └── speace_db.sql                 # MySQL schema, tables, relations & seed data
├── api/
│   ├── auth.php                      # Authentication API
│   ├── reports.php                   # Report submission & tracking API
│   ├── admin_reports.php             # Admin status transition & staff assign API
│   └── analytics.php                 # Analytics, KPI metrics & CSV export
├── admin/
│   ├── index.php                     # Admin Central Dashboard
│   └── login.php                     # Admin login page
├── index.php                         # Student Web Portal (Mobile & Desktop View)
├── login.php                         # Student Log In & Identity Validation (Frame 2)
├── install.php                       # Database setup & connection diagnostics
├── run_server.bat                    # 1-Click launcher
└── README.md                         # Documentation
```
