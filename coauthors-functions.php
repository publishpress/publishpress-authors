<?php
/**
 * @package     MultipleAuthors\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.7
 *
 * @deprecated 3.2.5-beta.8
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/functions/coauthors-functions.php';

if (!class_exists('PublishPress_Authors_CoAuthors_Functions')) {
    /**
     * Class PublishPress_Authors_CoAuthors_Functions
     *
     * @deprecated 3.2.5-beta.8
     */
    class PublishPress_Authors_CoAuthors_Functions
    {
    }
}
