/**
 * @file
 * Site preview.
 */

(function (window, Drupal, drupalSettings) {
  jQuery.fn.dialogToggleOnlyWhenCollapsed = () => {
    const dialogWidth = jQuery('#drupal-off-canvas').dialog('option', 'width');
    if (dialogWidth <= 440) {
      jQuery.fn.dialogToggle();
    }
  };

  jQuery.fn.dialogToggle = () => {
    const dialogWidth = jQuery('#drupal-off-canvas').dialog('option', 'width');
    const state = dialogWidth <= 440 ? 'collapsed' : 'expanded';
    const width = state === 'collapsed' ? '1200' : '440';
    const label = state === 'collapsed' ? '>>' : '<<';
    const height = jQuery('#drupal-off-canvas').dialog('option', 'height');

    jQuery('#dialog-toggle').html(label);
    jQuery('#drupal-off-canvas').dialog('option', {
      width,
      resizable: false,
    });

    // Fix height size, use original value before resizing.
    jQuery('#drupal-off-canvas-wrapper').css('height', `${height}px`);
    jQuery('#drupal-off-canvas').css('height', `${height}px`);
  };

  jQuery.fn.visualEditorOpenDialog = (url, dialog) => {
    const ajaxSettings = {
      url,
      dialogRenderer: 'off_canvas',
      dialogType: 'dialog',
      dialog,
    };

    Drupal.ajax(ajaxSettings).execute();
  };

  jQuery.fn.visualEditorReload = (storage, id) => {
    if (storage === 'node' && id) {
      // Reload node after save to refresh form state and prevent error:
      // The content has either been modified by another user,
      // or you have already submitted modifications.
      // As a result, your changes cannot be saved.
      // Reload Drupal page to refresh form state.
      window.location.href = `/node/${id}`;
    }
  };

  Drupal.behaviors.visualEditorLoad = {
    attach(context) {
      const {
        openLoad,
        isPreview = false,
        node,
      } = drupalSettings.visual_editor;
      const { selector } = drupalSettings.decoupled_preview_iframe;
      const width = 440;
      // Attach dialog toggle event
      if (once('dialog-toggle', '#dialog-toggle', context).length) {
        jQuery('#dialog-toggle').bind('click', () => {
          jQuery.fn.dialogToggle();
        });
      }
      // Body ready events
      once('visual_editor', selector, context).forEach(() => {
        if (openLoad && node) {
          // Open dialog
          jQuery.fn.visualEditorOpenDialog(
            `/visual_editor/form/node/${node}/edit?preview=${isPreview}`,
            {
              width,
              resizable: false,
            },
          );
          // Remove resizable from dialog
          jQuery(window).on('dialog:aftercreate', function () {
            jQuery('#drupal-off-canvas').dialog('option', {
              width,
              resizable: false,
            });
          });
        }
      });
    },
  };

  Drupal.behaviors.visualEditorListener = {
    attach() {
      window.addEventListener(
        'message',
        (event) => {
          const { data } = event;
          const { type } = data;

          if (!type) {
            return;
          }

          if (type === 'VISUAL_EDITOR_COMPONENT_ORDER') {
            const {
              items: { updated },
              active,
              over,
            } = data.changes;

            if (active.index !== over.index) {
              updated.forEach((item, index) => {
                jQuery(`tr[data-visual-editor-uuid="${item}"]`)
                  .children('td.delta-order.tabledrag-hide')
                  .find('select')
                  .val(index);
              });

              const collapse = 'input[name="field_components_collapse_all"]';
              jQuery(collapse).trigger('mousedown');
              jQuery(collapse).trigger('mousedown');
            }

            event.stopImmediatePropagation();
          }

          if (type === 'VISUAL_EDITOR_COMPONENT') {
            const { uuid } = data;
            const trSelector = `tr[data-visual-editor-uuid="${uuid}"]`;
            const editButtonSelector = 'div.paragraphs-actions > input';
            const editButton = jQuery(trSelector).find(editButtonSelector);
            const editButtonId = editButton.attr('id');
            const tabId = jQuery(`#${editButtonId}`)
              .parents('details.field-group-tab')
              .first()
              .attr('id');
            const tabWrapper = 'div.field-group-tabs-wrapper';

            // Click on tab
            if (tabId) {
              jQuery(`a[href="#${tabId}"]`, tabWrapper).click();
            }
            // Expand paragraph
            editButton.trigger('mousedown');
            // Expand dialog
            jQuery.fn.dialogToggleOnlyWhenCollapsed();

            // @todo: find why scrollIntoView got reset
            if (trSelector) {
              document.querySelector(trSelector).scrollIntoView({
                behavior: 'auto',
                block: 'center',
                inline: 'nearest',
              });
            }

            event.stopImmediatePropagation();
          }
        },
        false,
      );
    },
  };
})(window, Drupal, drupalSettings);
