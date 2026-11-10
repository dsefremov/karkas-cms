<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleMountRepository;
use Karkas\Application\Blog\BlogPostRepository;
use Karkas\Application\Site\SiteRequestResolver;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicBlogController extends AbstractController
{
    public function __construct(
        private readonly BlogPostRepository $posts,
        private readonly ModuleInstanceRepository $instances,
        private readonly ModuleMountRepository $mounts,
        private readonly SiteRequestResolver $siteResolver,
    ) {
    }

    #[Route('/blog', name: 'public_blog_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        try {
            $site = $this->siteResolver->resolvePublicSite($request);
        } catch (RuntimeException) {
            return new Response('Unknown host', Response::HTTP_NOT_FOUND);
        }
        $siteId = $site['id'];
        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null === $instance) {
            return new Response('Blog module is not enabled', Response::HTTP_NOT_FOUND);
        }
        $mount = $this->mounts->resolveByPrefix($siteId, '/blog');
        if (null === $mount) {
            return new Response('Blog mount missing', Response::HTTP_NOT_FOUND);
        }
        $articles = $this->posts->listPublic($siteId, $instance['id']);

        return $this->render('blog/list.html.twig', [
            'site_label' => $site['label'],
            'articles' => $articles,
        ]);
    }

    #[Route('/blog/{slug}', name: 'public_blog_post', methods: ['GET'], requirements: ['slug' => '[a-zA-Z0-9\-]+'])]
    public function show(Request $request, string $slug): Response
    {
        try {
            $site = $this->siteResolver->resolvePublicSite($request);
        } catch (RuntimeException) {
            return new Response('Unknown host', Response::HTTP_NOT_FOUND);
        }
        $siteId = $site['id'];
        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null === $instance) {
            return new Response('Blog module is not enabled', Response::HTTP_NOT_FOUND);
        }
        $resolved = $this->posts->resolvePublishedSlug($siteId, $slug);
        if (null !== $resolved && $resolved !== $slug) {
            return $this->redirect('/blog/'.$resolved, Response::HTTP_MOVED_PERMANENTLY);
        }
        $post = $this->posts->findActiveBySlug($siteId, $instance['id'], $slug);
        if (null === $post) {
            return new Response('Article not found', Response::HTTP_NOT_FOUND);
        }

        return $this->render('blog/post.html.twig', [
            'site_label' => $site['label'],
            'post' => $post,
        ]);
    }
}
