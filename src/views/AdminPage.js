import { useState, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
// import apiFetch from "@wordpress/api-fetch";
import {
	Card,
	CardBody,
	CardHeader,
	Notice,
	__experimentalHeading as Heading,
} from "@wordpress/components";
import { PageTitle, PageHeading } from "../components/PageTitle";
import Overview from "../components/Overview";
import OverViewDirtyPages from "../components/OverViewDirtyPages";
import OverviewResources from "../components/OverviewResources";
import { formatBytes } from "../lib/formatBytes";

// Safe component fallbacks
const Text = ({ children, size, weight, color, ...props }) => (
	<span
		style={{
			fontSize: size || "inherit",
			fontWeight: weight || "normal",
			color: color || "inherit",
		}}
		{...props}
	>
		{children}
	</span>
);

const Spacer = ({ margin = 2, ...props }) => (
	<div style={{ margin: `${margin * 0.25}rem 0` }} {...props} />
);

// Helper function to format date
const formatDate = (dateString) => {
	if (!dateString) return "";
	const date = new Date(dateString);
	return `${date.toLocaleDateString()} ${date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}`;
};

const AdminPage = () => {
	const [stats, setStats] = useState(
		window.carbonfooterVars?.initialData || {
			average: 0,
			total_measured: 0,
			hosting_status: false,
			total_emissions: 0,
			latest_test_date: null,
			resource_stats: {},
		},
	);
	const [heaviestPages, setHeaviestPages] = useState([]);
	// const [isRefreshing, setIsRefreshing] = useState(false);
	const [notice, setNotice] = useState(null);

	// Load initial data
	useEffect(() => {
		loadStats();
		loadHeaviestPages();
	}, []);

	const loadStats = async () => {
		try {
			const formData = new FormData();
			formData.append("action", "carbonfooter_get_stats");
			formData.append("nonce", window.carbonfooterVars?.nonce);

			const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
				method: "POST",
				body: formData,
			});

			const data = await response.json();
			if (data.success) {
				setStats(data.data);
			}
		} catch (error) {
			console.error("Error loading stats:", error);
		}
	};

	const loadHeaviestPages = async () => {
		try {
			const formData = new FormData();
			formData.append("action", "carbonfooter_get_heaviest_pages");
			formData.append("nonce", window.carbonfooterVars?.nonce);
			formData.append("limit", "10");

			const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
				method: "POST",
				body: formData,
			});

			const data = await response.json();
			if (data.success) {
				setHeaviestPages(data.data);
			}
		} catch (error) {
			console.error("Error loading heaviest pages:", error);
		}
	};

	return (
		<div className="wrap">
			<PageTitle title={__("Carbonfooter", "carbonfooter")} />

			{stats.latest_test_date && (
				<Text color="#666" size="14px">
					{__("Last test on", "carbonfooter")}{" "}
					{formatDate(stats.latest_test_date)}
				</Text>
			)}
			<Spacer margin={4} />

			<PageHeading title={__("Overview", "carbonfooter")} />

			{notice && (
				<Notice
					status={notice.type}
					isDismissible={true}
					onRemove={() => setNotice(null)}
				>
					{notice.message}
				</Notice>
			)}

			<Spacer margin={4} />

			<Overview stats={stats} />

			<Spacer margin={6} />

			<OverViewDirtyPages heaviestPages={heaviestPages} maxPages={5} />

			{stats.resource_stats && Object.keys(stats.resource_stats).length > 0 && (
				<>
					<Spacer margin={6} />

					<OverviewResources stats={stats} />
				</>
			)}
		</div>
	);
};

export default AdminPage;
