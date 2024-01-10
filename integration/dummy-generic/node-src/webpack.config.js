const path = require('path');

module.exports = {
  entry: './exports.js',
  output: {
    filename: '../node-dist/exports.js',
    path: __dirname,
  },
  target: 'node'
};