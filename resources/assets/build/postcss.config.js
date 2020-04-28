/* eslint-disable */

const cssnanoConfig = {
  preset: ["default", { discardComments: { removeAll: true } }],
};

const purgecss = require("@fullhuman/postcss-purgecss")({
  // Specify the paths to all of the template files in your project
  content: [
    "./src/**/*.html",
    "./src/**/*.vue",
    "./src/**/*.jsx",
    // etc.
  ],

  // Include any special characters you're using in this regular expression
  defaultExtractor: (content) => content.match(/[\w-/:]+(?<!:)/g) || [],
});

module.exports = ({ file, options }) => {
  return {
    parser: options.enabled.optimize ? "postcss-safe-parser" : undefined,
    plugins: {
      autoprefixer: true,
      ...(process.env.NODE_ENV === "production" ? [purgecss] : []),
      cssnano: options.enabled.optimize ? cssnanoConfig : false,
    },
  };
};
