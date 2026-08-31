/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue"],
    darkMode: "class",
    theme: {
      container: {
        center: true,
      },
      extend: {
        colors: {
          primary: {
            DEFAULT: "#4361ee",
            light: "#eaf1ff",
            "dark-light": "rgba(67,97,238,.15)",
          },
          secondary: {
            DEFAULT: "#805dca",
            light: "#ebe4f7",
            "dark-light": "rgb(128 93 202 / 15%)",
          },
          success: {
            DEFAULT: "#00ab55",
            light: "#ddf5f0",
            "dark-light": "rgba(0,171,85,.15)",
          },
          danger: {
            DEFAULT: "#e7515a",
            light: "#fff5f5",
            "dark-light": "rgba(231,81,90,.15)",
          },
          warning: {
            DEFAULT: "#e2a03f",
            light: "#fff9ed",
            "dark-light": "rgba(226,160,63,.15)",
          },
          info: {
            DEFAULT: "#2196f3",
            light: "#e7f7ff",
            "dark-light": "rgba(33,150,243,.15)",
          },
          dark: {
            DEFAULT: "#3b3f5c",
            light: "#eaeaec",
            "dark-light": "rgba(59,63,92,.15)",
          },
          black: {
            DEFAULT: "#0e1726",
            light: "#e3e4eb",
            "dark-light": "rgba(14,23,38,.15)",
          },
          white: {
            DEFAULT: "#ffffff",
            light: "#e0e6ed",
            dark: "#888ea8",
          },
          // Jetons de terrain. L'écran de séance ne suit pas la grille du
          // template : il est tenu d'une main, en plein soleil, dans une salle
          // sans électricité. Jaune plein, noir plein, contours nets.
          jaune: {
            DEFAULT: "#f2c200",
            sourd: "#fdf4d0",
          },
          noir: "#111111",
          blanc: "#ffffff",
          ligne: "#d8d8d2",
          "gris-texte": "#5b5b55",
          "success-texte": "#00753c",
        },
        fontFamily: {
          // Le template écrit `font-nunito` partout ; on redirige la clé vers
          // IBM Plex Sans plutôt que de réécrire chaque vue.
          nunito: ["IBM Plex Sans", "Nunito", "sans-serif"],
          titre: ["Archivo", "sans-serif"],
          corps: ["IBM Plex Sans", "sans-serif"],
          chiffre: ["IBM Plex Mono", "monospace"],
        },
        borderRadius: {
          net: "4px",
          carte: "8px",
        },
        spacing: {
          4.5: "18px",
          // 48 px : une cible tactile pour un doigt, pas pour un curseur.
          tactile: "48px",
        },
        boxShadow: {
          "3xl":
            "0 2px 2px rgb(224 230 237 / 46%), 1px 6px 7px rgb(224 230 237 / 46%)",
        },
        typography: {
          DEFAULT: {
            css: {
              h1: { fontSize: "40px" },
              h2: { fontSize: "32px" },
              h3: { fontSize: "28px" },
              h4: { fontSize: "24px" },
              h5: { fontSize: "20px" },
              h6: { fontSize: "16px" },
            },
          },
        },
      },
    },
    plugins: [
      require("@tailwindcss/forms")({
        strategy: "base", // only generate global styles
      }),
      require("@tailwindcss/typography"),
    ],
  };
