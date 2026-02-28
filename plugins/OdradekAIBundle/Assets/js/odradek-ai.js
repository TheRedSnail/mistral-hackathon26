/**
 * Odradek AI — Frontend Logic
 * Handles: split-screen resize, iframe navigation, element selector,
 *          context capture, SSE chat loop, plan mode, tool visualization.
 */
(function () {
    'use strict';

    // ── DOM refs ────────────────────────────────────────────────────────────
    const splitEl      = document.getElementById('odradek-split');
    const mauticPane   = document.getElementById('odradek-mautic-pane');
    const dividerEl    = document.getElementById('odradek-divider');
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

    // ── Drag-to-resize divider ───────────────────────────────────────────────
    (function initDivider() {
        let dragging = false;
        let startY   = 0;
        let startH   = 0;

        dividerEl.addEventListener('mousedown', (e) => {
            dragging  = true;
            startY    = e.clientY;
            startH    = mauticPane.getBoundingClientRect().height;
            dividerEl.classList.add('dragging');
            document.body.style.userSelect = 'none';
            document.body.style.cursor     = 'ns-resize';
            // Overlay iframe to keep mouse events during drag
            iframe.style.pointerEvents = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!dragging) return;
            const delta  = e.clientY - startY;
            const totalH = splitEl.getBoundingClientRect().height;
            const newH   = Math.max(80, Math.min(totalH - 160, startH + delta));
            mauticPane.style.flex = `0 0 ${newH}px`;
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
    });

    backBtn.addEventListener('click', () => {
        try { iframe.contentWindow.history.back(); } catch (_) {}
    });

    forwardBtn.addEventListener('click', () => {
        try { iframe.contentWindow.history.forward(); } catch (_) {}
    });

    function navigateIframe(path) {
        // Ensure absolute path within same origin
        const url = path.startsWith('http') ? path : window.location.origin + path;
        iframe.src = url;
    }

    function reloadIframe() {
        try {
            iframe.contentWindow.location.reload();
        } catch (_) {
            iframe.src = iframe.src;
        }
    }

    // ── Element selector mode ────────────────────────────────────────────────
    let selectorCleanup = null;

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
        return ctx;
    }

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
            const steps = (extra.steps || []).map((s, i) =>
                `<li>${escHtml(String(s))}</li>`).join('');
            el.innerHTML = `
                <div class="plan-title">&#9635; Execution Plan</div>
                <ol>${steps}</ol>
                <div class="plan-actions">
                    <button class="plan-approve-btn">&#10003; Approve &amp; Execute</button>
                    <button class="plan-cancel-btn">&#10005; Cancel</button>
                </div>`;
            el.querySelector('.plan-approve-btn').addEventListener('click', () => {
                el.remove();
                if (state.pendingPlanMessages) {
                    sendMessages(state.pendingPlanMessages, buildContext(), false, true);
                    state.pendingPlanMessages = null;
                }
            });
            el.querySelector('.plan-cancel-btn').addEventListener('click', () => {
                el.remove();
                state.pendingPlanMessages = null;
                setStatus('');
                setBusy(false);
            });
        } else if (type === 'error') {
            el.innerHTML = `<strong>Error:</strong> ${escHtml(content)}`;
        }

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
        if (result.contacts) return `${result.count} contact(s) found`;
        if (result.emails)   return `${result.count} email(s) found`;
        if (result.campaigns) return `${result.count} campaign(s) found`;
        if (result.segments) return `${result.count} segment(s) found`;
        if (result.reports)  return `${result.count} report(s) found`;
        return JSON.stringify(result).slice(0, 80);
    }

    // ── Send / chat ──────────────────────────────────────────────────────────
    function setBusy(busy) {
        state.busy       = busy;
        inputEl.disabled = busy;
        sendBtn.disabled = busy;
    }

    function setStatus(text) {
        statusEl.textContent = text;
    }

    function sendUserMessage() {
        const text = inputEl.value.trim();
        if (!text || state.busy) return;

        inputEl.value = '';

        // Add to history
        const userMsg = { role: 'user', content: text };
        state.messages.push(userMsg);
        appendMessage('user', text);

        const planMode = planModeChk.checked;
        const ctx      = buildContext();

        if (planMode) {
            // Store messages for later approval
            state.pendingPlanMessages = [...state.messages];
        }

        sendMessages(state.messages, ctx, planMode, false);
    }

    function sendMessages(messages, context, planMode, approved) {
        setBusy(true);
        setStatus(planMode && !approved ? 'Planning…' : 'Thinking…');

        const payload = { messages, context, planMode, approved };

        let currentAiEl = null;
        let currentAiBody = null;

        const source = new EventSource(CHAT_URL + '?_sse=1');
        // Use fetch + manual SSE parsing for POST
        source.close();

        // Manual POST SSE via fetch + ReadableStream
        fetch(CHAT_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
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

        function handleSseEvent(event, rawData) {
            let data;
            try { data = JSON.parse(rawData); } catch (_) { return; }

            if (event === 'content') {
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

            } else if (event === 'tool_call') {
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
                }

            } else if (event === 'plan') {
                currentAiEl = null; // reset AI bubble
                appendMessage('plan', '', { steps: data.steps });
                setBusy(false);
                setStatus('');

            } else if (event === 'error') {
                appendMessage('error', data.message);
                setBusy(false);
                setStatus('');

            } else if (event === 'done') {
                // Remove streaming cursor
                if (currentAiBody) currentAiBody.classList.remove('odradek-cursor');
                currentAiEl   = null;
                currentAiBody = null;

                // Reload Mautic iframe if any mutating tool ran
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

})();
