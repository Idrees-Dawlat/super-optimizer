# Super Optimizer 🚀

> **High-performance, 100% free, local WordPress image optimization engine.**
> Pro-grade lossy compression, WebP/AVIF generation, auto-resizing, and crash-proof bulk optimization — without subscriptions, credit limits, or cloud locks.

---

## 🌟 Why Super Optimizer?

Most WordPress image optimizer plugins (EWWW, Smush, ShortPixel, Imagify) use restrictive freemium models:
* Free tiers only offer weak lossless compression (~5–10% file size reduction).
* High-ratio lossy compression and modern formats are locked behind paid cloud subscriptions.
* They enforce monthly image credits and bombard the admin dashboard with upsell banners.

**Super Optimizer** eliminates the middleman:
* ⚡ **100% Free Forever:** No API keys, no monthly image caps, no cloud costs.
* 🖥️ **100% Local Processing:** Runs securely on your own server using PHP's native Imagick or GD engine.
* 📉 **Real Pro Compression:** Visual lossy compression (80–82% quality) achieving **60%–80% file size savings** indistinguishable to the human eye.
* 🧼 **Zero Bloat:** No ads, no telemetry, no nag screens.

---

## ✨ Planned Features

### 1. High-Efficiency Local Compression
- **WebP & AVIF Conversion:** Automatically generates next-gen format siblings for all uploaded media.
- **Smart Lossy Slider:** Fine-tune image quality (default 82%) for the optimal balance between size and quality.
- **EXIF & Metadata Stripping:** Safely removes bloated camera metadata (GPS, device info) to shave off unnecessary kilobytes.

### 2. Auto-Resize on Upload
- Automatically detects massive high-resolution photos (e.g., 5000px+, 10MB+ phone uploads).
- Scales them down to a customizable web-safe maximum (e.g., 2048px) *before* generating thumbnails, saving immense disk space.

### 3. Automatic Upload Hook
- Set it and forget it workflow.
- Hooks directly into wp_generate_attachment_metadata to optimize originals and all registered sub-sizes (	humbnail, medium, large, etc.) on upload.

### 4. Crash-Proof Bulk Optimizer
- Live AJAX-driven batch queue to optimize existing media libraries.
- Processes 1–2 attachments per step to eliminate PHP max_execution_time timeouts and memory limit crashes on shared hosting.
- Real-time statistics: files processed, total MB saved, and percentage reduction.
- Full pause, cancel, and auto-resume capabilities.

### 5. Transparent Next-Gen Delivery
- **Rewrite Rules:** Native .htaccess / Nginx rewrite rules to serve .webp transparently without altering your WordPress post database content.
- **Picture Tag Fallback:** Optional frontend HTML filter replacing <img> tags with <picture> tags.

### 6. Safe Local Backups & One-Click Restore
- Keep original uncompressed copies in a local /uploads/super-optimizer-backups/ directory.
- Instant one-click restore per media item or across the entire library.

---

## 🛠️ Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 to 8.3+
- **PHP Extensions:** Imagick (recommended) or GD with WebP support

---

## 📄 License

GPL-2.0-or-later
