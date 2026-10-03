<?php
/**
 * Shared page header: brand, engine status and primary navigation.
 *
 * Expects $so_active_tab ('optimize' | 'settings') and $diagnostics.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$so_tabs = [
    'optimize' => [
        'label' => __('Optimize', 'super-optimizer'),
        'url'   => admin_url('upload.php?page=super-optimizer-bulk'),
    ],
    'settings' => [
        'label' => __('Settings', 'super-optimizer'),
        'url'   => admin_url('options-general.php?page=super-optimizer-settings'),
    ],
];

$so_webp_ready = !empty($diagnostics['imagick_webp']) || !empty($diagnostics['gd_webp']);
?>
<header class="so-masthead">
    <div class="so-masthead-top">
        <div class="so-brand">
            <span class="so-brand-name"><?php esc_html_e('Super Optimizer', 'super-optimizer'); ?></span>
            <span class="so-brand-version">v<?php echo esc_html(SUPER_OPTIMIZER_VERSION); ?></span>
        </div>
        <div class="so-engine">
            <?php
            printf(
                /* translators: 1: image engine name, 2: WebP support status */
                esc_html__('Image engine: %1$s · WebP %2$s', 'super-optimizer'),
                '<strong>' . esc_html($diagnostics['active_engine_name']) . '</strong>',
                $so_webp_ready ? esc_html__('supported', 'super-optimizer') : esc_html__('not supported', 'super-optimizer')
            );
            ?>
        </div>
    </div>
    <nav class="so-tabs" aria-label="<?php esc_attr_e('Super Optimizer sections', 'super-optimizer'); ?>">
        <?php foreach ($so_tabs as $so_tab_key => $so_tab) : ?>
            <a class="so-tab<?php echo $so_tab_key === $so_active_tab ? ' is-active' : ''; ?>"
               href="<?php echo esc_url($so_tab['url']); ?>"
               <?php echo $so_tab_key === $so_active_tab ? 'aria-current="page"' : ''; ?>>
                <?php echo esc_html($so_tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
<hr class="wp-header-end" style="display:none">
