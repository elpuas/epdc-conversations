(() => {
	'use strict';

	const trackingConfig = window.epdcConversationsTracking || {};

	function getDeviceType() {
		const userAgent = navigator.userAgent || '';

		if (/tablet|ipad/i.test(userAgent)) {
			return 'tablet';
		}

		if (/mobile|android|iphone|ipod/i.test(userAgent)) {
			return 'mobile';
		}

		if (/macintosh|windows|linux/i.test(userAgent)) {
			return 'desktop';
		}

		return 'unknown';
	}

	function buildPayload(linkElement) {
		const currentUrl = window.location.href || '';
		const referrerUrl = document.referrer || '';
		let url;

		try {
			url = new URL(currentUrl);
		} catch {
			url = new URL(window.location.origin);
		}

		return {
			event_type: linkElement.dataset.epdcConversationsEvent || 'whatsapp_click',
			page_url: currentUrl,
			referrer_url: referrerUrl,
			device_type: getDeviceType(),
			utm_source: url.searchParams.get('utm_source') || '',
			utm_medium: url.searchParams.get('utm_medium') || '',
			utm_campaign: url.searchParams.get('utm_campaign') || ''
		};
	}

	function sendTracking(payload) {
		if (!trackingConfig.restUrl || typeof window.fetch !== 'function') {
			return;
		}

		window.fetch(trackingConfig.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': trackingConfig.nonce || ''
			},
			body: JSON.stringify(payload),
			keepalive: true
		}).catch(() => {
			// Do not interrupt navigation when tracking fails.
		});
	}

	function maybeSendGaEvent() {
		if (!trackingConfig.gaEnabled || typeof window.gtag !== 'function') {
			return;
		}

		const gaPayload = Object.assign(
			{
				event_category: 'EPDC Conversations',
				event_label: window.location.pathname
			},
			trackingConfig.gaEventData || {}
		);

		if (gaPayload.event_label === '{{pathname}}') {
			gaPayload.event_label = window.location.pathname;
		}

		window.gtag(
			'event',
			trackingConfig.gaEventName || 'epdc_whatsapp_click',
			gaPayload
		);
	}

	document.addEventListener('click', (event) => {
		const link = event.target.closest('[data-epdc-conversations] .epdc-conversations__link');

		if (!link) {
			return;
		}

		const payload = buildPayload(link);

		sendTracking(payload);
		maybeSendGaEvent();
	});

	document.querySelectorAll('[data-epdc-conversations]').forEach((button) => {
		button.setAttribute('data-epdc-ready', 'true');
	});
})();
