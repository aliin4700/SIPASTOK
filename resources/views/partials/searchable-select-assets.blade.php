@once
<style>
    .sipastok-ss-container {
        position: relative;
        width: 100%;
    }
    .sipastok-ss-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 0.92rem;
        line-height: 1.4;
        color: #212529;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        cursor: pointer;
        text-align: left;
        user-select: none;
        gap: 0.4rem;
    }
    .sipastok-ss-trigger:focus,
    .sipastok-ss-container.is-open .sipastok-ss-trigger {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
    }
    .sipastok-ss-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sipastok-ss-label.is-placeholder {
        color: #6c757d;
    }
    .sipastok-ss-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        flex-shrink: 0;
    }
    .sipastok-ss-clear {
        display: none;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #e9ecef;
        color: #495057;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        border: none;
        padding: 0;
    }
    .sipastok-ss-clear:hover {
        background: #dc3545;
        color: #fff;
    }
    .sipastok-ss-container.has-value .sipastok-ss-clear {
        display: inline-flex;
    }
    .sipastok-ss-caret {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #6c757d;
        display: inline-block;
    }
    .sipastok-ss-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1060;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        overflow: hidden;
    }
    .sipastok-ss-container.is-open .sipastok-ss-dropdown {
        display: block;
    }
    .sipastok-ss-search-wrap {
        padding: 0.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .sipastok-ss-list {
        max-height: 240px;
        overflow-y: auto;
        margin: 0;
        padding: 0.25rem 0;
        list-style: none;
    }
    .sipastok-ss-group-header {
        padding: 0.35rem 0.75rem;
        font-size: 0.73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        background: #f1f5f9;
    }
    .sipastok-ss-option {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        color: #1e293b;
        cursor: pointer;
        transition: background-color 0.1s ease;
    }
    .sipastok-ss-option:hover,
    .sipastok-ss-option.is-active {
        background-color: #eff6ff;
        color: #0d6efd;
    }
    .sipastok-ss-option.is-selected {
        background-color: #0d6efd;
        color: #fff;
        font-weight: 600;
    }
    .sipastok-ss-empty {
        padding: 0.75rem;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
    }
</style>

<script>
(function () {
    if (window.SipastokSearchableSelect) {
        return;
    }

    let openContainer = null;

    function closeCurrent() {
        if (openContainer) {
            openContainer.classList.remove('is-open');
            openContainer = null;
        }
    }

    document.addEventListener('click', function (event) {
        if (openContainer && !openContainer.contains(event.target)) {
            closeCurrent();
        }
    });

    function getPlaceholderText(select) {
        const emptyOpt = select.querySelector('option[value=""]');
        return emptyOpt ? emptyOpt.textContent.trim() : '-- Pilih Item --';
    }

    function syncTrigger(select) {
        const container = select._ssContainer;
        if (!container) {
            return;
        }

        const labelEl = container.querySelector('.sipastok-ss-label');
        const selectedOption = select.options[select.selectedIndex];
        const hasVal = Boolean(selectedOption && selectedOption.value !== '');

        container.classList.toggle('has-value', hasVal);
        if (hasVal) {
            labelEl.textContent = selectedOption.textContent.trim();
            labelEl.classList.remove('is-placeholder');
        } else {
            labelEl.textContent = getPlaceholderText(select);
            labelEl.classList.add('is-placeholder');
        }
    }

    function buildList(select, listEl, query) {
        listEl.innerHTML = '';
        const q = (query || '').trim().toLowerCase();
        let matchCount = 0;

        function appendOptionItem(opt) {
            if (!opt.value || opt.hidden) {
                return;
            }
            const label = opt.textContent.trim();
            const searchMeta = (opt.getAttribute('data-search') || label).toLowerCase();
            if (q && !label.toLowerCase().includes(q) && !searchMeta.includes(q)) {
                return;
            }

            const li = document.createElement('li');
            li.className = 'sipastok-ss-option' + (select.value === opt.value ? ' is-selected' : '');
            li.textContent = label;
            li.setAttribute('data-value', opt.value);

            li.addEventListener('click', function (e) {
                e.stopPropagation();
                select.value = opt.value;
                syncTrigger(select);
                closeCurrent();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            listEl.appendChild(li);
            matchCount++;
        }

        Array.from(select.children).forEach(function (child) {
            if (child.tagName === 'OPTGROUP') {
                const tempFragment = document.createDocumentFragment();
                const groupCountBefore = matchCount;

                Array.from(child.children).forEach(function (opt) {
                    if (!opt.value || opt.hidden) {
                        return;
                    }
                    const label = opt.textContent.trim();
                    const searchMeta = (opt.getAttribute('data-search') || label).toLowerCase();
                    if (q && !label.toLowerCase().includes(q) && !searchMeta.includes(q)) {
                        return;
                    }

                    const li = document.createElement('li');
                    li.className = 'sipastok-ss-option' + (select.value === opt.value ? ' is-selected' : '');
                    li.textContent = label;
                    li.setAttribute('data-value', opt.value);

                    li.addEventListener('click', function (e) {
                        e.stopPropagation();
                        select.value = opt.value;
                        syncTrigger(select);
                        closeCurrent();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    tempFragment.appendChild(li);
                    matchCount++;
                });

                if (matchCount > groupCountBefore) {
                    const headerLi = document.createElement('li');
                    headerLi.className = 'sipastok-ss-group-header';
                    headerLi.textContent = child.getAttribute('label') || '';
                    listEl.appendChild(headerLi);
                    listEl.appendChild(tempFragment);
                }
            } else if (child.tagName === 'OPTION') {
                appendOptionItem(child);
            }
        });

        if (matchCount === 0) {
            const emptyLi = document.createElement('li');
            emptyLi.className = 'sipastok-ss-empty';
            emptyLi.textContent = 'Item tidak ditemukan.';
            listEl.appendChild(emptyLi);
        }
    }

    function enhanceSelect(select) {
        if (!select || select._ssContainer) {
            if (select && select._ssContainer) {
                syncTrigger(select);
            }
            return;
        }

        // Bersihkan wrapper hasil clone jika ada
        const nextEl = select.nextElementSibling;
        if (nextEl && nextEl.classList.contains('sipastok-ss-container')) {
            nextEl.remove();
        }

        select.style.display = 'none';

        const container = document.createElement('div');
        container.className = 'sipastok-ss-container';
        container.innerHTML =
            '<div class="sipastok-ss-trigger" tabindex="0" role="combobox" aria-expanded="false">' +
                '<span class="sipastok-ss-label is-placeholder"></span>' +
                '<span class="sipastok-ss-actions">' +
                    '<button type="button" class="sipastok-ss-clear" title="Kosongkan pilihan" aria-label="Kosongkan pilihan">&times;</button>' +
                    '<span class="sipastok-ss-caret"></span>' +
                '</span>' +
            '</div>' +
            '<div class="sipastok-ss-dropdown">' +
                '<div class="sipastok-ss-search-wrap">' +
                    '<input type="search" class="form-control form-control-sm sipastok-ss-search" placeholder="Ketik nama item untuk mencari..." autocomplete="off">' +
                '</div>' +
                '<ul class="sipastok-ss-list"></ul>' +
            '</div>';

        select.insertAdjacentElement('afterend', container);
        select._ssContainer = container;

        const trigger = container.querySelector('.sipastok-ss-trigger');
        const clearBtn = container.querySelector('.sipastok-ss-clear');
        const searchInput = container.querySelector('.sipastok-ss-search');
        const listEl = container.querySelector('.sipastok-ss-list');

        syncTrigger(select);

        function openDropdown() {
            if (openContainer && openContainer !== container) {
                closeCurrent();
            }
            container.classList.add('is-open');
            openContainer = container;
            searchInput.value = '';
            buildList(select, listEl, '');
            setTimeout(function () {
                searchInput.focus();
            }, 10);
        }

        trigger.addEventListener('click', function (e) {
            if (e.target === clearBtn || clearBtn.contains(e.target)) {
                return;
            }
            if (container.classList.contains('is-open')) {
                closeCurrent();
            } else {
                openDropdown();
            }
        });

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });

        clearBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            select.value = '';
            syncTrigger(select);
            closeCurrent();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        searchInput.addEventListener('input', function () {
            buildList(select, listEl, searchInput.value);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeCurrent();
                trigger.focus();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const firstOpt = listEl.querySelector('.sipastok-ss-option');
                if (firstOpt) {
                    firstOpt.click();
                }
            }
        });

        select.addEventListener('change', function () {
            syncTrigger(select);
        });
    }

    function initAll(root) {
        const scope = root || document;
        scope.querySelectorAll('select.sipastok-searchable-select').forEach(enhanceSelect);
    }

    window.SipastokSearchableSelect = {
        init: initAll,
        enhance: enhanceSelect,
        sync: syncTrigger,
    };

    window.SipastokItemForm = {
        clearRow: function (row) {
            if (!row) return;
            row.querySelectorAll('select').forEach(function (sel) {
                sel.value = '';
                syncTrigger(sel);
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            });
            row.querySelectorAll('input:not([type="hidden"])').forEach(function (inp) {
                inp.value = '';
            });
            row.querySelectorAll('.mr-item-info, .production-item-info, .waste-item-info').forEach(function (info) {
                info.textContent = '';
            });
        },
        reindexRows: function (tbody, rowSelector) {
            Array.from(tbody.querySelectorAll(rowSelector)).forEach(function (row, idx) {
                row.querySelectorAll('select, input, textarea').forEach(function (field) {
                    const name = field.getAttribute('name');
                    if (name) {
                        field.setAttribute('name', name.replace(/items\[\d+\]/, 'items[' + idx + ']'));
                    }
                });
            });
        },
        removeOrClearRow: function (row, tbody, rowSelector) {
            if (!row || !tbody) return;
            const rows = tbody.querySelectorAll(rowSelector);
            if (rows.length > 1) {
                row.remove();
                window.SipastokItemForm.reindexRows(tbody, rowSelector);
            } else {
                window.SipastokItemForm.clearRow(row);
            }
        },
        resetAllRows: function (tbody, rowSelector) {
            if (!tbody) return;
            tbody.querySelectorAll(rowSelector).forEach(function (row) {
                window.SipastokItemForm.clearRow(row);
            });
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
        });
    } else {
        initAll(document);
    }
})();
</script>
@endonce
