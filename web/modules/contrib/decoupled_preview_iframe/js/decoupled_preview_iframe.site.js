/**
 * @file
 * Site preview.
 */

(function (window, Drupal, drupalSettings) {
  jQuery.fn.toggleShowPublished = (state) => {
    const { draftUrl, publishedUrl, selector } =
      drupalSettings.decoupled_preview_iframe;
    if (state) {
      jQuery(selector).attr('src', publishedUrl);
      jQuery('#preview_url_anchor').attr('href', publishedUrl);
      jQuery('#preview_url_anchor').text(publishedUrl);
    } else {
      jQuery(selector).attr('src', draftUrl);
      jQuery('#preview_url_anchor').attr('href', draftUrl);
      jQuery('#preview_url_anchor').text(draftUrl);
    }
  };

  Drupal.behaviors.decoupledPreviewIframeLoad = {
    attach(context) {
      if (once('show_published', '#show_published_toggle', context).length) {
        jQuery('#show_published_toggle').bind('click', (event) => {
          jQuery.fn.toggleShowPublished(event.target.checked);
        });
      }

      const { selector } = drupalSettings.decoupled_preview_iframe;
      const iframe = context.querySelector(selector);

      if (!iframe) {
        return;
      }

      iframe.addEventListener('load', () => {
        iframe.classList.add('ready');
      });
    },
  };

  Drupal.behaviors.decoupledPreviewIframeLoadSyncRoute = {
    attach() {
      window.addEventListener(
        'message',
        (event) => {
          const { routeSyncType = 'DECOUPLED_PREVIEW_IFRAME_ROUTE_SYNC' } =
            drupalSettings.decoupled_preview_iframe;
          const { data } = event;

          if (data.type !== routeSyncType || !data.path) {
            return;
          }

          if (window.location.pathname !== data.path) {
            window.location.href = data.path;
          }
        },
        false,
      );
    },
  };
})(window, Drupal, drupalSettings);
