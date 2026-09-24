<?php
/**
 * @package     MultipleAuthors\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 *
 * The IDE can probably display the following aliases as Ignored Class Aliases Declarations,
 * but that is happening because we created a .ide.php file defining those classes with the
 * a deprecated attribute in the comments, for the IDE is able to recognize it as deprecated.
 * Just ignore the warning.
 */

if (!defined('ABSPATH')) {
    exit;
}

class_alias('MultipleAuthors\\Plugin', 'PP_Multiple_authors_plugin');
class_alias('MultipleAuthors\\Container', 'PublishPress\\Addon\\Multiple_authors\\Container');
class_alias('MultipleAuthors\\Factory', 'PublishPress\Addon\Multiple_authors\Factory');
class_alias('MultipleAuthors\\Services', 'PublishPress\Addon\Multiple_authors\Services');
class_alias('MultipleAuthors\\Widget', 'PublishPress\Addon\Multiple_authors\Widget');
class_alias('MultipleAuthors\\Classes\\Admin_Ajax', 'PublishPress\\Addon\\Multiple_authors\\Classes\\Admin_Ajax');
class_alias(
    'MultipleAuthors\\Classes\\Author_Editor',
    'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor'
);
class_alias(
    'MultipleAuthors\\Classes\\Authors_Iterator',
    'PublishPress\\Addon\\Multiple_authors\\Classes\\Authors_Iterator'
);
class_alias(
    'MultipleAuthors\\Classes\\Authors_Iterator',
    'Multiple_authors_iterator'
);
class_alias('MultipleAuthors\\Classes\\CLI', 'PublishPress\\Addon\\Multiple_authors\\Classes\\CLI');
class_alias(
    'MultipleAuthors\\Classes\\Content_Model',
    'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model'
);
class_alias('MultipleAuthors\\Classes\\Installer', 'PublishPress\\Addon\\Multiple_authors\\Classes\\Installer');
class_alias('MultipleAuthors\\Classes\\Post_Editor', 'PublishPress\\Addon\\Multiple_authors\\Classes\\Post_Editor');
class_alias('MultipleAuthors\\Classes\\Query', 'PublishPress\\Addon\\Multiple_authors\\Classes\\Query');
class_alias('MultipleAuthors\\Classes\\Utils', 'PublishPress\\Addon\\Multiple_authors\\Classes\\Utils');
class_alias(
    'MultipleAuthors\\Classes\\Objects\\Author',
    'PublishPress\\Addon\\Multiple_authors\\Classes\\Objects\\Author'
);
class_alias(
    'MultipleAuthors\\Classes\\Integrations\\Theme',
    'PublishPress\\Addon\\Multiple_authors\\Classes\\Integrations\\Theme'
);

if (!defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
    define('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION', PP_AUTHORS_VERSION);
}
