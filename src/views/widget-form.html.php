<p>
    <label for="<?php echo esc_attr($context['ids']['title']); ?>"><?php echo esc_html($context['labels']['title']); ?></label>
    <input class="widefat" id="<?php echo esc_attr($context['ids']['title']); ?>" name="<?php echo esc_attr($context['names']['title']); ?>" type="text"
           value="<?php echo esc_attr($context['values']['title']); ?>">

    <label for="<?php echo esc_attr($context['ids']['title_plural']); ?>"><?php echo esc_html($context['labels']['title_plural']); ?></label>
    <input class="widefat" id="<?php echo esc_attr($context['ids']['title_plural']); ?>" name="<?php echo esc_attr($context['names']['title_plural']); ?>" type="text" value="<?php echo esc_attr($context['values']['title_plural']); ?>">

</p>

<p>
    <label for="<?php echo esc_attr($context['ids']['layout']); ?>"><?php echo esc_html($context['labels']['layout']); ?></label>

    <select id="<?php echo esc_attr($context['ids']['layout']); ?>" name="<?php echo esc_attr($context['names']['layout']); ?>">
        <?php foreach ($context['layouts'] as $layout => $label) : ?>
            <option value="<?php echo esc_attr($layout); ?>"
                <?php selected($layout, $context['values']['layout']); ?>
            ><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
</p>

<input type="hidden" id="<?php echo esc_attr($context['ids']['nonce']); ?>" name="<?php echo esc_attr($context['names']['nonce']); ?>" value="<?php echo esc_attr($context['values']['nonce']); ?>"/>