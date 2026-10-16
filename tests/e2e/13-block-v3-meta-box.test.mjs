import { blockScenario, blockSupport } from '../lib/block-scenario.mjs';

// ACF block version 3 on a post that also has a crop field in a meta box.
// The meta box field sets up the field's JavaScript on page load, which is
// what used to hide block bugs.
blockScenario({
  title: 'ACF block version 3 with a meta box',
  blockName: 'acf/aiarc-test-v3',
  skip: await blockSupport({ minimumAcfVersion: '6.6' }),
  expandedEditor: true,
  metaBox: true,
});
