<p align="center">
  <img src=".github/banner.png" alt="Smart Dashboard for Moodle" width="100%">
</p>

<p align="center">
  <a href="https://moodle.org/plugins"><img src="https://img.shields.io/badge/Moodle-4.0%2B-ff6c00?style=for-the-badge&logo=moodle&logoColor=white" alt="Moodle 4.0+"></a>
  <a href="https://www.gnu.org/licenses/gpl-3.0"><img src="https://img.shields.io/badge/License-GPL_v3-blue?style=for-the-badge" alt="GPL v3"></a>
  <a href="#"><img src="https://img.shields.io/badge/Release-v1.0.0-brightgreen?style=for-the-badge" alt="Release"></a>
  <a href="#"><img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
</p>

<p align="center">
  <strong>A powerful, all-in-one analytics dashboard for Moodle.</strong><br>
  Built for <b>teachers</b>, <b>managers</b>, and <b>admins</b> — with a beautiful dark-mode interface.
</p>

---

## ✨ Features at a Glance

<table>
<tr>
<td width="50%">

### 📊 Overview
- Course cards with student counts & images
- Admin category browser with enrollment stats
- Hierarchical navigation with recursive counts

</td>
<td width="50%">

### 👥 Student Progress
- Cross-course completion tracking
- Per-student activity drill-down
- Filters by course, category & status
- CSV export

</td>
</tr>
<tr>
<td width="50%">

### ✅ Grading
- Pending submissions across all courses
- Assignment breakdown with due dates
- Course-level filtering

</td>
<td width="50%">

### 📈 Analytics
- System-wide enrollment statistics
- Category breakdown charts
- Student-to-teacher ratio visualization
- CSV export

</td>
</tr>
<tr>
<td width="50%">

### 💰 Payment Analytics
- Revenue tracking (actual vs estimated)
- Per-category revenue charts
- Time-range & date filtering
- Currency toggle & CSV export

</td>
<td width="50%">

### ⚙️ Settings
- Payment calculation mode
- Currency display toggle
- Per-site persistent configuration

</td>
</tr>
</table>

---

## 🚀 Installation

### Option 1 — Download ZIP
1. Download the latest release ZIP from [Releases](../../releases)
2. In Moodle, go to **Site Administration → Plugins → Install plugins**
3. Upload the ZIP file and follow the prompts

### Option 2 — Git Clone
```bash
cd /path/to/moodle/local
git clone https://github.com/Smartlearn-edu/moodle_local_smartdashboard.git smartdashboard
```
Then visit **Site Administration → Notifications** to complete the install.

> **Important:** The folder inside `/local/` must be named **`smartdashboard`**.

---

## 🔧 Usage

After installation, navigate to:

```
https://your-site.com/local/smartdashboard/
```

| Role | What you see |
|---|---|
| **Teacher** | Your courses, student progress, grading queue |
| **Manager** | All of the above + category analytics, system stats |
| **Admin** | Full access including payment analytics & settings |

---

## 📋 Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.0 or later |
| PHP | 7.4 or later |
| Dependencies | None |

---

## 🗂️ Plugin Structure

```
smartdashboard/
├── amd/
│   ├── src/main.js                    # Frontend logic (AMD module)
│   └── build/main.min.js             # Minified build
├── classes/
│   ├── external/
│   │   ├── analytics.php             # API: progress, analytics, payments, settings
│   │   └── grading.php               # API: grading overview
│   └── output/
│       └── dashboard.php             # Renderable output class
├── db/
│   └── services.php                  # External service definitions
├── lang/en/
│   └── local_smartdashboard.php      # Language strings
├── templates/
│   └── dashboard.mustache            # Mustache template
├── index.php                         # Entry point
├── styles.css                        # Dark mode styling
└── version.php                       # Plugin metadata
```

---

## 🌐 API Reference

All functions are available via Moodle's AJAX interface:

| Function | Type | Description |
|---|---|---|
| `local_smartdashboard_get_cross_course_progress` | read | Student progress across courses |
| `local_smartdashboard_get_student_detailed_progress` | read | Activity-level detail for one student |
| `local_smartdashboard_get_grading_overview` | read | Assignments needing grading |
| `local_smartdashboard_get_system_analytics` | read | System-wide enrollment analytics |
| `local_smartdashboard_get_payment_analytics` | read | Revenue and payment data |
| `local_smartdashboard_save_dashboard_settings` | write | Save dashboard configuration |
| `local_smartdashboard_get_dashboard_settings` | read | Retrieve dashboard configuration |

---

## 🤝 Contributing

1. **Fork** the repository
2. Create a **feature branch** — `git checkout -b feature/your-feature`
3. **Commit** your changes — `git commit -m 'Add your feature'`
4. **Push** — `git push origin feature/your-feature`
5. Open a **Pull Request**

---

## 📝 Changelog

### v1.0.0 — 2026-02-15
- 🎉 Initial stable release
- Overview, Student Progress, Grading, Analytics, Payment Analytics & Settings
- Dark mode UI with responsive sidebar
- CSV export for all data sections
- Hierarchical category filtering
- Admin/Manager/Teacher role support

---

## 📄 License

Licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

---

<p align="center">
  Made with ❤️ by <a href="https://smartlearn.education"><strong>SmartLearn Education</strong></a>
</p>
