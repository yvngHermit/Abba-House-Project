ABBA HOUSE PHP + XAMPP WEBSITE

Default admin login:
Email: admin@email.com
Password: admin123

How to run in XAMPP:
1. Copy the full abba_house_php folder into C:\xampp\htdocs\
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin in your browser.
4. Create/import the database by importing: database/abba_house.sql
5. Open: http://localhost/abba_house_php/

Folder structure:
abba_house_php/
├── index.php
├── README.txt
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── database/
│   └── abba_house.sql
├── includes/
│   ├── auth.php
│   ├── db.php
│   ├── footer.php
│   └── header.php
├── pages/
│   ├── home.php
│   ├── contact.php
│   ├── signin.php
│   ├── dashboard.php
│   ├── register_orphan.php
│   └── logout.php
└── uploads/
    ├── orphans/
    └── certificates/

Pages included:
- Public homepage
- Public contact page with inquiry form
- Admin sign in page
- Admin dashboard with statistics and attendance marking
- Register orphan page with photo and birth certificate upload
- Sidebar menu and logout confirmation modal

Database tables:
- admins
- orphans
- attendance
- contacts

Latest updates:
- Added responsive CSS so the site uses wider desktop layouts and still keeps the mobile design on smaller screens.
- Added a View on Maps button on the Contact page location card.
- Added admin visitor message pages:
  pages/messages.php shows submitted inquiries as summary cards.
  pages/message_view.php shows the full selected message.
- The dashboard now includes a Visitor Messages statistic card and sidebar link.
