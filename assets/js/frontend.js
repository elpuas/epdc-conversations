(() => {
	'use strict';

	document.querySelectorAll('[data-epdc-conversations]').forEach((button) => {
		button.setAttribute('data-epdc-ready', 'true');
	});
})();
