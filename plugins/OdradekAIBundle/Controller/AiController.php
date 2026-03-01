<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AiController extends CommonController
{
    public function indexAction(Request $request, CoreParametersHelper $params, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Security: Only Mautic admins may access the AI UI
        $user = $this->getUser();
        if (!$user || !$user->isAdmin()) {
            return new Response('Forbidden', 403);
        }

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

        $csrfToken = $csrfTokenManager->getToken('odradek_ai_chat')->getValue();

        $user = $this->getUser();

        $html = $twig->render('@OdradekAI/Ai/index.html.twig', [
            'assetBase'  => '/plugins/OdradekAIBundle/Assets',
            'model'      => $model,
            'chatUrl'    => $this->generateUrl('odradek_ai_chat'),
            'csrfToken'  => $csrfToken,
            'userName'   => $user ? ($user->getFirstName() ?: $user->getUsername()) : 'User',
            'apiKeySet'  => !empty($apiKey),
        ]);

        return new Response($html, 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' =>
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data:; " .
                "frame-src 'self'; " .
                "connect-src 'self';",
        ]);
    }

    /**
     * Panel-only view: renders just the AI chat pane (no Mautic iframe).
     * Loaded inside the injected bottom-panel iframe on every Mautic page.
     */
    public function panelAction(Request $request, CoreParametersHelper $params, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Security: Only Mautic admins may access the AI panel
        $user = $this->getUser();
        if (!$user || !$user->isAdmin()) {
            return new Response('Forbidden', 403);
        }

        $enabled = (bool) $params->get('odradek_ai_enabled');
        $apiKey  = (string) $params->get('odradek_ai_api_key');
        $model   = (string) ($params->get('odradek_ai_model') ?: 'mistral-large-latest');

        if (!$enabled || empty($apiKey)) {
            return new Response('<html><body style="background:#0d1117;color:#8b949e;font-family:sans-serif;padding:40px;text-align:center"><p>Odradek AI is not enabled. Configure it in Settings → AI Settings.</p></body></html>', 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $csrfToken = $csrfTokenManager->getToken('odradek_ai_chat')->getValue();
        $user      = $this->getUser();

        $twig = $this->container->get('twig');
        $html = $twig->render('@OdradekAI/Ai/panel.html.twig', [
            'assetBase'  => '/plugins/OdradekAIBundle/Assets',
            'model'      => $model,
            'chatUrl'    => $this->generateUrl('odradek_ai_chat'),
            'csrfToken'  => $csrfToken,
            'userName'   => $user ? ($user->getFirstName() ?: $user->getUsername()) : 'User',
            'apiKeySet'  => !empty($apiKey),
        ]);

        return new Response($html, 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'Content-Security-Policy' =>
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data:; " .
                "connect-src 'self';",
        ]);
    }
}
