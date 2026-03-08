(function () {
    var themeColorPicker = document.getElementById('waal-theme-color');
    var themeColorHex = document.getElementById('waal-theme-color-hex');
    var normalizeHexColor = function (value) {
        var normalized = String(value || '').trim().toUpperCase();
        if (normalized && normalized.charAt(0) !== '#') {
            normalized = '#' + normalized;
        }
        return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : '';
    };

    if (themeColorPicker && themeColorHex) {
        themeColorPicker.addEventListener('input', function () {
            themeColorHex.value = String(themeColorPicker.value || '').toUpperCase();
        });

        themeColorHex.addEventListener('input', function () {
            var normalized = normalizeHexColor(themeColorHex.value);
            if (normalized) {
                themeColorPicker.value = normalized;
            }
        });

        themeColorHex.addEventListener('blur', function () {
            var normalized = normalizeHexColor(themeColorHex.value);
            themeColorHex.value = normalized || String(themeColorPicker.value || '').toUpperCase();
        });
    }

    var insightsForm = document.querySelector('.waal-insights-form');
    if (insightsForm) {
        var rangeSelect = document.getElementById('waal-compliance-range');
        var fromInput = document.getElementById('waal-compliance-from');
        var toInput = document.getElementById('waal-compliance-to');

        var syncComplianceRangeState = function () {
            if (!rangeSelect || !fromInput || !toInput) {
                return;
            }
            var isCustom = rangeSelect.value === 'custom';
            fromInput.disabled = !isCustom;
            toInput.disabled = !isCustom;
        };

        if (rangeSelect) {
            rangeSelect.addEventListener('change', syncComplianceRangeState);
        }

        insightsForm.addEventListener('submit', function () {
            if (rangeSelect && rangeSelect.value === 'custom') {
                if (fromInput) fromInput.disabled = false;
                if (toInput) toInput.disabled = false;
            }
        });

        syncComplianceRangeState();
    }

    var form = document.getElementById('waal-table-toolbar');
    if (form) {
        var perPage = document.getElementById('waal-per-page');
        var search = document.getElementById('waal-search');
        var timer;

        if (perPage) {
            perPage.addEventListener('change', function () {
                form.submit();
            });
        }

        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    form.submit();
                }, 450);
            });
        }
    }

    var purgeOpen = document.getElementById('waal-open-purge-modal');
    var purgeModal = document.getElementById('waal-purge-modal');
    var filterForm = document.getElementById('waal-log-filter-form');
    var presetPicker = document.querySelector('[data-waal-preset-picker]');
    var savePresetOpen = document.getElementById('waal-open-preset-modal');
    var savePresetModal = document.getElementById('waal-save-preset-modal');
    var savePresetForm = document.getElementById('waal-save-preset-form');
    if (filterForm && presetPicker) {
        var presetInput = presetPicker.querySelector('[data-waal-preset-input]');
        var presetTrigger = presetPicker.querySelector('[data-waal-preset-trigger]');
        var presetLabel = presetPicker.querySelector('[data-waal-preset-label]');
        var presetMenu = presetPicker.querySelector('[data-waal-preset-menu]');
        var deletePresetForm = document.getElementById('waal-delete-preset-form');
        var deletePresetKey = deletePresetForm ? deletePresetForm.querySelector('[data-waal-preset-delete-key]') : null;
        var closePresetMenu = function () {
            if (!presetMenu || !presetTrigger) return;
            presetMenu.hidden = true;
            presetTrigger.setAttribute('aria-expanded', 'false');
            presetPicker.classList.remove('is-open');
        };
        if (presetTrigger && presetMenu && presetInput) {
            presetTrigger.addEventListener('click', function () {
                var willOpen = presetMenu.hidden;
                closePresetMenu();
                if (willOpen) {
                    presetMenu.hidden = false;
                    presetTrigger.setAttribute('aria-expanded', 'true');
                    presetPicker.classList.add('is-open');
                }
            });
            presetMenu.querySelectorAll('[data-waal-preset-option]').forEach(function (option) {
                option.addEventListener('click', function () {
                    presetInput.value = option.getAttribute('data-waal-preset-option') || '';
                    if (presetLabel) {
                        presetLabel.textContent = option.getAttribute('data-waal-preset-label-text') || '';
                    }
                    closePresetMenu();
                    filterForm.submit();
                });
            });
            presetMenu.querySelectorAll('[data-waal-preset-delete]').forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (!deletePresetForm || !deletePresetKey) return;
                    deletePresetKey.value = button.getAttribute('data-waal-preset-delete') || '';
                    deletePresetForm.submit();
                });
            });
            document.addEventListener('click', function (e) {
                if (!presetPicker.contains(e.target)) {
                    closePresetMenu();
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePresetMenu();
                }
            });
        }
    }
    if (savePresetOpen && savePresetModal && savePresetForm && filterForm) {
        var copyPresetFilters = function () {
            ['from', 'to', 'user', 'log_event', 'log_action', 'q'].forEach(function (name) {
                var source = filterForm.querySelector('[name="' + name + '"]');
                var target = savePresetForm.querySelector('[name="' + name + '"]');
                if (source && target) {
                    target.value = source.value || '';
                }
            });
        };
        var closeSavePresetModal = function () {
            savePresetModal.classList.remove('is-visible');
            savePresetModal.setAttribute('aria-hidden', 'true');
        };
        savePresetOpen.addEventListener('click', function () {
            copyPresetFilters();
            savePresetModal.classList.add('is-visible');
            savePresetModal.setAttribute('aria-hidden', 'false');
            var nameInput = savePresetForm.querySelector('[name="waal_preset_name"]');
            if (nameInput) {
                nameInput.value = '';
                nameInput.focus();
            }
        });
        savePresetModal.querySelectorAll('[data-waal-close-preset-modal]').forEach(function (el) {
            el.addEventListener('click', closeSavePresetModal);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && savePresetModal.classList.contains('is-visible')) {
                closeSavePresetModal();
            }
        });
    }
    if (purgeOpen && purgeModal) {
        purgeOpen.addEventListener('click', function () {
            purgeModal.classList.add('is-visible');
            purgeModal.setAttribute('aria-hidden', 'false');
        });
        purgeModal.querySelectorAll('[data-waal-close-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                purgeModal.classList.remove('is-visible');
                purgeModal.setAttribute('aria-hidden', 'true');
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && purgeModal.classList.contains('is-visible')) {
                purgeModal.classList.remove('is-visible');
                purgeModal.setAttribute('aria-hidden', 'true');
            }
        });
    }

    var ipBadgeSelector = '.waal-ip-badge[data-ip]';
    var ipTooltip;
    var ipTooltipTimer;
    var ipCache = Object.create(null);

    var ensureIpTooltip = function () {
        if (ipTooltip) return ipTooltip;
        ipTooltip = document.createElement('div');
        ipTooltip.className = 'waal-ip-tooltip';
        ipTooltip.hidden = true;
        document.body.appendChild(ipTooltip);
        ipTooltip.addEventListener('mouseenter', function () {
            clearTimeout(ipTooltipTimer);
        });
        ipTooltip.addEventListener('mouseleave', function () {
            ipTooltip.hidden = true;
        });
        return ipTooltip;
    };

    var escapeHtml = function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    var scopeLabel = function (scope) {
        var map = {
            'public': 'Public',
            'private': 'Private',
            'loopback': 'Loopback',
            'reserved': 'Reserved',
            'invalid': 'Invalid'
        };
        return map[scope] || scope;
    };

    var detectIpType = function (ip) {
        return ip && ip.indexOf(':') !== -1 ? 'IPv6' : 'IPv4';
    };

    var detectIpScope = function (ip) {
        var value = String(ip || '').trim().toLowerCase();
        if (!value) return 'invalid';
        if (value === '::1' || value.indexOf('fe80:') === 0) return 'loopback';
        if (value.indexOf('127.') === 0 || value.indexOf('10.') === 0 || value.indexOf('192.168.') === 0) return 'private';
        if (value.indexOf('172.') === 0) {
            var parts = value.split('.');
            var second = parseInt(parts[1], 10);
            if (!isNaN(second) && second >= 16 && second <= 31) return 'private';
        }
        return 'public';
    };

    var renderIpInfo = function (info) {
        var i18n = window.waalAdminI18n || {};
        var geo = info.geo || {};
        var rows = [
            [i18n.ipInfoType || 'Type', info.type || '-'],
            [i18n.ipInfoScope || 'Scope', scopeLabel(info.scope || '-')],
            [i18n.ipInfoCountry || 'Country', geo.country || '-'],
            [i18n.ipInfoRegion || 'Region', geo.region || '-'],
            [i18n.ipInfoCity || 'City', geo.city || '-'],
            [i18n.ipInfoOrg || 'Organization', geo.org || '-'],
            [i18n.ipInfoTimezone || 'Timezone', geo.timezone || '-']
        ];
        var html = '<div class="waal-ip-tooltip-head"><strong>' + escapeHtml(i18n.ipInfoTitle || 'IP Information') + '</strong></div>';
        html += '<div class="waal-ip-tooltip-ip">' + escapeHtml(info.ip || '-') + '</div>';
        html += '<dl class="waal-ip-tooltip-list">';
        rows.forEach(function (row) {
            html += '<dt>' + escapeHtml(row[0]) + '</dt><dd>' + escapeHtml(row[1]) + '</dd>';
        });
        html += '</dl>';
        if (info.geo_lookup_disabled) {
            html += '<p class="waal-ip-tooltip-note">' + escapeHtml(i18n.ipInfoExternalDisabled || 'External IP geolocation is disabled until you enable it in Settings.') + '</p>';
        }
        return html;
    };

    var placeTooltipNear = function (target) {
        if (!ipTooltip || !target) return;
        var rect = target.getBoundingClientRect();
        var top = window.scrollY + rect.bottom + 8;
        var left = window.scrollX + rect.left;
        var maxLeft = window.scrollX + window.innerWidth - 310;
        if (left > maxLeft) left = Math.max(window.scrollX + 8, maxLeft);
        ipTooltip.style.top = top + 'px';
        ipTooltip.style.left = left + 'px';
    };

    var loadIpInfo = function (ip, done) {
        if (ipCache[ip]) {
            done(ipCache[ip]);
            return;
        }
        if (!window.waalAdminAjax || !waalAdminAjax.ajaxUrl || !waalAdminAjax.nonce) {
            done(null);
            return;
        }
        if (!Number(window.waalAdminAjax.geoLookupEnabled || 0)) {
            done({
                ip: ip,
                type: detectIpType(ip),
                scope: detectIpScope(ip),
                geo: {},
                geo_lookup_disabled: true
            });
            return;
        }
        var body = new URLSearchParams();
        body.set('action', 'waal_ip_info');
        body.set('nonce', waalAdminAjax.nonce);
        body.set('ip', ip);
        fetch(waalAdminAjax.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json && json.success && json.data) {
                    ipCache[ip] = json.data;
                    done(json.data);
                } else {
                    done(null);
                }
            })
            .catch(function () {
                done(null);
            });
    };

    document.addEventListener('mouseover', function (e) {
        var badge = e.target.closest(ipBadgeSelector);
        if (!badge) return;
        var ip = badge.getAttribute('data-ip');
        if (!ip) return;
        var tooltip = ensureIpTooltip();
        var i18n = window.waalAdminI18n || {};
        clearTimeout(ipTooltipTimer);
        tooltip.hidden = false;
        tooltip.innerHTML = '<div class="waal-ip-tooltip-loading">' + escapeHtml(i18n.ipInfoLoading || 'Loading IP info...') + '</div>';
        placeTooltipNear(badge);

        var baseInfo = {
            ip: ip,
            type: detectIpType(ip),
            scope: detectIpScope(ip),
            geo: {}
        };

        loadIpInfo(ip, function (info) {
            if (!tooltip || tooltip.hidden) return;
            tooltip.innerHTML = renderIpInfo(info || baseInfo);
            placeTooltipNear(badge);
        });
    });

    document.addEventListener('mouseout', function (e) {
        var badge = e.target.closest(ipBadgeSelector);
        if (!badge || !ipTooltip) return;
        ipTooltipTimer = setTimeout(function () {
            if (ipTooltip) ipTooltip.hidden = true;
        }, 160);
    });

    var detailModal = document.getElementById('waal-log-detail-modal');
    if (detailModal) {
        var detailFields = {
            no: detailModal.querySelector('[data-waal-detail-no]'),
            severity: detailModal.querySelector('[data-waal-detail-severity]'),
            name: detailModal.querySelector('[data-waal-detail-name]'),
            role: detailModal.querySelector('[data-waal-detail-role]'),
            event: detailModal.querySelector('[data-waal-detail-event]'),
            action: detailModal.querySelector('[data-waal-detail-action]'),
            content: detailModal.querySelector('[data-waal-detail-content]'),
            ua: detailModal.querySelector('[data-waal-detail-ua]'),
            time: detailModal.querySelector('[data-waal-detail-time]'),
            ip: detailModal.querySelector('[data-waal-detail-ip]'),
            ipMeta: detailModal.querySelector('[data-waal-detail-ip-meta]'),
            jsonToggle: detailModal.querySelector('[data-waal-toggle-json]'),
            jsonPre: detailModal.querySelector('[data-waal-detail-json]'),
            incidentStatus: detailModal.querySelector('[data-waal-incident-status]'),
            incidentNote: detailModal.querySelector('[data-waal-incident-note]'),
            incidentNoteMeta: detailModal.querySelector('[data-waal-incident-note-meta]'),
            incidentSave: detailModal.querySelector('[data-waal-save-incident-note]')
        };
        var currentDetailData = {};

        var closeDetailModal = function () {
            detailModal.classList.remove('is-visible');
            detailModal.setAttribute('aria-hidden', 'true');
        };

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.waal-open-log-detail');
            if (!trigger) return;

            var raw = trigger.getAttribute('data-waal-detail') || '';
            var data = {};
            try {
                data = JSON.parse(raw);
            } catch (err) {
                data = {};
            }
            currentDetailData = Object.assign({}, data);

            var bindText = function (key, value) {
                if (detailFields[key]) detailFields[key].textContent = value || '-';
            };

            bindText('no', String(data.no || '-'));
            bindText('severity', String(data.severity || '-'));
            bindText('name', String(data.name || '-'));
            bindText('role', String(data.role || '-'));
            bindText('event', String(data.event || '-'));
            bindText('action', String(data.action || '-'));
            bindText('content', String(data.content || '-'));
            bindText('ua', String(data.ua || '-'));
            bindText('time', String(data.time || '-'));
            bindText('ip', String(data.ip || '-'));
            var i18n = window.waalAdminI18n || {};
            if (detailFields.incidentNote) {
                detailFields.incidentNote.value = String(data.incident_note || '');
            }
            if (detailFields.incidentStatus) {
                detailFields.incidentStatus.value = String(data.incident_status || 'open');
            }
            if (detailFields.incidentNoteMeta) {
                detailFields.incidentNoteMeta.textContent = String(data.incident_updated_at || '').trim()
                    ? (i18n.lastUpdated || 'Last updated') + ': ' + String(data.incident_updated_at)
                    : (i18n.notesStored || 'Notes are stored locally for audit investigation.');
            }
            if (detailFields.jsonPre) {
                detailFields.jsonPre.hidden = true;
                detailFields.jsonPre.textContent = '';
            }
            if (detailFields.jsonToggle) {
                detailFields.jsonToggle.textContent = detailFields.jsonToggle.getAttribute('data-open-label') || 'View JSON';
            }

            var rawIp = String(data.raw_ip || '').trim();
            if (detailFields.ipMeta) {
                detailFields.ipMeta.textContent = i18n.ipInfoLoading || 'Loading IP info...';
            }

            if (!rawIp || rawIp === '-') {
                var noIpText = i18n.ipInfoError || 'IP info is not available.';
                if (detailFields.ipMeta) detailFields.ipMeta.textContent = noIpText;
                currentDetailData.ip_info = null;
                currentDetailData.ip_info_text = noIpText;
            } else {
                // Always show basic local detail first so users still get useful IP context
                // even when external geo lookup is blocked or unavailable.
                var baseRows = [
                    (i18n.ipInfoType || 'Type') + ': ' + detectIpType(rawIp),
                    (i18n.ipInfoScope || 'Scope') + ': ' + scopeLabel(detectIpScope(rawIp))
                ];
                var baseMetaText = baseRows.join(' | ');
                if (detailFields.ipMeta) {
                    detailFields.ipMeta.textContent = baseMetaText;
                }
                currentDetailData.ip_info = {
                    ip: rawIp,
                    type: detectIpType(rawIp),
                    scope: detectIpScope(rawIp),
                    geo: {}
                };
                currentDetailData.ip_info_text = baseMetaText;

                loadIpInfo(rawIp, function (info) {
                    if (!detailFields.ipMeta) return;
                    if (!info) {
                        // Keep previously shown local IP details instead of replacing with error.
                        return;
                    }
                    var geo = info.geo || {};
                    var rows = [
                        (i18n.ipInfoType || 'Type') + ': ' + (info.type || '-'),
                        (i18n.ipInfoScope || 'Scope') + ': ' + scopeLabel(info.scope || '-'),
                        (i18n.ipInfoCountry || 'Country') + ': ' + (geo.country || '-'),
                        (i18n.ipInfoRegion || 'Region') + ': ' + (geo.region || '-'),
                        (i18n.ipInfoCity || 'City') + ': ' + (geo.city || '-'),
                        (i18n.ipInfoOrg || 'Organization') + ': ' + (geo.org || '-'),
                        (i18n.ipInfoTimezone || 'Timezone') + ': ' + (geo.timezone || '-')
                    ];
                    if (info.geo_lookup_disabled) {
                        rows.push(i18n.ipInfoExternalDisabled || 'External IP geolocation is disabled until you enable it in Settings.');
                    }
                    var ipMetaText = rows.join(' | ');
                    detailFields.ipMeta.textContent = ipMetaText;
                    currentDetailData.ip_info = info;
                    currentDetailData.ip_info_text = ipMetaText;
                });
            }

            detailModal.classList.add('is-visible');
            detailModal.setAttribute('aria-hidden', 'false');
        });

        detailModal.querySelectorAll('[data-waal-close-log-detail]').forEach(function (el) {
            el.addEventListener('click', closeDetailModal);
        });
        if (detailFields.jsonToggle && detailFields.jsonPre) {
            detailFields.jsonToggle.addEventListener('click', function () {
                var isHidden = detailFields.jsonPre.hidden;
                var openLabel = detailFields.jsonToggle.getAttribute('data-open-label') || 'View JSON';
                var closeLabel = detailFields.jsonToggle.getAttribute('data-close-label') || 'Hide JSON';
                if (isHidden) {
                    detailFields.jsonPre.textContent = JSON.stringify(currentDetailData || {}, null, 2);
                    detailFields.jsonPre.hidden = false;
                    detailFields.jsonToggle.textContent = closeLabel;
                    return;
                }
                detailFields.jsonPre.hidden = true;
                detailFields.jsonToggle.textContent = openLabel;
            });
        }

        if (detailFields.incidentSave && detailFields.incidentNote) {
            detailFields.incidentSave.addEventListener('click', function () {
                var logId = parseInt(currentDetailData.id, 10);
                if (isNaN(logId) || logId <= 0 || !window.waalAdminAjax || !waalAdminAjax.ajaxUrl || !waalAdminAjax.incidentNonce) {
                    return;
                }

                var i18n = window.waalAdminI18n || {};
                var defaultLabel = detailFields.incidentSave.getAttribute('data-label-default') || detailFields.incidentSave.textContent || 'Save Note';
                detailFields.incidentSave.disabled = true;
                detailFields.incidentSave.textContent = i18n.saving || 'Saving...';

                var body = new URLSearchParams();
                body.append('action', 'waal_save_incident_note');
                body.append('nonce', waalAdminAjax.incidentNonce);
                body.append('log_id', String(logId));
                body.append('incident_note', detailFields.incidentNote.value || '');
                body.append('incident_status', detailFields.incidentStatus ? String(detailFields.incidentStatus.value || 'open') : 'open');

                fetch(waalAdminAjax.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString(),
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json();
                }).then(function (result) {
                    if (!result || !result.success || !result.data) {
                        throw new Error((result && result.data && result.data.message) ? result.data.message : (i18n.saveFailed || 'Failed to save incident note.'));
                    }
                    currentDetailData.incident_note = String(result.data.note || '');
                    currentDetailData.incident_status = String(result.data.status || 'open');
                    currentDetailData.incident_updated_at = String(result.data.updated_at || '');
                    if (detailFields.incidentStatus) {
                        detailFields.incidentStatus.value = currentDetailData.incident_status;
                    }
                    if (detailFields.incidentNoteMeta) {
                        var updatedText = currentDetailData.incident_updated_at
                            ? (i18n.lastUpdated || 'Last updated') + ': ' + currentDetailData.incident_updated_at
                            : '';
                        var statusLabel = String(result.data.status_label || '').trim();
                        detailFields.incidentNoteMeta.textContent = statusLabel
                            ? statusLabel + (updatedText ? ' | ' + updatedText : '')
                            : (updatedText || (i18n.notesStored || 'Notes are stored locally for audit investigation.'));
                    }
                    detailFields.incidentSave.textContent = i18n.saved || 'Saved';
                    setTimeout(function () {
                        detailFields.incidentSave.textContent = defaultLabel;
                    }, 1000);
                }).catch(function (error) {
                    if (detailFields.incidentNoteMeta) {
                        detailFields.incidentNoteMeta.textContent = error && error.message ? error.message : (i18n.saveFailed || 'Failed to save incident note.');
                    }
                    detailFields.incidentSave.textContent = defaultLabel;
                }).finally(function () {
                    detailFields.incidentSave.disabled = false;
                });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && detailModal.classList.contains('is-visible')) {
                closeDetailModal();
            }
        });
    }
})();
