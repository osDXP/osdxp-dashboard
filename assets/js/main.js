(($) => {
  $(() => {
    const clearMessages = $wrapper => {
      $wrapper.find('.error-messages').remove();
      $wrapper.find('.success-messages').remove();
    };

    const renderMessages = (response, $wrapper) => {
      const messageTypes = ['error', 'success'];

      // Process messages.
      Object.keys(messageTypes).forEach(i => {
        const messageType = messageTypes[i];

        if (response[`${messageType}_messages`]) {
          const $messages = $(`<div class="${messageType}-messages">`);

          Object.keys(response[`${messageType}_messages`]).forEach(i => {
            $messages.append(
              $(`<div class="${messageType}-message">`).text(response[`${messageType}_messages`][i])
            );
          });

          $wrapper.append($messages);
        }
      });
    };

    $('.js-osdxp-submit-module-license').on('click', event => {
      event.preventDefault();

      const pressEnterEvent = $.Event('keypress', {which: 13});
      $(event.currentTarget).siblings('.js-osdxp-module-license').trigger(pressEnterEvent);
    });

    $('.js-osdxp-module-license').on('keypress', event => {
      // Only handle enter key press (code = 13).
      if (event.keyCode !== 13 && event.which !== 13) {
        return;
      }

      event.preventDefault();

      const $field = $(event.currentTarget);
      const $wrapper = $field.parents('.module-license-key');
      const pluginSlug = $field.data('module');
      const licenseKey = $field.val();

      // Disable field until the request has been complete.
      $field.prop('disabled', true);

      // Send license request.
      $.ajax(`${OSDXPDashboard.restUrl}/license/${pluginSlug}/${licenseKey}`, {
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', OSDXPDashboard.restNonce);
        },
        method: 'POST'
      }).always(() => {
        $field.prop('disabled', false);
      }).done((response) => {
        // Remove previously set error/success messages.
        clearMessages($wrapper);

        if (response.license_key_markup) {
          // Add license key markup.
          $wrapper.append(response.license_key_markup);

          // Remove any license errors for this plugin.
          $(`.${pluginSlug}-license-error`).fadeOut();

          $wrapper.find('.license-input-wrapper').addClass('hidden');
        }

        renderMessages(response, $wrapper);
      });
    });

    $('.module-license-key').on('click', '.js-osdxp-module-remove-license', event => {
      event.preventDefault();

      // Display a confirmation and only continue if the user clicked "Yes".
      if (!confirm(OSDXPDashboard.text.licenseKeyRemovalConfirmation)) {
        return;
      }

      const $button = $(event.currentTarget);
      const $wrapper = $button.parents('.module-license-key');
      const pluginSlug = $button.data('module');

      // Disable field until the request has been complete.
      $button.prop('disabled', true);

      // Send license request.
      $.ajax(`${OSDXPDashboard.restUrl}/license/${pluginSlug}`, {
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', OSDXPDashboard.restNonce);
        },
        method: 'DELETE'
      }).always(() => {
        $button.prop('disabled', false);
      }).done((response) => {
        // Remove previously set error/success messages.
        clearMessages($wrapper);

        if (response.success) {
          // Remove the license key markup.
          $button.parents('.display-license').remove();

          // Clear value from license field.
          $wrapper.find('.js-osdxp-module-license').val(null);

          // Show license field.
          $wrapper.find('.license-input-wrapper').removeClass('hidden');

          // Remove any license errors for this plugin.
          $(`.${pluginSlug}-license-error`).fadeOut();
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

    $(".dxp-dashboard #wp-admin-bar-root-default li").each( function() {
        $(this).addClass('current');
    });

    $('.plugin-update-tr td').attr('colspan', 4);
  });
})(jQuery);
