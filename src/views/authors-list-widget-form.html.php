<p>
    <label for="<?php echo esc_attr($context['ids']['title']); ?>"><?php echo esc_html($context['labels']['title']); ?></label>
    <input class="widefat" id="<?php echo esc_attr($context['ids']['title']); ?>" name="<?php echo esc_attr($context['names']['title']); ?>" type="text"
           value="<?php echo esc_attr($context['values']['title']); ?>">
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
<p>
    <label for="<?php echo esc_attr($context['ids']['authors']); ?>"><?php echo esc_html($context['labels']['authors']); ?></label>

    <select id="<?php echo esc_attr($context['ids']['authors']); ?>" name="<?php echo esc_attr($context['names']['authors']); ?>">
        <?php foreach ($context['options']['authors'] as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>"
                <?php selected($key, $context['values']['authors']); ?>
            ><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>

    </select>
</p>

<p>
    <label for="<?php echo esc_attr($context['ids']['orderby']); ?>"><?php echo esc_html($context['labels']['orderby']); ?></label>

    <select id="<?php echo esc_attr($context['ids']['orderby']); ?>" name="<?php echo esc_attr($context['names']['orderby']); ?>">

        <?php foreach ($context['options']['orderby'] as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>"
                <?php selected($key, $context['values']['orderby']); ?>
            ><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>

    </select>
</p>

<p>
    <label for="<?php echo esc_attr($context['ids']['order']); ?>"><?php echo esc_html($context['labels']['order']); ?></label>

    <select id="<?php echo esc_attr($context['ids']['order']); ?>" name="<?php echo esc_attr($context['names']['order']); ?>">

        <?php foreach ($context['options']['order'] as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>"
                <?php selected($key, $context['values']['order']); ?>
            ><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>

    </select>
</p>

<p>
    <label for="<?php echo esc_attr($context['ids']['limit_per_page']); ?>"><?php echo esc_html($context['labels']['limit_per_page']); ?></label>
    <input class="widefat" id="<?php echo esc_attr($context['ids']['limit_per_page']); ?>" name="<?php echo esc_attr($context['names']['limit_per_page']); ?>" type="number"
           value="<?php echo esc_attr($context['values']['limit_per_page']); ?>">
</p>

<p>
    <input class="checkbox" id="<?php echo esc_attr($context['ids']['show_empty']); ?>" name="<?php echo esc_attr($context['names']['show_empty']); ?>" type="checkbox"
    <?php checked($context['values']['show_empty'], true); ?>
    />
    <label for="<?php echo esc_attr($context['ids']['show_empty']); ?>"><?php echo esc_html($context['labels']['show_empty']); ?></label>
</p>

<p>
    <input class="checkbox" id="<?php echo esc_attr($context['ids']['search_box']); ?>" name="<?php echo esc_attr($context['names']['search_box']); ?>" type="checkbox"
    <?php checked($context['values']['search_box'], true); ?>
    />
    <label for="<?php echo esc_attr($context['ids']['search_box']); ?>"><?php echo $context['labels']['search_box'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
</p>

<p>
    <label for="<?php echo esc_attr($context['ids']['search_field']); ?>"><?php echo $context['labels']['search_field'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
    <input class="widefat" id="<?php echo esc_attr($context['ids']['search_field']); ?>" name="<?php echo esc_attr($context['names']['search_field']); ?>" type="text"
           value="<?php echo esc_attr($context['values']['search_field']); ?>">
</p>

<input type="hidden" id="<?php echo esc_attr($context['ids']['nonce']); ?>" name="<?php echo esc_attr($context['names']['nonce']); ?>" value="<?php echo esc_attr($context['values']['nonce']); ?>"/>