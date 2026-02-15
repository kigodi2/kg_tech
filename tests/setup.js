/**
 * Jest Setup File
 * Global configuration for unit tests
 */

// jsdom provides window.location by default, no need to mock

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.sessionStorage = sessionStorageMock;

// Mock FormData
global.FormData = class {
  constructor() {
    this.data = {};
  }
  append(key, value) {
    this.data[key] = value;
  }
  get(key) {
    return this.data[key];
  }
};

// Mock File API
global.File = class {
  constructor(bits, filename, options = {}) {
    this.name = filename;
    this.size = bits.reduce((acc, bit) => acc + bit.length, 0);
    this.type = options.type || '';
    this.content = bits.join('');
  }
};

global.Blob = class {
  constructor(bits, options = {}) {
    this.size = bits.reduce((acc, bit) => acc + bit.length, 0);
    this.type = options.type || '';
  }
};

// Suppress console warnings in tests
global.console.warn = jest.fn();
global.console.error = jest.fn();
