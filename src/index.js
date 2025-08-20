import { createRoot } from "@wordpress/element";
import AdminPage from "./views/AdminPage";
import SettingsPage from "./views/SettingsPage";
import ResultsPage from "./views/ResultsPage";
import "./assets/styles/admin.scss";

// Render the appropriate page when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
	// Main admin page
	const adminRoot = document.getElementById("carbonfooter-admin-root");
	if (adminRoot) {
		const root = createRoot(adminRoot);
		root.render(<AdminPage />);
	}

	// Settings page
	const settingsRoot = document.getElementById("carbonfooter-settings-root");
	if (settingsRoot) {
		const root = createRoot(settingsRoot);
		root.render(<SettingsPage />);
	}

	// Results page
	const resultsRoot = document.getElementById("carbonfooter-results-root");
	if (resultsRoot) {
		const root = createRoot(resultsRoot);
		root.render(<ResultsPage />);
	}
});
