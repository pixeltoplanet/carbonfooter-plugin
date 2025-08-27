// Merge Dutch i18n JSON into a handle-named file for carbonfooter-admin
// Usage: node scripts/merge-i18n.js

const fs = require("fs");
const path = require("path");

const languagesDir = path.join(__dirname, "..", "languages");
const outputFile = path.join(
	languagesDir,
	"carbonfooter-nl_NL-carbonfooter-admin.json",
);

function loadJson(filePath) {
	const content = fs.readFileSync(filePath, "utf8");
	return JSON.parse(content);
}

function extractLocaleData(obj) {
	if (!obj || !obj.locale_data) return {};
	return obj.locale_data.carbonfooter || obj.locale_data.messages || {};
}

function main() {
	const files = fs
		.readdirSync(languagesDir)
		.filter(
			(f) =>
				/^carbonfooter-nl_NL-.*\.json$/.test(f) &&
				!/-carbonfooter-admin\.json$/.test(f),
		);

	const merged = {};

	for (const f of files) {
		const json = loadJson(path.join(languagesDir, f));
		const dict = extractLocaleData(json);
		for (const key of Object.keys(dict)) {
			if (key === "") continue;
			merged[key] = dict[key];
		}
	}

	const out = {
		"translation-revision-date": new Date().toISOString(),
		generator: "merge-i18n.js",
		domain: "carbonfooter",
		locale_data: {
			carbonfooter: Object.assign(
				{
					"": {
						domain: "carbonfooter",
						lang: "nl_NL",
						"plural-forms": "nplurals=2; plural=(n != 1);",
					},
				},
				merged,
			),
		},
	};

	fs.writeFileSync(outputFile, JSON.stringify(out));
	process.stdout.write(
		`Merged ${files.length} files into ${path.basename(outputFile)}\n`,
	);
}

main();
