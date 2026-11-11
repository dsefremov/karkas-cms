<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Search\SiteSearchService;
use Karkas\Application\Site\SiteRequestResolver;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicSearchController extends AbstractController
{
    public function __construct(
        private readonly SiteSearchService $search,
        private readonly SiteRequestResolver $siteResolver,
    ) {
    }

    #[Route('/search', name: 'public_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        try {
            $site = $this->siteResolver->resolvePublicSite($request);
        } catch (RuntimeException) {
            return new Response('Unknown host', Response::HTTP_NOT_FOUND);
        }
        $q = (string) $request->query->get('q', '');
        $page = (int) $request->query->get('page', '1');
        $perPage = (int) $request->query->get('per_page', (string) SiteSearchService::DEFAULT_PER_PAGE);
        $payload = $this->search->search((int) $site['id'], $q, $page, $perPage);
        $totalPages = $payload['total'] > 0
            ? (int) ceil($payload['total'] / $payload['per_page'])
            : 0;

        return $this->render('search/results.html.twig', [
            'site_label' => $site['label'],
            'payload' => $payload,
            'total_pages' => $totalPages,
        ]);
    }
}
