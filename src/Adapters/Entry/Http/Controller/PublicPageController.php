<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Content\SitePageRepository;
use Karkas\Application\Site\SiteRequestResolver;
use Karkas\Domain\Content\SitePageTree;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicPageController extends AbstractController
{
    public function __construct(
        private readonly SitePageRepository $pages,
        private readonly SiteRequestResolver $siteResolver,
    ) {
    }

    #[Route(
        '/{pagePath}',
        name: 'public_page',
        methods: ['GET'],
        requirements: ['pagePath' => '[a-zA-Z0-9\/\-]+'],
        priority: -100,
    )]
    public function show(Request $request, string $pagePath): Response
    {
        try {
            $site = $this->siteResolver->resolvePublicSite($request);
        } catch (RuntimeException) {
            return new Response('Unknown host', Response::HTTP_NOT_FOUND);
        }
        $siteId = (int) $site['id'];
        $normalized = SitePageTree::normalizePath($pagePath);

        $resolved = $this->pages->findRedirectTarget($siteId, $normalized);
        if (null !== $resolved && $resolved !== $normalized) {
            return $this->redirect('/'.$resolved, Response::HTTP_MOVED_PERMANENTLY);
        }

        $page = $this->pages->findActiveByPath($siteId, $normalized);
        if (null === $page) {
            return new Response('Page not found', Response::HTTP_NOT_FOUND);
        }

        return $this->render('page/show.html.twig', [
            'site_label' => $site['label'],
            'page' => $page,
        ]);
    }
}
