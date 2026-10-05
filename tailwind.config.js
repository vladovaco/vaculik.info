/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/**/*.php'],
  darkMode: 'media',
  theme: {
    extend: {
      fontFamily: { sans: ['system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'] },
    },
  },
  plugins: [require('@tailwindcss/forms')],
};
