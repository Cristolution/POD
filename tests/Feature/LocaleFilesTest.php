<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleFilesTest extends TestCase
{
    /**
     * Single flat lang/en.json must be a valid, non-empty JSON object.
     */
    public function test_en_file_is_valid_json(): void
    {
        $values = json_decode(file_get_contents(base_path('lang/en.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Single flat lang/ar.json must be a valid, non-empty JSON object.
     */
    public function test_ar_file_is_valid_json(): void
    {
        $values = json_decode(file_get_contents(base_path('lang/ar.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Single flat lang/tr.json must be a valid, non-empty JSON object.
     */
    public function test_tr_file_is_valid_json(): void
    {
        $values = json_decode(file_get_contents(base_path('lang/tr.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
    }

    /**
     * Every key present in en.json must also exist in ar.json and tr.json
     * so the placeholder locales stay in sync until Tasks 4/5 translate them.
     */
    public function test_every_en_key_exists_in_ar_and_tr(): void
    {
        $en = json_decode(file_get_contents(base_path('lang/en.json')), true, 512, JSON_THROW_ON_ERROR);
        $ar = json_decode(file_get_contents(base_path('lang/ar.json')), true, 512, JSON_THROW_ON_ERROR);
        $tr = json_decode(file_get_contents(base_path('lang/tr.json')), true, 512, JSON_THROW_ON_ERROR);

        $missingAr = array_diff(array_keys($en), array_keys($ar));
        $missingTr = array_diff(array_keys($en), array_keys($tr));

        $this->assertEmpty($missingAr, 'Missing keys in ar: '.implode(', ', $missingAr));
        $this->assertEmpty($missingTr, 'Missing keys in tr: '.implode(', ', $missingTr));
    }
}
