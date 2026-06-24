/*
|--------------------------------------------------------------------------
| app.js — Centralized modular JS entry
|--------------------------------------------------------------------------
| Vanilla JS. Exposes a single `OB` namespace with reusable helpers so
| views never duplicate inline fetch/toast/modal logic.
|
| Imports Bootstrap JS + the global theme so Vite bundles everything.
*/

import 'bootstrap';
import '../css/theme.css';

/* ==========================================================================
   HTTP helpers
   --------------------------------------------------------------------------
   All requests send the Laravel CSRF token and JSON headers by default.
   FormData bodies (file uploads) skip JSON content-type automatically.
   ========================================================================== */
function csrfToken() {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
}

async function request(method, url, body = null, options = {}) {
    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.headers || {}),
    };

    let payload = body;
    const isFormData = body instanceof FormData;
    if (body && !isFormData) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const res = await fetch(url, {
        method,
        headers,
        body: method === 'GET' || method === 'HEAD' ? undefined : payload,
        credentials: 'same-origin',
        ...options,
    });

    const contentType = res.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await res.json() : await res.text();

    if (!res.ok) {
        const err = new Error(`Request failed (${res.status})`);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data;
}

const http = {
    get:    (url, options)       => request('GET', url, null, options),
    post:   (url, body, options) => request('POST', url, body, options),
    put:    (url, body, options) => request('PUT', url, body, options),
    delete: (url, body, options) => request('DELETE', url, body, options),
};

/* ==========================================================================
   Toast / alert notifications
   ========================================================================== */
function ensureToastContainer() {
    let c = document.querySelector('.ob-toast-container');
    if (!c) {
        c = document.createElement('div');
        c.className = 'ob-toast-container';
        document.body.appendChild(c);
    }
    return c;
}

const TOAST_VARIANTS = {
    success: { icon: 'fa-circle-check', cls: 'text-bg-success' },
    error:   { icon: 'fa-circle-exclamation', cls: 'text-bg-danger' },
    warning: { icon: 'fa-triangle-exclamation', cls: 'text-bg-warning' },
    info:    { icon: 'fa-circle-info', cls: 'text-bg-primary' },
};

function toast(message, type = 'info', delay = 4000) {
    const container = ensureToastContainer();
    const variant = TOAST_VARIANTS[type] || TOAST_VARIANTS.info;

    const el = document.createElement('div');
    el.className = `toast align-items-center border-0 ${variant.cls}`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="fa-solid ${variant.icon}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    container.appendChild(el);

    const bsToast = window.bootstrap
        ? new window.bootstrap.Toast(el, { delay })
        : null;
    if (bsToast) {
        bsToast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    } else {
        setTimeout(() => el.remove(), delay);
    }
}

/* ==========================================================================
   Loading states
   ========================================================================== */
function setLoading(target, isLoading, loadingText = 'Working…') {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;

    if (isLoading) {
        el.dataset.originalHtml = el.innerHTML;
        el.setAttribute('disabled', 'disabled');
        el.setAttribute('aria-busy', 'true');
        el.innerHTML = `<span class="spinner-border spinner-border-sm me-2"
            role="status" aria-hidden="true"></span>${loadingText}`;
    } else {
        el.removeAttribute('disabled');
        el.removeAttribute('aria-busy');
        if (el.dataset.originalHtml !== undefined) {
            el.innerHTML = el.dataset.originalHtml;
            delete el.dataset.originalHtml;
        }
    }
}

/* ==========================================================================
   Modal helpers (Bootstrap-backed)
   ========================================================================== */
const modal = {
    show(selector) {
        const el = document.querySelector(selector);
        if (el && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(el).show();
        }
    },
    hide(selector) {
        const el = document.querySelector(selector);
        if (el && window.bootstrap) {
            const inst = window.bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        }
    },
};

/* ==========================================================================
   Form serialization
   --------------------------------------------------------------------------
   serialize()  -> plain object (repeated names become arrays)
   formData()   -> FormData (use for file uploads)
   ========================================================================== */
function serialize(form) {
    const el = typeof form === 'string' ? document.querySelector(form) : form;
    const fd = new FormData(el);
    const out = {};
    for (const [key, value] of fd.entries()) {
        if (key in out) {
            out[key] = Array.isArray(out[key]) ? [...out[key], value] : [out[key], value];
        } else {
            out[key] = value;
        }
    }
    return out;
}

function formData(form) {
    const el = typeof form === 'string' ? document.querySelector(form) : form;
    return new FormData(el);
}

/* ==========================================================================
   Wizard UI behaviors
   --------------------------------------------------------------------------
   Progressive enhancement wired on DOMContentLoaded. Safe to run on any
   page — each block no-ops if its hooks aren't present.
   ========================================================================== */
function initIdTypeToggle() {
    const radios = document.querySelectorAll('[data-ob-idtype]');
    if (!radios.length) return;

    const backField = document.querySelector('[data-ob-idfield="back"]');
    const sync = () => {
        const selected = document.querySelector('[data-ob-idtype]:checked');
        const isIne = selected && selected.value === 'ine';
        if (backField) backField.style.display = isIne ? '' : 'none';
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    sync();
}

function initUploadFields() {
    // Annex limit injected by the layout (falls back to 15 MB).
    const maxBytes = (window.__obAnnexMaxBytes || 15 * 1024 * 1024);

    document.querySelectorAll('[data-ob-upload="zone"]').forEach((zone) => {
        const input = zone.querySelector('[data-ob-upload="input"]');
        const text = zone.querySelector('[data-ob-upload="text"]');
        const icon = zone.querySelector('.ob-upload__icon i');
        if (!input) return;

        // Allow multiple so split parts can be carried on one input.
        input.setAttribute('multiple', 'multiple');

        async function handleSelection(fileList) {
            const file = fileList && fileList[0];
            if (!file) return;
            zone.classList.remove('is-error');

            // Split PDFs over the limit into parts (dynamic import keeps
            // pdf-lib out of the main bundle until a split is actually needed).
            let parts;
            try {
                if (text) text.textContent = '…';
                const { splitPdfIfNeeded, OversizeError } = await import('./pdf-split');
                try {
                    parts = await splitPdfIfNeeded(file, maxBytes);
                } catch (err) {
                    if (err instanceof OversizeError || err.name === 'OversizeError') {
                        showError(zone, text, icon,
                            window.__obOversizeMsg || 'El archivo es demasiado grande y no se puede dividir.');
                        input.value = '';
                        return;
                    }
                    throw err;
                }
            } catch (e) {
                showError(zone, text, icon,
                    window.__obSplitErrMsg || 'No se pudo procesar el PDF.');
                input.value = '';
                return;
            }

            // Replace the input's files with the (possibly multiple) parts.
            const dt = new DataTransfer();
            parts.forEach((p) => dt.items.add(p));
            input.files = dt.files;

            zone.classList.add('is-filled');
            if (icon) icon.className = 'fa-solid fa-circle-check';
            if (text) {
                text.textContent = parts.length > 1
                    ? `${file.name} — ${parts.length} ${window.__obPartsLabel || 'partes'}`
                    : file.name;
            }
        }

        input.addEventListener('change', () => handleSelection(input.files));

        ['dragover', 'dragenter'].forEach((evt) =>
            zone.addEventListener(evt, (e) => { e.preventDefault(); zone.classList.add('is-dragover'); }));
        ['dragleave', 'drop'].forEach((evt) =>
            zone.addEventListener(evt, () => zone.classList.remove('is-dragover')));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer?.files?.length) {
                handleSelection(e.dataTransfer.files);
            }
        });
    });
}

function showError(zone, text, icon, msg) {
    zone.classList.remove('is-filled');
    zone.classList.add('is-error');
    if (icon) icon.className = 'fa-solid fa-triangle-exclamation';
    if (text) text.textContent = msg;
}

function initPostalLookup() {
    const input = document.querySelector('[data-ob-postal="input"]');
    if (!input) return;

    const stateEl     = document.querySelector('[data-ob-postal="state"]');
    const cityEl      = document.querySelector('[data-ob-postal="city"]');
    const coloniaEl   = document.querySelector('[data-ob-postal="colonia"]');
    const coloniaMan  = document.querySelector('[data-ob-postal="colonia-manual"]');
    const statusEl    = document.querySelector('[data-ob-postal="status"]');
    const preselect   = coloniaEl?.dataset.selected || '';

    function setManual(on) {
        // Toggle free typing for state/city.
        [stateEl, cityEl].forEach((el) => {
            if (!el) return;
            if (on) el.removeAttribute('readonly');
            else el.setAttribute('readonly', 'readonly');
        });
        // Swap which colonia control is active + carries name="colonia".
        if (coloniaEl && coloniaMan) {
            if (on) {
                coloniaEl.classList.add('d-none');
                coloniaEl.removeAttribute('name');
                coloniaEl.removeAttribute('required');
                coloniaMan.classList.remove('d-none');
                coloniaMan.setAttribute('name', 'colonia');
                coloniaMan.setAttribute('required', 'required');
            } else {
                coloniaMan.classList.add('d-none');
                coloniaMan.removeAttribute('name');
                coloniaMan.removeAttribute('required');
                coloniaEl.classList.remove('d-none');
                coloniaEl.setAttribute('name', 'colonia');
                coloniaEl.setAttribute('required', 'required');
            }
        }
    }

    function fillColonias(list, selected) {
        if (!coloniaEl) return;
        coloniaEl.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '—';
        coloniaEl.appendChild(placeholder);
        list.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === selected) opt.selected = true;
            coloniaEl.appendChild(opt);
        });
    }

    async function lookup() {
        const code = (input.value || '').replace(/\D/g, '');
        if (code.length !== 5) return;

        if (statusEl) statusEl.textContent = '';
        try {
            const data = await OB.http.get(`/postal/${code}`);
            if (data.found) {
                setManual(false);
                if (stateEl) { stateEl.value = data.estado || ''; stateEl.setAttribute('readonly', 'readonly'); }
                if (cityEl)  { cityEl.value  = data.city   || ''; cityEl.setAttribute('readonly', 'readonly'); }
                fillColonias(data.colonias || [], preselect);
                if (statusEl) statusEl.textContent = '';
            } else {
                // Not in catalog → manual entry fallback.
                setManual(true);
                if (stateEl) stateEl.value = '';
                if (cityEl)  cityEl.value = '';
                if (statusEl) {
                    statusEl.textContent = window.__obPostalNotFound
                        || 'Código no encontrado. Capture los datos manualmente.';
                    statusEl.style.color = 'var(--ob-warning)';
                }
            }
        } catch (e) {
            if (statusEl) {
                statusEl.textContent = 'No se pudo consultar el código postal.';
                statusEl.style.color = 'var(--ob-danger)';
            }
        }
    }

    input.addEventListener('blur', lookup);
    input.addEventListener('input', () => {
        if ((input.value || '').replace(/\D/g, '').length === 5) lookup();
    });

    // Re-run on load if a code is already present (resume / back-nav).
    if ((input.value || '').replace(/\D/g, '').length === 5) lookup();
}

function initWizard() {
    initIdTypeToggle();
    initUploadFields();
    initPostalLookup();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWizard);
} else {
    initWizard();
}

/* ==========================================================================
   Public namespace
   ========================================================================== */
const OB = { http, toast, setLoading, modal, serialize, formData, initWizard };
window.OB = OB;

export default OB;