/** @type { import('@storybook/server-webpack5').StorybookConfig } */
const config = {
  stories: [
    "../stories/**/*.mdx", 
    "../stories/**/*.stories.@(json|yaml|yml)", 
    "../components/**/*.mdx", 
    "../components/**/*.stories.@(json|yaml|yml)"
  ],
  addons: [
    "@storybook/addon-links", 
    "@storybook/addon-essentials", 
    "@lullabot/storybook-drupal-addon"
  ],
  framework: {
    name: "@storybook/server-webpack5",
    options: {
      builder: {
        useSWC: true,
      },
    },
  },
  docs: {
    autodocs: "tag",
  },
};
export default config;
