/**
 * Odradek AI — Frontend Logic
 * Handles: split-screen resize, iframe navigation, element selector,
 *          context capture, SSE chat loop, plan mode, tool visualization.
 */
(function () {
    'use strict';

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
    const statusEl     = document.getElementById('odradek-status');

    const CHAT_URL     = window.ODRADEK_CHAT_URL || '/odradek/ai/chat';

    // ── State ───────────────────────────────────────────────────────────────
    const state = {
        messages:    [],   // [{role, content}] sent to backend
        contextItems: [],  // [{type, label, data}]
        selectMode:  false,
        busy:        false,
        pendingPlanMessages: null, // messages saved when plan shown
    };

    // ── Expand / collapse AI panel ───────────────────────────────────────────
    function expandAI() {
        aiPane.classList.add('ai-expanded');
        dividerEl.classList.add('ai-visible');
        inputEl.focus();
    }

    function collapseAI() {
        aiPane.classList.remove('ai-expanded');
        dividerEl.classList.remove('ai-visible');
        aiPane.style.height = ''; // revert to CSS default (38px)
    }

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

    // ── iframe navigation tracking ───────────────────────────────────────────
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

    function navigateIframe(path) {
        if (!path || typeof path !== 'string') return;
        // Reject external URLs and protocol-relative URLs
        if (/^(https?:)?\/\//i.test(path)) return;
        const safePath = path.startsWith('/') ? path : '/' + path;
        iframe.src = window.location.origin + safePath;
    }

    function reloadIframe() {
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

    selectBtn.addEventListener('click', () => {
        if (state.selectMode) disableSelectMode();
        else                  enableSelectMode();
    });

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
        try {
            ctx.url       = ctx.url       || iframe.contentWindow.location.href;
            ctx.pageTitle = ctx.pageTitle || iframe.contentDocument.title;
        } catch (_) {}
        dbg('[OdradekGJS] buildContext →', {
            hasSelectedComponents: !!(ctx.selectedComponents && ctx.selectedComponents.length),
            componentCount: ctx.selectedComponents ? ctx.selectedComponents.length : 0,
            firstType: ctx.selectedComponents && ctx.selectedComponents[0] && ctx.selectedComponents[0].type,
            chipCount: state.contextItems.length,
        });
        return ctx;
    }

    // ── Empty state ──────────────────────────────────────────────────────────
    function renderEmptyState() {
        if (messagesEl.children.length === 0) {
            const el = document.createElement('div');
            el.id = 'odradek-empty-state';
            el.innerHTML = `
                <div class="empty-state-icon">&#11041;</div>
                <div class="empty-state-title">Odradek AI</div>
                <div class="empty-state-sub">Ask anything or use the quick actions below</div>`;
            messagesEl.appendChild(el);
        }
    }

    function clearEmptyState() {
        const es = document.getElementById('odradek-empty-state');
        if (es) es.remove();
    }

    renderEmptyState();

    // ── Message rendering ────────────────────────────────────────────────────
    function appendMessage(type, content, extra) {
        const el = document.createElement('div');
        el.className = `odradek-msg msg-${type}`;

        if (type === 'user') {
            el.innerHTML = `<div class="msg-label">You</div><div class="msg-body">${escHtml(content)}</div>`;
        } else if (type === 'ai') {
            el.innerHTML = `<div class="msg-label">Odradek AI</div><div class="msg-body odradek-cursor"></div>`;
        } else if (type === 'tool') {
            el.dataset.toolId = extra.id || '';
            el.innerHTML = `
                <div class="tool-header">
                    <span class="odradek-spinner"></span>
                    <span>${escHtml(extra.name)}</span>
                </div>
                <div class="tool-args">${escHtml(JSON.stringify(extra.args, null, 2))}</div>
                <div class="tool-result"></div>`;
        } else if (type === 'plan') {
            const steps     = (extra.steps     || []).map(s => `<li>${escHtml(String(s))}</li>`).join('');
            const questions = extra.questions  || [];

            let qaHtml = '';
            if (questions.length > 0) {
                qaHtml = '<div class="plan-questions"><strong>A few questions before I start:</strong><dl>';
                questions.forEach((item, i) => {
                    const hint = item.hint ? ` placeholder="${escHtml(item.hint)}"` : '';
                    qaHtml += `<dt>${escHtml(item.q)}</dt>`;
                    qaHtml += `<dd><input type="text" class="plan-answer" data-idx="${i}"${hint}></dd>`;
                });
                qaHtml += '</dl></div>';
            }

            el.innerHTML = `
                <div class="plan-title">&#9635; Execution Plan</div>
                <ol>${steps}</ol>
                ${qaHtml}
                <div class="plan-actions">
                    <button class="plan-approve-btn">&#10003; Approve &amp; Execute</button>
                    <button class="plan-cancel-btn">&#10005; Cancel</button>
                </div>`;

            el.querySelector('.plan-approve-btn').addEventListener('click', () => {
                const answers = [...el.querySelectorAll('.plan-answer')]
                    .map((inp, i) => ({ q: questions[i]?.q || `Q${i + 1}`, a: inp.value.trim() }))
                    .filter(a => a.a);

                el.remove();

                if (state.pendingPlanMessages) {
                    let msgs = [...state.pendingPlanMessages];
                    if (answers.length > 0) {
                        const answerText = answers.map(a => `${a.q}: ${a.a}`).join('\n');
                        msgs = [...msgs, { role: 'user', content: `My answers:\n${answerText}` }];
                    }
                    sendMessages(msgs, buildContext(), false, true);
                    state.pendingPlanMessages = null;
                }
            });

            el.querySelector('.plan-cancel-btn').addEventListener('click', () => {
                el.remove();
                state.pendingPlanMessages = null;
                setStatus('');
                setBusy(false);
            });
        } else if (type === 'thinking') {
            el.className = 'odradek-msg msg-thinking';
            el.innerHTML = `<div class="thinking-row">
        <span class="odradek-spinner"></span>
        <span class="thinking-label">Thinking...</span>
    </div>`;
        } else if (type === 'error') {
            el.innerHTML = `<strong>Error:</strong> ${escHtml(content)}`;
        }

        clearEmptyState();
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return el;
    }

    function findToolMsg(id) {
        return messagesEl.querySelector(`.msg-tool[data-tool-id="${id}"]`);
    }

    function updateToolMsg(id, result) {
        const el = findToolMsg(id);
        if (!el) return;

        // Remove spinner
        const spinner = el.querySelector('.odradek-spinner');
        if (spinner) spinner.remove();

        const header = el.querySelector('.tool-header');
        if (header) {
            const icon = document.createElement('span');
            icon.textContent = result && result.success === false ? '✗' : '✓';
            icon.style.color  = result && result.success === false ? '#f85149' : '#3fb950';
            header.prepend(icon);
        }

        const resultEl = el.querySelector('.tool-result');
        if (resultEl) {
            const summary = result ? summarizeResult(result) : 'Done';
            resultEl.textContent = summary;
            if (result && result.success === false) resultEl.classList.add('error');
        }
    }

    function summarizeResult(result) {
        if (result.message) return result.message;
        if (result.error)   return '✗ ' + result.error;
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
            const rate = result.report.compliance_rate;
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
        return JSON.stringify(result).slice(0, 80);
    }

    // ── Quick-action buttons & inline action drawer ──────────────────────────
    const quickBtns       = document.querySelectorAll('.quick-action-btn');
    const quickActionsEl  = document.getElementById('odradek-quick-actions');
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
        quickActionsEl.style.display = 'none';
        actionDrawer.hidden          = false;
        actionInputEl.focus();
    }

    function closeActionDrawer() {
        actionDrawer.hidden          = true;
        quickActionsEl.style.display = '';
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

    quickBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (state.busy) return;
            expandAI();
            openActionDrawer(btn.dataset.action);
        });
    });

    // ── Send / chat ──────────────────────────────────────────────────────────
    function setBusy(busy) {
        state.busy            = busy;
        inputEl.disabled      = busy;
        sendBtn.disabled      = busy;
        actionRunBtn.disabled = busy;
        quickBtns.forEach(b => { b.disabled = busy; });
    }

    function setStatus(text) {
        statusEl.textContent = text;
    }

    // Synchronous GJS selection capture — called at send time so the AI always
    // has the current selection regardless of whether the async event binding is live.
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

    function sendUserMessage() {
        const text = inputEl.value.trim();
        if (!text || state.busy) return;

        inputEl.value = '';

        // Add to history
        const userMsg = { role: 'user', content: text };
        state.messages.push(userMsg);
        appendMessage('user', text);

        captureGjsSelectionNow();          // snapshot current GJS selection (if any)
        const planMode = planModeChk.checked;
        const ctx      = buildContext();

        // Always snapshot messages — backend may auto-trigger plan mode even when
        // the checkbox is unchecked, and the approve handler needs these messages.
        state.pendingPlanMessages = [...state.messages];

        sendMessages(state.messages, ctx, planMode, false);
    }

    function sendMessages(messages, context, planMode, approved) {
        setBusy(true);
        setStatus(planMode && !approved ? 'Planning…' : 'Thinking…');

        const payload = { messages, context, planMode, approved };

        let currentAiEl = null;
        let currentAiBody = null;

        // Thinking indicator — shown immediately before any SSE arrives
        const thinkingEl = appendMessage('thinking', '');
        let thinkingRemoved = false;
        function removeThinking() {
            if (!thinkingRemoved && thinkingEl && thinkingEl.parentNode) {
                thinkingEl.remove();
                thinkingRemoved = true;
            }
        }

        // Batch state — keyed by batchId
        const batchGroups = {};

        const source = new EventSource(CHAT_URL + '?_sse=1');
        // Use fetch + manual SSE parsing for POST
        source.close();

        // Manual POST SSE via fetch + ReadableStream
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
            appendMessage('error', err.message);
            setBusy(false);
            setStatus('');
        });

        // ── SSE event handler ──────────────────────────────────────────────
        const mutatingTools = new Set([
            'create_contact', 'update_contact', 'delete_contact',
            'create_email', 'update_email',
            'create_segment',
        ]);

        let didMutate = false;

        function createBatchGroup(batchId, total, toolName) {
            const el = document.createElement('div');
            el.className = 'odradek-msg msg-batch';
            el.innerHTML = `
                <div class="batch-header">
                    <span class="odradek-spinner"></span>
                    <span class="batch-label">&#9889; Executing ${total} operation${total !== 1 ? 's' : ''}&#8230;</span>
                    <span class="batch-counter">0/${total}</span>
                </div>
                <ul class="batch-list"></ul>`;
            messagesEl.appendChild(el);
            messagesEl.scrollTop = messagesEl.scrollHeight;
            return {
                el,
                listEl:    el.querySelector('.batch-list'),
                counterEl: el.querySelector('.batch-counter'),
                headerEl:  el.querySelector('.batch-header'),
                spinnerEl: el.querySelector('.odradek-spinner'),
                items: {},
            };
        }

        function updateBatchGroup(group, data) {
            group.counterEl.textContent = `${data.completed}/${data.total}`;
            let li = group.items[data.callId];
            if (!li) {
                li = document.createElement('li');
                li.className = 'batch-item';
                group.listEl.appendChild(li);
                group.items[data.callId] = li;
            }
            const icon    = data.success ? '&#10003;' : '&#10007;';
            const iconCls = data.success ? 'batch-icon--ok' : 'batch-icon--fail';
            const keyPart = data.keyArg ? ` <span class="batch-key-arg">${escHtml(data.keyArg)}</span>` : '';
            const sumPart = data.summary ? ` \u2014 ${escHtml(data.summary)}` : '';
            li.className  = `batch-item batch-item--${data.success ? 'ok' : 'fail'}`;
            li.innerHTML  = `<span class="batch-icon ${iconCls}">${icon}</span> ${escHtml(data.toolName)}${keyPart}${sumPart}`;
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function collapseBatchGroup(group, data) {
            if (group.spinnerEl) group.spinnerEl.remove();
            group.listEl.classList.add('batch-list--collapsed');
            const allOk = data.failCount === 0;
            const label = group.el.querySelector('.batch-label');
            if (label) {
                const failNote = data.failCount > 0 ? `, ${data.failCount} failed` : '';
                label.innerHTML = `<span class="batch-icon ${allOk ? 'batch-icon--ok' : 'batch-icon--warn'}">${allOk ? '&#10003;' : '&#9888;'}</span> `
                                + `${data.successCount}/${data.total} completed${failNote}`;
            }
            group.counterEl.textContent = '';
            group.headerEl.classList.add('batch-header--done');
            group.headerEl.title = 'Click to expand';
            group.headerEl.addEventListener('click', () => {
                group.listEl.classList.toggle('batch-list--collapsed');
                group.headerEl.title = group.listEl.classList.contains('batch-list--collapsed')
                    ? 'Click to expand' : 'Click to collapse';
            });
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function handleSseEvent(event, rawData) {
            let data;
            try { data = JSON.parse(rawData); } catch (_) { return; }

            if (event === 'content') {
                removeThinking();
                if (!currentAiEl) {
                    currentAiEl   = appendMessage('ai', '');
                    currentAiBody = currentAiEl.querySelector('.msg-body');
                }
                currentAiBody.textContent += data.text;
                // Add cursor class (will be removed on done)
                currentAiBody.classList.add('odradek-cursor');
                messagesEl.scrollTop = messagesEl.scrollHeight;

                // Track full AI reply for history
                const last = state.messages[state.messages.length - 1];
                if (last && last.role === 'assistant') {
                    last.content += data.text;
                } else {
                    state.messages.push({ role: 'assistant', content: data.text });
                }

            } else if (event === 'thinking') {
                // no-op: thinkingEl already created before fetch

            } else if (event === 'tool_call') {
                removeThinking();
                appendMessage('tool', '', { name: data.name, args: data.args, id: data.id });
                setStatus(`Running: ${data.name}`);

            } else if (event === 'tool_result') {
                updateToolMsg(data.id, data.result);
                if (mutatingTools.has(data.tool)) didMutate = true;

            } else if (event === 'client_tool') {
                if (data.tool === 'navigate_mautic') {
                    navigateIframe(data.args.path);
                    updateToolMsg(data.id, { success: true, message: `Navigated to ${data.args.path}` });
                } else if (data.tool === 'get_page_info') {
                    // Inject current page info into next context
                    try {
                        const iWin = iframe.contentWindow;
                        const iDoc = iframe.contentDocument;
                        addContextChip('page', iDoc.title || iWin.location.href, {
                            url: iWin.location.href,
                            pageTitle: iDoc.title,
                        });
                    } catch (_) {}
                    updateToolMsg(data.id, { success: true, message: 'Page info captured' });
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
                            // Standard GrapesJS text component: set('content') triggers
                            // its change:content listener which calls components() internally.
                            selected.set('content', html);
                        } else {
                            // MJML components (mj-text, mj-button, …): replace inner children directly.
                            // NOTE: do NOT call set('content', html) here — on mj-text it sets a raw
                            // attribute that triggers a re-render clearing the children we just set.
                            selected.components(html);
                        }
                        // Refresh the chip immediately — GrapesJS won't re-fire component:selected
                        // for an already-selected component, so the chip would stay stale otherwise.
                        setTimeout(function() {
                            if (gjsSelectedAll.length) buildGjsChip(gjsSelectedAll);
                        }, 50);
                        updateToolMsg(data.id, { success: true, message: 'Component updated in builder' });
                    } catch (e) {
                        updateToolMsg(data.id, { success: false, error: e.message });
                    }
                }

            } else if (event === 'batch_start') {
                removeThinking();
                batchGroups[data.batchId] = createBatchGroup(data.batchId, data.total, data.toolName);
                setStatus(`Executing ${data.total} operations\u2026`);

            } else if (event === 'batch_progress') {
                const group = batchGroups[data.batchId];
                if (group) updateBatchGroup(group, data);
                if (mutatingTools.has(data.toolName)) didMutate = true;
                // Handle client-side tools inside a batch (e.g. navigate_mautic)
                if (data.toolName === 'navigate_mautic' && data.args && data.args.path) {
                    navigateIframe(data.args.path);
                }

            } else if (event === 'batch_done') {
                const group = batchGroups[data.batchId];
                if (group) { collapseBatchGroup(group, data); delete batchGroups[data.batchId]; }
                setStatus('');

            } else if (event === 'plan') {
                removeThinking();
                currentAiEl = null; // reset AI bubble
                appendMessage('plan', '', { steps: data.steps });
                setBusy(false);
                setStatus('');

            } else if (event === 'error') {
                appendMessage('error', data.message);
                setBusy(false);
                setStatus('');

            } else if (event === 'done') {
                removeThinking();

                // ── Post-stream markdown rendering + prompt detection ─────────────────
                if (currentAiEl && currentAiBody) {
                    const last = state.messages[state.messages.length - 1];
                    const fullText = (last && last.role === 'assistant') ? last.content : (currentAiBody.textContent || '');

                    const ASK_MARKER = '[ASK]:';
                    const askIdx = fullText.indexOf(ASK_MARKER);

                    if (askIdx !== -1) {
                        const narrativePart = fullText.slice(0, askIdx).trim();
                        const questionPart  = fullText.slice(askIdx + ASK_MARKER.length).trim();

                        currentAiBody.innerHTML = narrativePart ? renderMarkdown(narrativePart) : '';

                        const promptEl = document.createElement('div');
                        promptEl.className = 'msg-prompt';
                        promptEl.innerHTML =
                            '<div class="msg-prompt-label">&#9670; Needs your input</div>' +
                            '<div class="msg-prompt-body">' + renderMarkdown(questionPart) + '</div>';
                        currentAiEl.appendChild(promptEl);
                    } else {
                        currentAiBody.innerHTML = renderMarkdown(fullText);
                    }

                    currentAiBody.classList.remove('odradek-cursor');
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }

                currentAiEl   = null;
                currentAiBody = null;

                if (didMutate) {
                    setTimeout(reloadIframe, 400);
                    didMutate = false;
                }

                setBusy(false);
                setStatus('');
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
        state.messages    = [];
        state.contextItems = [];
        messagesEl.innerHTML = '';
        chipsEl.innerHTML    = '';
        setStatus('');
        renderEmptyState();
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
