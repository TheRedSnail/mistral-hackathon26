<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AiController extends CommonController
{
    public function indexAction(Request $request, CoreParametersHelper $params): Response
    {
        $enabled = (bool) $params->get('odradek_ai_enabled');
        $apiKey  = (string) $params->get('odradek_ai_api_key');
        $model   = (string) ($params->get('odradek_ai_model') ?: 'mistral-large-latest');

        if (!$enabled || empty($apiKey)) {
            // Render a notice page using Mautic's default layout
            return $this->delegateView([
                'contentTemplate' => '@OdradekAI/Ai/not_enabled.html.twig',
                'viewParameters'  => [
                    'enabled' => $enabled,
                    'hasKey'  => !empty($apiKey),
                ],
                'passthroughVars' => [
                    'activeLink'     => '#odradek_ai',
                    'mauticContent'  => 'odradekAi',
                ],
            ]);
        }

        // Mautic's sidebar uses AJAX navigation (XMLHttpRequest). The split-screen
        // UI needs the full viewport, so redirect the browser to do a real page load.
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['redirect' => $this->generateUrl('odradek_ai_index')]);
        }

        // Return a standalone response (no Mautic chrome) so our split-screen
        // layout can use the full viewport.
        $twig = $this->container->get('twig');

        $html = $twig->render('@OdradekAI/Ai/index.html.twig', [
            'assetBase'  => '/plugins/OdradekAIBundle/Assets',
            'model'      => $model,
            'chatUrl'    => $this->generateUrl('odradek_ai_chat'),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
