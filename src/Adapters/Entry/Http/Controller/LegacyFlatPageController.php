<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Content\SitePageRepository;
use Karkas\Application\Site\SiteRequestResolver;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyFlatPageController extends AbstractController
{
    public function __construct(
        private readonly SitePageRepository $pages,
        private readonly SiteRequestResolver $siteResolver,
    ) {
    }

    #[Route(
        '/p/{slug}',
        name: 'legacy_flat_page',
        methods: ['GET'],
        requirements: ['slug' => '[a-z0-9][a-z0-9\-]*'],
        priority: 10,
    )]
    public function legacyFlatRedirect(Request $request, string $slug): Response
    {
        try {
            $site = $this->siteResolver->resolvePublicSite($request);
        } catch (RuntimeException) {
            return new Response('Unknown host', Response::HTTP_NOT_FOUND);
        }
        $siteId = (int) $site['id'];
        $slug = strtolower($slug);
        $candidate = $this->pages->findBySlugOnSite($siteId, $slug);
        if (null === $candidate) {
            return new Response('Page not found', Response::HTTP_NOT_FOUND);
        }
        $fullPath = (string) $candidate['full_path'];

        return $this->redirect('/'.$fullPath, Response::HTTP_MOVED_PERMANENTLY);
    }
}
