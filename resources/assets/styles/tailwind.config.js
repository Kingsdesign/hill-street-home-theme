module.exports = {
  //corePlugins: ["container"],
  theme: {
    container: {
      center: true,
    },
    extend: {
      screens: {
        /*sm: { min: "640px", max: "767px" },
        md: { min: "768px", max: "1023px" },
        lg: { min: "1024px", max: "1279px" },
        xl: { min: "1280px" },*/
        [`xs-max`]: { max: "639px" },
        [`sm-max`]: { max: "767px" },
        [`md-max`]: { max: "1023px" },
        [`lg-max`]: { max: "1279px" },
      },
      colors: {
        blue: {
          "50": "#E6E9F0",
          "100": "#BEC8DA",
          "200": "#94A5C1",
          "300": "#6782AD",
          "400": "#4B689A",
          "500": "#1F508F",
          "600": "#194986",
          "700": "#14407D",
          "800": "#103772",
          "900": "#07265F",
        },
        gray: {
          "50": "#F7F8F3",
          "100": "#EDEEEA",
          "200": "#E1E2DD",
          "300": "#CECECA",
          "400": "#A9AAA6",
          "500": "#888985",
          "600": "#61625E",
          "700": "#4F4F4C",
          "800": "#30312D",
          "900": "#10110D",
        },
      },
    },
  },
  variants: {},
  plugins: [],
};
