import { blockScenario, blockSupport } from '../lib/block-scenario.mjs';

// ACF block version 1, registered with acf_register_block_type(). ACF 5.10
// and older register it with WordPress block API version 1, newer versions
// with 2.
blockScenario({
  title: 'ACF block version 1',
  blockName: 'acf/aiarc-test-v1',
  skip: await blockSupport({ minimumAcfVersion: '5.8' }),
});
