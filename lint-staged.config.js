export default {
  'backend/**/*.php': ['./backend/vendor/bin/pint'],
  'frontend/**/*.{ts,tsx,js,jsx}': ['eslint --fix --max-warnings=0', 'prettier --write'],
  'frontend/**/*.{css,json,md}': ['prettier --write'],
}
