<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Injects a collapsible AI chat panel into every Mautic admin page.
 *
 * The panel renders as a fixed bottom bar. When expanded it loads
 * the chat-only panel endpoint in an iframe. State (open/closed)
 * is persisted in localStorage.
 */
class InjectPanelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CoreParametersHelper  $params,
        private readonly UrlGeneratorInterface  $router,
        private readonly TokenStorageInterface  $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Only inject when AI is enabled and has an API key
        $enabled = (bool) $this->params->get('odradek_ai_enabled');
        $apiKey  = (string) $this->params->get('odradek_ai_api_key');
        if (!$enabled || empty($apiKey)) {
            return;
        }

        // Only inject for authenticated admin users
        $token = $this->tokenStorage->getToken();
        if (!$token || !is_object($token->getUser())) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();

        // Only process HTML responses
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        // Skip AJAX/JSON/SSE responses
        if ($request->isXmlHttpRequest()) {
            return;
        }

        // Skip the standalone AI page and the panel page (avoid recursion)
        $route = $request->attributes->get('_route', '');
        if (str_starts_with($route, 'odradek_ai')) {
            return;
        }

        // Skip non-admin routes (public pages, API, etc.)
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/s/')) {
            return;
        }

        // Skip redirects and non-200 responses
        if ($response->getStatusCode() !== 200) {
            return;
        }

        $html = $response->getContent();
        if (!$html || !str_contains($html, '</body>')) {
            return;
        }

        $panelUrl = $this->router->generate('odradek_ai_panel');
        $snippet  = $this->buildPanelSnippet($panelUrl);

        $html = str_replace('</body>', $snippet . '</body>', $html);
        $response->setContent($html);
    }

    private function buildPanelSnippet(string $panelUrl): string
    {
        $snippet = <<<'HTML'
<!-- Odradek AI — Always-on Panel -->
<style>
#odradek-inject-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 99999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    pointer-events: none;
}

#odradek-inject-bar * {
    pointer-events: auto;
}

#odradek-inject-toggle {
    position: relative;
    z-index: 2;
    float: right;
    margin-right: 24px;
    display: flex;
    align-items: center;
    gap: 6px;
    background: #161b22;
    color: #c9d1d9;
    border: 1px solid #30363d;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    padding: 5px 14px 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, box-shadow 0.15s;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.3);
    font-family: inherit;
    letter-spacing: 0.3px;
}

#odradek-inject-toggle:hover {
    background: #1c2128;
    box-shadow: 0 -2px 12px rgba(0,200,168,0.15);
}

#odradek-inject-toggle .otgl-logo {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

#odradek-inject-toggle .otgl-caret {
    font-size: 10px;
    transition: transform 0.2s;
    margin-left: 2px;
}

#odradek-inject-bar.panel-open #odradek-inject-toggle .otgl-caret {
    transform: rotate(180deg);
}

#odradek-inject-frame-wrap {
    display: none;
    clear: both;
    height: 420px;
    background: #0d1117;
    border-top: 1px solid #30363d;
    box-shadow: 0 -4px 24px rgba(0,0,0,0.5);
    position: relative;
}

#odradek-inject-bar.panel-open #odradek-inject-frame-wrap {
    display: block;
}

#odradek-inject-frame {
    width: 100%;
    height: 100%;
    border: none;
    background: #0d1117;
}

#odradek-inject-resize {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    cursor: ns-resize;
    background: transparent;
}

#odradek-inject-resize:hover,
#odradek-inject-resize.dragging {
    background: rgba(0,200,168,0.3);
}
</style>

<div id="odradek-inject-bar">
    <button id="odradek-inject-toggle" title="Toggle Odradek AI panel">
        <svg class="otgl-logo" width="16" height="16" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="16" cy="16" r="14" stroke="#007A68" stroke-width="0.75" stroke-dasharray="2.5 2" opacity="0.4"/>
            <line x1="16" y1="2" x2="16" y2="30" stroke="#00A088" stroke-width="1.2" stroke-linecap="round" opacity="0.8"/>
            <line x1="2" y1="16" x2="30" y2="16" stroke="#00A088" stroke-width="1.2" stroke-linecap="round" opacity="0.8"/>
            <line x1="4.69" y1="4.69" x2="27.31" y2="27.31" stroke="#00C4A8" stroke-width="1.2" stroke-linecap="round" opacity="0.65"/>
            <line x1="27.31" y1="4.69" x2="4.69" y2="27.31" stroke="#00C4A8" stroke-width="1.2" stroke-linecap="round" opacity="0.65"/>
            <circle cx="16" cy="16" r="5" fill="#0d1117" stroke="#00C4A8" stroke-width="1.5"/>
            <circle cx="16" cy="16" r="2.5" fill="#00C4A8"/>
            <circle cx="16" cy="16" r="1" fill="#c9d1d9"/>
        </svg>
        <span>ODRADEK AI</span>
        <span class="otgl-caret">&#9650;</span>
    </button>

    <div id="odradek-inject-frame-wrap">
        <div id="odradek-inject-resize" title="Drag to resize"></div>
        <iframe id="odradek-inject-frame" src="about:blank" loading="lazy"></iframe>
    </div>
</div>

<script>
(function() {
    'use strict';
    var PANEL_URL = ODRADEK_PANEL_URL_PLACEHOLDER;
    var KEY       = 'odradek_panel_state';
    var KEY_H     = 'odradek_panel_height';

    var bar       = document.getElementById('odradek-inject-bar');
    var toggle    = document.getElementById('odradek-inject-toggle');
    var wrap      = document.getElementById('odradek-inject-frame-wrap');
    var frame     = document.getElementById('odradek-inject-frame');
    var resizer   = document.getElementById('odradek-inject-resize');
    var loaded    = false;

    // Restore saved height
    var savedH = parseInt(localStorage.getItem(KEY_H), 10);
    if (savedH && savedH > 200) {
        wrap.style.height = savedH + 'px';
    }

    function openPanel() {
        bar.classList.add('panel-open');
        localStorage.setItem(KEY, 'open');
        if (!loaded) {
            frame.src = PANEL_URL;
            loaded = true;
        }
        // Send current page context to the panel iframe
        setTimeout(sendPageContext, 600);
    }

    function closePanel() {
        bar.classList.remove('panel-open');
        localStorage.setItem(KEY, 'closed');
    }

    toggle.addEventListener('click', function() {
        if (bar.classList.contains('panel-open')) {
            closePanel();
        } else {
            openPanel();
        }
    });

    // Drag to resize
    var dragging = false, startY = 0, startH = 0;

    resizer.addEventListener('mousedown', function(e) {
        dragging = true;
        startY = e.clientY;
        startH = wrap.getBoundingClientRect().height;
        resizer.classList.add('dragging');
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'ns-resize';
        if (frame) frame.style.pointerEvents = 'none';
        e.preventDefault();
    });

    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        var delta = startY - e.clientY; // dragging up increases height
        var newH = Math.max(200, Math.min(window.innerHeight - 60, startH + delta));
        wrap.style.height = newH + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        resizer.classList.remove('dragging');
        document.body.style.userSelect = '';
        document.body.style.cursor = '';
        if (frame) frame.style.pointerEvents = '';
        localStorage.setItem(KEY_H, String(parseInt(wrap.style.height, 10)));
    });

    // Send page context to panel iframe
    function sendPageContext() {
        if (!frame || !frame.contentWindow) return;
        try {
            var ctx = {
                type: 'odradek_page_context',
                url: window.location.href,
                title: document.title,
                visibleText: (document.querySelector('.content-body') ||
                              document.querySelector('#app-content') ||
                              document.body).innerText.substring(0, 3000)
            };
            frame.contentWindow.postMessage(ctx, '*');
        } catch(e) { /* cross-origin safety */ }
    }

    // Listen for navigation requests from the panel
    window.addEventListener('message', function(e) {
        if (!e.data || e.data.type !== 'odradek_navigate') return;
        var path = e.data.path;
        if (path && typeof path === 'string' && path.startsWith('/')) {
            window.location.href = path;
        }
    });

    // Re-send context whenever Mautic does SPA navigation
    var lastUrl = window.location.href;
    setInterval(function() {
        if (window.location.href !== lastUrl) {
            lastUrl = window.location.href;
            if (bar.classList.contains('panel-open')) {
                setTimeout(sendPageContext, 500);
            }
        }
    }, 1000);

    // Restore saved state
    if (localStorage.getItem(KEY) === 'open') {
        openPanel();
    }
})();
</script>
HTML;
        // Replace the placeholder with the actual URL
        return str_replace(
            'ODRADEK_PANEL_URL_PLACEHOLDER',
            json_encode($panelUrl),
            $snippet
        );
    }
}
