<?php

if (!defined('ABSPATH')) {
    exit;
}

$show_sidebar   = $context['show_sidebar'];
$show_tabs      = $context['show_tabs'];
$modules        = $context['modules'];
$module_output  = $context['module_output'];
$sidebar_output = $context['sidebar_output'];
$sidebar_output = $context['sidebar_output'];
$slug           = $context['slug'];
$settings_slug  = $context['settings_slug'];
?>
<div class="wrap multiple_authors-admin <?php echo ($show_sidebar ? 'allex_container_with_sidebar multiple_authors_with_sidebar container' : ''); ?>">
    <div class="allex-row">
        <div class="<?php echo ($show_sidebar ? 'allex-col-3-4' : 'allex-col-1'); ?>">
            <?php if ($show_tabs) : ?>
                <h2 class="nav-tab-wrapper">
                    <?php foreach ($modules as $module) : ?>
                        <?php if (!empty($module->options_page) && $module->options_page->enabled == 'on') : ?>
                            <a
                                href="?page=<?php echo esc_attr($slug); ?>&module=<?php echo esc_attr($module->settings_slug); ?>"
                                class="nav-tab <?php echo ($settings_slug == $module->settings_slug ? 'nav-tab-active' : ''); ?>">
                                <?php echo $module->title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </h2>
            <?php endif; ?>

            <div class="ppma-module-settings"><?php echo $module_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        </div>

        <?php if ($show_sidebar) : ?>
            <div class="allex-col-1-4">
                <?php echo $sidebar_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
    </div>
</div>
