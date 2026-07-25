========================================================================
                      WORKSHOP & BILLING MANAGEMENT SYSTEM
                             (AutoMaster Pro 2026)
========================================================================

Author Details:
---------------
Name            : Kesavan M
Register No     : 95272462208
Course          : Master of Computer Applications (MCA)
College         : Sardar Raja College of Engineering, Alangulam
Supervisor      : Mrs.M.Shilpa Reena., MCA. (Assistant Professor)
HOD             : Mrs. A.Jesintha., MCA. (Assistant Professor & Head)
Date            : July 2026

------------------------------------------------------------------------
1. PROJECT DESCRIPTION
------------------------------------------------------------------------
The Workshop & Billing Management System is a web-based management software 
package designed to automate the operations of modern automotive garages 
and service centers. It replaces manual registers with a centralized 
relational database to track customer files, vehicle records, repair 
service job cards, mechanic assignments, spare parts inventory levels, 
and sales checkouts. 

Key Modules:
* Live KPI Dashboard: Real-time summaries and analytics widgets (Chart.js 
  weekly revenue line chart and service breakdown conic donut chart).
* Customer CRM: Create, Read, Update, and Delete client directory records.
* Vehicle Register: Track plate numbers, chassis VINs, odometer readings, 
  and fuel types linked to their registered owners.
* Job Cards: Log customer complaints, diagnostics notes, assigned mechanics, 
  labor charges, and track progress states.
* Parts Inventory: Maintain stock limits and SKU items with automatic 
  color-coded warning tags for low stock limits.
* POS Invoicing: Compile parts and service labor, apply customizable GST tax 
  percentages and discount rates, and generate print-ready receipt layouts.
* Staff Security: Role-based locks restricting settings and user creations.

------------------------------------------------------------------------
2. SYSTEM REQUIREMENTS & TECHNICAL STACK
------------------------------------------------------------------------
Software Stack:
* Frontend      : HTML5, Cascading Style Sheets (CSS3 with Glassmorphic design),
                  JavaScript (ES6+, Chart.js charts)
* Backend       : PHP 7.4 or above
* Database      : MySQL 5.7 or above (MariaDB)
* Server Environment: XAMPP / WAMP local package

Hardware Requirements:
* Processor     : Intel Core i3 or higher / AMD equivalent
* Memory (RAM)  : Minimum 4 GB (8 GB recommended)
* Hard Disk     : Minimum 500 MB free space
* Web Browser   : Google Chrome, Safari, or Microsoft Edge

------------------------------------------------------------------------
3. SETUP AND INSTALLATION INSTRUCTIONS
------------------------------------------------------------------------
Follow these steps to run the project locally on Windows using XAMPP:

Step 1: Install XAMPP
* Download and install XAMPP (supporting PHP 7.4+) from Apache Friends.

Step 2: Start Services
* Open the XAMPP Control Panel and start both "Apache" and "MySQL" services.

Step 3: Move Project Files
* Copy or move the entire project folder "AutoMasterPro2026" to your XAMPP 
  htdocs directory:
  Path: C:\xampp\htdocs\AutoMasterPro2026

Step 4: Database Setup (phpMyAdmin)
* Open your browser and navigate to: http://localhost/phpmyadmin/
* Create a new database named: "automasterpro" (utf8mb4_general_ci).
* Click on the database name on the left sidebar to select it.
* Go to the "Import" tab at the top.
* Click "Choose File" and select the database script "automasterpro.sql" 
  from your project folder (C:\xampp\htdocs\AutoMasterPro2026\automasterpro.sql).
* Scroll down and click the "Go" button to import all tables and seed records.

Step 5: Access the Web Portal
* Open your web browser and open: http://localhost/AutoMasterPro2026/
* The application will load and automatically redirect you to the login panel.

------------------------------------------------------------------------
4. DEFAULT LOGIN CREDENTIALS
------------------------------------------------------------------------
Use the following credentials to access the Administrator dashboard:

* Username      : kesavan
* Password      : admin07
* Access Role   : Administrator
* Status        : Active

------------------------------------------------------------------------
5. PROJECT DIRECTORY STRUCTURE
------------------------------------------------------------------------
* /auth             - User login processing, verification, and logout
* /config           - Database server host setup (`db.php`)
* /assets           - Main styling assets:
    - /css          - CSS styling sheets (sidebar, topbar, cards layouts)
    - /js           - JS frontend engines (dashboard charts, view details triggers)
    - /images       - Sidebar BMW illustrations and profile avatars
* /includes         - Reusable sidebar layouts and user session headers
* /uploads          - Storage folder for profile images and part thumbnails
* root directory    - Main application module scripts:
    - customer.php      (Customer CRM database)
    - vehicles.php      (Vehicle specifications record)
    - services.php      (Active Job Cards tracker)
    - inventory.php     (Parts stock levels list)
    - billing.php       (Point of Sale invoice generator)
    - print_invoice.php (Receipt thermal template layout)
    - reports.php       (Business sales analytics dashboard)
    - users.php         (Administrator credentials manager)
    - profile.php       (Staff profile card editor)
    - settings.php      (Garage profile header options)
    - dashboard.php      (Live KPI dashboard)

------------------------------------------------------------------------
6. DOCUMENTATION & REPORT DELIVERABLES
------------------------------------------------------------------------
The following academic project reports are generated inside the folder:
1. `Workshop_Billing_Management_System_Report.docx`: Detailed Word report 
   complying with Anna University project submission guidelines (Times New 
   Roman, formatted pages count, customized chapters, and testing tables).
2. `Workshop_Billing_Management_System_Report.pdf`: Portable document 
   representation including embedded diagrams and screenshots.
3. `generate_report.py`: Programmatic Python compiler script that builds 
   the DOCX and exports it to PDF using Word COM interfaces.
