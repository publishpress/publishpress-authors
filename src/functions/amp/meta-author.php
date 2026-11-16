<?php
if (!defined('ABSPATH')) {
    exit;
}

$coauthors = get_post_authors($this->get('post_id')); ?>
<li class="amp-wp-byline">
    <?php publishpress_authors_the_author(); ?>
</li>
