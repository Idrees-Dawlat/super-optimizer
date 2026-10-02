<?php
/**
 * Frontend Next-Gen Image Delivery for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WebP delivery via transparent HTML picture replacement or server rewrite rules.
 */
class Delivery
{
    /**
     * Initializes delivery hooks.
     */
    public static function init(): void
    {
        $mode = Settings::get('serve_webp', 'picture');

        if ($mode === 'picture') {
            // Apply picture replacement on frontend output
            add_filter('the_content', [__CLASS__, 'filter_html_images'], 9999);
            add_filter('post_thumbnail_html', [__CLASS__, 'filter_html_images'], 9999);
            add_filter('widget_text', [__CLASS__, 'filter_html_images'], 9999);
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

        // Match <img> tags that are not already enclosed in a <picture> element
        return preg_replace_callback(
            '/<picture>.*?<\/picture>|(<img\s+[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>)/is',
            function ($matches) {
                // If it's already inside a <picture> tag, leave untouched
                if (empty($matches[1])) {
                    return $matches[0];
                }

                $img_tag = $matches[1];
                $src     = $matches[2];

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

                $srcset_attr = !empty($webp_srcset) ? ' srcset="' . esc_attr($webp_srcset) . '"' : ' srcset="' . esc_url($webp_url) . '"';

                return '<picture><source type="image/webp"' . $srcset_attr . '>' . $img_tag . '</picture>';
            },
            $content
        );
    }

    /**
     * Generates Apache / LiteSpeed .htaccess rewrite directives.
     *
     * @return string
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
     *
     * @return string
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
