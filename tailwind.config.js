/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        brand: {
          DEFAULT: '#F97316',
          dark:    '#EA6C10',
          light:   '#FB923C',
        },
        dark: {
          900: '#0F172A',
          800: '#1E293B',
          700: '#334155',
          600: '#475569',
        },
        sidebar: '#0F172A',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
