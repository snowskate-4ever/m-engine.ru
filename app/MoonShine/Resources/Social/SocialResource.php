<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Social;

use Illuminate\Database\Eloquent\Model;
use App\Models\Social;
use App\MoonShine\Resources\Social\Pages\SocialIndexPage;
use App\MoonShine\Resources\Social\Pages\SocialFormPage;
use App\MoonShine\Resources\Social\Pages\SocialDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Social, SocialIndexPage, SocialFormPage, SocialDetailPage>
 */
class SocialResource extends ModelResource
{
    protected string $model = Social::class;

    public function getTitle(): string
    {
        return __('moonshine.socials.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            SocialIndexPage::class,
            SocialFormPage::class,
            SocialDetailPage::class,
        ];
    }
}
