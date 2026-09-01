# 🚀 Elite IoT Portfolio & Energy Command Center

A high-end, futuristic, and fully responsive portfolio ecosystem designed for multi-disciplinary innovators. Bridges the gap between hardware and software, featuring a robust PHP 8.x backend, a real-time energy telemetry dashboard, and a public IoT control center — all integrated with **Home Assistant**.

![Repo Name](https://img.shields.io/badge/Repository-elite--iot--portfolio-blue?style=for-the-badge&logo=github)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)
![IoT](https://img.shields.io/badge/IoT-Live_Telemetry-green?style=for-the-badge&logo=homeassistant)
![Tailwind](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css)

## 🌐 Live

- **Portfolio:** [manoranjan.dev](https://manoranjan.dev)
- **Power Station:** [manoranjan.dev/power.php](https://manoranjan.dev/power.php)
- **IoT Control Center:** [manoranjan.dev/hacontrol.php](https://manoranjan.dev/hacontrol.php)

---

## ✨ Features

### ⚡ Live Energy Command Center (`power.php`)
A redesigned, animated monitoring suite for dual-site (Shop & Home) energy systems:
- **Animated background** — floating blobs, grid overlay, particle effects
- **Live clock + refresh countdown ring** — 15s auto-refresh with visual ring indicator
- **Combined top stats bar** — total solar, total load, Shop SOC, Home SOC in one glance
- **Interactive SVG power flow diagrams** — wattage-based animation speed, glow pulses on active nodes
- **Animated SOC gauges** — circular SVG gauge with color transitions (green → yellow → red by SOC%)
- **Smooth battery SOC bars** — gradient bars for shop/home and each battery pack
- **Chart.js gradients** — beautiful area charts for solar generation and storage level (24h)
- **Critical thermal alerts** — live alert banner when battery temperature exceeds threshold
- **Per-pack telemetry** — Pack 1 and Pack 2 SOC, current, cell delta, charge/discharge switch state

### 🎛️ Public IoT Control Center (`hacontrol.php`)
A public-facing control panel for authorized HA device control:
- **Canvas arc gauges** — voltage and current with severity-color coding and glow effects
- **Live pump control** — START/STOP water pump with visual RUNNING/STOPPED badge
- **All HA control entities** — switches, lights, automations, scenes from the database
- **Android-style pattern lock** — 3×3 canvas pattern lock (mouse + touch) gates all state changes
- **Rate limiting** — 5 wrong attempts → 5-minute lockout
- **Auto-refresh** — 15s countdown with live entity state updates

### 🔐 Pattern Lock Security (`api/ha_control_public.php`)
- Verifies bcrypt-hashed pattern stored in the `ha_settings` database table
- Session-based rate limiting (5 attempts → 5-min lockout)
- Entity type auto-detection (switch, light, automation, scene)
- Secure cURL call to Home Assistant REST API
- Returns `{success: true}` or structured error with `remaining`/`locked` fields

### 🖥️ Admin Panel (Dark Sidebar Layout)
Fully redesigned admin dashboard with a fixed dark sidebar:
- **Dashboard** (`admin/index.php`) — visit stats (today/7-day/total), project count, quick links
- **Add/Edit Projects** — image upload, category, links
- **Blog & Testimonials** — full CRUD management
- **Profile Page** (`admin/profile.php`) — photo upload with live JS preview, full name, email, mobile, bio, password change with strength meter and show/hide toggle

### 🏠 Home Assistant Settings (`admin/ha_settings.php`)
- HA URL, Long-Lived Access Token, enable/disable toggle
- Site A and Site B custom names
- Live connection test with latency display
- Auto-writes `includes/iot_config.php` on save (backward compatible with `ha_proxy.php`)
- **Pattern Lock Setup** — draw or type the 3×3 pattern for hacontrol.php authorization
  - Stored as a bcrypt hash — never recoverable, only resettable
  - Default pattern: `1235`

### 📋 Entity Management (`admin/power_settings.php`)
Manage all Home Assistant entity IDs from the admin panel without touching code:
- Add/edit/delete entities with entity key, entity ID, friendly name, type, site, display unit
- Control visibility: `show_in_power` (power.php) and `show_in_control` (hacontrol.php)
- Display order control
- Type-color-coded badges (sensor, switch, light, binary_sensor, automation, scene)

### 🎮 Admin HA Control Panel (`admin/ha_control.php`)
Admin-only live control interface (requires login):
- Auto-refresh every 5/15/30s with live clock
- Toggle switches and lights with green glow on ON state
- Trigger automations and scenes
- Sensor read-only grid with live values

### 👤 User Profile (`admin/profile.php`)
- Profile photo with camera overlay upload button and JS preview
- Editable: full name, email, mobile, bio
- Password change with current/new/confirm fields, show/hide toggles, and 4-bar strength meter

### 🔑 Login Page
- Animated glassmorphism card with float-in animation
- 3 animated background blobs + grid overlay
- Loading spinner on submit, shake animation on incorrect credentials

### 🗄️ Database Migration
Run `db_migrate.php` to apply all schema changes without phpMyAdmin:
- Password protected (`MIGRATE_KEY` in the file — default: `manoranjan2025`)
- Runs each SQL statement individually with per-statement status
- Treats "Duplicate column" as non-fatal
- "Delete This File" button for post-migration security cleanup

---

## 🛠️ Tech Stack

| Layer | Technologies |
|---|---|
| Backend | PHP 8.x, PDO MySQL |
| Frontend | HTML5, Tailwind CSS (CDN), Vanilla JS, SVG |
| Charts | Chart.js (with gradient fills) |
| IoT | Home Assistant REST API, cURL |
| Auth | bcrypt (`password_hash`/`password_verify`), PHP sessions |
| UI/UX | Font Awesome 6, AOS animations, Glassmorphism, Canvas API |

---

## 📥 Installation

### 1. Clone
```bash
git clone https://github.com/manoranjan2050/elite-iot-portfolio.git
cd elite-iot-portfolio
```

### 2. Database Setup
Option A — Run `db_migrate.php` (recommended):
1. Upload all files to your server
2. Visit `yourdomain.com/db_migrate.php`
3. Enter the migrate key (default: `manoranjan2025`)
4. Click **Run Migration** — all tables are created automatically
5. Delete `db_migrate.php` after use (or click the button on the page)

Option B — phpMyAdmin:
1. Open `database_migration.sql` and run it in phpMyAdmin

### 3. Configuration
1. Copy `includes/db_sample.php` → `includes/db.php` and enter your database credentials
2. Copy `includes/iot_config_sample.php` → `includes/iot_config.php` and enter your HA URL + token
   (Or set these from **Admin → HA Settings** — the file is auto-updated)

### 4. Admin Access
- URL: `yourdomain.com/login.php`
- Default credentials: `admin` / `password123` — **change immediately after first login**

### 5. Permissions
```bash
chmod 755 uploads/
chmod 644 includes/iot_config.php
```

---

## 🔌 Home Assistant Setup

1. In HA, go to **Profile → Long-Lived Access Tokens → Create Token**
2. In admin panel, go to **HA Settings** and enter your HA URL and token
3. Click **Save** — the config file is updated automatically
4. Use **Entity Management** to map all your HA entity IDs
5. Set your **Pattern Lock** for public control access

---

## 🚀 Deployment (Hostinger Ready)

1. Upload all files to `public_html`
2. Set `uploads/` to 755 write permissions
3. Run the database migration (see above)
4. Configure HA settings from the admin panel

---

## 📁 Project Structure

```
manoranjan.dev/
├── index.php               # Main portfolio page
├── about.php               # About page
├── power.php               # Energy command center (enhanced)
├── hacontrol.php           # Public IoT control center + pattern lock
├── login.php               # Redesigned admin login
├── contact.php             # Contact form
├── db_migrate.php          # One-click database migration runner
├── database_migration.sql  # Full SQL migration script
│
├── admin/
│   ├── index.php           # Dashboard (stats, quick links, projects)
│   ├── profile.php         # User profile + password change
│   ├── ha_settings.php     # HA URL, token, pattern lock setup
│   ├── power_settings.php  # Entity management (power.php + hacontrol.php)
│   ├── ha_control.php      # Admin live control panel
│   ├── blog.php            # Blog post management
│   ├── testimonials.php    # Testimonial management
│   ├── edit_project.php    # Edit existing project
│   └── includes/
│       ├── admin_head.php  # Shared HTML head + CSS
│       ├── sidebar.php     # Fixed dark sidebar + top bar
│       └── admin_footer.php# Closes layout + JS
│
├── api/
│   ├── ha_proxy.php        # HA state proxy (public read-only)
│   ├── ha_control.php      # Admin HA control (session-authenticated)
│   └── ha_control_public.php # Public HA control (pattern-authenticated)
│
├── includes/
│   ├── db.php              # PDO connection
│   ├── functions.php       # Auth, sanitize, track visit helpers
│   ├── iot_config.php      # HA credentials (auto-generated, gitignored)
│   ├── header.php          # Site header + nav (with HAControl link)
│   └── footer.php          # Site footer + scripts
│
└── uploads/                # User-uploaded images
```

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

*Developed by **[Manoranjan](https://github.com/manoranjan2050)** — innovating at the intersection of Hardware, Software, and Finance.*
