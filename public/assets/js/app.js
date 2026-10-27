/* FGU 3.0 — JavaScript progresif (tanpa dependensi, tanpa inline script agar CSP tetap ketat). */
(function () {
	'use strict';

	var $ = function (sel, root) { return (root || document).querySelector(sel); };
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

	$$('.tableWrap table, .judgeScoreTableWrap table').forEach(function (table) {
		var headings = [];
		$$('thead tr:first-child th', table).forEach(function (heading) {
			for (var span = 0; span < heading.colSpan; span++) {
				headings.push(heading.textContent.trim());
			}
		});
		$$('tbody tr', table).forEach(function (row) {
			var column = 0;
			$$('td', row).forEach(function (cell) {
				if (cell.colSpan === 1 && headings[column] && !cell.hasAttribute('data-label')) {
					cell.setAttribute('data-label', headings[column]);
				}
				column += cell.colSpan;
			});
		});
	});

	/* ---------- Musik beranda publik ---------- */
	var publicMusic = $('[data-public-music]');
	var musicToggle = $('[data-music-toggle]');
	if (publicMusic && musicToggle) {
		var updateMusicToggle = function () {
			var playing = !publicMusic.paused;
			var icon = $('[data-music-icon]', musicToggle);
			if (icon) { icon.textContent = playing ? 'Ⅱ' : '▶'; }
			musicToggle.setAttribute('aria-label', playing ? 'Jeda musik FGU' : 'Putar musik FGU');
			musicToggle.setAttribute('title', playing ? 'Jeda musik FGU' : 'Putar musik FGU');
		};
		var playPublicMusic = function () {
			var playback = publicMusic.play();
			if (playback && typeof playback.then === 'function') {
				playback.then(updateMusicToggle).catch(updateMusicToggle);
			}
		};
		publicMusic.addEventListener('play', updateMusicToggle);
		publicMusic.addEventListener('pause', updateMusicToggle);
		musicToggle.addEventListener('click', function () {
			if (publicMusic.paused) { playPublicMusic(); }
			else { publicMusic.pause(); }
		});
		updateMusicToggle();
		playPublicMusic();
	}

	/* ---------- Konfirmasi & status pending ---------- */
	document.addEventListener('submit', function (ev) {
		var form = ev.target;
		if (form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
			ev.preventDefault();
			return;
		}
		if (ev.defaultPrevented) { return; }
		if (form.matches && form.matches('form[data-min-participants]') && !validateMinimumParticipants(form, true)) {
			ev.preventDefault();
			return;
		}
		$$('[data-pending-text]', form).forEach(function (btn) {
			setTimeout(function () { btn.textContent = btn.dataset.pendingText; btn.classList.add('is-pending'); }, 0);
		});
	});
	document.addEventListener('click', function (ev) {
		var btn = ev.target.closest('[data-confirm-btn]');
		if (btn && !window.confirm(btn.dataset.confirmBtn)) { ev.preventDefault(); }
	});

	/* ---------- Tombol ekspor: cegah klik ganda ---------- */
	$$('[data-export-btn]').forEach(function (a) {
		a.addEventListener('click', function () {
			a.classList.add('is-pending');
			setTimeout(function () { a.classList.remove('is-pending'); }, 4000);
		});
	});

	/* ---------- Toast ---------- */
	var toast = $('[data-toast]');
	if (toast) {
		setTimeout(function () {
			toast.classList.add('leaving');
			setTimeout(function () { toast.remove(); }, 350);
		}, 4500);
	}

	/* ---------- Dialog ---------- */
	document.addEventListener('click', function (ev) {
		var opener = ev.target.closest('[data-dialog-open]');
		if (opener) {
			var dlg = document.getElementById(opener.dataset.dialogOpen);
			if (dlg && typeof dlg.showModal === 'function') {
				fillDialog(dlg, opener);
				dlg.showModal();
			}
			return;
		}
		if (ev.target.closest('[data-dialog-close]')) {
			var d = ev.target.closest('dialog');
			if (d) { d.close(); }
			return;
		}
		if (ev.target.tagName === 'DIALOG') { ev.target.close(); } // klik latar
	});

	function setValue(el, v) {
		if (v === null || v === undefined) { v = ''; }
		if (el.type === 'checkbox') { el.checked = !!v; } else { el.value = v; }
	}

	function syncCompetitionGenderLabel(form) {
		var participation = form.elements.participation_type;
		var gender = form.elements.gender_option;
		if (!participation || !gender) { return; }
		var both = $('option[value="both"]', gender);
		if (both) {
			both.textContent = participation.value === 'team' ? both.dataset.teamLabel : both.dataset.individualLabel;
		}
	}

	function fillDialog(dlg, opener) {
		var form = $('form', dlg);
		var data = null;
		if (opener.hasAttribute('data-fill')) {
			try { data = JSON.parse(opener.dataset.fill) || {}; } catch (e) { data = {}; }
		}
		if (form && data !== null) { form.reset(); }
		if (form && opener.dataset.action && form.hasAttribute('data-field-action')) { form.action = opener.dataset.action; }
		if (data === null) { return; }

		Object.keys(data).forEach(function (key) {
			var v = data[key];
			$$('[data-field="' + key + '"]', dlg).forEach(function (el) { el.textContent = v === null ? '' : v; });
			if (!form) { return; }
			if (key === 'criteria') { return; }
			if (key === 'scores') { buildScoreFields(form, v); return; }

			var multi = $$('[name="' + key + '[]"]', form);
			if (multi.length && Array.isArray(v)) {
				var wanted = v.map(String);
				multi.forEach(function (el) { el.checked = wanted.indexOf(el.value) !== -1; });
				return;
			}
			var el = form.elements[key];
			if (el && !(el instanceof RadioNodeList) && 'value' in el) { setValue(el, v); }
		});

		if (form) {
			syncCompetitionGenderLabel(form);
			$$('[data-rows]', form).forEach(function (box) {
				var key = box.dataset.rows;
				rebuildRows(form, key, Array.isArray(data[key]) ? data[key] : []);
			});
			var isEdit = data.id !== undefined && data.id !== null && data.id !== '';
			var title = $('[data-title-new]', dlg);
			if (title) { title.textContent = isEdit ? title.dataset.titleEdit : title.dataset.titleNew; }
			var role = $('[data-role-select]', form);
			if (role) {
				role.disabled = data.self === '1';
				toggleRoleFields(role);
			}
		}
	}

	document.addEventListener('change', function (ev) {
		if (ev.target.matches && ev.target.matches('[name="participation_type"]')) {
			if (ev.target.form) { syncCompetitionGenderLabel(ev.target.form); }
		}
	});

	/* ---------- Baris dinamis (kriteria / peserta) ---------- */
	function rowScope(el) { return el.closest('form') || document; }

	function updateOpenRowCount(form, key) {
		var box = $('[data-rows="' + key + '"]', form);
		var count = $('[data-row-count="' + key + '"]', form);
		if (box && count) { count.textContent = String(box.children.length); }
		if (box) {
			$$('[data-row-remove]', box).forEach(function (button) {
				button.hidden = box.children.length <= 1;
			});
		}
	}

	function syncOpenRegistration(form) {
		var select = $('[data-open-competition]', form);
		if (!select) { return; }
		$$('[data-open-fields]', form).forEach(function (group) {
			var active = group.dataset.openFields === select.value;
			group.hidden = !active;
			$$('input, select, textarea, button[data-row-add], button[data-row-remove]', group).forEach(function (control) {
				control.disabled = !active;
			});
			var box = $('[data-rows]', group);
			if (box) { updateOpenRowCount(form, box.dataset.rows); }
		});
	}

	function addRow(scope, key, values) {
		var box = $('[data-rows="' + key + '"]', scope);
		var tpl = $('[data-row-template="' + key + '"]', scope);
		if (!box || !tpl) { return null; }
		var max = parseInt(box.dataset.max, 10);
		if (max && box.children.length >= max) { return null; }
		var row = tpl.content.firstElementChild.cloneNode(true);
		if (values) {
			$$('input', row).forEach(function (inp) { inp.value = values; });
		}
		box.appendChild(row);
		syncParticipantPhotoRequirement(scope);
		return row;
	}

	function rebuildRows(scope, key, list) {
		var box = $('[data-rows="' + key + '"]', scope);
		if (!box || box.dataset.rows === 'participants') { return; }
		while (box.firstChild) { box.removeChild(box.firstChild); }
		(list.length ? list : ['']).forEach(function (v) { addRow(scope, key, v); });
	}

	document.addEventListener('click', function (ev) {
		var add = ev.target.closest('[data-row-add]');
		if (add) {
			var scope = rowScope(add);
			var row = addRow(scope, add.dataset.rowAdd);
			updateOpenRowCount(scope, add.dataset.rowAdd);
			var first = row && $('input', row);
			if (first) { first.focus(); }
			return;
		}
		var rm = ev.target.closest('[data-row-remove]');
		if (rm) {
			var item = rm.closest('.dynamicRow');
			var box = item && item.parentElement;
			if (!item) { item = rm.closest('.participantRow'); box = item && item.parentElement; }
			if (box && box.children.length > 1) { item.remove(); updateOpenRowCount(rowScope(rm), box.dataset.rows); }
		}
	});

	function syncParticipantPhotoRequirement(form) {
		$$('[data-rows="participants"] input[name="name[]"]', form).forEach(function (nameInput) {
			var row = nameInput.closest('.participantRow');
			var active = nameInput.value.trim() !== '';
			$$('input[name="parent_name[]"], select[name="gender[]"], select[name="education_level[]"]', row).forEach(function (control) {
				control.required = active;
			});
			var photoInput = row && $('input[name="photo[]"]', row);
			if (photoInput) {
				photoInput.required = active && photoInput.dataset.hasPhoto !== '1';
			}
		});
	}

	function validateMinimumParticipants(form, report) {
		var min = parseInt(form.dataset.minParticipants, 10);
		var names = $$('[data-rows="participants"] input[name="name[]"]', form);
		var filled = names.filter(function (input) { return input.value.trim() !== ''; }).length;
		var valid = filled >= min;
		names.forEach(function (input, index) {
			input.setCustomValidity(!valid && index === 0 ? 'Isi minimal ' + min + ' peserta.' : '');
		});
		if (!valid && report && names[0]) { names[0].reportValidity(); }
		return valid;
	}
	document.addEventListener('input', function (ev) {
		if (ev.target.matches('input[name="name[]"]')) {
			syncParticipantPhotoRequirement(ev.target.form);
			if (ev.target.form && ev.target.form.hasAttribute('data-min-participants')) {
				validateMinimumParticipants(ev.target.form, false);
			}
		}
	});
	$$('form').forEach(syncParticipantPhotoRequirement);

	$$('[data-open-competition]').forEach(function (select) {
		var form = select.closest('form');
		if (!form) { return; }
		syncOpenRegistration(form);
		select.addEventListener('change', function () { syncOpenRegistration(form); });
	});

	var workCompetition = $('#k-comp');
	var workGender = $('[data-work-gender]');
	if (workCompetition && workGender) {
		var syncWorkGender = function () {
			var selected = workCompetition.options[workCompetition.selectedIndex];
			var allowed = (selected.dataset.genders || 'L,P').split(',');
			var current = workGender.value;
			while (workGender.options.length > 1) { workGender.remove(1); }
			[['L', 'Putra'], ['P', 'Putri']].forEach(function (item) {
				if (allowed.indexOf(item[0]) !== -1) {
					workGender.add(new Option(item[1], item[0]));
				}
			});
			workGender.value = allowed.indexOf(current) !== -1 ? current : '';
		};
		workCompetition.addEventListener('change', syncWorkGender);
		syncWorkGender();
	}

	/* ---------- Kolom nilai pada dialog koreksi ---------- */
	function buildScoreFields(form, scores) {
		var box = $('[data-score-fields]', form);
		if (!box) { return; }
		while (box.firstChild) { box.removeChild(box.firstChild); }
		(scores || []).forEach(function (s) {
			var wrap = document.createElement('div');
			wrap.className = 'field';
			var label = document.createElement('label');
			label.textContent = s.name + ' (0–' + s.max + ')';
			var input = document.createElement('input');
			input.type = 'text';
			input.name = 'score[' + s.id + ']';
			input.value = s.value;
			input.required = true;
			input.inputMode = 'decimal';
			input.pattern = '\\d+([.,]\\d{1,2})?';
			wrap.appendChild(label);
			wrap.appendChild(input);
			box.appendChild(wrap);
		});
	}

	/* ---------- Peran -> field desa / penugasan juri ---------- */
	function toggleRoleFields(select) {
		var form = select.closest('form');
		var village = $('[data-village-field]', form);
		var competitions = $('[data-competition-field]', form);
		var managedCompetitions = $('[data-manager-competition-field]', form);
		if (village) { village.hidden = select.value !== 'village_admin'; }
		if (competitions) { competitions.hidden = select.value !== 'judge' && select.value !== 'competition_admin'; }
		if (managedCompetitions) { managedCompetitions.hidden = select.value !== 'competition_admin'; }
	}
	$$('[data-role-select]').forEach(function (s) {
		toggleRoleFields(s);
		s.addEventListener('change', function () { toggleRoleFields(s); });
	});

	/* ---------- Drawer menu publik ---------- */
	var drawer = $('#mobileDrawer');
	var drawerButton = $('[data-drawer-open]');
	document.addEventListener('click', function (ev) {
		if (!drawer) { return; }
		if (ev.target.closest('[data-drawer-open]')) {
			drawer.hidden = false;
			if (drawerButton) { drawerButton.setAttribute('aria-expanded', 'true'); }
			return;
		}
		if (ev.target.closest('[data-drawer-close]') || ev.target === drawer || ev.target.closest('#mobileDrawer a')) {
			drawer.hidden = true;
			if (drawerButton) { drawerButton.setAttribute('aria-expanded', 'false'); }
		}
	});

	/* ---------- Menu akun navbar publik: tutup saat klik di luar / Esc ---------- */
	document.addEventListener('click', function (ev) {
		$$('.userMenu[open]').forEach(function (m) { if (!m.contains(ev.target)) { m.removeAttribute('open'); } });
		$$('.navDropdown[open]').forEach(function (m) {
			if (!m.contains(ev.target) || ev.target.closest('.navDropdownPanel a')) { m.removeAttribute('open'); }
		});
	});
	document.addEventListener('keydown', function (ev) {
		if (ev.key === 'Escape') {
			$$('.userMenu[open], .navDropdown[open]').forEach(function (m) { m.removeAttribute('open'); });
			if (drawer && !drawer.hidden) {
				drawer.hidden = true;
				if (drawerButton) { drawerButton.setAttribute('aria-expanded', 'false'); }
			}
		}
	});

	/* ---------- Sidebar portal (mobile) ---------- */
	var sidebar = $('#sidebar');
	var overlay = $('.sidebarOverlay');
	var sidebarButton = $('[data-sidebar-open]');
	function sidebarSet(open) {
		if (!sidebar) { return; }
		sidebar.classList.toggle('open', open);
		if (overlay) { overlay.classList.toggle('show', open); }
		if (sidebarButton) { sidebarButton.setAttribute('aria-expanded', open ? 'true' : 'false'); }
	}
	document.addEventListener('click', function (ev) {
		if (ev.target.closest('[data-sidebar-open]')) { sidebarSet(true); return; }
		if (ev.target.closest('[data-sidebar-close], #sidebar a')) { sidebarSet(false); }
	});
	document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { sidebarSet(false); } });

	/* ---------- Form penilaian: pratinjau total & batas ---------- */
	$$('[data-score-form]').forEach(function (form) {
		var out = $('[data-score-total]', form);
		var inputs = $$('[data-score-input]', form);
		var num = function (s) { return parseFloat(String(s).replace(',', '.')); };

		function calc() {
			var sum = 0, weighted = 0, weights = 0, filled = 0;
			inputs.forEach(function (inp) {
				var v = num(inp.value), min = parseFloat(inp.dataset.min || '0'), max = parseFloat(inp.dataset.max);
				var weight = parseFloat(inp.dataset.weight || '0');
				var bad = inp.value !== '' && (isNaN(v) || v < min || v > max);
				inp.setCustomValidity(bad ? 'Nilai harus antara ' + min + ' dan ' + max : '');
				inp.classList.toggle('invalid', bad);
				if (inp.value !== '' && !isNaN(v)) { filled++; sum += v; weighted += v / max * weight; weights += weight; }
			});
			var total = form.dataset.scoreMethod === 'points' ? sum : (weights ? 100 * weighted / weights : 0);
			out.textContent = filled ? (Math.round(total * 100) / 100).toLocaleString('id-ID') : '–';
		}
		form.addEventListener('input', calc);
		calc();
	});

	/* ---------- Buka dialog dari hash (mis. #reg-3) ---------- */
	function openFromHash() {
		if (location.hash.indexOf('#reg-') !== 0) { return; }
		var target = document.getElementById(location.hash.slice(1));
		if (target && typeof target.showModal === 'function' && !target.open) { target.showModal(); }
	}
	openFromHash();
	window.addEventListener('hashchange', openFromHash);

	/* ---------- Dashboard penilaian terbaru ---------- */
	var feed = document.querySelector('[data-live-feed]');
	if (feed) {
		var rows = feed.querySelector('[data-feed-rows]');
		var status = feed.querySelector('[data-feed-status]');
		var pagination = feed.querySelector('[data-feed-pagination]');
		var pageLabel = feed.querySelector('[data-feed-page]');
		var prev = feed.querySelector('[data-feed-prev]');
		var next = feed.querySelector('[data-feed-next]');
		var total = parseInt(feed.dataset.total, 10) || 0;
		var page = 1;
		var pageSize = 10;
		var busy = false;
		function refreshFeed(targetPage) {
			if (busy) { return; }
			busy = true;
			var requestedPage = targetPage || page;
			fetch(feed.dataset.feedUrl + '?page=' + requestedPage, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
				.then(function (response) {
					if (!response.ok) { throw new Error('HTTP ' + response.status); }
					return response.json();
				})
				.then(function (data) {
					if (!Array.isArray(data.items) || !Number.isInteger(data.total) || !Number.isInteger(data.page)) { throw new Error('Format respons tidak valid'); }
					page = data.page;
					pageSize = data.page_size;
					total = data.total;
					rows.replaceChildren();
					if (data.items.length === 0) {
						var empty = document.createElement('p');
						empty.className = 'muted';
						empty.textContent = 'Belum ada penilaian masuk.';
						rows.appendChild(empty);
					}
					data.items.forEach(function (item, index) {
						var row = document.createElement('div');
						row.className = 'feedItem';
						var rank = document.createElement('span');
						rank.className = 'feedRank';
						rank.textContent = String((page - 1) * pageSize + index + 1);
						var info = document.createElement('div');
						info.className = 'feedInfo';
						var competition = document.createElement('b');
						competition.textContent = item.competition;
						var entry = document.createElement('span');
						entry.textContent = item.village + ' · ' + item.entry;
						var judge = document.createElement('small');
						judge.textContent = 'Juri ' + (item.judge || '—') + ' · ' + item.time;
						info.append(competition, entry, judge);
						var score = document.createElement('div');
						score.className = 'feedScore';
						var value = document.createElement('b');
						value.textContent = item.total;
						var criteria = document.createElement('small');
						criteria.textContent = item.criteria_count + ' kriteria';
						score.append(value, criteria);
						row.append(rank, info, score);
						rows.appendChild(row);
					});
					var pages = Math.max(1, Math.ceil(total / pageSize));
					pagination.hidden = total <= pageSize;
					if (pageLabel) {
						pageLabel.textContent = 'Halaman ' + page + ' dari ' + pages + ' · ' + total + ' penilaian';
					}
					if (prev) { prev.disabled = page <= 1; }
					if (next) { next.disabled = page >= pages; }
					if (status) {
						status.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
					}
				})
				.catch(function () {
					status.textContent = 'Pembaruan gagal. Coba muat ulang halaman.';
				})
				.finally(function () { busy = false; });
		}
		feed.querySelector('[data-feed-refresh]').addEventListener('click', function () { refreshFeed(1); });
		if (prev) { prev.addEventListener('click', function () { refreshFeed(page - 1); }); }
		if (next) { next.addEventListener('click', function () { refreshFeed(page + 1); }); }
		window.setInterval(function () { refreshFeed(page); }, 15000);
	}

	/* ---------- Sidebar Super Admin ---------- */
	var adminShell = document.querySelector('[data-admin-shell], [data-role-shell]');
	if (adminShell) {
		var storageKey = 'fgu-sidebar-collapsed';
		try {
			if (localStorage.getItem(storageKey) === '1') { adminShell.classList.add('collapsed'); }
		} catch (e) {
			// Preferensi sidebar bersifat opsional bila penyimpanan browser tidak tersedia.
		}
		var toggle = adminShell.querySelector('[data-admin-sidebar-toggle]');
		if (toggle) {
			toggle.addEventListener('click', function () {
				var collapsed = adminShell.classList.toggle('collapsed');
				try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (e) {}
				toggle.setAttribute('aria-label', collapsed ? 'Tampilkan menu' : 'Sembunyikan menu');
			});
		}
		var adminSidebar = adminShell.querySelector('.adminSidebar');
		var adminOverlay = adminShell.querySelector('.adminSidebarOverlay');
		var adminMenuButton = adminShell.querySelector('[data-admin-menu-open]');
		function adminSidebarSet(open) {
			if (!adminSidebar) { return; }
			adminSidebar.classList.toggle('open', open);
			if (adminOverlay) { adminOverlay.classList.toggle('show', open); }
			if (adminMenuButton) { adminMenuButton.setAttribute('aria-expanded', open ? 'true' : 'false'); }
		}
		adminShell.addEventListener('click', function (ev) {
			if (ev.target.closest('[data-admin-menu-open]')) { adminSidebarSet(true); return; }
			if (ev.target.closest('[data-admin-menu-close], .adminSideNav a')) { adminSidebarSet(false); }
		});
		document.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape') { adminSidebarSet(false); }
		});
	}
})();
