"use strict";

(function ($) {
  $(function () {
    $('body.index-php h1').first().text("".concat(osDXPDashboard.text.siteTitle, " Dashboard")).after('<span class="subtitle dashboard-subtitle">Welcome to your osDXP dashboard!</span>');

    var clearMessages = function clearMessages($wrapper) {
      $wrapper.find('.error-messages').remove();
      $wrapper.find('.success-messages').remove();
    };

    var renderMessages = function renderMessages(response, $wrapper) {
      var messageTypes = ['error', 'success']; // Process messages.

      Object.keys(messageTypes).forEach(function (i) {
        var messageType = messageTypes[i];

        if (response["".concat(messageType, "_messages")]) {
          var $messages = $("<div class=\"".concat(messageType, "-messages\">"));
          Object.keys(response["".concat(messageType, "_messages")]).forEach(function (i) {
            $messages.append($("<div class=\"".concat(messageType, "-message\">")).text(response["".concat(messageType, "_messages")][i]));
          });
          $wrapper.append($messages);
        }
      });
    };

    $('.js-osdxp-submit-module-license').on('click', function (event) {
      event.preventDefault();
      var pressEnterEvent = $.Event('keypress', {
        which: 13
      });
      $(event.currentTarget).siblings('.js-osdxp-module-license').trigger(pressEnterEvent);
    });
    $('.js-osdxp-module-license').on('keypress', function (event) {
      // Only handle enter key press (code = 13).
      if (event.keyCode !== 13 && event.which !== 13) {
        return;
      }

      event.preventDefault();
      var $field = $(event.currentTarget);
      var $wrapper = $field.parents('.module-license-key');
      var pluginSlug = $field.data('module');
      var licenseKey = $field.val(); // Disable field until the request has been complete.

      $field.prop('disabled', true); // Send license request.

      $.ajax("".concat(osDXPDashboard.restUrl, "/license/").concat(pluginSlug, "/").concat(licenseKey), {
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', osDXPDashboard.restNonce);
        },
        method: 'POST'
      }).always(function () {
        $field.prop('disabled', false);
      }).done(function (response) {
        // Remove previously set error/success messages.
        clearMessages($wrapper);

        if (response.license_key_markup) {
          // Add license key markup.
          $wrapper.append(response.license_key_markup); // Remove any license errors for this plugin.

          $(".".concat(pluginSlug, "-license-error")).fadeOut();
          $wrapper.find('.license-input-wrapper').addClass('hidden');
        }

        renderMessages(response, $wrapper);
      });
    });
    $('.module-license-key').on('click', '.js-osdxp-module-remove-license', function (event) {
      event.preventDefault(); // Display a confirmation and only continue if the user clicked "Yes".

      if (!confirm(osDXPDashboard.text.licenseKeyRemovalConfirmation)) {
        return;
      }

      var $button = $(event.currentTarget);
      var $wrapper = $button.parents('.module-license-key');
      var pluginSlug = $button.data('module'); // Disable field until the request has been complete.

      $button.prop('disabled', true); // Send license request.

      $.ajax("".concat(osDXPDashboard.restUrl, "/license/").concat(pluginSlug), {
        beforeSend: function beforeSend(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', osDXPDashboard.restNonce);
        },
        method: 'DELETE'
      }).always(function () {
        $button.prop('disabled', false);
      }).done(function (response) {
        // Remove previously set error/success messages.
        clearMessages($wrapper);

        if (response.success) {
          // Remove the license key markup.
          $button.parents('.display-license').remove(); // Clear value from license field.

          $wrapper.find('.js-osdxp-module-license').val(null); // Show license field.

          $wrapper.find('.license-input-wrapper').removeClass('hidden'); // Remove any license errors for this plugin.

          $(".".concat(pluginSlug, "-license-error")).fadeOut();
        }

        renderMessages(response, $wrapper);
      });
    });
    var dxpActions = $('#dxp-actions');
    var dxpActionsContainer = dxpActions.closest('#dxp_actions.postbox');
    var dxpActionsHideLabel = $('label[for="dxp_actions-hide"]');
    dxpActions.prependTo('#dashboard-widgets').addClass('meta-box-sortables no-padding-top');
    dxpActionsContainer.remove();
    dxpActionsHideLabel.remove();
    $(".dxp-dashboard #wp-admin-bar-root-default li").each(function () {
      $(this).addClass('current');
    });
    $('.plugin-update-tr td').attr('colspan', 4);
  });
})(jQuery);
/**
 * @toggle functionality for the upload module node in modules installed page
 *
 */


jQuery(document).ready(function ($) {
  $('#osdxp-module-upload-button').click(function () {
    $('#osdxp-module-upload-field').toggle();
  });
});
//# sourceMappingURL=app.js.map
