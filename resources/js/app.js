const config = window.inventrackConfig || {};

function updateClock() {
    const clock = document.getElementById('live-clock');
    if (!clock) return;

    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    clock.textContent = `${hours}:${minutes}:${seconds}`;
}

setInterval(updateClock, 1000);
updateClock();

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('inventrack-theme', newTheme);

    if (typeof window.updateChartTheme === 'function') {
        window.updateChartTheme();
    }
}

window.toggleTheme = toggleTheme;

const desktopSidebarMedia = window.matchMedia('(min-width: 992px)');

function isDesktopSidebar() {
    return desktopSidebarMedia.matches;
}

function rememberSidebarHoverOpen(open) {
    if (open) {
        sessionStorage.setItem('inventrack-sidebar-hover-open', '1');
        return;
    }

    sessionStorage.removeItem('inventrack-sidebar-hover-open');
}

function shouldRestoreSidebarHoverOpen() {
    return sessionStorage.getItem('inventrack-sidebar-hover-open') === '1';
}

function setDesktopSidebarCollapsed(collapsed) {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('.main-footer');

    if (!sidebar || !mainContent) return;

    sidebar.classList.toggle('collapsed', collapsed);
    mainContent.classList.toggle('sidebar-collapsed', collapsed);
    if (footer) footer.classList.toggle('sidebar-collapsed', collapsed);
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay || isDesktopSidebar()) return;

    sidebar.classList.remove('collapsed');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

window.toggleSidebar = toggleSidebar;

function syncDesktopSidebarHoverState() {
    if (!isDesktopSidebar()) return;

    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    setDesktopSidebarCollapsed(!(sidebar.matches(':hover') || shouldRestoreSidebarHoverOpen()));
}

function normalizeSidebarForViewport() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('.main-footer');

    if (!sidebar || !overlay || !mainContent) return;

    if (isDesktopSidebar()) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        syncDesktopSidebarHoverState();
        return;
    }

    sidebar.classList.remove('collapsed');
    mainContent.classList.remove('sidebar-collapsed');
    if (footer) footer.classList.remove('sidebar-collapsed');
}

normalizeSidebarForViewport();

const sidebarEl = document.getElementById('sidebar');

if (sidebarEl) {
    sidebarEl.addEventListener('mouseenter', () => {
        if (isDesktopSidebar()) {
            rememberSidebarHoverOpen(true);
            setDesktopSidebarCollapsed(false);
        }
    });

    sidebarEl.addEventListener('mouseleave', () => {
        if (isDesktopSidebar()) {
            rememberSidebarHoverOpen(false);
            setDesktopSidebarCollapsed(true);
        }
    });
}

requestAnimationFrame(syncDesktopSidebarHoverState);

const Toast = window.Swal
    ? window.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        customClass: {
            popup: document.documentElement.getAttribute('data-theme') === 'dark'
                ? 'swal-toast-custom swal-dark'
                : 'swal-toast-custom',
        },
    })
    : null;

window.Toast = Toast;

if (Toast && config.flash?.success) {
    Toast.fire({ icon: 'success', title: config.flash.success });
}

if (Toast && config.flash?.error) {
    Toast.fire({ icon: 'error', title: config.flash.error });
}

function swalConfirm(title, text, icon, confirmText, formSelector) {
    if (!window.Swal) {
        if (window.confirm(text || title)) {
            document.querySelector(formSelector)?.submit();
        }
        return;
    }

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    window.Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText || 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: isDark ? 'swal-dark' : '',
            confirmButton: 'swal-btn-confirm',
            cancelButton: 'swal-btn-cancel',
        },
        buttonsStyling: false,
    }).then((result) => {
        if (result.isConfirmed) {
            document.querySelector(formSelector)?.submit();
        }
    });
}

window.swalConfirm = swalConfirm;

document.querySelectorAll('.sidebar-link').forEach((link) => {
    link.addEventListener('click', () => {
        if (!isDesktopSidebar()) {
            document.getElementById('sidebar')?.classList.remove('show');
            document.getElementById('sidebarOverlay')?.classList.remove('show');
            return;
        }

        rememberSidebarHoverOpen(true);
    });
});

let cachedSidebarWidth = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width')) || 270;
window.addEventListener('resize', () => {
    cachedSidebarWidth = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width')) || cachedSidebarWidth;
});

let mousemoveTicking = false;
let lastMouseEvent = null;

document.addEventListener('mousemove', (event) => {
    if (!sidebarEl || !isDesktopSidebar() || !shouldRestoreSidebarHoverOpen()) return;

    lastMouseEvent = event;
    if (mousemoveTicking) return;
    mousemoveTicking = true;

    // Throttle to once per animation frame instead of firing on every
    // mousemove event — avoids repeated forced-layout reads (getBoundingClientRect)
    // while the sidebar expand/collapse transition is running.
    requestAnimationFrame(() => {
        mousemoveTicking = false;
        if (!lastMouseEvent || !sidebarEl) return;

        const sidebarRect = sidebarEl.getBoundingClientRect();
        const isInsideExpandedSidebar =
            lastMouseEvent.clientX >= sidebarRect.left &&
            lastMouseEvent.clientX <= sidebarRect.left + cachedSidebarWidth &&
            lastMouseEvent.clientY >= sidebarRect.top &&
            lastMouseEvent.clientY <= sidebarRect.bottom;

        if (!isInsideExpandedSidebar) {
            rememberSidebarHoverOpen(false);
            setDesktopSidebarCollapsed(true);
        }
    });
}, { passive: true });

window.addEventListener('resize', normalizeSidebarForViewport);

function relocateInventrackModal(modal) {
    if (!modal || !modal.classList.contains('inventrack-modal')) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}

window.relocateInventrackModal = relocateInventrackModal;

document.querySelectorAll('.modal.inventrack-modal').forEach((modal) => {
    relocateInventrackModal(modal);
});

document.addEventListener('show.bs.modal', (event) => {
    relocateInventrackModal(event.target);
});

const sectionShell = document.getElementById('sectionShell');
const sectionCache = new Set([config.currentSectionId].filter(Boolean));
const routeSectionMap = {
    dashboard: 'dashboardSection',
    items: 'itemsSection',
    transactions: 'transactionsSection',
    stock: 'stockSection',
    reports: 'reportsSection',
    users: 'usersSection',
    'pending-users': 'usersSection',
    'Stok-request': 'stockRequestsSection',
    'stuff-requests': 'stuffRequestsSection',
    import: 'importSection',
};

function sectionIdFromUrl(url) {
    const parsedUrl = new URL(url, window.location.origin);
    const path = parsedUrl.pathname.replace(/^\/+/, '');
    const firstSegment = path.split('/')[0] || 'dashboard';

    if (firstSegment === 'transactions') {
        const isTeknikCtx = config.isTeknik || (config.isSuperAdmin && parsedUrl.searchParams.get('sa_bidang') === 'teknik');
        if (isTeknikCtx) {
            return parsedUrl.searchParams.get('type') === 'out'
                ? 'transactionsIssueSection'
                : 'transactionsReceiptSection';
        }
    }

    return routeSectionMap[firstSegment] || 'dashboardSection';
}

function getSectionLink(sectionId) {
    return document.querySelector(`.sidebar-link[data-section="${sectionId}"]`);
}

function updateTopbarActions(sectionId) {
    const topbarActions = document.getElementById('topbarRightActions');
    if (!topbarActions) return;

    // 1. Move any currently active actions back to their wrappers
    const currentActions = topbarActions.querySelector('.section-header-actions');
    if (currentActions) {
        const originalWrapperId = currentActions.dataset.originalWrapper;
        if (originalWrapperId) {
            const wrapper = document.getElementById(originalWrapperId);
            if (wrapper) {
                wrapper.appendChild(currentActions);
            }
        }
    }

    // Clear topbar actions
    topbarActions.innerHTML = '';

    // 2. Find actions in the new active section
    const activeSection = document.getElementById(sectionId);
    if (!activeSection) return;

    const wrapper = activeSection.querySelector('.header-action-wrapper');
    if (wrapper) {
        const actions = wrapper.querySelector('.section-header-actions');
        if (actions) {
            if (!wrapper.id) {
                wrapper.id = 'wrapper-' + sectionId;
            }
            actions.dataset.originalWrapper = wrapper.id;
            
            // Move to header only on desktop (min-width: 992px)
            if (window.matchMedia('(min-width: 992px)').matches) {
                topbarActions.appendChild(actions);
            } else {
                // Return to table wrapper on mobile/tablet
                wrapper.appendChild(actions);
            }
        }
    }
}

function setActiveSection(sectionId) {
    document.querySelectorAll('.content-section').forEach((section) => {
        section.hidden = section.id !== sectionId;
        section.classList.toggle('active', section.id === sectionId);
    });

    document.querySelectorAll('.sidebar-link[data-section]').forEach((link) => {
        link.classList.toggle('active', link.dataset.section === sectionId);
    });

    updateTopbarActions(sectionId);

    if (typeof window.Chart !== 'undefined') {
        Object.values(window.Chart.instances || {}).forEach((chart) => chart.resize());
    }
}

function updatePageHeading(title, subtitle) {
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');

    if (title && pageTitle) pageTitle.textContent = title;
    if (pageSubtitle) pageSubtitle.textContent = subtitle || '';
    if (title) document.title = `${title} - Nextlog`;
}

function closeMobileSidebarAfterNavigation() {
    if (isDesktopSidebar()) return;
    document.getElementById('sidebar')?.classList.remove('show');
    document.getElementById('sidebarOverlay')?.classList.remove('show');
}

function executeSectionScripts(container) {
    container.querySelectorAll('script').forEach((oldScript) => {
        const script = document.createElement('script');
        Array.from(oldScript.attributes).forEach((attr) => script.setAttribute(attr.name, attr.value));
        script.text = oldScript.textContent;
        oldScript.replaceWith(script);
    });
}

function relocateSectionModals(section) {
    section.querySelectorAll('.modal.inventrack-modal').forEach((modal) => relocateInventrackModal(modal));
}

async function fetchSection(sectionId, href) {
    if (!sectionShell) throw new Error('Section shell tidak ditemukan.');

    const requestUrl = new URL(href, window.location.origin);
    requestUrl.searchParams.set('inventrack_section', '1');

    const response = await fetch(requestUrl.toString(), {
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) throw new Error(`Section request failed: ${response.status}`);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = await response.text();
    const fragment = wrapper.querySelector('.inventrack-section-fragment');
    if (!fragment) throw new Error('Section fragment tidak ditemukan.');

    const section = document.createElement('section');
    section.id = sectionId;
    section.className = 'content-section';
    section.hidden = true;
    section.dataset.loaded = 'true';
    section.dataset.title = fragment.dataset.title || '';
    section.dataset.subtitle = fragment.dataset.subtitle || '';
    section.innerHTML = fragment.innerHTML;
    sectionShell.appendChild(section);

    setActiveSection(sectionId);
    wrapper.querySelectorAll('script').forEach((script) => section.appendChild(script));
    executeSectionScripts(section);
    relocateSectionModals(section);
    sectionCache.add(sectionId);

    return section;
}

function resetTransactionForm(sectionId) {

    const section = document.getElementById(sectionId);

    if (!section) {
        console.log('SECTION TIDAK DITEMUKAN');
        return;
    }

    const form = section.querySelector('#txInlineForm');

    if (!form) {
        console.log('FORM TIDAK DITEMUKAN');
        return;
    }

    console.log('RESET FORM:', sectionId);

    form.reset();

    // reset field readonly
    section.querySelectorAll(
        '#txInlineNoNormalisasi, #txInlineCategory, #txInlineItemCategory, #txInlineStock, #txInlineVolume, #txInlineLokasi, #txInlineUnit'
    ).forEach(el => {
        el.value = '';
    });

    // reset ship checkbox
    section.querySelectorAll('.tx-ship-checkbox').forEach(cb => {
        cb.checked = false;
    });

    const allShip = section.querySelector('#txShipAll');

    if (allShip) {
        allShip.checked = false;
    }

    const warning = section.querySelector('#txInlineStockWarning');

    if (warning) {
        warning.style.display = 'none';
    }
}

async function switchSection(sectionId, trigger, options = {}) {
    const link = trigger || getSectionLink(sectionId);
    if (!link || !sectionShell) return true;

    const href = link.getAttribute('href');
    const existingSection = document.getElementById(sectionId);

    try {
        if (!sectionCache.has(sectionId) && !existingSection) {
            sectionShell.classList.add('section-loading');
            await fetchSection(sectionId, href);
        }

        const targetSection = document.getElementById(sectionId);
        if (!targetSection) return true;

        setActiveSection(sectionId);
        if (
            sectionId === 'transactionsReceiptSection' ||
            sectionId === 'transactionsIssueSection'
        ) {
            resetTransactionForm(sectionId);
        }
        updatePageHeading(targetSection.dataset.title || link.textContent.trim(), targetSection.dataset.subtitle || '');
        closeMobileSidebarAfterNavigation();

        if (!options.replace && window.location.href !== new URL(href, window.location.origin).href) {
            history.pushState({ sectionId }, '', href);
        }
    } catch (error) {
        console.error(error);
        window.location.href = href;
        return true;
    } finally {
        sectionShell.classList.remove('section-loading');
    }

    return false;
}

window.switchSection = switchSection;

window.addEventListener('popstate', () => {
    const sectionId = sectionIdFromUrl(window.location.href);
    switchSection(sectionId, getSectionLink(sectionId), { replace: true });
});

function syncTopbarActions() {
    if (config.currentSectionId) {
        updateTopbarActions(config.currentSectionId);
    }
}
window.addEventListener('resize', syncTopbarActions);

if (config.currentSectionId) {
    updateTopbarActions(config.currentSectionId);
}

async function switchSaBidang(sectionId, trigger) {
    if (!sectionShell) return true;

    const href = trigger.getAttribute('href');
    const url = new URL(href, window.location.origin);
    url.searchParams.set('inventrack_section', '1');

    sectionShell.classList.add('section-loading');

    try {
        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) throw new Error(`Section request failed: ${response.status}`);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = await response.text();
        const fragment = wrapper.querySelector('.inventrack-section-fragment');
        if (!fragment) throw new Error('Section fragment tidak ditemukan.');

        // Find the existing section
        let section = document.getElementById(sectionId);
        if (!section) {
            section = document.createElement('section');
            section.id = sectionId;
            section.className = 'content-section';
            sectionShell.appendChild(section);
        }

        // Replace content
        section.innerHTML = fragment.innerHTML;
        section.dataset.title = fragment.dataset.title || '';
        section.dataset.subtitle = fragment.dataset.subtitle || '';

        // Execute scripts & relocate modals
        wrapper.querySelectorAll('script').forEach((script) => section.appendChild(script));
        executeSectionScripts(section);
        relocateSectionModals(section);

        setActiveSection(sectionId);
        updatePageHeading(section.dataset.title, section.dataset.subtitle);

        if (window.location.href !== new URL(href, window.location.origin).href) {
            history.pushState({ sectionId }, '', href);
        }
    } catch (error) {
        console.error(error);
        window.location.href = href;
        return true;
    } finally {
        sectionShell.classList.remove('section-loading');
    }

    return false;
}

window.switchSaBidang = switchSaBidang;
