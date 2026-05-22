/**
 * MSC Stealth Login Admin JavaScript
 */

(function($) {
	'use strict';

	// Toggle visibility of related fields based on checkbox state.
	$(document).ready(function() {
		// Show/hide advanced security options based on master toggle.
		$('#advanced_security_enabled').on('change', function() {
			var isChecked = $(this).is(':checked');
			$('.mscsl-advanced-option').toggle(isChecked);
			// When hiding advanced options, also hide brute force options.
			if (!isChecked) {
				$('.mscsl-bruteforce-option').hide();
			} else {
				// Trigger brute force toggle to show/hide based on its state.
				$('#brute_force_enabled').trigger('change');
			}
		}).trigger('change');

		// Show/hide brute force settings based on enable checkbox.
		// Only applies when advanced security is enabled.
		$('#brute_force_enabled').on('change', function() {
			var isChecked = $(this).is(':checked');
			var advancedEnabled = $('#advanced_security_enabled').is(':checked');
			// Only show brute force options if advanced security is enabled.
			$('.mscsl-bruteforce-option').toggle(isChecked && advancedEnabled);
		}).trigger('change');

		// Show/hide email options based on email master toggle (not advanced_security_enabled).
		function toggleEmailOptions() {
			var emailMasterEnabled = $('#email_notifications_enabled').length === 0 || $('#email_notifications_enabled').is(':checked');
			var lockoutEmailEnabled = $('#lockout_email_enabled').is(':checked');

			// Show email section header row if email master is enabled.
			$('.mscsl-email-option').each(function() {
				var $row = $(this);
				var isDetail = $row.hasClass('mscsl-email-detail');
				if (isDetail) {
					// Detail rows need both email master AND lockout email enabled.
					$row.toggle(emailMasterEnabled && lockoutEmailEnabled);
				} else {
					// Main rows just need email master enabled.
					$row.toggle(emailMasterEnabled);
				}
			});
		}

		// Listen to email master toggle if it exists.
		if ($('#email_notifications_enabled').length > 0) {
			$('#email_notifications_enabled').on('change', toggleEmailOptions);
		}
		$('#lockout_email_enabled').on('change', toggleEmailOptions);
		toggleEmailOptions();

		// Show/hide progressive options.
		function toggleProgressiveOptions() {
			var advancedEnabled = $('#advanced_security_enabled').is(':checked');
			var progressiveEnabled = $('#progressive_lockout_enabled').is(':checked');

			if (advancedEnabled && progressiveEnabled) {
				$('.mscsl-progressive-option').show();
			} else {
				$('.mscsl-progressive-option').hide();
			}
		}

		$('#advanced_security_enabled').on('change', toggleProgressiveOptions);
		$('#progressive_lockout_enabled').on('change', toggleProgressiveOptions);
		toggleProgressiveOptions();

		// Show/hide wp-admin redirect URL based on hide wp-admin checkbox.
		$('#hide_wp_admin').on('change', function() {
			var isChecked = $(this).is(':checked');
			$('#wp_admin_redirect').closest('tr').toggle(isChecked);
		}).trigger('change');

		// Validate custom login slug.
		$('#custom_login_slug').on('blur', function() {
			var slug = $(this).val().trim();
			slug = slug.replace(/^\/+/, '');

			// Reserved slugs.
			var reserved = ['wp-admin', 'wp-login', 'wp-login.php', 'login', 'admin', 'dashboard', 'wp'];

			if (slug === '') {
				slug = 'secure-login';
			}

			if (reserved.indexOf(slug) !== -1 || slug.substring(0, 3).toLowerCase() === 'wp-') {
				alert(mscslAdmin.reservedSlug);
				$(this).val('secure-login');
				slug = 'secure-login';
			}

			$(this).val(slug);
		});

		// Live preview of login URL.
		function updateLoginUrlPreview() {
			var slug = $('#custom_login_slug').val() || 'secure-login';
			slug = slug.trim().replace(/^\/+/, '');

			// Reserved slugs.
			var reserved = ['wp-admin', 'wp-login', 'wp-login.php', 'login', 'admin', 'dashboard', 'wp'];
			if (reserved.indexOf(slug) !== -1 || slug.substring(0, 3).toLowerCase() === 'wp-') {
				slug = 'secure-login';
			}

			var base = window.location.origin;
			$('.mscsl-url-preview').text(base + '/' + slug);
		}

		$('#custom_login_slug').on('input', updateLoginUrlPreview);
		updateLoginUrlPreview();

		// Copy recovery URL to clipboard.
		$('#mscsl-copy-recovery-url').on('click', function() {
			var url = $('#mscsl-recovery-url').text();
			var $feedback = $('#mscsl-copy-feedback');
			var copiedText = $feedback.data('copied') || 'Copied!';

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(function() {
					$feedback.text(copiedText).show();
					setTimeout(function() {
						$feedback.hide();
					}, 2000);
				}).catch(function() {
					// Fallback for older browsers.
					var $temp = $('<input type="text" />');
					$('body').append($temp);
					$temp.val(url).select();
					document.execCommand('copy');
					$temp.remove();
					$feedback.text(copiedText).show();
					setTimeout(function() {
						$feedback.hide();
					}, 2000);
				});
			} else {
				// Fallback for older browsers.
				var $temp = $('<input type="text" />');
				$('body').append($temp);
				$temp.val(url).select();
				document.execCommand('copy');
				$temp.remove();
				$feedback.text(copiedText).show();
				setTimeout(function() {
					$feedback.hide();
				}, 2000);
			}
		});

		// Dismiss data tracking notice via AJAX.
		$(document).on('click', '.mscsl-data-tracking-notice .notice-dismiss', function() {
			$.post(ajaxurl, {
				action: 'mscsl_dismiss_data_notice',
				_wpnonce: mscslAdmin.dismissNonce
			});
		});
	});

})(jQuery);
