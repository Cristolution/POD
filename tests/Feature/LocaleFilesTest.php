<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleFilesTest extends TestCase
{
    /**
     * Single flat lang/en.php must return a valid, non-empty array.
     */
    public function test_en_file_is_valid_php(): void
    {
        $values = require base_path('lang/en.php');

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Single flat lang/ar.php must return a valid, non-empty array.
     */
    public function test_ar_file_is_valid_php(): void
    {
        $values = require base_path('lang/ar.php');

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Single flat lang/tr.php must return a valid, non-empty array.
     */
    public function test_tr_file_is_valid_php(): void
    {
        $values = require base_path('lang/tr.php');

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Every key present in en.php must also exist in ar.php and tr.php
     * so the placeholder locales stay in sync until Tasks 4/5 translate them.
     */
    public function test_every_en_key_exists_in_ar_and_tr(): void
    {
        $en = require base_path('lang/en.php');
        $ar = require base_path('lang/ar.php');
        $tr = require base_path('lang/tr.php');

        $missingAr = array_diff(array_keys($en), array_keys($ar));
        $missingTr = array_diff(array_keys($en), array_keys($tr));

        $this->assertEmpty($missingAr, 'Missing keys in ar: '.implode(', ', $missingAr));
        $this->assertEmpty($missingTr, 'Missing keys in tr: '.implode(', ', $missingTr));
    }
}
