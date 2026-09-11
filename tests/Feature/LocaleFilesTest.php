<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class LocaleFilesTest extends TestCase
{
    /**
     * Every lang/en/ PHP file must return a valid array when included.
     */
    public function test_every_en_file_returns_a_valid_array(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('lang/en'))
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        $this->assertNotEmpty($files, 'No lang/en/*.php files were found.');

        foreach ($files as $file) {
            $values = require $file;

            $this->assertIsArray($values, "File {$file} did not return an array");
        }
    }
}
