import { execFile } from 'node:child_process';
import path from 'node:path';
import { promisify } from 'node:util';
import { landoAppRoot } from './config.mjs';

const execFileAsync = promisify(execFile);

// Directory the Lando app root is mounted to inside the containers
const containerAppRoot = '/app';

/**
 * Runs a lando command in the app directory and returns its trimmed stdout.
 */
export async function lando(args) {
  try {
    const { stdout } = await execFileAsync('lando', args, {
      cwd: landoAppRoot,
      maxBuffer: 64 * 1024 * 1024,
    });

    return stdout.trim();
  } catch (error) {
    const output = [error.stdout, error.stderr]
      .filter(Boolean)
      .join('\n')
      .trim();

    throw new Error(
      `lando ${args.join(' ')} failed:\n${output || error.message}`,
    );
  }
}

/**
 * Runs WP-CLI inside the appserver container.
 */
export function wp(...args) {
  return lando(['wp', ...args]);
}

/**
 * Runs a snippet of PHP inside the site and returns what it printed.
 */
export function wpEval(code) {
  return wp('eval', code);
}

/**
 * Translates a path inside the container to the matching path on the host.
 */
export function hostPath(containerPath) {
  if (
    containerPath !== containerAppRoot &&
    !containerPath.startsWith(`${containerAppRoot}/`)
  ) {
    throw new Error(
      `${containerPath} is outside ${containerAppRoot}, cannot map it to the host`,
    );
  }

  return path.join(landoAppRoot, containerPath.slice(containerAppRoot.length));
}

/**
 * The public URL of the appserver, preferring the https proxy address.
 */
export async function appserverUrl() {
  const [info] = JSON.parse(
    await lando(['info', '--service', 'appserver', '--format', 'json']),
  );
  const proxied = info.urls.filter((url) => !url.includes('localhost'));
  const url =
    proxied.find((candidate) => candidate.startsWith('https://')) ||
    proxied[0] ||
    info.urls[0];

  if (!url) {
    throw new Error('lando info did not list any URLs for the appserver');
  }

  return url.replace(/\/$/, '');
}
