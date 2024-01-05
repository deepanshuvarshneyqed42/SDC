/** @type { import('@storybook/server').Preview } */
const preview = {
  globals: {
    drupalTheme: 'sdc_custom',
    supportedDrupalThemes: {
      sdc_custom: {title: 'SDC Custom'},
      claro: {title: 'Claro'},
    },
  },
  parameters: {
    server: {
      // Replace this with your Drupal site URL, or an environment variable.
      url: 'https://sdc.ddev.site',
    },
    actions: { argTypesRegex: "^on[A-Z].*" },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
};

export default preview;
