import {
	Card,
	CardBody,
	CardHeader,
	Notice,
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { Table, ActionButtons } from "./Table";

const NotTestedPages = () => {
	const [untestedPages, setUntestedPages] = useState({});
	const [heaviestPages, setHeaviestPages] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [error, setError] = useState(null);

	useEffect(() => {
		loadData();
	}, []);

	const loadData = async () => {
		try {
			setIsLoading(true);
			await Promise.all([loadUntestedPages(), loadHeaviestPages()]);
		} catch (error) {
			console.error("Error loading data:", error);
			setError(__("Error loading data", "carbonfooter"));
		} finally {
			setIsLoading(false);
		}
	};

	const loadUntestedPages = async () => {
		const formData = new FormData();
		formData.append("action", "carbonfooter_get_untested_pages");
		formData.append("nonce", window.carbonfooterVars?.nonce);

		const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
			method: "POST",
			body: formData,
		});

		const data = await response.json();
		if (data.success) {
			setUntestedPages(data.data);
		} else {
			throw new Error(__("Failed to load untested pages", "carbonfooter"));
		}
	};

	const loadHeaviestPages = async () => {
		const formData = new FormData();
		formData.append("action", "carbonfooter_get_heaviest_pages");
		formData.append("nonce", window.carbonfooterVars?.nonce);
		// formData.append("limit", "10");

		const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
			method: "POST",
			body: formData,
		});

		const data = await response.json();
		if (data.success) {
			setHeaviestPages(data.data);
		} else {
			throw new Error(__("Failed to load heaviest pages", "carbonfooter"));
		}
	};

	const formatDate = (dateString) => {
		const date = new Date(dateString);
		return date.toLocaleDateString();
	};

	const getTotalUntestedPages = () => {
		let total = 0;
		for (const group of Object.values(untestedPages)) {
			total += group.pages.length;
		}
		return total;
	};

	// Define table columns for untested pages
	const untestedPagesColumns = [
		{
			key: "title",
			label: __("Title", "carbonfooter"),
			align: "left",
		},
		{
			key: "actions",
			label: __("Actions", "carbonfooter"),
			align: "right",
		},
	];

	if (isLoading) {
		return (
			<Card>
				<CardHeader>
					<Heading level={2}>{__("Pages Overview", "carbonfooter")}</Heading>
				</CardHeader>
				<CardBody>
					<Notice status="info">
						{__("Loading pages data...", "carbonfooter")}
					</Notice>
				</CardBody>
			</Card>
		);
	}

	if (error) {
		return (
			<Card>
				<CardHeader>
					<Heading level={2}>{__("Pages Overview", "carbonfooter")}</Heading>
				</CardHeader>
				<CardBody>
					<Notice status="error">{error}</Notice>
				</CardBody>
			</Card>
		);
	}

	const totalUntestedPages = getTotalUntestedPages();

	let untestedPagesTitle = __("Untested Pages", "carbonfooter");

	if (totalUntestedPages > 0) {
		untestedPagesTitle = `${__("Untested Pages", "carbonfooter")}`;
	}

	return (
		<Panel>
			<PanelBody
				title={untestedPagesTitle}
				initialOpen={false}
				className="carbonfooter-settings-panel"
			>
				{totalUntestedPages > 0 ? (
					<div>
						{Object.entries(untestedPages).map(([postType, group]) => (
							<div key={postType} style={{ marginBottom: "24px" }}>
								<h4
									style={{
										margin: "0 0 12px 0",
										fontSize: "16px",
										fontWeight: "600",
										color: "#1d2327",
									}}
								>
									{group.label}
								</h4>
								<div style={{ overflowX: "auto" }}>
									<Table
										data={group.pages.map((page) => ({
											...page,
											actions: (
												<ActionButtons
													actions={[
														// {
														// 	href: page.edit_url,
														// 	label: __("Edit", "carbonfooter"),
														// },
														{
															href: page.url,
															label: __(
																"Visit & calculate emissions",
																"carbonfooter",
															),
															target: "_blank",
															rel: "noopener noreferrer",
														},
													]}
												/>
											),
										}))}
										columns={untestedPagesColumns}
										style={{
											width: "100%",
											borderCollapse: "collapse",
											fontSize: "14px",
										}}
									/>
								</div>
							</div>
						))}
					</div>
				) : (
					<Notice status="success">
						{__("All pages have been tested for emissions!", "carbonfooter")}
					</Notice>
				)}
			</PanelBody>
		</Panel>
	);
};

export default NotTestedPages;
