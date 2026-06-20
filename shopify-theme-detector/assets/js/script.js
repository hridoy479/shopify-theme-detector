/* Shopify Theme Detector - frontend logic (vanilla JS, no build step) */
( function () {
	'use strict';

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = String( value === null || value === undefined ? '' : value );
		return div.innerHTML;
	}

	function confidenceClass( score ) {
		if ( score >= 75 ) {
			return 'std-confidence--high';
		}
		if ( score >= 40 ) {
			return 'std-confidence--medium';
		}
		return 'std-confidence--low';
	}

	function field( label, value ) {
		return (
			'<div class="std-field">' +
			'<p class="std-field__label">' + escapeHtml( label ) + '</p>' +
			'<p class="std-field__value">' + escapeHtml( value ) + '</p>' +
			'</div>'
		);
	}

	function renderResults( data ) {
		if ( false === data.is_shopify ) {
			return (
				'<div class="std-results__header">' +
				'<div>' +
				'<p class="std-results__title">' + escapeHtml( data.url ) + '</p>' +
				'<p class="std-results__subtitle">' + escapeHtml( data.message || '' ) + '</p>' +
				'</div>' +
				'</div>'
			);
		}

		var score = parseInt( data.confidence, 10 ) || 0;
		var screenshot = data.screenshot
			? '<img class="std-results__screenshot" src="' + escapeHtml( data.screenshot ) + '" alt="" loading="lazy" />'
			: '';

		var published = null === data.published || undefined === data.published
			? STD_Data.i18n.notAvailable
			: ( data.published ? STD_Data.i18n.published : STD_Data.i18n.unpublished );

		return (
			'<div class="std-results__header">' +
			screenshot +
			'<div>' +
			'<p class="std-results__title">' + escapeHtml( data.theme_name ) + '</p>' +
			'<p class="std-results__subtitle">' + escapeHtml( data.url ) + '</p>' +
			'</div>' +
			'</div>' +
			'<span class="std-confidence ' + confidenceClass( score ) + '">' + score + '% ' + escapeHtml( STD_Data.i18n.confidence ) + '</span>' +
			'<div class="std-grid">' +
			field( STD_Data.i18n.themeId, data.theme_id || STD_Data.i18n.notAvailable ) +
			field( STD_Data.i18n.themeVendor, data.theme_vendor ) +
			field( STD_Data.i18n.themeType, data.theme_type ) +
			field( STD_Data.i18n.themeVersion, data.theme_version ) +
			field( STD_Data.i18n.published, published ) +
			field( STD_Data.i18n.method, data.detection_method ) +
			field( STD_Data.i18n.timestamp, data.timestamp ) +
			'</div>'
		);
	}

	function setLoading( widget, loading ) {
		var submit = widget.querySelector( '[data-std-submit]' );
		var spinner = widget.querySelector( '[data-std-spinner]' );
		var label = widget.querySelector( '.std-form__submit-label' );

		submit.disabled = loading;
		spinner.hidden = ! loading;
		label.textContent = loading ? STD_Data.i18n.loading : STD_Data.i18n.detectLabel;
	}

	function showMessage( widget, message, type ) {
		var box = widget.querySelector( '[data-std-message]' );

		if ( ! message ) {
			box.hidden = true;
			box.textContent = '';
			return;
		}

		box.hidden = false;
		box.className = 'std-message std-message--' + type;
		box.textContent = message;
	}

	function handleSubmit( event ) {
		event.preventDefault();

		var form = event.currentTarget;
		var widget = form.closest( '[data-std-widget]' );
		var input = widget.querySelector( '[data-std-url-input]' );
		var results = widget.querySelector( '[data-std-results]' );
		var url = input.value.trim();

		if ( '' === url ) {
			showMessage( widget, STD_Data.i18n.emptyUrl, 'error' );
			return;
		}

		showMessage( widget, '', 'info' );
		results.hidden = true;
		setLoading( widget, true );

		var body = new URLSearchParams();
		body.set( 'action', 'std_detect_theme' );
		body.set( 'nonce', STD_Data.nonce );
		body.set( 'store_url', url );

		fetch( STD_Data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				setLoading( widget, false );

				if ( ! json.success ) {
					showMessage( widget, ( json.data && json.data.message ) || STD_Data.i18n.genericErr, 'error' );
					return;
				}

				results.innerHTML = renderResults( json.data );
				results.hidden = false;
			} )
			.catch( function () {
				setLoading( widget, false );
				showMessage( widget, STD_Data.i18n.genericErr, 'error' );
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var forms = document.querySelectorAll( '[data-std-form]' );

		forms.forEach( function ( form ) {
			form.addEventListener( 'submit', handleSubmit );
		} );
	} );
} )();
