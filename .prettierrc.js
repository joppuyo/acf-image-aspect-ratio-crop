module.exports = {
  singleQuote: true,
  trailingComma: 'es5',
  overrides: [
    {
      files: ['*.php'],
      options: {
        trailingComma: 'all',
      }
    }
  ]
};
