# 🚀 Elite IoT Portfolio & Energy Command Center

A high-end, futuristic, and fully responsive portfolio ecosystem designed for multi-disciplinary innovators. This project bridges the gap between hardware and software, featuring a robust PHP 8.x backend and a real-time hardware telemetry dashboard integrated with **Home Assistant**.

![Repo Name](https://img.shields.io/badge/Repository-elite--iot--portfolio-blue?style=for-the-badge&logo=github)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)
![IoT](https://img.shields.io/badge/IoT-Live_Telemetry-green?style=for-the-badge&logo=homeassistant)
![Tailwind](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css)

## ✨ Elite Features

### ⚡ Live Energy Command Center (`power.php`)
A professional-grade monitoring suite for dual-site (Shop & Home) energy systems:
- **Animated Power Flow:** High-end SVG/CSS visualizations showing real-time energy movement between Solar panels, Grid, Batteries, and Load.
- **Shop Master Sync:** Advanced multi-pack battery telemetry, featuring per-pack SOC, total Busbar Sync (Amps), and pack health indicators.
- **System Intelligence:** Dynamic calculation of "Backup Time Remaining" and "Time to Full Charge" with interactive UI animations.
- **Analog Health Gauge:** Custom circular SVG gauge for real-time system synchronization monitoring.
- **Secure API Proxy:** A protected PHP gateway (`api/ha_proxy.php`) that delivers real-time Home Assistant data while keeping your internal URL and Tokens hidden from the public.

### 📁 Dynamic Content Management
Full CRUD Admin Dashboard to manage your professional presence:
- **Projects:** Showcase your coding, PCB design, and IoT prototypes.
- **Testimonials:** Manage client and partner feedback to build immediate trust.
- **Blog/Insights:** A dedicated space for sharing market analysis and technical trends.

### 📱 Premium Mobile Experience
- **Responsive Architecture:** Every chart and grid adapts perfectly to small screens.
- **Glassmorphic UI:** A modern, dark-themed aesthetic with smooth transitions and typewriter effects.
- **Functional Mobile Menu:** Custom-built navigation optimized for touch devices.

## 🛠️ Tech Stack

- **Backend:** PHP 8.x (PDO), MySQL.
- **Frontend:** HTML5, Tailwind CSS, Vanilla JavaScript, SVG Animations.
- **Integration:** Home Assistant REST API, GitHub REST API.
- **UI/UX:** FontAwesome 6, AOS (Animate On Scroll), Inter Font.

## 📥 Installation & Setup

### 1. Repository Initialization
```bash
git clone https://github.com/manoranjan2050/elite-iot-portfolio.git
cd elite-iot-portfolio
```

### 2. Database Configuration
1. Create a MySQL database and import `database_setup.txt`.
2. Navigate to `includes/`, copy `db_sample.php` to `db.php`, and enter your credentials.

### 3. IoT & Home Assistant Setup
1. Navigate to `includes/`, copy `iot_config_sample.php` to `iot_config.php`.
2. Enter your Home Assistant URL and **Long-Lived Access Token**.
3. Map your sensor entities in the `entities` constant at the bottom of `power.php`.

### 4. Admin Access
- **URL:** `yourdomain.com/login.php`
- **Default:** `admin` / `password123` (**Change immediately after login!**)

## 🚀 Deployment (Hostinger Ready)

Specifically optimized for **Hostinger Premium** and other shared hosting environments.
1. Upload the project folder to `public_html`.
2. Set write permissions (755) for the `uploads/` directory.
3. Configure `includes/db.php` and `includes/iot_config.php`.

## 📄 License

This project is licensed under the MIT License.

---
*Developed by **[Manoranjan](https://github.com/manoranjan2050)** — Innovating at the intersection of Hardware, Software, and Finance.*
