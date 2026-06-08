# PHPFileVault — Modern PHP File Manager

A single-file, feature-rich PHP file manager for cPanel and shared hosting. Upload, edit, preview, organize — all from your browser.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Size](https://img.shields.io/badge/Size-Single%20File-blue?style=flat-square)
![Responsive](https://img.shields.io/badge/Responsive-Mobile%20%26%20Desktop-orange?style=flat-square)

---

## Features

### File Operations
- Browse directories with breadcrumb navigation
- Upload files (multi-file) and entire folders with structure preservation
- Drag & drop upload support
- Download files
- Create / rename / delete files and folders
- Copy / move files to any destination with folder browser picker
- View and change file permissions (chmod)
- Extract ZIP archives
- Create ZIP archives from selected files
- Real-time search and filter
- Sort by name, size, or date

### Code Editor (Ace Editor)
- Syntax highlighting for 30+ languages (PHP, JS, Python, Java, Go, Rust, etc.)
- 8 dark themes (Monokai, Dracula, GitHub Dark, One Dark, etc.)
- Line numbers, code folding, indent guides
- Find & replace (Ctrl+F)
- Auto-detect language from file extension
- Word wrap toggle, font size controls
- Save with Ctrl+S — no page reload (AJAX)
- Full keyboard shortcut support

### File Preview
- Images (JPG, PNG, GIF, SVG, WebP, AVIF)
- Video player (MP4, WebM, AVI, MOV)
- Audio player (MP3, WAV, OGG, FLAC)
- PDF viewer
- Text / code preview

### Multi-Select & Bulk Actions
- Desktop: Click checkboxes, Ctrl+A to select all
- Mobile: Long-press to enter selection mode, tap to toggle
- Bulk delete, copy, move, chmod, extract, zip creation

### Mobile Responsive
- Fully responsive dark theme UI
- Collapsible sidebar on mobile
- Touch-friendly buttons and modals
- Adaptive table columns

### Security
- Password-protected login (session-based)
- Path traversal protection
- File path validation with `realpath()`
- cPanel ModSecurity compatible (.htaccess included)

---

## Quick Start

### 1. Download

Download `index.php` and `.htaccess` — that's it! No database, no dependencies.

### 2. Upload to Server

Upload both files to your `public_html` directory:

```
public_html/
├── index.php      ← Main file manager
└── .htaccess      ← Server configuration
```

### 3. Access

Visit your domain in a browser:

```
https://yourdomain.com
```

Enter the password (default: `AnTor999` — change it!).

---

## Configuration

Edit the top of `index.php`:

```php
$CONFIG = [
    'password'    => 'AnTor999',   // Change this!
    'max_upload'  => 500,           // Max upload size in MB
    'theme'       => 'dark',        // UI theme
];
```

---

## cPanel Setup

If you get **403 Forbidden** errors, ensure these lines are in your `.htaccess`:

```apache
<IfModule mod_security.c>
    SecRuleEngine Off
</IfModule>

<IfModule mod_php.c>
    php_value post_max_size 100M
    php_value upload_max_filesize 100M
    php_value memory_limit 256M
</IfModule>
```

Or disable ModSecurity in cPanel → Security → ModSecurity.

---

## Keyboard Shortcuts

### File Manager
| Key | Action |
|-----|--------|
| `/` | Focus search |
| `?` | Toggle shortcuts panel |
| `Ctrl+A` | Select all files |
| `Esc` | Close modal / exit selection |

### Code Editor
| Key | Action |
|-----|--------|
| `Ctrl+S` | Save file |
| `Ctrl+F` | Find & Replace |
| `Ctrl+Z` | Undo |
| `Ctrl+Y` | Redo |

---

## Supported Languages (Editor)

PHP, HTML, CSS, JavaScript, TypeScript, JSX, Python, Java, Ruby, Go, Rust, C/C++, C#, SQL, Shell/Bash, YAML, JSON, XML, SCSS, Sass, Less, Markdown, Lua, Perl, Swift, Kotlin, Dockerfile, and more.

---

## Requirements

- PHP 7.4+ (8.0+ recommended)
- Web server (Apache with mod_php, or LiteSpeed)
- cPanel shared hosting or any PHP-capable server

---

## File Structure

This is a **single-file** application. Everything — HTML, CSS, JavaScript, and PHP — is contained in one `index.php` file (~2300 lines).

| Component | Technology |
|-----------|-----------|
| Backend | PHP (vanilla, no frameworks) |
| Frontend | Vanilla JS + CSS Grid/Flexbox |
| Editor | Ace Editor (CDN) |
| Icons | Emoji (zero dependencies) |
| Auth | PHP Sessions |

---

## License

MIT License — use freely in personal and commercial projects.

---

## Contributing

Contributions welcome! Feel free to open issues or submit pull requests.

---

## Author

**Bijoy Kumar Nath** — [@bijoyknath999](https://github.com/bijoyknath999/)

---

## Changelog

### v1.0 (2026)
- Initial release
- File browsing, upload, download, create, rename, delete
- Code editor with Ace Editor (30+ languages, 8 themes)
- File preview (images, video, audio, PDF, text)
- Multi-select with bulk actions
- ZIP create/extract
- Destination picker for copy/move
- Folder upload with structure preservation
- Mobile responsive dark UI
- Keyboard shortcuts
- cPanel compatible with .htaccess
