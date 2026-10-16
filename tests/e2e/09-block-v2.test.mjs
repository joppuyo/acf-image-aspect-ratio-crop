import { blockScenario, blockSupport } from '../lib/block-scenario.mjs';

// ACF block version 2, registered with block.json and WordPress block API
// version 2. ACF reads block.json from version 6.0.
blockScenario({
  title: 'ACF block version 2',
  blockName: 'acf/aiarc-test-v2',
  skip: await blockSupport({ minimumAcfVersion: '6.0' }),
});
