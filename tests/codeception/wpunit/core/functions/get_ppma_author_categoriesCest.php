<?php

namespace core\functions;

use MultipleAuthorCategories\AuthorCategoriesSchema;
use WpunitTester;

class get_ppma_author_categoriesCest
{
    public function testGetAuthorCategoriesCanOrderByPreviouslySupportedColumns(WpunitTester $I)
    {
        global $wpdb;

        AuthorCategoriesSchema::createTableIfNotExists();
        AuthorCategoriesSchema::createMetaTableIfNotExists();

        $tableName = AuthorCategoriesSchema::tableName();
        $categories = [
            [
                'category_name' => 'Older category',
                'plural_name'   => 'Older categories',
                'slug'          => 'older-category',
                'created_at'    => '2024-01-01 00:00:00',
            ],
            [
                'category_name' => 'Newer category',
                'plural_name'   => 'Newer categories',
                'slug'          => 'newer-category',
                'created_at'    => '2025-01-01 00:00:00',
            ],
        ];

        foreach ($categories as $category) {
            $wpdb->insert(
                $tableName,
                array_merge(
                    $category,
                    [
                        'category_order'  => 0,
                        'category_status' => 7,
                        'meta_data'       => $category['slug'],
                    ]
                )
            );
        }

        $results = get_ppma_author_categories(
            [
                'limit'           => 2,
                'orderby'         => 'created_at',
                'order'           => 'DESC',
                'category_status' => 7,
                'no_cache'        => true,
            ]
        );

        $I->assertSame(
            ['newer-category', 'older-category'],
            wp_list_pluck($results, 'slug')
        );

        $results = get_ppma_author_categories(
            [
                'limit'           => 2,
                'orderby'         => 'meta_data',
                'order'           => 'DESC',
                'category_status' => 7,
                'no_cache'        => true,
            ]
        );

        $I->assertSame(
            ['older-category', 'newer-category'],
            wp_list_pluck($results, 'slug')
        );
    }
}
