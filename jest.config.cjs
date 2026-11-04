module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests/js'],
  setupFiles: ['<rootDir>/tests/js/setup.js'],
  transform: {
    '\\.m?js$': 'babel-jest',
  },
  collectCoverageFrom: ['resources/js/**/*.js', '!resources/js/index.js'],
};
