<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Musician;
use App\Models\ConcertVenue;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use App\Services\Music\MusicPublicSearchService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

final class MusicSitemapController extends Controller
{
    public function __invoke(): Response
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        $push = static function (string $loc) use (&$lines): void {
            $lines[] = '<url><loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc><changefreq>weekly</changefreq></url>';
        };

        URL::forceRootUrl((string) config('app.url'));

        $push(URL::route('discover'));
        foreach (MusicPublicSearchService::scopedDiscoverRouteCategories() as $cat) {
            $push(URL::route('discover.category', ['category' => $cat]));
        }

        $this->appendPublicProfiles($push, Musician::class, 'public.musicians.show');
        $this->appendPublicProfiles($push, Teacher::class, 'public.teachers.show');
        $this->appendPublicProfiles($push, Peformer::class, 'public.performers.show');
        $this->appendPublicProfiles($push, Studio::class, 'public.studios.show');
        $this->appendPublicProfiles($push, Rehersal::class, 'public.rehearsals.show');
        $this->appendPublicProfiles($push, ConcertVenue::class, 'public.concert-venues.show');
        $this->appendPublicProfiles($push, School::class, 'public.schools.show');
        $this->appendPublicProfiles($push, RecordLabel::class, 'public.labels.show');
        $this->appendPublicProfiles($push, ProducerCenter::class, 'public.producer-centers.show');
        $this->appendPublicProfiles($push, Shop::class, 'public.shops.show');

        $lines[] = '</urlset>';
        $body = implode('', $lines);

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * @param  callable(string): void  $push
     * @param  class-string  $modelClass
     */
    private function appendPublicProfiles(callable $push, string $modelClass, string $routeName): void
    {
        $modelClass::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->select(['id', 'slug'])
            ->chunkById(500, function ($chunk) use ($push, $routeName): void {
                foreach ($chunk as $row) {
                    $push(URL::route($routeName, ['slug' => $row->slug]));
                }
            });
    }
}
