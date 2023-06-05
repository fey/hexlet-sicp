<?php

namespace Tests\Feature\Http\Controllers;

use Github\Api\Gists;
use GrahamCampbell\GitHub\GitHubManager;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\TestCase;

#[Feature('Sitemap')]
class SitemapControllerTest extends TestCase
{
    #[DisplayName('Sitemap сайта корректна и доступна')]
    public function testIndex(): void
    {
        $gists = $this->createMock(Gists::class);
        $gists->method('show')->willReturn(
            $this->getFixture()
        );

        $github = $this->getMockBuilder(GitHubManager::class)
            ->disableOriginalConstructor()
            ->addMethods(['gists'])
            ->getMock();
        $github->method('gists')->willReturn($gists);

        $this->app->instance(GitHubManager::class, $github);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
    }

    private function getFixture(): array
    {
        $sitemapFilepath = base_path('tests/fixtures/sitemap.xml');
        return [
            "files" => [
                "sitemap.xml" => [
                    "content" => file_get_contents($sitemapFilepath),
                ],
            ],
        ];
    }
}
