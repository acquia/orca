const utils = require ('../src/utils')

test('resolved path', () => {
  expect(utils.resolvePath('~/orca')).toBe(`${process.env.HOME}/orca`);
});
