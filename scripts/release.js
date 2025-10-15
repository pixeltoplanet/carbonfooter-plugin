#!/usr/bin/env node

/*
 * CarbonFooter Plugin Release Script (Node)
 *
 * Creates a versioned zip in releases/ by staging files with excludes,
 * ensuring build assets exist, and zipping the staging directory.
 */

const fs = require("node:fs");
const path = require("node:path");
const os = require("node:os");
const { execSync } = require("node:child_process");

const ROOT = path.resolve(__dirname, "..");

function log(msg) {
	console.log(`[*] ${msg}`);
}

function warn(msg) {
	console.warn(`[!] ${msg}`);
}

function error(msg) {
	console.error(`[x] ${msg}`);
}

function read(file) {
	return fs.readFileSync(file, "utf8");
}

function getVersionFromPluginHeader(content) {
	const match = content.match(
		/^[ \t\/*#@]*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/im,
	);
	if (!match)
		throw new Error("Could not extract Version from carbonfooter.php");
	return match[1];
}

function ensureDir(dir) {
	if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
}

function cmdExists(cmd) {
	try {
		execSync(
			process.platform === "win32" ? `where ${cmd}` : `command -v ${cmd}`,
			{ stdio: "ignore" },
		);
		return true;
	} catch {
		return false;
	}
}

function main() {
	log("CarbonFooter Plugin Release");

	const pluginMain = path.join(ROOT, "carbonfooter.php");
	if (!fs.existsSync(pluginMain)) {
		throw new Error("carbonfooter.php not found. Run from plugin root.");
	}

	const version = getVersionFromPluginHeader(read(pluginMain));
	log(`Detected version: ${version}`);

	const releasesDir = path.join(ROOT, "releases");
	ensureDir(releasesDir);

	const zipName = `carbonfooter-v${version}.zip`;
	const zipPath = path.join(releasesDir, zipName);

	if (fs.existsSync(zipPath)) {
		warn(`Removing existing zip: ${zipName}`);
		fs.unlinkSync(zipPath);
	}

	// Create staging dir
	const tempDir = fs.mkdtempSync(
		path.join(os.tmpdir(), "carbonfooter-release-"),
	);
	const stagingDir = path.join(tempDir, "carbonfooter");
	ensureDir(stagingDir);
	log(`Staging at: ${stagingDir}`);

	// Copy with exclusions via rsync for speed/filters
	const exclude = [
		"releases/",
		"ideas/",
		"contributing/",
		"src/",
		"vendor/",
		"node_modules/",
		".git/",
		".gitignore",
		".github/",
		"*.log",
		"logs/",
		"*.sh",
		"scripts/",
		".vscode",
		".cursor",
		"bun.lockb",
		"biome.json",
		".editorconfig",
		".phpcs.xml.dist",
		".phpunit.xml.dist",
		"package.json",
		"package-lock.json",
		"pnpm-lock.yaml",
		"webpack.config.js",
		"composer.json",
		"composer.lock",
		".DS_Store",
		"Thumbs.db",
		"*.tmp",
		"*.bak",
	];

	if (!cmdExists("rsync")) {
		throw new Error(
			"rsync not found. Please install rsync or adapt the script to a pure Node copy.",
		);
	}

	const excludeArgs = exclude.map((e) => `--exclude='${e}'`).join(" ");
	execSync(`rsync -av ${excludeArgs} "${ROOT}/" "${stagingDir}/"`, {
		stdio: "inherit",
	});

	// Ensure build assets exist in staging; if not, attempt build and copy
	const stagedBuildJs = path.join(stagingDir, "build", "index.js");
	if (!fs.existsSync(stagedBuildJs)) {
		warn("Build files not found in staging. Attempting to build...");

		const pkgJson = path.join(ROOT, "package.json");
		const canBuild = fs.existsSync(pkgJson) && cmdExists("pnpm");
		if (!canBuild) {
			throw new Error(
				"Cannot build: package.json not found or pnpm not installed. Run pnpm run build manually.",
			);
		}

		log("Running pnpm install --frozen-lockfile ...");
		execSync("pnpm install --frozen-lockfile", { cwd: ROOT, stdio: "inherit" });

		log("Running pnpm run build ...");
		execSync("pnpm run build", { cwd: ROOT, stdio: "inherit" });

		// Copy build/ to staging
		const sourceBuild = path.join(ROOT, "build");
		if (!fs.existsSync(sourceBuild)) {
			throw new Error("Build failed: no build directory found");
		}
		execSync(
			`rsync -av "${sourceBuild}/" "${path.join(stagingDir, "build")}/"`,
			{ stdio: "inherit" },
		);
	}

	// Zip staging dir
	if (!cmdExists("zip")) {
		throw new Error("zip command not found. Please install zip.");
	}
	log(`Creating zip: ${zipName}`);
	execSync(`cd "${tempDir}" && zip -r "${zipPath}" carbonfooter/ > /dev/null`, {
		stdio: "inherit",
		shell: "/bin/bash",
	});

	// Cleanup
	fs.rmSync(tempDir, { recursive: true, force: true });

	// Summary
	const sizeBytes = fs.statSync(zipPath).size;
	const sizeKb = (sizeBytes / 1024).toFixed(1);
	log("Release created successfully");
	console.log(`  File: ${zipPath}`);
	console.log(`  Size: ${sizeKb} KB`);

	// Optional: list zip contents (first ~20 entries) if unzip exists
	if (cmdExists("unzip")) {
		try {
			log("Archive contents (head):");
			execSync(`unzip -l "${zipPath}" | head -20`, {
				stdio: "inherit",
				shell: "/bin/bash",
			});
		} catch {}
	}
}

try {
	main();
} catch (e) {
	error(e.message || String(e));
	process.exit(1);
}
