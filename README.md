# 🖨️ Redis CSV Upload Progress

A mini Laravel application that allows users to upload CSV files, processes them in the background, and displays real-time upload status. Built to demonstrate proficiency in Laravel, queues, and real-time updates.  

---

## ✨ Features

- 📁 Upload CSV files with product data  
- ⚡ Background processing using Laravel queues (Redis + Horizon recommended)  
- 🔄 Real-time updates of upload status and progress  
- ✅ Prevents duplicate entries (idempotent)  
- 📝 Supports upserting records based on a unique key  
- 🎨 Clean, responsive UI with TailwindCSS  

---

## 🛠️ Tech Stack

| Component | Tool |
|-----------|------|
| 🖥️ Backend Framework | Laravel 11 |
| 🗄️ Database | SQLite (default, can use MySQL/Postgres) |
| ⚙️ Queue | Redis + Laravel Horizon |
| 🎨 Frontend | Blade, TailwindCSS |
| 💻 Language | PHP 8.2+ |
| 🏗️ Architecture | REST API + background job workers |

---

## 🚀 Installation

```bash
# 1️⃣ Clone the repo
git clone https://github.com/qistuna456/redis-upload.git
cd redis-upload

# 2️⃣ Install dependencies
composer install
npm install
npm run build

# 3️⃣ Copy environment config
cp .env.example .env

# 4️⃣ Set up your .env variables
APP_NAME="Redis CSV Upload"
APP_URL=http://redis.test

# Database (example SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=/full/path/to/database/database.sqlite

# 5️⃣ Generate app key
php artisan key:generate

# 6️⃣ Run migrations
php artisan migrate

# 7️⃣ Start queue worker
php artisan queue:work redis --tries=1

# 8️⃣ Serve the app
php artisan serve
```

## 🖱️ Usage

- 🏠 Go to the homepage.
- 📤 Click **Upload CSV** and select your CSV file.
- 👀 Watch the **Recent Uploads** table update in real time.
- Each row shows:
  - 🆔 Upload ID
  - 📄 File name
  - 🔖 Status (`processing`, `completed`, `failed`)
  - 📊 Progress (# processed / total)
  - 🕒 Created & completed timestamps

---

## 📄 CSV Format

| Field |
|-------|
| 🔑 UNIQUE_KEY |
| 🏷️ PRODUCT_TITLE |
| 📝 PRODUCT_DESCRIPTION |
| 🎨 STYLE# |
| 🎨 SANMAR_MAINFRAME_COLOR |
| 📏 SIZE |
| 🌈 COLOR_NAME |
| 💲 PIECE_PRICE |

**Requirements:**

- 🧹 Non-UTF-8 characters are automatically cleaned
- 🔁 Idempotent: re-uploading the same file does not create duplicates
- 🆕 Supports upsert via `UNIQUE_KEY`

---

## 💡 Notes / Recommendations

- 🔧 Ensure Redis server is running for queue jobs
- 🛑 Max upload size is 10MB by default (`file|max:10240` in validation)
- 👀 Optional: Use Horizon to monitor queue jobs
- ⚠️ For large CSVs, increase `upload_max_filesize` and `post_max_size` in `php.ini`

---

## 📸 Screenshots

<img width="1187" height="596" alt="image" src="https://github.com/user-attachments/assets/09e2172e-33aa-421b-ad01-aa97943db599" />


---

## 📜 License

MIT © Qistuna Yusof

