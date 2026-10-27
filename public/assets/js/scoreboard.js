(() => {
	'use strict';

	const board = document.querySelector('[data-scoreboard]');
	if (!board) return;

	const connection = board.querySelector('[data-connection]');
	const roundLabel = board.querySelector('[data-round]');
	const rows = board.querySelector('[data-score-rows]');
	const updated = board.querySelector('[data-updated]');
	const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
	let loading = false;

	function cell(text, className) {
		const element = document.createElement('td');
		if (className) element.className = className;
		element.textContent = text;
		return element;
	}

	function render(data) {
		const currentRound = Number(data.current_round);
		const labels = data.round_labels;
		roundLabel.textContent = data.is_finished ? 'PERTANDINGAN SELESAI' : (labels[currentRound - 1] || 'Menunggu babak');
		board.querySelectorAll('[data-round-indicator]').forEach((indicator) => {
			indicator.classList.toggle('active', Number(indicator.dataset.roundIndicator) === currentRound && !data.is_finished);
			indicator.classList.toggle('complete', data.is_finished || Number(indicator.dataset.roundIndicator) < currentRound);
		});
		board.querySelectorAll('[data-round-column]').forEach((column) => {
			column.classList.toggle('current', Number(column.dataset.roundColumn) === currentRound && !data.is_finished);
		});

		const fragment = document.createDocumentFragment();
		if (!data.results.length) {
			const row = document.createElement('tr');
			row.append(cell('Menunggu desa yang hadir untuk mulai bertanding.', 'spectatorEmpty'));
			row.firstElementChild.colSpan = labels.length + 3;
			fragment.append(row);
		} else {
			data.results.forEach((result) => {
				const row = document.createElement('tr');
				const rank = cell('', '');
				const badge = document.createElement('span');
				badge.className = 'spectatorRankBadge';
				badge.textContent = result.rank;
				rank.append(badge);
				row.append(rank);
				row.append(cell(result.village_name, 'spectatorVillage'));
				labels.forEach((label, index) => {
					row.append(cell(numberFormat.format(Number(result.round_scores[index + 1] || 0))));
				});
				row.append(cell(numberFormat.format(Number(result.final_score || 0)), 'spectatorTotal'));
				fragment.append(row);
			});
		}
		rows.replaceChildren(fragment);
		connection.textContent = 'LIVE · TERHUBUNG';
		connection.parentElement.classList.add('connected');
		updated.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
	}

	async function refresh() {
		if (loading) return;
		loading = true;
		try {
			const response = await fetch(board.dataset.feedUrl, { cache: 'no-store', headers: { Accept: 'application/json' } });
			if (!response.ok) throw new Error('Pembaruan nilai gagal (' + response.status + ').');
			render(await response.json());
		} catch (error) {
			connection.textContent = 'MENYAMBUNG ULANG';
			connection.parentElement.classList.remove('connected');
			updated.textContent = error.message;
		} finally {
			loading = false;
		}
	}

	refresh();
	window.setInterval(() => {
		if (!document.hidden) refresh();
	}, 2500);
})();
