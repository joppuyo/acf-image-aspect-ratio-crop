import { after, before, describe, it } from 'node:test';
import * as browser from './browser.mjs';

/**
 * A scenario is a sequence of steps that build on each other, so once a step
 * fails the rest are skipped instead of failing one after another for the
 * same reason. Every scenario gets its own browser. Options are passed on to
 * describe, so a scenario can be skipped with { skip: 'reason' }.
 */
export function scenario(name, options, define) {
  if (typeof options === 'function') {
    define = options;
    options = {};
  }

  describe(name, options, () => {
    let failed = false;

    before(async () => {
      await browser.launch();
    });

    after(async () => {
      await browser.close();
    });

    const step = (title, run) => {
      it(title, async (t) => {
        if (failed) {
          t.skip('an earlier step failed');
          return;
        }

        try {
          await run(t);
        } catch (error) {
          failed = true;
          await browser.screenshotOnFailure(`${name} ${title}`);
          throw error;
        }
      });
    };

    define({ step, before, after });
  });
}
