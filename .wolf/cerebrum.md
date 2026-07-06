# Cerebrum

> OpenWolf's learning memory. Updated automatically as the AI learns from interactions.
> Do not edit manually unless correcting an error.
> Last updated: 2026-06-03

## User Preferences

<!-- How the user likes things done. Code style, tools, patterns, communication. -->

## Key Learnings

- **Project:** durra-alaseel-web
- **Description:** <<<<<<<< Update Guide >>>>>>>>>>>

## Do-Not-Repeat

<!-- Mistakes made and corrected. Each entry prevents the same mistake recurring. -->
<!-- Format: [YYYY-MM-DD] Description of what went wrong and what to do instead. -->

[2026-06-07] NEVER use `asset('public/...')` in Blade views.
[2026-06-07] On Windows, `realpath()` normalises all path separators to backslashes. Never compare `realpath($path) !== $path` to detect symlinks when `$path` is built by concatenating a `getcwd()` result (backslashes) with a URI (forward slashes) — the comparison is always unequal. Use `str_replace('\\','/',...) ` on both sides, or skip the check entirely. Also: `mime_content_type()` returns `text/plain` for CSS and JS on Windows — always use an explicit MIME map for known asset extensions. The `public/` directory is the web root — `asset()` already generates URLs relative to it. Use `asset('frontend/css/file.css')` not `asset('public/frontend/css/file.css')`. The bad form works only with a custom router.php shim but breaks with `php artisan serve`, Nginx, and Apache.

## Decision Log

<!-- Significant technical decisions with rationale. Why X was chosen over Y. -->
