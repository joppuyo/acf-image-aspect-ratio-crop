import fs from 'node:fs';
import path from 'node:path';
import {
  acfPlugin,
  admin,
  fixturesDir,
  pluginSlug,
  pluginsDir,
} from './config.mjs';
import { appserverUrl, hostPath, wp, wpEval } from './lando.mjs';

let urls = null;

/**
 * Drops the database, installs WordPress again, empties the uploads directory
 * and activates ACF, the plugin under test and the given extra plugins. Extra
 * plugins that exist under tests/fixtures/plugins are copied into the site
 * first, anything else has to be installed in the site already.
 */
export async function reset({ plugins = [] } = {}) {
  urls = null;

  await wp('db', 'reset', '--yes');
  await wp(
    'core',
    'install',
    `--url=${await appserverUrl()}`,
    '--title=Test site',
    `--admin_user=${admin.username}`,
    `--admin_password=${admin.password}`,
    `--admin_email=${admin.email}`,
    '--skip-email',
  );
  await cleanUploads();

  for (const slug of plugins) {
    installFixturePlugin(slug);
  }

  await wp('plugin', 'activate', acfPlugin, pluginSlug, ...plugins);
}

/**
 * Copies a plugin from tests/fixtures/plugins into the plugins directory of
 * the site, replacing any previous copy. Does nothing for slugs that have no
 * fixture, so installed third party plugins can be listed alongside fixtures.
 */
export function installFixturePlugin(slug) {
  const source = path.join(fixturesDir, 'plugins', slug);

  if (!fs.existsSync(source)) {
    return false;
  }

  const target = path.join(pluginsDir, slug);

  fs.rmSync(target, { recursive: true, force: true });
  fs.cpSync(source, target, { recursive: true });

  return true;
}

/**
 * Name, version and requirements of an installed plugin as declared in its
 * header, or null when it is not installed. wp plugin list would report the
 * requirements of the newest release on wordpress.org instead, and the header
 * is what WordPress checks on activation.
 */
export async function pluginInfo(slug) {
  const output = await wpEval(`
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $slug = ${JSON.stringify(slug)};
    foreach (get_plugins() as $file => $data) {
      if ($file === "$slug.php" || dirname($file) === $slug) {
        echo json_encode([
          'name' => $data['Name'],
          'version' => $data['Version'],
          'requires' => $data['RequiresWP'],
          'requires_php' => $data['RequiresPHP'],
        ]);
        return;
      }
    }
    echo 'null';
  `);

  return JSON.parse(output);
}

export async function wordPressVersion() {
  return wp('core', 'version');
}

/**
 * Compares two dotted version strings, returning a negative number, zero or a
 * positive number like a sort callback.
 */
export function compareVersions(a, b) {
  const partsA = String(a).split('.').map(Number);
  const partsB = String(b).split('.').map(Number);

  for (let i = 0; i < Math.max(partsA.length, partsB.length); i += 1) {
    const difference = (partsA[i] || 0) - (partsB[i] || 0);

    if (difference !== 0) {
      return difference;
    }
  }

  return 0;
}

/**
 * Removes everything from the uploads directory except dotfiles such as the
 * .gitkeep Bedrock ships with.
 */
export async function cleanUploads() {
  const uploadsDir = hostPath(await wpEval('echo wp_upload_dir()["basedir"];'));

  if (!fs.existsSync(uploadsDir)) {
    return;
  }

  for (const entry of fs.readdirSync(uploadsDir)) {
    if (!entry.startsWith('.')) {
      fs.rmSync(path.join(uploadsDir, entry), { recursive: true, force: true });
    }
  }
}

/**
 * Home and site URLs as WordPress sees them. In Bedrock these come from the
 * WP_HOME and WP_SITEURL constants rather than from the install URL.
 */
export async function siteUrls() {
  if (!urls) {
    urls = {
      home: (await wp('option', 'get', 'home')).replace(/\/$/, ''),
      site: (await wp('option', 'get', 'siteurl')).replace(/\/$/, ''),
    };
  }

  return urls;
}

export async function homeUrl(pathname = '') {
  return `${(await siteUrls()).home}/${pathname.replace(/^\//, '')}`;
}

export async function adminUrl(pathname = '') {
  return `${(await siteUrls()).site}/wp-admin/${pathname.replace(/^\//, '')}`;
}

export async function loginUrl() {
  return `${(await siteUrls()).site}/wp-login.php`;
}

/**
 * Front end URL of a post. Uses the ?p= form so it works with any permalink
 * structure.
 */
export async function postUrl(postId) {
  return homeUrl(`?p=${postId}`);
}
