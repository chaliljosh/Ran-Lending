// Loan Details — tab switching (Loan Details / Loan History).
//
// Markup contract (see loan-details.blade.php):
//   [data-ld-tabs]              the tablist wrapper
//   [data-ld-tab="name"]        each tab button
//   [data-ld-panel="name"]      each tab panel (inactive ones carry `hidden`)
//
// If the page has no tablist (e.g. the borrower has no loan history,
// or no application at all), this script simply does nothing.

(function () {
    "use strict";

    const tablist = document.querySelector("[data-ld-tabs]");
    if (!tablist) {
        return;
    }

    const tabs = Array.from(tablist.querySelectorAll("[data-ld-tab]"));
    const panels = {};

    document.querySelectorAll("[data-ld-panel]").forEach(function (panel) {
        panels[panel.dataset.ldPanel] = panel;
    });

    // =========================================================
    // ACTIVATE A TAB
    // =========================================================

    function activate(name, options) {
        const opts = options || {};

        if (!panels[name]) {
            return;
        }

        tabs.forEach(function (tab) {
            const isActive = tab.dataset.ldTab === name;

            tab.classList.toggle("is-active", isActive);
            tab.setAttribute("aria-selected", isActive ? "true" : "false");
            // Roving tabindex: only the active tab is in the tab order,
            // arrow keys move between tabs.
            tab.tabIndex = isActive ? 0 : -1;

            if (isActive && opts.focus) {
                tab.focus();
            }
        });

        Object.keys(panels).forEach(function (key) {
            panels[key].hidden = key !== name;
        });

        // Keep the chosen tab in the URL hash so a refresh (or a shared link)
        // lands on the same tab. The default tab keeps a clean URL.
        if (opts.updateUrl) {
            try {
                const base = window.location.pathname + window.location.search;
                const url = name === tabs[0].dataset.ldTab ? base : base + "#" + name;
                window.history.replaceState(null, "", url);
            } catch (err) {
                // history API unavailable — not critical.
            }
        }
    }

    // =========================================================
    // CLICK
    // =========================================================

    tabs.forEach(function (tab) {
        tab.addEventListener("click", function () {
            activate(tab.dataset.ldTab, { updateUrl: true });
        });
    });

    // =========================================================
    // KEYBOARD (Left / Right / Home / End)
    // =========================================================

    tablist.addEventListener("keydown", function (event) {
        const currentIndex = tabs.findIndex(function (tab) {
            return tab.classList.contains("is-active");
        });

        let nextIndex = null;

        switch (event.key) {
            case "ArrowRight":
                nextIndex = (currentIndex + 1) % tabs.length;
                break;
            case "ArrowLeft":
                nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                break;
            case "Home":
                nextIndex = 0;
                break;
            case "End":
                nextIndex = tabs.length - 1;
                break;
            default:
                return;
        }

        event.preventDefault();
        activate(tabs[nextIndex].dataset.ldTab, {
            focus: true,
            updateUrl: true,
        });
    });

    // =========================================================
    // CLICKABLE HISTORY ROWS
    // =========================================================
    //
    // The whole row is clickable, not just the "View" link. The link itself
    // stays in the markup (keyboard / screen-reader / right-click friendly),
    // this handler is only the mouse shortcut on top of it.
    //   data-href     -> open that loan (Ctrl/Cmd-click opens a new tab)
    //   data-ld-goto  -> the loan is already the one being viewed, so just
    //                    switch to the Loan Details tab

    if (panels.history) {
        panels.history.addEventListener("click", function (event) {
            // Real links / buttons do their own thing.
            if (event.target.closest("a, button")) {
                return;
            }

            // Don't hijack the click when the user is selecting text.
            const selection = window.getSelection();
            if (selection && String(selection).length > 0) {
                return;
            }

            const row = event.target.closest("tr.bwl-ld-row-link");
            if (!row) {
                return;
            }

            if (row.dataset.href) {
                if (event.ctrlKey || event.metaKey) {
                    window.open(row.dataset.href, "_blank", "noopener");
                } else {
                    window.location.href = row.dataset.href;
                }
            } else if (row.dataset.ldGoto) {
                activate(row.dataset.ldGoto, { updateUrl: true });
            }
        });
    }

    // =========================================================
    // INITIAL STATE — honour #history / #details in the URL
    // =========================================================

    const initial = window.location.hash.replace("#", "");
    if (initial && panels[initial]) {
        activate(initial);
    }
})();