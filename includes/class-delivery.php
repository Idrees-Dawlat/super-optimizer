<?php
/**
 * Frontend Next-Gen Image Delivery & Lazy Loading for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WebP delivery and high-efficiency lazy loading with Above-the-Fold LCP protection.
 */
class Delivery
{
    /**
     * Counter for tracking rendered images per request to skip above-the-fold assets.
     *
     * @var int
     */
    protected static int $image_counter = 0;

    /**
     * Initializes delivery and lazy loading hooks.
     */
    public static function init(): void
    {
        $settings = Settings::get_all();
        $mode     = $settings['webp_delivery_method'] ?? 'picture';

        // WebP Picture Tag Delivery
        if ($mode === 'picture') {
            add_filter('the_content', [__CLASS__, 'filter_html_images'], 9990);
            add_filter('post_thumbnail_html', [__CLASS__, 'filter_html_images'], 9990);
            add_filter('widget_text', [__CLASS__, 'filter_html_images'], 9990);
        }

        // Native Lazy Loading with Above-the-Fold skip
        if (!empty($settings['lazy_load'])) {
            add_filter('the_content', [__CLASS__, 'apply_lazy_loading'], 9995);
            add_filter('post_thumbnail_html', [__CLASS__, 'apply_lazy_loading'], 9995);
        }
    }

    /**
     * Rewrites standard <img> tags into modern <picture> tags with WebP source.
     *
     * @param string $content HTML content.
     * @return string Modified HTML.
     */
    public static function filter_html_images($content)
    {
        if (is_admin() || is_feed() || empty($content) || !is_string($content)) {
            return $content;
        }

        $exclusions = self::get_exclusion_list('webp_exclusions');

        return preg_replace_callback(
            '/<picture>.*?<\/picture>|(<img\s+[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>)/is',
            function ($matches) use ($exclusions) {
                if (empty($matches[1])) {
                    return $matches[0];
                }

                $img_tag = $matches[1];
                $src     = $matches[2];

                // Check exclusions
                if (self::is_excluded($img_tag, $src, $exclusions)) {
                    return $img_tag;
                }

                // Check if image is an upload from this site
                $upload_dir = wp_upload_dir();
                $base_url   = $upload_dir['baseurl'];
                $base_path  = $upload_dir['basedir'];

                if (strpos($src, $base_url) === false) {
                    return $img_tag;
                }

                $relative_path = str_replace($base_url, '', $src);
                $file_path     = $base_path . $relative_path;
                $webp_path     = $file_path . '.webp';
                $webp_url      = $src . '.webp';

                // Only rewrite if sibling WebP actually exists on disk
                if (!file_exists($webp_path)) {
                    return $img_tag;
                }

                // Check for srcset attribute
                $webp_srcset = '';
                if (preg_match('/srcset=[\'"]([^\'"]+)[\'"]/i', $img_tag, $srcset_matches)) {
                    $sources = explode(',', $srcset_matches[1]);
                    $webp_sources = [];
                    foreach ($sources as $source) {
                        $parts = preg_split('/\s+/', trim($source));
                        if (!empty($parts[0])) {
                            $webp_sources[] = $parts[0] . '.webp' . (!empty($parts[1]) ? ' ' . $parts[1] : '');
                        }
                    }
                    if (!empty($webp_sources)) {
                        $webp_srcset = implode(', ', $webp_sources);
                    }
                }

                $srcset_attr = !empty($webp_srcset)
                    ? ' srcset="' . esc_attr($webp_srcset) . '"'
                    : ' srcset="' . esc_url($webp_url) . '"';

                return '<picture><source type="image/webp"' . $srcset_attr . '>' . $img_tag . '</picture>';
            },
            $content
        );
    }

    /**
     * Applies high-performance lazy loading skipping above-the-fold images.
     *
     * @param string $content HTML content.
     * @return string
     */
    public static function apply_lazy_loading($content)
    {
        if (is_admin() || is_feed() || empty($content) || !is_string($content)) {
            return $content;
        }

        $above_fold_limit = (int) Settings::get('lazy_load_above_fold', 3);
        $exclusions       = self::get_exclusion_list('lazy_load_exclusions');

        return preg_replace_callback(
            '/<img\s+([^>]*?)>/is',
            function ($matches) use ($above_fold_limit, $exclusions) {
                $img_tag = $matches[0];
                $attrs   = $matches[1];

                self::$image_counter++;

                // Skip the first N images above the fold to maximize Core Web Vitals LCP score
                if (self::$image_counter <= $above_fold_limit) {
                    return $img_tag;
                }

                // Check exclusions
                if (self::is_excluded($img_tag, $attrs, $exclusions)) {
                    return $img_tag;
                }

                // If loading attribute already set, do not duplicate
                if (strpos($attrs, 'loading=') !== false) {
                    return $img_tag;
                }

                return '<img loading="lazy" decoding="async" ' . $attrs . '>';
            },
            $content
        );
    }

    /**
     * Parses multiline exclusion textarea into array of clean tokens.
     *
     * @param string $setting_key Setting name.
     * @return array
     */
    protected static function get_exclusion_list(string $setting_key): array
    {
        $raw = (string) Settings::get($setting_key, '');
        if (empty($raw)) {
            return [];
        }

        $lines = explode("\n", str_replace("\r", '', $raw));
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $clean[] = $line;
            }
        }

        return $clean;
    }

    /**
     * Tests if an image element or URL matches configured exclusion rules.
     */
    protected static function is_excluded(string $html, string $url, array $exclusions): bool
    {
        if (empty($exclusions)) {
            return false;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        foreach ($exclusions as $rule) {
            // Check page:/path/ syntax
            if (strpos($rule, 'page:') === 0) {
                $page_match = trim(substr($rule, 5));
                if (!empty($page_match) && strpos($request_uri, $page_match) !== false) {
                    return true;
                }
                continue;
            }

            // Substring search in url or html element
            if (stripos($url, $rule) !== false || stripos($html, $rule) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates Apache / LiteSpeed .htaccess rewrite directives.
     */
    public static function get_htaccess_rules(): string
    {
        return <<<HTACCESS
# BEGIN Super Optimizer WebP Delivery
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_ACCEPT} image/webp
    RewriteCond %{REQUEST_FILENAME} (.+)\.(jpe?g|png)$
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.webp -f
    RewriteRule (.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,E=accept:1,L]
</IfModule>
<IfModule mod_headers.c>
    Header append Vary Accept env=REDIRECT_accept
</IfModule>
<IfModule mod_mime.c>
    AddType image/webp .webp
</IfModule>
# END Super Optimizer WebP Delivery
HTACCESS;
    }

    /**
     * Generates Nginx configuration block.
     */
    public static function get_nginx_rules(): string
    {
        return <<<NGINX
# Super Optimizer Nginx Configuration
map \$http_accept \$super_opt_webp_suffix {
    default "";
    "~*image/webp" ".webp";
}

location ~* ^/wp-content/uploads/(.+)\.(jpe?g|png)$ {
    add_header Vary Accept;
    try_files \$uri\$super_opt_webp_suffix \$uri =404;
}
NGINX;
    }
}
