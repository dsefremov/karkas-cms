<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stage 0 only: exposes web SAPI ini snapshot for Apache/Docker smoke tests (not for production).
 */
final class Stage0WebSapiController extends AbstractController
{
    #[Route('/_stage0/web-sapi-probe', name: 'stage0_web_sapi_probe', methods: ['GET'])]
    public function probe(): Response
    {
        if ('prod' === $this->getParameter('kernel.environment')) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $disabled = array_values(array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions')))));

        return new JsonResponse([
            'sapi' => PHP_SAPI,
            'disable_functions' => $disabled,
            'shell_exec_callable' => function_exists('shell_exec') && !in_array('shell_exec', $disabled, true),
            'exec_callable' => function_exists('exec') && !in_array('exec', $disabled, true),
        ]);
    }
}
