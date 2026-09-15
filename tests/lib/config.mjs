import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const libDir = path.dirname(fileURLToPath(import.meta.url));

// The plugin has to be checked out inside the plugins directory of the Lando
// site, so the site layout can be derived from the location of this file.
export const pluginDir = path.resolve(libDir, '..', '..');
export const pluginSlug = path.basename(pluginDir);
export const pluginsDir = path.dirname(pluginDir);
export const fixturesDir = path.join(pluginDir, 'tests', 'fixtures');
export const outputDir = path.join(pluginDir, 'tests', 'output');

// Directory containing .lando.yml. Found by walking up from the plugin
// directory unless given explicitly.
export const landoAppRoot =
  process.env.LANDO_APP_ROOT || findLandoAppRoot(pluginDir);

// Credentials of the admin user that wp core install creates on every reset
export const admin = {
  username: process.env.WP_ADMIN_USER || 'admin',
  password: process.env.WP_ADMIN_PASSWORD || 'admin',
  email: process.env.WP_ADMIN_EMAIL || 'admin@wp.test',
};

// Slug of the ACF plugin already installed in the site, either the Pro or the
// free version
export const acfPlugin = process.env.ACF_PLUGIN || 'advanced-custom-fields-pro';

// Set HEADLESS=false to watch the browser
export const headless = process.env.HEADLESS !== 'false';

// Slows every browser action down by this many milliseconds when watching
export const slowMo = Number(process.env.SLOWMO || 0);

function findLandoAppRoot(start) {
  let dir = start;

  while (!fs.existsSync(path.join(dir, '.lando.yml'))) {
    const parent = path.dirname(dir);

    if (parent === dir) {
      throw new Error(
        `Could not find a .lando.yml above ${start}. Set LANDO_APP_ROOT to the Lando app directory.`,
      );
    }

    dir = parent;
  }

  return dir;
}
