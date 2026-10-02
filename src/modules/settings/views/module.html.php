<?php

if (!defined('ABSPATH')) {
    exit;
}

$url            = $context['url'];
$has_config_link = $context['has_config_link'];
$slug           = $context['slug'];
$icon_class     = $context['icon_class'];
$form_action    = $context['form_action'];
$title          = $context['title'];
$description    = $context['description'];
?>
<?php if ($url) : ?>
<a href="<?php echo esc_attr($url); ?>">
<?php endif; ?>

    <div
            class="publishpress-module module-enabled <?php echo ($has_config_link ? 'has-configure-link' : ''); ?>"
            id="<?php echo esc_attr($slug); ?>">

        <?php if ($icon_class) : ?>
            <span class="<?php echo esc_attr($icon_class); ?> float-right module-icon"></span>
        <?php endif; ?>

        <form
                method="GET"
                action="<?php echo esc_attr($form_action); ?>">

            <h4><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h4>
            <p><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        </form>
    </div>

<?php if ($url) : ?>
</a>
<?php endif; ?>
