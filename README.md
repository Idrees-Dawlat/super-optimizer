# Super Optimizer

A high-performance, local WordPress image optimization engine engineered for speed, reliability, and precision. Pro-grade lossy compression, WebP and AVIF generation, auto-resizing, and crash-proof bulk optimization without cloud dependencies, credit limits, or telemetry.

---

## Overview

Most WordPress image optimizer plugins enforce freemium restrictions:
* Free tiers only provide weak lossless compression with 5% to 10% file size reduction.
* High-ratio lossy compression and next-generation formats are locked behind paid cloud subscriptions.
* They enforce monthly image quotas and add intrusive dashboard banners.

Super Optimizer provides an enterprise-grade local processing architecture:
* Zero Cloud Dependencies: Runs entirely on your host infrastructure using PHP Imagick or GD.
* High-Ratio Compression: Tuned visual lossy compression achieving 60% to 80% file size reduction without perceptible quality degradation.
* Relational Queue & State Machine: Dedicated database tables with indexed one-to-many entity relationships tracking master attachments and sub-sizes.
* Non-Blocking Architecture: Batch-isolated AJAX execution designed to avoid timeouts and memory exhaustion on shared and enterprise hosts alike.
* Zero Telemetry and Bloat: Clean, focused codebase with zero external tracking scripts or advertisements.

---

## Core Architecture

### 1. Relational Database Schema
Super Optimizer isolates optimization state into dedicated, high-performance database tables rather than bloating `wp_postmeta`:
* `wp_super_optimizer_items`: Master entity tracking attachment ID, MIME type, original byte size, optimized byte size, bytes saved, compression ratio, next-gen format flags, and lifecycle state (`pending`, `processing`, `completed`, `failed`).
* `wp_super_optimizer_subsizes`: Child entity maintaining a strict one-to-many relationship with master items. Tracks individual WordPress sub-sizes (e.g., `full`, `thumbnail`, `medium`, `large`, custom theme crops), dimensions, file paths, WebP/AVIF siblings, and savings.
* Automatic lifecycle hooks: WordPress attachment deletion triggers cascading cleanup to prevent orphaned records.

### 2. Dual-Engine Processing Pipeline
* Primary Driver: PHP `Imagick` (ImageMagick) with custom sampling factors, colorspace preservation, and EXIF/metadata stripping.
* Fallback Driver: PHP `GD` with truecolor resampling and native WebP/AVIF output.
* Safe Processing: Verifies file headers, MIME types, and available memory before initiating transformations.

### 3. Upload Optimization & Downscaling
* Intercepts media uploads before sub-size generation.
* Scales excessive raw resolutions (e.g. 6000px camera uploads) down to a configurable web-safe maximum (default 2048px).
* Automatically optimizes all generated WordPress thumbnail variations.

### 4. Resilient Bulk Processing Engine
* Browser-managed AJAX queue processing items in atomic chunks.
* Heartbeat monitoring with auto-pause, resume, and individual error logging.
* Live dashboard showing processed items, storage saved, and reduction percentages.

### 5. Next-Generation Format Delivery
* Generates sibling `.webp` and `.avif` files alongside standard JPEG/PNG assets.
* Transparent frontend delivery via responsive HTML `<picture>` tag rewriting or server rewrite rules.

---

## System Requirements

* WordPress: 6.0 or higher
* PHP: 7.4 to 8.3+
* PHP Extensions: `imagick` (recommended) or `gd` with WebP support

---

## License

GPL-2.0-or-later

