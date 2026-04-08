/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./*.php", "./admin/*.php", "./js/*.js"],
  theme: {
    extend: {
      colors: {
        primary: '#865294',
        accent: '#865294',
        'accent-light': '#F3E8FF',
        surface: '#FFFFFF',
        bg: '#FFF3E1',
        text: '#2D1B36',
      },
    },
  },
  plugins: [],
}
