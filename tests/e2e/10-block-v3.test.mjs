import { blockScenario, blockSupport } from '../lib/block-scenario.mjs';

// ACF block version 3, registered with block.json and WordPress block API
// version 3. ACF has it from version 6.6, and from 6.8 mounts its form with
// the remount action, which the field did not set itself up on before.
blockScenario({
  title: 'ACF block version 3',
  blockName: 'acf/aiarc-test-v3',
  skip: await blockSupport({ minimumAcfVersion: '6.6' }),
  expandedEditor: true,
});
