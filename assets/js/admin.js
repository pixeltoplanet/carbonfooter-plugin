/**
 * CarbonFooter admin scripts
 */

(function ($) {
	$(document).ready(function () {
		// Toggle automatic checks setting
		$("#carbonfooter_auto_check_enabled").on("change", function () {
			if ($(this).is(":checked")) {
				// Show a simple confirmation alert
				if (!confirm(carbonfooterAdmin.confirmAutoCheck)) {
					$(this).prop("checked", false);
				}
			}
		});

		// Handle measure button clicks
		$(document).on("click", ".measure-emissions", function (e) {
			e.preventDefault();

			const $button = $(this);
			const postId = $button.data("post-id");
			const originalText = $button.text();

			// Disable button and show loading state
			$button.prop("disabled", true).text(carbonfooterVars.i18n.measuring);

			// Make AJAX request
			$.ajax({
				url: carbonfooterVars.ajaxUrl,
				type: "POST",
				data: {
					action: "carbonfooter_measure",
					nonce: carbonfooterVars.nonce,
					post_id: postId,
				},
				// biome-ignore lint/complexity/useArrowFunction: <explanation>
				success: function (response) {
					if (response.success && response.data) {
						// Update the emissions display
						$button
							.parent()
							.html(
								`${response.data.formatted} <button class="button button-small measure-emissions" data-post-id="${postId}">${carbonfooterVars.i18n.measureAgain}</button>`,
							);
					} else {
						// Show error and restore button
						alert(carbonfooterVars.i18n.error);
						$button.prop("disabled", false).text(originalText);
					}
				},
				// biome-ignore lint/complexity/useArrowFunction: <explanation>
				error: function () {
					// Show error and restore button
					alert(carbonfooterVars.i18n.error);
					$button.prop("disabled", false).text(originalText);
				},
			});
		});
	});
})(jQuery);
