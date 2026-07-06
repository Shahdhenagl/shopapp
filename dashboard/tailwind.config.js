/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#1B2A4A',
          50: '#eef2f8',
          100: '#d6e0ee',
          200: '#aec1dd',
          300: '#7e9bc6',
          400: '#5275ab',
          500: '#37588f',
          600: '#2b4574',
          700: '#243a60',
          800: '#1B2A4A',
          900: '#15213a',
        },
      },
      fontFamily: {
        sans: ['Cairo', 'Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
