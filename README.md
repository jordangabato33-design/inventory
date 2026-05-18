# InvenTrack — Computer Hardware Inventory System
**NEUST Case Study | BSIT 3rd Year | AY 2025-2026 | 2nd Semester**

Subjects: ITWS03 · ITWS04 · ITWS05
Faculty: Sir Rey John P. Aguilar, MSIT · Sir Camilo G. Villaviza Jr.

---

## COMPLETE SETUP GUIDE (Step-by-Step)

### STEP 1 — Edit your Windows HOSTS file
Run Notepad as Administrator, then open:
```
C:\Windows\System32\drivers\etc\hosts
```
Add this line at the bottom:
```
127.0.0.1   casestudy
```
Save the file. This allows PHP to connect using hostname "casestudy".

---

### STEP 2 — Place project in XAMPP
Copy the `inventory` folder to:
```
C:\xampp\htdocs\inventory
```

---

### STEP 3 — Start XAMPP
Open XAMPP Control Panel and start:
- ✅ Apache
- ✅ MySQL

---

### STEP 4 — Import the Database
1. Open browser → go to `http://casestudy/phpmyadmin`
2. Click **"New"** on the left sidebar
3. Database name: `inventory_db` → Collation: `utf8mb4_general_ci` → Click **Create**
4. Click `inventory_db` → Click **Import** tab
5. Click **Choose File** → select `database.sql` from this project
6. Click **Go**

You should see tables: `users`, `products`, `stock_in`, `stock_out`
And Triggers + Stored Procedures auto-created.

---

### STEP 5 — Install Pusher via Composer
Open Command Prompt in the project folder:
```
cd C:\xampp\htdocs\inventory
composer install
```
This downloads the Pusher PHP SDK into the `vendor/` folder.

---

### STEP 6 — Get Free Pusher Keys
1. Go to https://pusher.com and create a free account
2. Create a new app (any name, cluster: ap1 for Asia)
3. Go to App Keys tab
4. Copy: App ID, Key, Secret, Cluster

---

### STEP 7 — Configure .env
Copy `.env.example` to `.env` and fill in your values:
```
DB_HOST=casestudy
DB_NAME=inventory_db
DB_USER=root
DB_PASS=
DB_PORT=3306

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=ap1
```
⚠️ NEVER commit your real `.env` to GitHub. Only commit `.env.example`.

---

### STEP 8 — Access the System
Open your browser:
```
http://casestudy/inventory/
```

---

## DEFAULT LOGIN CREDENTIALS
| Username | Password | Role  |
|----------|----------|-------|
| admin    | password | Admin |
| staff    | password | Staff |

---

## FOLDER STRUCTURE
```
inventory/
├── assets/css/style.css          ← Full dark-theme CSS
├── config/
│   ├── env.php                   ← .env file loader
│   ├── database.php              ← MySQL connection
│   └── pusher.php                ← Pusher PHP SDK setup
├── includes/
│   ├── auth.php                  ← Sessions, CSRF, helpers
│   ├── header.php                ← Sidebar + Pusher JS init
│   └── footer.php                ← Closing tags
├── modules/
│   ├── auth/
│   │   ├── login.php             ← Login page (Bcrypt verify)
│   │   └── logout.php            ← Session destroy
│   ├── products/
│   │   ├── index.php             ← Product list with search
│   │   ├── create.php            ← Add product (Admin)
│   │   ├── edit.php              ← Edit product (Admin)
│   │   ├── view.php              ← Product detail + history
│   │   └── delete.php            ← Delete product (Admin)
│   ├── stock-in/
│   │   ├── index.php             ← Stock-In records
│   │   └── create.php            ← Record Stock-In + Pusher
│   ├── stock-out/
│   │   ├── index.php             ← Stock-Out records
│   │   └── create.php            ← Record Stock-Out + Pusher
│   ├── transactions/
│   │   └── index.php             ← Full history with filters
│   └── users/
│       ├── index.php             ← User list (Admin)
│       ├── create.php            ← Add user (Admin)
│       ├── edit.php              ← Edit user (Admin)
│       └── delete.php            ← Delete user (Admin)
├── vendor/                       ← Composer packages (Pusher SDK)
├── index.php                     ← Dashboard
├── database.sql                  ← Full SQL schema
├── composer.json                 ← Composer config
├── .env                          ← Your real keys (DO NOT COMMIT)
└── .env.example                  ← Template for GitHub
```

---

## SECURITY FEATURES IMPLEMENTED
| Feature | Implementation |
|---------|---------------|
| Password Hashing | `password_hash($pass, PASSWORD_BCRYPT)` |
| CSRF Protection | Token per form, verified on POST |
| SQL Injection | Prepared Statements (`bind_param`) |
| XSS Prevention | `htmlspecialchars()` on all outputs |
| Session Security | `session_regenerate_id()` on login |
| Role-Based Access | Admin / Staff middleware checks |
| Input Validation | Server-side validation on all forms |

## DATABASE FEATURES
| Feature | Description |
|---------|-------------|
| CRUD | Products, Stock-In, Stock-Out, Users |
| Trigger 1 | Auto-increases stock after stock_in insert |
| Trigger 2 | Auto-decreases stock after stock_out insert |
| Stored Proc 1 | `GetLowStockProducts()` |
| Stored Proc 2 | `GetProductHistory(id)` |
| Stored Proc 3 | `GetDashboardSummary()` |

## REAL-TIME FEATURES (Pusher WebSockets)
- Stock quantity updates instantly on all pages
- New transactions appear live in dashboard
- Toast notifications for every stock movement
- Zero page refresh needed

---

## PRESENTATION CHECKLIST
- [ ] XAMPP running (Apache + MySQL)
- [ ] `casestudy` in hosts file
- [ ] Database imported (4 tables, 2 triggers, 3 stored procedures)
- [ ] Composer installed (`vendor/` folder exists)
- [ ] `.env` configured with Pusher keys
- [ ] System opens at `http://casestudy/inventory/`
- [ ] Login works with admin/password
- [ ] Real-time updates working (Pusher connected)
- [ ] GitHub repo is public with `.env.example`
- [ ] Proper school uniform ✅
