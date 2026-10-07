/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#ffd700',
        secondary: '#1a1a1a',
        accent: '#ff3e3e',
      }
    },
  },
  plugins: [],
}
