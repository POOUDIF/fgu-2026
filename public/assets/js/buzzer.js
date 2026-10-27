(() => {
	'use strict';

	const root = document.querySelector('[data-buzzer]');
	if (!root) return;

	const buttons = [...root.querySelectorAll('[data-buzzer-btn]')];
	const names = [...root.querySelectorAll('[data-buzzer-name]')];
	const overlay = root.querySelector('[data-buzzer-overlay]');
	const winner = root.querySelector('[data-buzzer-winner]');
	const winnerNo = root.querySelector('[data-buzzer-winner-no]');
	const winnerName = root.querySelector('[data-buzzer-winner-name]');
	const renameButton = root.querySelector('[data-buzzer-rename]');
	const storageKey = 'fgu.buzzer.names';
	const sound = new Audio(root.dataset.sound);
	sound.preload = 'auto';
	let locked = false;

	try {
		const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
		names.forEach((input, index) => {
			if (typeof saved[index] === 'string' && saved[index].trim()) input.value = saved[index];
		});
	} catch (error) { /* penyimpanan tidak tersedia; pakai nama bawaan */ }

	function saveNames() {
		try { localStorage.setItem(storageKey, JSON.stringify(names.map((input) => input.value.trim()))); } catch (error) { /* abaikan */ }
	}

	function press(index) {
		if (locked || !buttons[index]) return;
		locked = true;
		sound.currentTime = 0;
		sound.play().catch(() => {});

		const color = [...buttons[index].classList].find((name) => name !== 'buzzerBtn' && name !== 'pressed');
		buttons.forEach((button, i) => {
			button.disabled = true;
			button.classList.toggle('pressed', i === index);
		});
		winner.className = 'buzzerWinner ' + color;
		winnerNo.textContent = String(index + 1);
		winnerName.textContent = names[index].value.trim() || 'Peserta ' + (index + 1);
		overlay.hidden = false;
		overlay.querySelector('button').focus();
	}

	function reset() {
		if (!locked) return;
		locked = false;
		overlay.hidden = true;
		buttons.forEach((button) => {
			button.disabled = false;
			button.classList.remove('pressed');
		});
	}

	buttons.forEach((button, index) => button.addEventListener('click', () => press(index)));
	root.querySelectorAll('[data-buzzer-reset]').forEach((button) => button.addEventListener('click', reset));

	renameButton.addEventListener('click', () => {
		const editing = names[0].readOnly;
		names.forEach((input) => { input.readOnly = !editing; });
		renameButton.textContent = editing ? 'Simpan nama' : 'Ubah nama peserta';
		if (editing) names[0].focus(); else saveNames();
	});

	root.querySelector('[data-buzzer-fullscreen]').addEventListener('click', () => {
		if (document.fullscreenElement) document.exitFullscreen();
		else root.requestFullscreen?.();
	});

	document.addEventListener('keydown', (event) => {
		if (event.target instanceof HTMLInputElement && !event.target.readOnly) return;
		if (event.ctrlKey || event.metaKey || event.altKey) return;
		if (/^[1-6]$/.test(event.key)) {
			press(Number(event.key) - 1);
		} else if (event.key === ' ' || event.key === 'Escape') {
			if (locked) { event.preventDefault(); reset(); }
		}
	});
})();
