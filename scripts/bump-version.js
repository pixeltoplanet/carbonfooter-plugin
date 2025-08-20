#!/usr/bin/env node

/*
 * Bump plugin version across all relevant files and update changelog.
 *
 * Prompts for:
 * - bump type: patch | minor | major
 * - title: required
 * - description: optional
 *
 * Updates:
 * - carbonfooter.php (header Version + CARBONFOOTER_VERSION)
 * - inc/class-plugin.php (public const VERSION)
 * - inc/class-constants.php (public const VERSION)
 * - package.json (version)
 * - composer.json (version)
 * - readme.txt (Stable tag + prepend changelog entry)
 */

const fs = require('fs');
const path = require('path');
const readline = require('readline');

const ROOT = path.resolve(__dirname, '..');

const FILES = {
  pluginMain: path.join(ROOT, 'carbonfooter.php'),
  classPlugin: path.join(ROOT, 'inc', 'class-plugin.php'),
  classConstants: path.join(ROOT, 'inc', 'class-constants.php'),
  packageJson: path.join(ROOT, 'package.json'),
  composerJson: path.join(ROOT, 'composer.json'),
  readme: path.join(ROOT, 'readme.txt'),
};

function read(file) {
  return fs.readFileSync(file, 'utf8');
}

function write(file, content) {
  fs.writeFileSync(file, content, 'utf8');
}

function bumpSemver(version, type) {
  const [major, minor, patch] = version.split('.').map((n) => parseInt(n, 10) || 0);
  if (!['patch', 'minor', 'major'].includes(type)) {
    throw new Error(`Invalid bump type: ${type}`);
  }
  if (type === 'major') return `${major + 1}.0.0`;
  if (type === 'minor') return `${major}.${minor + 1}.0`;
  return `${major}.${minor}.${(patch || 0) + 1}`;
}

function getCurrentVersionFromPluginHeader(content) {
  const match = content.match(/^[ \t\/*#@]*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/mi);
  if (!match) throw new Error('Could not find Version in plugin header');
  return match[1];
}

function updatePluginMain(content, newVersion) {
  let out = content.replace(/(Version:\s*)([0-9]+\.[0-9]+\.[0-9]+)/, `$1${newVersion}`);
  out = out.replace(/(define\('CARBONFOOTER_VERSION',\s*')[0-9]+\.[0-9]+\.[0-9]+('\);)/, `$1${newVersion}$2`);
  return out;
}

function updatePhpClassConstVersion(content, newVersion) {
  return content.replace(/(public\s+const\s+VERSION\s*=\s*')[0-9]+\.[0-9]+\.[0-9]+('\s*;)/, `$1${newVersion}$2`);
}

function updateJsonVersion(content, newVersion) {
  const obj = JSON.parse(content);
  obj.version = newVersion;
  return JSON.stringify(obj, null, 2) + '\n';
}

function updateReadme(content, newVersion, title, description) {
  // Stable tag
  let out = content.replace(/^(Stable tag:\s*)([0-9]+\.[0-9]+\.[0-9]+)/m, `$1${newVersion}`);

  // Prepend changelog entry after "== Changelog =="
  const changelogHeader = '== Changelog ==';
  const idx = out.indexOf(changelogHeader);
  if (idx !== -1) {
    const insertPos = idx + changelogHeader.length;
    const lines = [];
    lines.push('', `= ${newVersion} =`);
    if (title && title.trim()) {
      lines.push(`* ${title.trim()}`);
    }
    if (description && description.trim()) {
      const desc = description.trim().split(/\r?\n/).filter(Boolean);
      for (const d of desc) {
        lines.push(`* ${d}`);
      }
    }
    const insertion = '\n' + lines.join('\n') + '\n';
    out = out.slice(0, insertPos) + insertion + out.slice(insertPos);
  }
  return out;
}

async function prompt(query) {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  const answer = await new Promise((resolve) => rl.question(query, resolve));
  rl.close();
  return answer;
}

async function main() {
  try {
    const pluginMainContent = read(FILES.pluginMain);
    const currentVersion = getCurrentVersionFromPluginHeader(pluginMainContent);

    const bumpType = (await prompt(`Bump type (patch|minor|major) [patch]: `)).trim() || 'patch';
    if (!['patch', 'minor', 'major'].includes(bumpType)) {
      throw new Error('Bump type must be patch, minor, or major');
    }

    const title = (await prompt('Release title: ')).trim();
    if (!title) {
      throw new Error('Title is required');
    }
    const description = (await prompt('Description (optional, can be multi-line; end with Enter): ')).trim();

    const newVersion = bumpSemver(currentVersion, bumpType);

    // Write all updates
    write(FILES.pluginMain, updatePluginMain(pluginMainContent, newVersion));
    write(FILES.classPlugin, updatePhpClassConstVersion(read(FILES.classPlugin), newVersion));
    write(FILES.classConstants, updatePhpClassConstVersion(read(FILES.classConstants), newVersion));
    write(FILES.packageJson, updateJsonVersion(read(FILES.packageJson), newVersion));
    write(FILES.composerJson, updateJsonVersion(read(FILES.composerJson), newVersion));
    write(FILES.readme, updateReadme(read(FILES.readme), newVersion, title, description));

    // Summary
    console.log('\nVersion bump complete.');
    console.log(`  ${currentVersion} -> ${newVersion}`);
    console.log('Updated files:');
    Object.values(FILES).forEach((f) => console.log(`  - ${path.relative(ROOT, f)}`));
    console.log('\nNext steps:');
    console.log('  - Review readme.txt changelog formatting');
    console.log('  - Build assets if needed (pnpm run build)');
    console.log('  - Commit and tag the release');
  } catch (err) {
    console.error('Error:', err.message);
    process.exit(1);
  }
}

main();


