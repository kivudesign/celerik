module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests/js'],
  setupFiles: ['<rootDir>/tests/js/setup.js'],
  transform: {
    '\\.m?js$': 'babel-jest',
  },
  // FullCalendar and Preact ship ES modules only for the browser; transform them too.
  transformIgnorePatterns: ['/node_modules/(?!(preact|@fullcalendar)/)'],
  collectCoverageFrom: ['resources/js/**/*.js', '!resources/js/index.js'],
};
