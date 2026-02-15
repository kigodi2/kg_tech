export default {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests'],
  testMatch: ['**/__tests__/**/*.js', '**/?(*.)+(spec|test).js'],
  moduleFileExtensions: ['js', 'json'],
  collectCoverageFrom: [
    'resources/js/**/*.js',
    '!resources/js/**/*.min.js'
  ],
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  transform: {
    '^.+\\.js$': 'babel-jest'
  }
};
