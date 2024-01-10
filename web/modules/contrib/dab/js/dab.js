/**
 * @file
 * Drupal Component Builder behaviors.
 */
((Drupal) => {
  /**
   * Update the URL with the given params.
   * @param {Object.<string, string>} params
   *  The params to update.
   * @return {string}
   *  The new URL.
   */
  function updateUrlParams(params) {
    const urlObj = new URL(window.location.href);
    const searchParams = new URLSearchParams(urlObj.search);

    Object.keys(params).forEach((key) => {
      searchParams.set(key, params[key]);
    });

    urlObj.search = searchParams.toString();
    return urlObj.toString();
  }

  /**
   * Remove the given query param from the URL.
   * @param {string} paramName
   *   The name of the query param to remove.
   */
  function removeQueryParam(paramName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(paramName);
    const newUrl = url.toString();
    window.history.pushState({ path: newUrl }, '', newUrl);
  }

  /**
   * Add a behavior to reload the page when the user presses CTRL + R.
   */
  Drupal.behaviors.reloadBehavior = {
    attach(context) {
      const cacheClearForm = context.querySelector('#cache-clear-form');

      if (!cacheClearForm) {
        return;
      }

      window.addEventListener('keydown', (event) => {
        if (
          ((event.ctrlKey || event.metaKey) && event.key === 'r') ||
          event.keyCode === 116
        ) {
          event.preventDefault();

          cacheClearForm.submit();
        }
      });
    },
  };

  /**
   * Add a behavior to the iframe resize select.
   */
  Drupal.behaviors.resizeIframe = {
    attach(context) {
      const iframeResizeSelect = context.querySelector('#iframe-resize-select');
      const iframeContainer = context.querySelector('.dab-container');
      const iframe = context.querySelector('.dab-iframe');

      if (!iframeResizeSelect || !iframeContainer || !iframe) {
        return;
      }

      const changeIframeWidth = (value) => {
        const newUrl = updateUrlParams({ 'iframe-width': value });
        window.history.pushState({ path: newUrl }, '', newUrl);

        switch (value) {
          case 'mobile':
            iframeContainer.style.width = '320px';
            break;

          case 'tablet':
            iframeContainer.style.width = '768px';
            break;

          case 'desktop':
            iframeContainer.style.width = '1024px';
            break;

          case 'reset':
          default:
            iframeContainer.style.width = '';
            removeQueryParam('iframe-width');
            break;
        }
      };

      changeIframeWidth(iframeResizeSelect.value);

      iframeResizeSelect.addEventListener('change', (event) => {
        const { value } = event.target;
        changeIframeWidth(value);
      });
    },
  };

  /**
   * Add a behavior to the iframe provider select.
   */
  Drupal.behaviors.templateSelection = {
    attach(context, settings) {
      const templateSelect = context.querySelector('#template-select');
      const pathName = window.location.pathname;

      if (!templateSelect) {
        return;
      }

      templateSelect.addEventListener('change', (event) => {
        const providerValue = event.target.value;
        const newPathName = pathName.replace(
          settings.dab_component.route_parameters.provider,
          providerValue,
        );

        if (pathName !== newPathName) {
          window.location.pathname = newPathName;
        }
      });
    },
  };

  /**
   * Add a behavior to the iframe version select.
   */
  Drupal.behaviors.versionSelection = {
    attach(context) {
      const versionSelect = context.querySelector('#version-select');

      if (!versionSelect) {
        return;
      }

      versionSelect.addEventListener('change', (event) => {
        const versionValue = event.target.value;

        if (versionValue === '') {
          removeQueryParam('version');
          window.location.reload();
        } else {
          const fullUrl = updateUrlParams({ version: versionValue });
          window.location.href = fullUrl;
        }
      });
    },
  };

  /**
   * Reset all parameters in URL.
   */
  Drupal.behaviors.resetParams = {
    attach(context) {
      const versionSelect = context.querySelector('#reset-query-button');

      if (!versionSelect) {
        return;
      }

      if (window.location.search !== '') {
        versionSelect.style.display = 'block';
      } else {
        versionSelect.style.display = 'none';
      }
    },
  };
})(Drupal);
