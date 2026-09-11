<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\PrinterProviderProfile;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Public XML sitemap at /sitemap.xml — list every page we want indexed
     * plus the homepage + key index pages.
     */
    public function __invoke(): Response
    {
        $now = now()->toAtomString();

        $urls = collect()
            ->push($this->entry(route('home'), $now, 'daily', '1.0'))
            ->push($this->entry(route('browse.designs'), $now, 'daily', '0.9'))
            ->push($this->entry(route('browse.categories'), now()->subDay()->toAtomString(), 'weekly', '0.7'))
            ->push($this->entry(route('browse.designers'), now()->subDay()->toAtomString(), 'weekly', '0.7'));

        // Designs.
        Design::query()
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->select(['id', 'updated_at'])
            ->orderByDesc('updated_at')
            ->chunk(200, function ($chunk) use (&$urls): void {
                foreach ($chunk as $design) {
                    $urls->push($this->entry(
                        route('design.show', $design),
                        $design->updated_at->toAtomString(),
                        'weekly',
                        '0.8',
                    ));
                }
            });

        // Categories.
        Category::query()
            ->select(['id', 'updated_at'])
            ->chunk(200, function ($chunk) use (&$urls): void {
                foreach ($chunk as $category) {
                    $urls->push($this->entry(
                        route('browse.designs', ['category' => $category->id]),
                        $category->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'weekly',
                        '0.6',
                    ));
                }
            });

        // Designers.
        DesignerProfile::query()
            ->with(['user'])
            ->whereHas('publishedDesigns')
            ->select(['id', 'user_id'])
            ->chunk(200, function ($chunk) use (&$urls): void {
                foreach ($chunk as $designer) {
                    $urls->push($this->entry(
                        route('designer.show', $designer),
                        $designer->user?->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'weekly',
                        '0.6',
                    ));
                }
            });

        // Printers.
        PrinterProviderProfile::query()
            ->select(['id', 'updated_at'])
            ->chunk(200, function ($chunk) use (&$urls): void {
                foreach ($chunk as $printer) {
                    $urls->push($this->entry(
                        route('printer.show', $printer),
                        $printer->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'weekly',
                        '0.5',
                    ));
                }
            });

        $body = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL
            .$urls->implode(PHP_EOL).PHP_EOL
            .'</urlset>'.PHP_EOL;

        return response($body, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function entry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
            htmlspecialchars($loc, ENT_XML1),
            htmlspecialchars($lastmod, ENT_XML1),
            $changefreq,
            $priority,
        );
    }
}
