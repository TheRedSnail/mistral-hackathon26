/**
 * Odradek AI — Frontend Logic
 * Handles: split-screen resize, iframe navigation, element selector,
 *          context capture, SSE chat loop, plan mode, tool visualization.
 */
(function () {
    'use strict';

    // ── Panel mode (when embedded as bottom panel in Mautic pages) ──────────
    const PANEL_MODE = !!window.ODRADEK_PANEL_MODE;

    // ── Debug logging (only when window.ODRADEK_DEBUG === true) ─────────────
    const DEBUG = window.ODRADEK_DEBUG === true;
    function dbg(...a)     { if (DEBUG) console.log(...a); }
    function dbgWarn(...a) { if (DEBUG) console.warn(...a); }

    // ── DOM refs ────────────────────────────────────────────────────────────
    const splitEl      = document.getElementById('odradek-split');
    const mauticPane   = document.getElementById('odradek-mautic-pane');
    const aiPane       = document.getElementById('odradek-ai-pane');
    const dividerEl    = document.getElementById('odradek-divider');
    const headerEl     = document.getElementById('odradek-header');
    const minimizeBtn  = document.getElementById('odradek-minimize');
    const iframe       = document.getElementById('odradek-mautic-frame');
    const urlDisplay   = document.getElementById('odradek-url-display');
    const selectBtn    = document.getElementById('odradek-select-btn');
    const captureBtn   = document.getElementById('odradek-capture-btn');
    const backBtn      = document.getElementById('odradek-back');
    const forwardBtn   = document.getElementById('odradek-forward');
    const messagesEl   = document.getElementById('odradek-messages');
    const inputEl      = document.getElementById('odradek-input');
    const sendBtn      = document.getElementById('odradek-send');
    const clearBtn     = document.getElementById('odradek-clear');
    const chipsEl      = document.getElementById('odradek-context-chips');
    const planModeChk  = document.getElementById('odradek-plan-mode');

    // ── Status bar refs ─────────────────────────────────────────────────────
    const statusBar    = document.getElementById('odradek-status-bar');
    const statusPhrase = document.getElementById('odradek-status-phrase');
    const statusTimer  = document.getElementById('odradek-status-timer');

    // ── Nav rail + view refs ─────────────────────────────────────────────────
    const navChatBtn    = document.getElementById('odradek-nav-chat');
    const navContextBtn = document.getElementById('odradek-nav-context');
    const viewChat      = document.getElementById('odradek-view-chat');
    const viewContext   = document.getElementById('odradek-view-context');
    const ctxSavedEl    = document.getElementById('odradek-ctx-saved');

    const CHAT_URL     = window.ODRADEK_CHAT_URL || '/odradek/ai/chat';

    // ── State ───────────────────────────────────────────────────────────────
    const state = {
        messages:             [],   // [{role, content}] sent to backend
        contextItems:         [],   // [{type, label, data}]
        selectMode:           false,
        busy:                 false,
        pendingPlanMessages:  null, // messages saved when plan shown
        // Exchange card state
        currentCard:          null, // active .exchange-card DOM node
        currentActivityEl:    null, // .exchange-activities div
        currentMainBody:      null, // .msg-body inside .exchange-main
        currentActivityCount: 0,    // badge count
    };

    // ── AI Context — localStorage constants & field map ─────────────────────
    const CTX_STORAGE_KEY  = 'odradek_ai_context_v1';
    const CONV_STORAGE_KEY = 'odradek_ai_conv_v1';
    const CTX_FIELDS = [
        { key: 'company_name',     el: 'ctx-company-name'     },
        { key: 'industry',         el: 'ctx-industry'          },
        { key: 'logo_url',         el: 'ctx-logo-url'          },
        { key: 'brand_guidelines', el: 'ctx-brand-guidelines'  },
        { key: 'tone_of_voice',    el: 'ctx-tone-of-voice'     },
        { key: 'target_personas',  el: 'ctx-target-personas'   },
        { key: 'marketing_goals',  el: 'ctx-marketing-goals'   },
        { key: 'key_products',     el: 'ctx-key-products'      },
        { key: 'compliance_notes', el: 'ctx-compliance-notes'  },
        { key: 'other_context',    el: 'ctx-other-context'     },
    ];

    // ── Panel mode: receive page context from parent via postMessage ────────
    let parentPageContext = null;
    if (PANEL_MODE) {
        window.addEventListener('message', (e) => {
            if (e.data && e.data.type === 'odradek_page_context') {
                parentPageContext = {
                    url:         e.data.url   || '',
                    title:       e.data.title || '',
                    visibleText: e.data.visibleText || '',
                };
                // Auto-add page context chip on first reception
                if (state.contextItems.length === 0 && parentPageContext.url) {
                    addContextChip('page', parentPageContext.title || parentPageContext.url, {
                        url:         parentPageContext.url,
                        pageTitle:   parentPageContext.title,
                        visibleText: parentPageContext.visibleText,
                    });
                }
            }
        });
    }

    // ── Expand / collapse AI panel ───────────────────────────────────────────
    function expandAI() {
        aiPane.classList.add('ai-expanded');
        if (dividerEl) dividerEl.classList.add('ai-visible');
        inputEl.focus();
    }

    function collapseAI() {
        aiPane.classList.remove('ai-expanded');
        if (dividerEl) dividerEl.classList.remove('ai-visible');
        aiPane.style.height = ''; // revert to CSS default (38px)
    }

    // ── View switching (chat ↔ context panel) ────────────────────────────────
    let currentView = 'chat';
    function switchView(view) {
        currentView = view;
        const isChat = view === 'chat';
        if (viewChat)      viewChat.classList.toggle('view-hidden', !isChat);
        if (viewContext)   viewContext.classList.toggle('view-hidden', isChat);
        if (navChatBtn)    navChatBtn.classList.toggle('nav-active', isChat);
        if (navContextBtn) navContextBtn.classList.toggle('nav-active', !isChat);
    }
    if (navChatBtn)    navChatBtn.addEventListener('click', () => switchView('chat'));
    if (navContextBtn) navContextBtn.addEventListener('click', () => switchView('context'));

    // Click header to expand when collapsed
    headerEl.addEventListener('click', (e) => {
        if (e.target === minimizeBtn || e.target === clearBtn) return;
        if (!aiPane.classList.contains('ai-expanded')) expandAI();
    });

    // Minimize button collapses
    minimizeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        collapseAI();
    });

    // ── Drag-to-resize divider ───────────────────────────────────────────────
    if (!PANEL_MODE) {
        (function initDivider() {
            let dragging = false;
            let startY   = 0;
            let startH   = 0;

            dividerEl.addEventListener('mousedown', (e) => {
                dragging  = true;
                startY    = e.clientY;
                startH    = aiPane.getBoundingClientRect().height;
                dividerEl.classList.add('dragging');
                document.body.style.userSelect = 'none';
                document.body.style.cursor     = 'ns-resize';
                iframe.style.pointerEvents = 'none';
            });

            document.addEventListener('mousemove', (e) => {
                if (!dragging) return;
                const delta  = e.clientY - startY;           // positive = dragged down
                const totalH = splitEl.getBoundingClientRect().height;
                const newH   = Math.max(120, Math.min(totalH - 80, startH - delta));
                aiPane.style.height = `${newH}px`;
            });

            document.addEventListener('mouseup', () => {
                if (!dragging) return;
                dragging = false;
                dividerEl.classList.remove('dragging');
                document.body.style.userSelect = '';
                document.body.style.cursor     = '';
                iframe.style.pointerEvents     = '';
            });
        })();
    }

    // ── iframe navigation tracking ───────────────────────────────────────────
    if (!PANEL_MODE) {
        iframe.addEventListener('load', () => {
            try {
                const iDoc  = iframe.contentDocument || iframe.contentWindow.document;
                const iWin  = iframe.contentWindow;
                const url   = iWin.location.href;
                urlDisplay.textContent = url;
            } catch (_) {
                urlDisplay.textContent = iframe.src;
            }
            // Reset binding state on every navigation; setupGjsTracking handles
            // whether GrapesJS is actually present (no URL guard needed there).
            gjsListenerReady = false;
            gjsEditor        = null;
            gjsSelected      = null;
            gjsSelectedAll   = [];
            clearGjsComponentChip();
            setupGjsTracking();   // starts a fresh polling chain (cancels any stale one)
        });

        backBtn.addEventListener('click', () => {
            try { iframe.contentWindow.history.back(); } catch (_) {}
        });

        forwardBtn.addEventListener('click', () => {
            try { iframe.contentWindow.history.forward(); } catch (_) {}
        });
    }

    function navigateIframe(path) {
        if (!path || typeof path !== 'string') return;
        // Reject external URLs and protocol-relative URLs
        if (/^(https?:)?\/\//i.test(path)) return;
        const safePath = path.startsWith('/') ? path : '/' + path;
        // In panel mode, navigate the parent Mautic page
        if (PANEL_MODE) {
            try {
                window.parent.postMessage({ type: 'odradek_navigate', path: safePath }, '*');
            } catch (_) {}
            return;
        }
        iframe.src = window.location.origin + safePath;
    }

    function reloadIframe() {
        if (PANEL_MODE) {
            try { window.parent.postMessage({ type: 'odradek_navigate', path: window.parent.location.pathname }, '*'); } catch (_) {}
            return;
        }
        try {
            iframe.contentWindow.location.reload();
        } catch (_) {
            iframe.src = iframe.src;
        }
    }

    // ── Element selector mode ────────────────────────────────────────────────
    let selectorCleanup  = null;
    let gjsChipId        = null;   // numeric ID of the current GrapesJS selection chip
    let gjsListenerReady = false;  // whether we've bound to this page's GrapesJS editor
    let gjsEditor        = null;   // cached reference to the GrapesJS editor instance
    let gjsSelected           = null;   // reference to the currently selected GrapesJS component (first of selection)
    let gjsSelectedAll        = [];     // all currently selected components (multi-select aware)
    let gjsSelectionDebounce  = null;   // timer for debounced getSelectedAll() read
    let gjsRetryTimer         = null;   // handle for the in-flight tryBind timer

    function setupGjsTracking() {
        // Cancel any in-flight polling chain from a previous load event
        if (gjsRetryTimer) { clearTimeout(gjsRetryTimer); gjsRetryTimer = null; }
        if (gjsListenerReady) return;

        let iWin, iDoc;
        try {
            iWin = iframe.contentWindow;
            iDoc = iframe.contentDocument;
            if (!iWin) return;
        } catch (_) { return; }

        // ── Bind to a resolved editor instance ──────────────────────────────
        function bindToEditor(editor) {
            if (gjsListenerReady) return;
            if (gjsRetryTimer) { clearTimeout(gjsRetryTimer); gjsRetryTimer = null; }
            gjsEditor        = editor;
            gjsListenerReady = true;
            dbg('[OdradekGJS] bound to editor ✓');

            function syncSelection() {
                if (gjsSelectionDebounce) clearTimeout(gjsSelectionDebounce);
                gjsSelectionDebounce = setTimeout(function () {
                    gjsSelectionDebounce = null;
                    const all = gjsEditor.getSelectedAll ? gjsEditor.getSelectedAll() : [];
                    if (all.length) {
                        gjsSelectedAll = all;
                        gjsSelected    = all[0];  // keep single-ref for backward compat
                        dbg('[OdradekGJS] syncSelection — selected', all.length, 'component(s)');
                        buildGjsChip(all);
                    } else {
                        // Nothing live-selected (e.g. user clicked canvas background).
                        // Preserve gjsSelectedAll as fallback; just clear the visual chip.
                        dbg('[OdradekGJS] syncSelection — nothing selected, preserving cache');
                        clearGjsComponentChip();
                    }
                }, 0);  // next tick — lets GrapesJS finish its own state update first
            }

            editor.on('component:selected',   syncSelection);
            editor.on('component:deselected', syncSelection);
        }

        // ── Poll / bind ──────────────────────────────────────────────────────
        // GrapesJS in Mautic is bundled as a webpack IIFE so window.grapesjs is
        // NOT set. The builder exposes the editor via a jQuery event:
        //   $builder.trigger('builder:show', [editor])
        // We catch that via mQuery. We also keep polling window.grapesjs.editors
        // as a fallback for setups that do expose it.
        let attempts        = 0;
        let jqBound         = false;  // whether we've attached the jQuery listener

        function tryBind() {
            gjsRetryTimer = null;
            if (gjsListenerReady) return;
            attempts++;

            try {
                // Method A: window.grapesjs.editors (works when GrapesJS is global)
                const gjs     = iWin.grapesjs;
                const editors = gjs && gjs.editors;
                if (editors && editors.length) {
                    dbg('[OdradekGJS] found via grapesjs.editors on attempt', attempts);
                    bindToEditor(editors[editors.length - 1]);
                    return;
                }

                // Method B: intercept Mautic's builder:show jQuery event
                const jq        = iWin.mQuery || iWin.jQuery || iWin.$;
                const builderEl = iDoc && iDoc.querySelector('.builder');
                if (jq && builderEl && !jqBound) {
                    jqBound = true;
                    dbg('[OdradekGJS] attaching builder:show listener via mQuery');
                    jq(builderEl).off('builder:show.odradek').on('builder:show.odradek', function (evt, editor) {
                        dbg('[OdradekGJS] builder:show event caught, editor=', !!editor);
                        if (editor) bindToEditor(editor);
                    });
                } else if (!jqBound) {
                    dbg('[OdradekGJS] attempt', attempts,
                        '— window.grapesjs:', !!(gjs),
                        'mQuery:', !!jq,
                        '.builder element:', !!builderEl);
                }
            } catch (e) {
                dbgWarn('[OdradekGJS] tryBind error (attempt', attempts, '):', e);
            }

            // Keep polling: user might not have clicked the Builder button yet
            if (attempts < 60) gjsRetryTimer = setTimeout(tryBind, 500);
            else dbgWarn('[OdradekGJS] gave up polling after 30 s');
        }

        tryBind();
    }

    function enableSelectMode() {
        state.selectMode = true;
        selectBtn.classList.add('active');
        selectBtn.textContent = '✕ Cancel';

        const iDoc = iframe.contentDocument;
        if (!iDoc) { disableSelectMode(); return; }

        let lastEl = null;

        function onOver(e) {
            if (lastEl && lastEl !== e.target) {
                lastEl.classList.remove('odradek-highlight-overlay');
            }
            e.target.classList.add('odradek-highlight-overlay');
            lastEl = e.target;
            e.stopPropagation();
        }

        function onClick(e) {
            e.preventDefault();
            e.stopPropagation();

            const el      = e.target;
            const text    = (el.innerText || el.textContent || '').trim().slice(0, 300);
            const tag     = el.tagName.toLowerCase();
            const cssPath = getCssPath(el);

            el.classList.remove('odradek-highlight-overlay');
            addContextChip('element', `<${tag}>: ${text.slice(0, 30) || cssPath}`, {
                tag, text, cssPath,
            });

            disableSelectMode();
        }

        iDoc.addEventListener('mouseover', onOver, true);
        iDoc.addEventListener('click',     onClick, true);

        selectorCleanup = () => {
            iDoc.removeEventListener('mouseover', onOver, true);
            iDoc.removeEventListener('click',     onClick, true);
            if (lastEl) lastEl.classList.remove('odradek-highlight-overlay');
        };
    }

    function disableSelectMode() {
        state.selectMode = false;
        selectBtn.classList.remove('active');
        selectBtn.textContent = '⊹ Select';
        if (selectorCleanup) { selectorCleanup(); selectorCleanup = null; }
    }

    if (!PANEL_MODE) {
        selectBtn.addEventListener('click', () => {
            if (state.selectMode) disableSelectMode();
            else                  enableSelectMode();
        });
    }

    function getCssPath(el) {
        const parts = [];
        while (el && el.nodeType === 1 && el.tagName.toLowerCase() !== 'body') {
            let seg = el.tagName.toLowerCase();
            if (el.id)  seg += '#' + el.id;
            else if (el.className) seg += '.' + String(el.className).trim().split(/\s+/).join('.');
            parts.unshift(seg);
            el = el.parentElement;
        }
        return parts.join(' > ');
    }

    // ── Context capture ──────────────────────────────────────────────────────
    if (!PANEL_MODE) {
        captureBtn.addEventListener('click', () => {
            try {
                const iWin   = iframe.contentWindow;
                const iDoc   = iframe.contentDocument || iWin.document;
                const url    = iWin.location.href;
                const title  = iDoc.title || '';
                const text   = (iDoc.body.innerText || '').slice(0, 2000);
                addContextChip('page', title || url, { url, pageTitle: title, visibleText: text });
            } catch (err) {
                addContextChip('page', iframe.src, { url: iframe.src });
            }
        });
    }

    // ── Context chips ────────────────────────────────────────────────────────
    function addContextChip(type, label, data) {
        const id = Date.now();
        state.contextItems.push({ id, type, label, data });
        renderChips();
    }

    function removeContextChip(id) {
        state.contextItems = state.contextItems.filter(c => c.id !== id);
        renderChips();
    }

    function buildGjsChip(components) {
        // Extract data from each selected component
        const items = components.map(function(c, idx) {
            const type         = c.get('type') || c.get('tagName') || 'component';
            const el           = c.view && c.view.el;
            // Prefer live DOM (el.innerHTML) over stale model attribute (get('content')).
            // After components(html), get('content') stays stale but el.innerHTML is updated.
            // toHTML() serialises the model's component tree — also correct after components().
            const liveHtml     = (el && el.innerHTML) ? el.innerHTML : '';
            const modelContent = c.get('content') || '';
            const html         = liveHtml || (c.toHTML ? c.toHTML() : '') || modelContent;
            const tmp          = document.createElement('div');
            tmp.innerHTML      = liveHtml || modelContent;
            const text         = (tmp.innerText || tmp.textContent || '').trim();
            dbg('[OdradekGJS] buildGjsChip[' + idx + '] — type:', type, 'textPreview:', text.slice(0, 80));
            return { index: idx, type, text: text.slice(0, 500), html: html.slice(0, 2000) };
        });

        // Replace the existing GJS chip
        if (gjsChipId !== null)
            state.contextItems = state.contextItems.filter(c => c.id !== gjsChipId);
        gjsChipId = Date.now();
        const label = components.length === 1
            ? '\u2B21 ' + items[0].type + ': ' + (items[0].text.slice(0, 25) || items[0].type)
            : '\u2B21 ' + components.length + ' components selected';
        state.contextItems.push({
            id:    gjsChipId,
            type:  'gjs',
            label,
            data:  { selectedComponents: items },
        });
        renderChips();
    }

    function clearGjsComponentChip() {
        if (gjsChipId !== null) {
            state.contextItems = state.contextItems.filter(c => c.id !== gjsChipId);
            gjsChipId = null;
            renderChips();
        }
    }

    function renderChips() {
        chipsEl.innerHTML = '';
        state.contextItems.forEach(chip => {
            const el = document.createElement('div');
            el.className = 'context-chip';
            el.title = chip.label;
            el.innerHTML = `<span>${escHtml(chip.label.slice(0, 40))}</span>
                <span class="context-chip-remove" data-id="${chip.id}">×</span>`;
            chipsEl.appendChild(el);
        });
        chipsEl.querySelectorAll('.context-chip-remove').forEach(btn => {
            btn.addEventListener('click', () => removeContextChip(Number(btn.dataset.id)));
        });
    }

    function buildContext() {
        // Merge all context chips into one object
        const ctx = {};
        state.contextItems.forEach(chip => Object.assign(ctx, chip.data));
        // Also pull live URL if available
        if (PANEL_MODE && parentPageContext) {
            ctx.url       = ctx.url       || parentPageContext.url;
            ctx.pageTitle = ctx.pageTitle || parentPageContext.title;
        } else {
            try {
                ctx.url       = ctx.url       || iframe.contentWindow.location.href;
                ctx.pageTitle = ctx.pageTitle || iframe.contentDocument.title;
            } catch (_) {}
        }
        dbg('[OdradekGJS] buildContext →', {
            hasSelectedComponents: !!(ctx.selectedComponents && ctx.selectedComponents.length),
            componentCount: ctx.selectedComponents ? ctx.selectedComponents.length : 0,
            firstType: ctx.selectedComponents && ctx.selectedComponents[0] && ctx.selectedComponents[0].type,
            chipCount: state.contextItems.length,
        });
        return ctx;
    }

    // ── AI Context panel — localStorage helpers ──────────────────────────────
    function loadAiContext() {
        try { return JSON.parse(localStorage.getItem(CTX_STORAGE_KEY) || '{}'); }
        catch (_) { return {}; }
    }
    function saveAiContext(data) {
        try { localStorage.setItem(CTX_STORAGE_KEY, JSON.stringify(data)); } catch (_) {}
    }
    function populateContextForm() {
        const data = loadAiContext();
        CTX_FIELDS.forEach(f => {
            const el = document.getElementById(f.el);
            if (el) el.value = data[f.key] || '';
        });
    }
    function readContextForm() {
        const data = {};
        CTX_FIELDS.forEach(f => {
            const el = document.getElementById(f.el);
            if (el && el.value.trim()) data[f.key] = el.value.trim();
        });
        return data;
    }
    let ctxSaveTimer = null;
    function scheduleContextSave() {
        clearTimeout(ctxSaveTimer);
        ctxSaveTimer = setTimeout(() => {
            saveAiContext(readContextForm());
            if (ctxSavedEl) {
                ctxSavedEl.classList.add('visible');
                setTimeout(() => ctxSavedEl.classList.remove('visible'), 1800);
            }
        }, 800);
    }
    function initContextPanel() {
        populateContextForm();
        CTX_FIELDS.forEach(f => {
            const el = document.getElementById(f.el);
            if (el) el.addEventListener('input', scheduleContextSave);
        });
    }
    initContextPanel();

    // ── Conversation persistence (survives iframe navigation / page reload) ──
    function saveConversation() {
        try {
            if (state.messages.length > 0) {
                localStorage.setItem(CONV_STORAGE_KEY, JSON.stringify(state.messages));
            } else {
                localStorage.removeItem(CONV_STORAGE_KEY);
            }
        } catch (_) {}
    }
    function loadConversation() {
        try {
            const raw = localStorage.getItem(CONV_STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (_) { return null; }
    }
    function initConversationRestore() {
        const saved = loadConversation();
        if (!saved || saved.length === 0) return;
        state.messages = saved;
        hideWelcomeScreen();
        // Render simplified history bubbles — same visual style as a live session
        for (const msg of saved) {
            if (msg.role === 'user') {
                const el = document.createElement('div');
                el.className = 'odradek-msg msg-user';
                el.innerHTML = '<div class="msg-label">You</div>'
                    + '<div class="msg-body">' + escHtml(msg.content) + '</div>';
                messagesEl.appendChild(el);
            } else if (msg.role === 'assistant' && msg.content && msg.content.trim()) {
                const el = document.createElement('div');
                el.className = 'odradek-msg msg-ai';
                el.innerHTML = '<div class="msg-label">Odradek AI</div>'
                    + '<div class="msg-body">' + renderMarkdown(msg.content) + '</div>';
                messagesEl.appendChild(el);
            }
        }
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    // Save state before page unloads (catches all navigation causes)
    window.addEventListener('beforeunload', saveConversation);
    // Intercept F5 / Ctrl+R / Cmd+R — reload only the Mautic iframe, not the whole page
    if (!PANEL_MODE) {
        document.addEventListener('keydown', (e) => {
            const isReload = e.key === 'F5'
                || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'));
            if (isReload) {
                e.preventDefault();
                reloadIframe();
            }
        });
    }

    // ── Welcome screen ───────────────────────────────────────────────────────
    // Odradek spool mascot (half-block art):
    //
    //   ▄▄████████████▄▄
    //   ████████████████
    //   ▀▀▀▀▀██████▀▀▀▀▀
    //        ██████
    //   ▄▄▄▄▄██████▄▄▄▄▄
    //   ████████████████
    //   ▀▀████████████▀▀
    //
    // ▄ = lower half block  ▀ = upper half block  █ = full block
    // Top disc (2 lines), axle transition + centre + transition, bottom disc (2 lines)
    const ASCII_ART =
        '  \u2584\u2584\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2584\u2584\n' +
        '  \u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\n' +
        '  \u2580\u2580\u2580\u2580\u2580\u2588\u2588\u2588\u2588\u2588\u2588\u2580\u2580\u2580\u2580\u2580\n' +
        '       \u2588\u2588\u2588\u2588\u2588\u2588\n' +
        '  \u2584\u2584\u2584\u2584\u2584\u2588\u2588\u2588\u2588\u2588\u2588\u2584\u2584\u2584\u2584\u2584\n' +
        '  \u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\n' +
        '  \u2580\u2580\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2588\u2580\u2580';

    function getRecentActivity() {
        try {
            const raw = localStorage.getItem('odradek_recent');
            return raw ? JSON.parse(raw) : [];
        } catch (_) { return []; }
    }

    function addRecentActivity(text) {
        if (!text || text.length < 3) return;
        try {
            let items = getRecentActivity();
            items = [text, ...items.filter(i => i !== text)].slice(0, 8);
            localStorage.setItem('odradek_recent', JSON.stringify(items));
        } catch (_) {}
    }

    function showWelcomeScreen() {
        if (messagesEl.querySelector('.exchange-card')) return; // messages present
        hideWelcomeScreen();

        const userName  = window.ODRADEK_USER_NAME  || 'User';
        const apiKeySet = window.ODRADEK_API_KEY_SET !== false;
        const model     = window.ODRADEK_MODEL      || 'mistral-large-latest';
        const recent    = getRecentActivity();

        let recentHtml = '';
        if (recent.length > 0) {
            const items = recent.slice(0, 6).map((q, i) =>
                `<div class="recent-item" data-idx="${i}" title="${escHtml(q)}">${escHtml(q.slice(0, 70))}</div>`
            ).join('');
            recentHtml = `
                <hr class="welcome-divider">
                <div>
                    <div class="welcome-section-title">Recent</div>
                    <div class="welcome-recent-list">${items}</div>
                </div>`;
        }

        const el = document.createElement('div');
        el.id = 'odradek-welcome';
        el.innerHTML = `
            <pre class="welcome-ascii">${escHtml(ASCII_ART)}</pre>
            <div class="welcome-right">
                <div class="welcome-banner">Welcome back, ${escHtml(userName)}</div>
                <div class="welcome-meta">
                    <span>Model: <strong>${escHtml(model)}</strong></span>
                    <span class="${apiKeySet ? 'key-ok' : 'key-miss'}">${apiKeySet ? '&#10003; API key configured' : '&#10007; API key missing'}</span>
                </div>
                <hr class="welcome-divider">
                <div class="welcome-tips">
                    <div class="welcome-section-title">Try asking</div>
                    <div id="odradek-cycling-tips"></div>
                </div>
                ${recentHtml}
            </div>`;

        messagesEl.appendChild(el);

        // ── Randomized cycling tips ──────────────────────────────────
        const TIPS = [
            { icon: '🛡', title: 'Ethics Check', text: 'Ask me to check any email for dark patterns, manipulative language, and EU AI Act compliance before you send it.' },
            { icon: '📊', title: 'Campaign Insights', text: 'Ask "How is my welcome campaign performing?" and I\'ll analyze open rates, click-throughs, and suggest improvements.' },
            { icon: '🗺', title: 'Plan a Journey', text: 'Describe a goal like "re-engage cold leads" and I\'ll design a multi-step email journey with timing and messaging.' },
            { icon: '📋', title: 'Compliance Audit', text: 'Ask me to audit any campaign for GDPR and EU AI Act compliance — I\'ll flag risks and suggest fixes.' },
            { icon: '💬', title: 'Contact Sentiment', text: 'Give me a contact name or email and I\'ll analyze their engagement signals, sentiment, and communication tone.' },
            { icon: '❤️', title: 'Health Score', text: 'Ask "What\'s the health score for contact X?" to get an engagement score, churn risk, and recommended actions.' },
            { icon: '🌐', title: 'Build a Landing Page', text: 'Describe your page goal and audience — I\'ll create a full Mautic landing page with content and styling.' },
            { icon: '📋', title: 'Create a Form', text: 'Tell me what data you need to collect and I\'ll build a Mautic form with the right fields and validation.' },
            { icon: '📢', title: 'VoC Insights', text: 'Ask "What are customers saying?" and I\'ll aggregate feedback from forms, notes, and engagement signals into themes.' },
            { icon: '📊', title: 'Build a Survey', text: 'Say "Create an NPS survey" and I\'ll build a ready-made survey form with scoring. Templates: NPS, CSAT, CES, and more.' },
            { icon: '📈', title: 'Survey Results', text: 'Ask "What\'s the NPS score for form #5?" and I\'ll calculate the metric, show the breakdown, and interpret the results.' },
            { icon: '✉️', title: 'Create Emails', text: 'Describe the email you need — I\'ll pick a theme, create it, write the content, and open the preview for you.' },
            { icon: '👥', title: 'Manage Contacts', text: 'Ask me to list, create, update, or find contacts. I can also build segments based on any criteria.' },
            { icon: '🔍', title: 'Context Aware', text: 'Use the ⊕ Select button to highlight any element on the page — I\'ll use it as context for my next response.' },
            { icon: '📝', title: 'Plan Mode', text: 'Enable Plan Mode above to preview execution steps before I run them — great for complex multi-step tasks.' },
        ];

        // Shuffle and pick 3 random tips
        const shuffled = [...TIPS].sort(() => Math.random() - 0.5);
        const selected = shuffled.slice(0, 3);

        const tipsContainer = document.getElementById('odradek-cycling-tips');
        if (tipsContainer) {
            tipsContainer.innerHTML = selected.map(t =>
                `<div class="cycling-tip">
                    <span class="cycling-tip-icon">${t.icon}</span>
                    <div class="cycling-tip-body">
                        <strong>${escHtml(t.title)}</strong>
                        <span class="cycling-tip-text">${escHtml(t.text)}</span>
                    </div>
                </div>`
            ).join('');
        }

        // Wire up recent items as clickable
        el.querySelectorAll('.recent-item').forEach((item) => {
            item.addEventListener('click', () => {
                const idx = Number(item.dataset.idx);
                const q = recent[idx];
                if (q) {
                    inputEl.value = q;
                    inputEl.focus();
                }
            });
        });
    }

    function hideWelcomeScreen() {
        const ws = document.getElementById('odradek-welcome');
        if (ws) ws.remove();
    }

    showWelcomeScreen();
    initConversationRestore();

    // ── Exchange card system ─────────────────────────────────────────────────

    function startExchangeCard(userText) {
        hideWelcomeScreen();

        const card = document.createElement('div');
        card.className = 'exchange-card';

        // User message
        const userDiv = document.createElement('div');
        userDiv.className = 'odradek-msg msg-user';
        userDiv.innerHTML = `<div class="msg-label">You</div><div class="msg-body">${escHtml(userText)}</div>`;

        // Activities section (collapsed by default)
        const activitiesDiv = document.createElement('div');
        activitiesDiv.className = 'exchange-activities';
        activitiesDiv.innerHTML = `
            <div class="exchange-activities-header">
                <span class="exchange-activities-toggle">&#9654;</span>
                <span>Activities</span>
                <span class="exchange-activities-badge" style="display:none"></span>
            </div>
            <div class="activities-body"></div>`;

        // Main AI response section
        const mainDiv = document.createElement('div');
        mainDiv.className = 'exchange-main';
        mainDiv.innerHTML = `
            <div class="msg-label">Odradek AI</div>
            <div class="msg-body odradek-cursor"></div>`;

        card.appendChild(userDiv);
        card.appendChild(activitiesDiv);
        card.appendChild(mainDiv);
        messagesEl.appendChild(card);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        // Update state
        state.currentCard         = card;
        state.currentActivityEl   = activitiesDiv;
        state.currentMainBody     = mainDiv.querySelector('.msg-body');
        state.currentActivityCount = 0;

        // Toggle expand/collapse on header click
        activitiesDiv.querySelector('.exchange-activities-header').addEventListener('click', () => {
            activitiesDiv.classList.toggle('expanded');
        });

        return card;
    }

    function addActivityLine(name, id, args) {
        if (!state.currentActivityEl) return;
        const body = state.currentActivityEl.querySelector('.activities-body');
        if (!body) return;

        const escapedId = String(id).replace(/[^a-zA-Z0-9_-]/g, '_');

        const line = document.createElement('div');
        line.className = 'activity-line';
        line.dataset.actId = escapedId;

        let argsHtml = '';
        if (args && Object.keys(args).length > 0) {
            argsHtml = `<div class="tool-detail-args">${escHtml(JSON.stringify(args, null, 2))}</div>`;
        }

        line.innerHTML = `
            <span class="activity-icon-spin">&#9167;</span>
            <span class="activity-name">${escHtml(name)}</span>
            <div class="tool-detail" style="display:none">
                ${argsHtml}
                <div class="tool-detail-result"></div>
            </div>`;

        body.appendChild(line);

        // Update badge
        state.currentActivityCount++;
        const badge = state.currentActivityEl.querySelector('.exchange-activities-badge');
        if (badge) {
            badge.textContent = state.currentActivityCount;
            badge.style.display = '';
        }

        messagesEl.scrollTop = messagesEl.scrollHeight;
        return line;
    }

    function completeActivityLine(id, ok, result) {
        if (!state.currentActivityEl) return;
        const escapedId = String(id).replace(/[^a-zA-Z0-9_-]/g, '_');
        const line = state.currentActivityEl.querySelector(`.activity-line[data-act-id="${escapedId}"]`);
        if (!line) return;

        const spin = line.querySelector('.activity-icon-spin');
        if (spin) {
            spin.className = ok ? 'activity-icon-ok' : 'activity-icon-fail';
            spin.textContent = ok ? '&#10003;' : '&#10007;';
            spin.innerHTML   = ok ? '&#10003;' : '&#10007;';
        }

        if (result) {
            const detail  = line.querySelector('.tool-detail');
            const resEl   = detail && detail.querySelector('.tool-detail-result');
            if (resEl) {
                const summary = summarizeResult(result);
                resEl.textContent = summary;
                if (!ok) resEl.classList.add('error');
            }
            if (detail) detail.style.display = 'block';
        }

        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function updateActivityLineText(id, text) {
        if (!state.currentActivityEl) return;
        const escapedId = String(id).replace(/[^a-zA-Z0-9_-]/g, '_');
        const line = state.currentActivityEl.querySelector(`.activity-line[data-act-id="${escapedId}"]`);
        if (!line) return;
        const nameEl = line.querySelector('.activity-name');
        if (nameEl) nameEl.textContent = text;
    }

    function appendToMain(text) {
        if (!state.currentMainBody) return;
        state.currentMainBody.textContent += text;
        state.currentMainBody.classList.add('odradek-cursor');
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function finalizeCard() {
        if (!state.currentMainBody) {
            clearCardState();
            return;
        }

        const fullText = state.currentMainBody.textContent || '';
        const ASK_MARKER = '[ASK]:';
        const askIdx = fullText.indexOf(ASK_MARKER);

        if (askIdx !== -1) {
            const narrativePart = fullText.slice(0, askIdx).trim();
            const questionPart  = fullText.slice(askIdx + ASK_MARKER.length).trim();
            state.currentMainBody.innerHTML = narrativePart ? renderMarkdown(narrativePart) : '';
            const promptEl = document.createElement('div');
            promptEl.className = 'exchange-prompt';
            promptEl.innerHTML =
                '<div class="exchange-prompt-label">&#9670; Needs your input</div>' +
                '<div class="exchange-prompt-body">' + renderMarkdown(questionPart) + '</div>';
            state.currentMainBody.parentElement.appendChild(promptEl);
        } else {
            state.currentMainBody.innerHTML = renderMarkdown(fullText);
        }

        state.currentMainBody.classList.remove('odradek-cursor');
        messagesEl.scrollTop = messagesEl.scrollHeight;
        clearCardState();
    }

    function clearCardState() {
        state.currentCard         = null;
        state.currentActivityEl   = null;
        state.currentMainBody     = null;
        state.currentActivityCount = 0;
    }

    // ── Status bar — busy phrases + timer ────────────────────────────────────
    const BUSY_PHRASES = [
        'Consulting the oracle\u2026',
        'Untangling the spaghetti\u2026',
        'Asking the digital spirits\u2026',
        'Caffeinating the neural networks\u2026',
        'Summoning marketing wisdom\u2026',
        'Bribing the API\u2026',
        'Conjuring database magic\u2026',
        'Teaching electrons to dance\u2026',
        'Negotiating with the servers\u2026',
        'Persuading the algorithms\u2026',
        'Waking up the AI gremlins\u2026',
        'Loading creative juices\u2026',
        'Herding the bits and bytes\u2026',
        'Convincing the clouds to cooperate\u2026',
        'Mining for insights\u2026',
        'Decoding your request\u2026',
        'Spinning up the thinking machine\u2026',
        'Applying quantum marketing\u2026',
        'Synchronizing with the matrix\u2026',
        'Processing\u2026 please hold\u2026',
    ];

    let phraseTimer    = null;
    let timerInterval  = null;
    let busyStart      = null;
    let phraseIdx      = 0;

    function startBusy() {
        if (!statusBar) return;
        statusBar.classList.remove('status-idle');
        statusBar.classList.add('status-busy');
        busyStart  = Date.now();
        phraseIdx  = Math.floor(Math.random() * BUSY_PHRASES.length);
        if (statusPhrase) statusPhrase.textContent = BUSY_PHRASES[phraseIdx];
        phraseTimer = setInterval(() => {
            phraseIdx = (phraseIdx + 1) % BUSY_PHRASES.length;
            if (statusPhrase) statusPhrase.textContent = BUSY_PHRASES[phraseIdx];
        }, 3500);
        timerInterval = setInterval(() => {
            const s  = Math.floor((Date.now() - busyStart) / 1000);
            const mm = String(Math.floor(s / 60)).padStart(2, '0');
            const ss = String(s % 60).padStart(2, '0');
            if (statusTimer) statusTimer.textContent = `${mm}:${ss}`;
        }, 1000);
    }

    function stopBusy() {
        clearInterval(phraseTimer);
        clearInterval(timerInterval);
        phraseTimer   = null;
        timerInterval = null;
        if (!statusBar) return;
        statusBar.classList.remove('status-busy');
        statusBar.classList.add('status-idle');
        if (statusPhrase) statusPhrase.textContent = '';
        if (statusTimer)  statusTimer.textContent  = '';
    }

    // ── summarizeResult (used by completeActivityLine + old batch path) ──────
    function summarizeResult(result) {
        if (result.message) return result.message;
        if (result.error)   return '\u2717 ' + result.error;
        if (result.contacts)  return `${result.count} contact(s) found`;
        if (result.emails)    return `${result.count} email(s) found`;
        if (result.campaigns) return `${result.count} campaign(s) found`;
        if (result.segments)  return `${result.count} segment(s) found`;
        if (result.reports)   return `${result.count} report(s) found`;
        // Ethics analysis (ethics_score) vs contact sentiment (sentiment field)
        if (result.analysis) {
            if (result.analysis.sentiment !== undefined) {
                const score = result.analysis.sentiment_score;
                const level = result.analysis.sentiment;
                return score !== undefined
                    ? `Sentiment: ${level} (${score}/100) — see AI response for details`
                    : `Sentiment: ${level} — see AI response for details`;
            }
            const score = result.analysis.ethics_score;
            return score !== undefined
                ? `Ethics score: ${score}/100 — see AI response for details`
                : 'Ethics analysis complete — see AI response for details';
        }
        // Campaign performance insights
        if (result.insights) return 'Campaign analysis complete — see AI response for details';
        // Journey plan
        if (result.journey)  return `Journey plan ready: ${result.journey.journey_name || 'see AI response'}`;
        // Compliance report
        if (result.report) {
            const rate   = result.report.compliance_rate;
            const status = result.report.overall_compliance;
            return rate !== undefined
                ? `Compliance: ${status} (${rate}%) across ${result.emails_audited} email(s)`
                : 'Compliance report ready — see AI response for details';
        }
        // Contact health score
        if (result.score_data) {
            const score = result.score_data.health_score;
            const level = result.score_data.risk_level;
            return score !== undefined
                ? `Health score: ${score}/100 — ${level}`
                : 'Health score ready — see AI response for details';
        }
        // VoC: theme analysis
        if (result.themes) {
            return `Discovered ${result.theme_count} theme(s) from ${result.verbatim_count} verbatims`;
        }
        // VoC: verbatim collection
        if (result.verbatim_count !== undefined && !result.themes) {
            return `Collected ${result.verbatim_count} PII-redacted verbatims`;
        }
        // VoC: contact voice profile
        if (result.voc_profile) {
            const s = result.voc_profile.sentiment || 'unknown';
            const t = (result.voc_profile.topics || []).length;
            return `VoC profile: ${s} sentiment, ${t} topic(s)`;
        }
        // VoC: theme summary drill-down
        if (result.summary && result.theme_name) {
            const sev = result.summary.severity || 'unknown';
            return `Theme "${result.theme_name}": ${sev} severity`;
        }
        // VoC: response campaign plan
        if (result.campaign_plan) {
            const name   = result.campaign_plan.campaign_name || 'Campaign';
            const emails = (result.campaign_plan.emails || []).length;
            return `Campaign "${name}" — ${emails} email(s) planned`;
        }
        // VoC: insight segment created
        if (result.segment && result.segment.contact_count !== undefined) {
            return `Segment "${result.segment.name}" created with ${result.segment.contact_count} contacts`;
        }
        // Survey analytics result
        if (result.survey_type && result.metric) {
            return `${result.survey_type.toUpperCase()}: ${result.metric.summary || 'See details'}`;
        }
        // Survey creation result
        if (result.survey_type && result.form) {
            return `${result.survey_type.toUpperCase()} survey "${result.form.name}" created (ID #${result.form.id})`;
        }
        // Survey template list
        if (result.templates && !result.survey_type) {
            return `${Object.keys(result.templates).length} survey templates available`;
        }
        return JSON.stringify(result).slice(0, 80);
    }

    // ── Inline action drawer (kept for programmatic use) ──────────────────────
    const actionDrawer    = document.getElementById('odradek-action-drawer');
    const actionIcon      = document.getElementById('odradek-action-drawer-icon');
    const actionLabelEl   = document.getElementById('odradek-action-drawer-label');
    const actionHintEl    = document.getElementById('odradek-action-drawer-hint');
    const actionInputEl   = document.getElementById('odradek-action-input');
    const actionRunBtn    = document.getElementById('odradek-action-run');
    const actionCancelBtn = document.getElementById('odradek-action-cancel');

    // Per-action config: icon, label shown in drawer header, hint text,
    // input placeholder, whether the value is optional, and the prompt builder.
    const ACTION_CONFIG = {
        ethics: {
            icon:        '🛡',
            label:       'Ethics Check',
            hint:        'Enter the email name or ID — or leave blank, then paste the content in chat.',
            placeholder: 'Email name or ID (optional)…',
            optional:    true,
            buildPrompt: (val) => val.trim()
                ? `Check email "${val.trim()}" for dark patterns and EU AI Act compliance issues`
                : 'Check the email I\'m about to share for dark patterns and EU AI Act compliance issues'
        },
        performance: {
            icon:        '📊',
            label:       'Campaign Insights',
            hint:        'Which campaign would you like AI-powered insights on?',
            placeholder: 'Campaign name or ID…',
            buildPrompt: (val) => `Analyze the performance of campaign "${val.trim()}" and give me actionable insights`
        },
        journey: {
            icon:        '🗺',
            label:       'Plan Journey',
            hint:        'Describe your campaign goal and the AI will generate a structured email journey.',
            placeholder: 'e.g. Welcome new subscribers, Re-engage cold leads after 60 days…',
            buildPrompt: (val) => `Suggest a 3-email journey for: ${val.trim()}`
        },
        compliance: {
            icon:        '📋',
            label:       'Compliance Audit',
            hint:        'Which campaign should be audited for EU AI Act + GDPR compliance?',
            placeholder: 'Campaign name or ID…',
            buildPrompt: (val) => `Generate a full EU AI Act and GDPR compliance report for campaign "${val.trim()}"`
        },
        sentiment: {
            icon:        '💬',
            label:       'Contact Sentiment',
            hint:        'Which contact\'s sentiment and engagement signals should be analyzed?',
            placeholder: 'Contact name, email address, or ID…',
            buildPrompt: (val) => `Analyze the sentiment and engagement signals for contact "${val.trim()}"`
        },
        health: {
            icon:        '❤️',
            label:       'Health Score',
            hint:        'Which contact should be scored for engagement health and churn risk?',
            placeholder: 'Contact name, email address, or ID…',
            buildPrompt: (val) => `Score the engagement health and churn risk of contact "${val.trim()}"`
        },
        page: {
            icon:        '🌐',
            label:       'Build Page',
            hint:        'Describe the landing page you want — goal, audience, key messages, and tone.',
            placeholder: 'e.g. Lead gen page for our new SaaS product targeting marketing teams…',
            buildPrompt: (val) => `Build a landing page for: ${val.trim()}`
        },
        form: {
            icon:        '📋',
            label:       'Create Form',
            hint:        'Describe the form you need — type, goal, audience, and fields.',
            placeholder: 'e.g. Webinar registration form for B2B marketers, lead capture for demo requests…',
            buildPrompt: (val) => `Create a Mautic form for: ${val.trim()}`
        },
        voc: {
            icon:        '📢',
            label:       'VoC Insights',
            hint:        'Analyze customer feedback across forms, notes, and engagement signals.',
            placeholder: 'Form name or ID (optional, blank = all sources)…',
            optional:    true,
            buildPrompt: (val) => val.trim()
                ? `Analyze Voice of Customer themes from form "${val.trim()}"`
                : 'Analyze Voice of Customer feedback across all sources — show themes, sentiment, and quotes'
        },
        survey: {
            icon:        '📊',
            label:       'Build Survey',
            hint:        'Choose a survey type: NPS, CSAT, CES, Product-Market Fit, Onboarding, Churn/Exit, or Post-Purchase.',
            placeholder: 'e.g. NPS survey for Acme Corp, CSAT for our support team…',
            buildPrompt: (val) => `Create a VoC survey: ${val.trim()}`
        },
        surveyResults: {
            icon:        '📈',
            label:       'Survey Results',
            hint:        'Enter the survey form name or ID to calculate the score and get insights.',
            placeholder: 'Survey form name or ID…',
            buildPrompt: (val) => `Analyze the survey results for form "${val.trim()}" and give me the score with interpretation`
        }
    };

    let currentAction = null;

    function openActionDrawer(action) {
        const cfg = ACTION_CONFIG[action];
        if (!cfg) return;
        currentAction             = action;
        actionIcon.textContent    = cfg.icon;
        actionLabelEl.textContent = cfg.label;
        actionHintEl.textContent  = cfg.hint;
        actionInputEl.placeholder = cfg.placeholder;
        actionInputEl.value       = '';
        actionInputEl.classList.remove('input-error');
        actionDrawer.hidden          = false;
        actionInputEl.focus();
    }

    function closeActionDrawer() {
        actionDrawer.hidden          = true;
        currentAction                = null;
        actionInputEl.value          = '';
    }

    function submitActionDrawer() {
        if (!currentAction) return;
        const cfg = ACTION_CONFIG[currentAction];
        const val = actionInputEl.value;
        if (!cfg.optional && !val.trim()) {
            actionInputEl.classList.add('input-error');
            actionInputEl.focus();
            setTimeout(() => actionInputEl.classList.remove('input-error'), 1200);
            return;
        }
        const prompt = cfg.buildPrompt(val);
        closeActionDrawer();
        inputEl.value = prompt;
        sendUserMessage();
    }

    actionRunBtn.addEventListener('click', submitActionDrawer);
    actionCancelBtn.addEventListener('click', closeActionDrawer);
    actionInputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter')  { e.preventDefault(); submitActionDrawer(); }
        if (e.key === 'Escape') { closeActionDrawer(); }
    });

    // ── setBusy (disables inputs) ────────────────────────────────────────────
    function setBusy(busy) {
        state.busy            = busy;
        inputEl.disabled      = busy;
        sendBtn.disabled      = busy;
        actionRunBtn.disabled = busy;
    }

    // ── GJS selection capture ────────────────────────────────────────────────
    function captureGjsSelectionNow() {
        dbg('[OdradekGJS] captureGjsSelectionNow called; gjsSelected=', gjsSelected ? 'present' : 'null');
        // Try live selection from the bound editor first
        if (gjsEditor) {
            try {
                const all = gjsEditor.getSelectedAll ? gjsEditor.getSelectedAll() : [];
                if (all.length) {
                    gjsSelectedAll = all;
                    gjsSelected    = all[0];
                    dbg('[OdradekGJS] live selection via gjsEditor —', all.length, 'component(s)');
                    buildGjsChip(all);
                    return;
                }
            } catch (_) {}
        }

        // Fall back: scan grapesjs.editors (when GrapesJS is global)
        try {
            const iWin = iframe.contentWindow;
            const eds  = iWin && iWin.grapesjs && iWin.grapesjs.editors;
            if (eds && eds.length) {
                for (let i = eds.length - 1; i >= 0; i--) {
                    const editor = eds[i];
                    const all    = editor.getSelectedAll ? editor.getSelectedAll() : [];
                    if (all.length) {
                        gjsEditor      = editor;
                        gjsSelectedAll = all;
                        gjsSelected    = all[0];
                        dbg('[OdradekGJS] live selection via grapesjs.editors —', all.length, 'component(s)');
                        buildGjsChip(all);
                        return;
                    }
                }
                dbg('[OdradekGJS] no live selection found in any editor');
            }
        } catch (e) {
            dbgWarn('[OdradekGJS] captureGjsSelectionNow error:', e);
        }

        // Fall back to last-known selection (user clicked into chat input)
        if (gjsSelectedAll.length) {
            dbg('[OdradekGJS] using cached gjsSelectedAll (', gjsSelectedAll.length, 'components)');
            buildGjsChip(gjsSelectedAll);
        } else if (gjsSelected) {
            dbg('[OdradekGJS] using cached gjsSelected (single fallback)');
            buildGjsChip([gjsSelected]);
        } else {
            dbg('[OdradekGJS] no selection available');
        }
    }

    // ── Send / chat ──────────────────────────────────────────────────────────
    function sendUserMessage() {
        const text = inputEl.value.trim();
        if (!text || state.busy) return;

        inputEl.value = '';
        expandAI();

        // Add to history
        state.messages.push({ role: 'user', content: text });
        saveConversation();

        // Start exchange card + busy animation
        startExchangeCard(text);
        startBusy();

        captureGjsSelectionNow();          // snapshot current GJS selection (if any)
        const planMode = planModeChk.checked;
        const ctx      = buildContext();

        // Always snapshot messages — backend may auto-trigger plan mode even when
        // the checkbox is unchecked, and the approve handler needs these messages.
        state.pendingPlanMessages = [...state.messages];

        const aiCtx = loadAiContext();
        sendMessages(state.messages, ctx, planMode, false, aiCtx);
    }

    function sendMessages(messages, context, planMode, approved, aiContext) {
        setBusy(true);

        const payload = { messages, context, planMode, approved, aiContext: aiContext || {} };

        // Batch state — maps batchId → activity line escaped id
        const batchActIds = {};

        const mutatingTools = new Set([
            'create_contact', 'update_contact', 'delete_contact',
            'create_email', 'update_email',
            'create_segment',
        ]);

        let didMutate = false;

        // Manual POST SSE via fetch + ReadableStream
        const source = new EventSource(CHAT_URL + '?_sse=1');
        source.close();

        fetch(CHAT_URL, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.ODRADEK_CSRF_TOKEN || '',
            },
            body:    JSON.stringify(payload),
        }).then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf      = '';

            function pump() {
                return reader.read().then(({ done, value }) => {
                    if (done) return;
                    buf += dec.decode(value, { stream: true });
                    const lines = buf.split('\n');
                    buf = lines.pop(); // keep incomplete line

                    let event = null;
                    let data  = null;

                    for (const line of lines) {
                        if (line.startsWith('event: ')) {
                            event = line.slice(7).trim();
                        } else if (line.startsWith('data: ')) {
                            data = line.slice(6).trim();
                        } else if (line === '' && event && data !== null) {
                            handleSseEvent(event, data);
                            event = null;
                            data  = null;
                        }
                    }

                    return pump();
                });
            }

            return pump();
        }).catch(err => {
            // Show error in exchange-main if available
            if (state.currentMainBody) {
                state.currentMainBody.classList.remove('odradek-cursor');
                state.currentMainBody.innerHTML =
                    `<span style="color:#f85149"><strong>Error:</strong> ${escHtml(err.message)}</span>`;
            }
            clearCardState();
            stopBusy();
            setBusy(false);
        });

        // ── SSE event handler ──────────────────────────────────────────────
        function handleSseEvent(event, rawData) {
            let data;
            try { data = JSON.parse(rawData); } catch (_) { return; }

            if (event === 'content') {
                appendToMain(data.text || data.content || '');

                // Track full AI reply for history
                const last = state.messages[state.messages.length - 1];
                if (last && last.role === 'assistant') {
                    last.content += (data.text || data.content || '');
                } else {
                    state.messages.push({ role: 'assistant', content: (data.text || data.content || '') });
                }

            } else if (event === 'tool_call') {
                addActivityLine(data.name, data.id, data.args);

            } else if (event === 'tool_result') {
                const ok = !(data.result && data.result.success === false);
                completeActivityLine(data.id, ok, data.result);
                if (mutatingTools.has(data.tool)) didMutate = true;

            } else if (event === 'client_tool') {
                if (data.tool === 'navigate_mautic') {
                    navigateIframe(data.args.path);
                    completeActivityLine(data.id, true, { message: `Navigated to ${data.args.path}` });
                } else if (data.tool === 'get_page_info') {
                    if (PANEL_MODE && parentPageContext) {
                        addContextChip('page', parentPageContext.title || parentPageContext.url, {
                            url: parentPageContext.url,
                            pageTitle: parentPageContext.title,
                            visibleText: parentPageContext.visibleText,
                        });
                    } else {
                        try {
                            const iWin = iframe.contentWindow;
                            const iDoc = iframe.contentDocument;
                            addContextChip('page', iDoc.title || iWin.location.href, {
                                url: iWin.location.href,
                                pageTitle: iDoc.title,
                            });
                        } catch (_) {}
                    }
                    completeActivityLine(data.id, true, { message: 'Page info captured' });
                } else if (data.tool === 'update_grapesjs_component') {
                    try {
                        const idx      = Number(data.args.componentIndex ?? 0) || 0;
                        let   selected = (gjsSelectedAll.length > idx) ? gjsSelectedAll[idx] : (gjsSelectedAll[0] || null);
                        if (!selected) selected = gjsSelected;
                        if (!selected && gjsEditor) { try { selected = gjsEditor.getSelected(); } catch(_){} }
                        if (!selected) throw new Error('No component is selected — please click the block in the builder first');

                        const ctype = selected.get('type') || '';
                        const html  = data.args.html || '';
                        dbg('[OdradekGJS] update_grapesjs_component — idx:', idx, 'ctype:', ctype, 'html len:', html.length, 'preview:', html.slice(0, 120));

                        if (!html) throw new Error('AI returned empty HTML — nothing to apply');

                        if (ctype === 'text') {
                            selected.set('content', html);
                        } else {
                            selected.components(html);
                        }
                        setTimeout(function() {
                            if (gjsSelectedAll.length) buildGjsChip(gjsSelectedAll);
                        }, 50);
                        completeActivityLine(data.id, true, { message: 'Component updated in builder' });
                    } catch (e) {
                        completeActivityLine(data.id, false, { success: false, error: e.message });
                    }
                }

            } else if (event === 'batch_start') {
                const batchActId = 'batch-' + data.batchId;
                batchActIds[data.batchId] = batchActId;
                addActivityLine('\u26a1 ' + data.total + ' operation' + (data.total !== 1 ? 's' : '') + ' \u2014 0/' + data.total, batchActId, null);

            } else if (event === 'batch_progress') {
                const batchActId = batchActIds[data.batchId];
                if (batchActId) {
                    updateActivityLineText(batchActId,
                        '\u26a1 ' + data.toolName + ' \u2014 ' + data.completed + '/' + data.total);
                }
                if (mutatingTools.has(data.toolName)) didMutate = true;
                // Handle client-side tools inside a batch (e.g. navigate_mautic)
                if (data.toolName === 'navigate_mautic' && data.args && data.args.path) {
                    navigateIframe(data.args.path);
                }

            } else if (event === 'batch_done') {
                const batchActId = batchActIds[data.batchId];
                if (batchActId) {
                    const allOk  = data.failCount === 0;
                    const note   = data.failCount > 0 ? `, ${data.failCount} failed` : '';
                    const label  = `${data.successCount}/${data.total} completed${note}`;
                    completeActivityLine(batchActId, allOk, { message: label });
                    delete batchActIds[data.batchId];
                }

            } else if (event === 'plan') {
                // Render plan inside the exchange-main body
                if (state.currentMainBody) {
                    const steps     = (data.steps     || []).map(s => `<li>${escHtml(String(s))}</li>`).join('');
                    const questions = data.questions   || [];
                    const hasQ      = questions.length > 0;

                    let qaHtml = '';
                    if (hasQ) {
                        let qItems = '';
                        questions.forEach((item, i) => {
                            const hint = item.hint ? ` placeholder="${escHtml(item.hint)}"` : '';
                            qItems += `<div class="plan-qa-item">`
                                + `<div class="plan-q">${escHtml(item.q)}</div>`
                                + `<input type="text" class="plan-answer" data-idx="${i}"${hint}>`
                                + `</div>`;
                        });
                        qaHtml = '<div class="plan-questions">'
                            + '<div class="plan-questions-label">? A few questions before I start</div>'
                            + qItems
                            + '<div class="plan-questions-actions">'
                            + '<button class="plan-approve-btn">&#10003; Submit &amp; Execute</button>'
                            + '<button class="plan-cancel-btn">&#10005; Cancel</button>'
                            + '</div></div>';
                    }

                    const planBody = state.currentMainBody;
                    planBody.classList.remove('odradek-cursor');
                    planBody.innerHTML = `
                        <div class="plan-title">&#9635; Execution Plan</div>
                        <ol>${steps}</ol>
                        ${qaHtml}
                        ${!hasQ ? `<div class="plan-actions">
                            <button class="plan-approve-btn">&#10003; Approve &amp; Execute</button>
                            <button class="plan-cancel-btn">&#10005; Cancel</button>
                        </div>` : ''}`;

                    // Prevent the immediately-following `done` event from
                    // calling finalizeCard() and overwriting the plan card.
                    clearCardState();

                    // Wire approve/submit button (use planBody, not state.currentMainBody)
                    planBody.querySelector('.plan-approve-btn').addEventListener('click', () => {
                        const answers = [...planBody.querySelectorAll('.plan-answer')]
                            .map((inp, i) => ({ q: questions[i]?.q || `Q${i + 1}`, a: inp.value.trim() }))
                            .filter(a => a.a);

                        // Replace plan in old card with a notice
                        const actionsEl = planBody.querySelector('.plan-actions');
                        if (actionsEl) actionsEl.remove();
                        const qaEl = planBody.querySelector('.plan-questions');
                        if (qaEl) qaEl.remove();
                        const notice = document.createElement('p');
                        notice.className = 'exchange-notice';
                        notice.textContent = 'Plan approved. Executing\u2026';
                        planBody.appendChild(notice);

                        if (state.pendingPlanMessages) {
                            let msgs = [...state.pendingPlanMessages];
                            if (answers.length > 0) {
                                const answerText = answers.map(a => `${a.q}: ${a.a}`).join('\n');
                                msgs = [...msgs, { role: 'user', content: `My answers:\n${answerText}` }];
                            }
                            // Find original user message for display
                            const origMsg    = state.pendingPlanMessages.filter(m => m.role === 'user').pop();
                            const displayText = origMsg ? origMsg.content.slice(0, 150) : 'Executing approved plan\u2026';

                            startExchangeCard(displayText);
                            startBusy();
                            sendMessages(msgs, buildContext(), false, true, loadAiContext());
                            state.pendingPlanMessages = null;
                        }
                    });

                    // Wire cancel button
                    planBody.querySelector('.plan-cancel-btn').addEventListener('click', () => {
                        ['.plan-actions', '.plan-questions'].forEach(sel => {
                            const el = planBody.querySelector(sel);
                            if (el) el.remove();
                        });
                        const notice = document.createElement('p');
                        notice.className = 'exchange-notice';
                        notice.textContent = 'Cancelled.';
                        planBody.appendChild(notice);
                        state.pendingPlanMessages = null;
                        stopBusy();
                        setBusy(false);
                    });

                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }

                stopBusy();
                setBusy(false);

            } else if (event === 'error') {
                if (state.currentMainBody) {
                    state.currentMainBody.classList.remove('odradek-cursor');
                    state.currentMainBody.innerHTML =
                        `<span style="color:#f85149"><strong>Error:</strong> ${escHtml(data.message)}</span>`;
                }
                clearCardState();
                stopBusy();
                setBusy(false);

            } else if (event === 'done') {
                finalizeCard();

                if (didMutate) {
                    setTimeout(reloadIframe, 400);
                    didMutate = false;
                }

                // Save to recent activity
                const lastUserMsg = state.messages.filter(m => m.role === 'user').pop();
                if (lastUserMsg) addRecentActivity(lastUserMsg.content);

                // Persist conversation so it survives page reloads
                saveConversation();

                stopBusy();
                setBusy(false);
            }
        }
    }

    // ── Input keybindings ────────────────────────────────────────────────────
    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendUserMessage();
        }
    });

    sendBtn.addEventListener('click', sendUserMessage);

    clearBtn.addEventListener('click', () => {
        state.messages     = [];
        state.contextItems = [];
        saveConversation();   // removes from localStorage (messages is now empty)
        clearCardState();
        messagesEl.innerHTML = '';
        chipsEl.innerHTML    = '';
        stopBusy();
        showWelcomeScreen();
    });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderMarkdown(text) {
        let t = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        const blocks = [];
        for (const block of t.split(/\n{2,}/)) {
            const trimmed = block.trim();
            if (!trimmed) continue;
            const lines = trimmed.split('\n');
            const isOrderedList = lines.every(l => /^\d+\.\s/.test(l.trim()) || l.trim() === '');
            if (isOrderedList) {
                let html = '<ol>';
                for (const line of lines) {
                    const item = line.replace(/^\d+\.\s*/, '').trim();
                    if (item) html += '<li>' + inlineMarkdown(item) + '</li>';
                }
                html += '</ol>';
                blocks.push(html);
            } else {
                blocks.push('<p>' + lines.map(l => inlineMarkdown(l)).join('<br>') + '</p>');
            }
        }
        return blocks.join('');
    }

    function inlineMarkdown(text) {
        let s = escHtml(text);
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/\*(.+?)\*/g,     '<em>$1</em>');
        s = s.replace(/`([^`]+)`/g,     '<code>$1</code>');
        return s;
    }
})();
