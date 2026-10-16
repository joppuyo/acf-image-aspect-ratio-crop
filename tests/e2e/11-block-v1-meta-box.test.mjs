import { blockScenario, blockSupport } from '../lib/block-scenario.mjs';

// ACF block version 1 on a post that also has a crop field in a meta box.
// The meta box field sets up the field's JavaScript on page load, which is
// what used to hide block bugs.
blockScenario({
  title: 'ACF block version 1 with a meta box',
  blockName: 'acf/aiarc-test-v1',
  skip: await blockSupport({ minimumAcfVersion: '5.8' }),
  metaBox: true,
});
