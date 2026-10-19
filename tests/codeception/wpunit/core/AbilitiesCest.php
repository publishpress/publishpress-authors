<?php

class AbilitiesCest
{
    public function tryToRegisterReadOnlyAbilitiesWhenTheCoreApiIsAvailable(WpunitTester $I)
    {
        if (!function_exists('wp_get_ability')) {
            $I->markTestSkipped('The Abilities API requires WordPress 6.9 or later.');
            return;
        }

        $ability_names = [
            'publishpress-authors/list-authors',
            'publishpress-authors/get-author',
            'publishpress-authors/get-post-authors',
            'publishpress-authors/get-author-fields',
        ];

        foreach ($ability_names as $ability_name) {
            $I->assertNotNull(
                wp_get_ability($ability_name),
                sprintf('Expected ability "%s" to be registered.', $ability_name)
            );
        }
    }
}
