{{--
    Problems reported to TOIC, and TOIC's maintenance notice, shared by every page.
    FRONT END ONLY for now: kept in this browser (localStorage), starting from ToicController::issues().
    So a problem reported while signed in as a Dean shows up for TOIC in the same browser.
    TODO (backend): an issues table, and a maintenance setting everyone reads.

    window.IARIS_ISSUES: all(), get(id), add(issue), save(), next() (a new INC number), open() (how many need work),
    now() (a timestamp), mode() / setMode({on, msg}) (maintenance).
--}}
<script>
    window.IARIS_ISSUES = (() => {
        const KEY = 'iaris.issues', MODE = 'iaris.maintenance';
        let list = null;
        try { list = JSON.parse(localStorage.getItem(KEY)); } catch (e) {}
        if (!Array.isArray(list)) list = @json(\App\Http\Controllers\ToicController::issues());
        const save = () => { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} };
        const now = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };
        return {
            all: () => list.slice(),
            get: id => list.find(x => x.id === id),
            add: issue => { list.unshift(issue); save(); },
            save,
            now,
            next: () => 'INC-' + String(Math.max(0, ...list.map(x => +x.id.split('-')[1])) + 1).padStart(4, '0'),
            open: () => list.filter(x => x.st === 'new' || x.st === 'investigating').length,
            mode: () => { try { return JSON.parse(localStorage.getItem(MODE)) || { on: false }; } catch (e) { return { on: false }; } },
            setMode: m => { try { localStorage.setItem(MODE, JSON.stringify(m)); } catch (e) {} },
        };
    })();
    // TOIC's sidebar: how many issues need work
    (() => {
        const badge = document.getElementById('issuesBadge'), n = IARIS_ISSUES.open();
        if (badge) { badge.textContent = n; badge.classList.toggle('d-none', !n); }
    })();
    // Maintenance notice (partials/maintenance-banner)
    (() => {
        const m = IARIS_ISSUES.mode(), banner = document.getElementById('maintenanceBanner');
        if (!banner || !m.on) return;
        banner.querySelector('span').textContent = m.msg || 'iARIS is in maintenance. You can view data, but changes are paused until TOIC finishes.';
        banner.classList.remove('d-none');
    })();
</script>
