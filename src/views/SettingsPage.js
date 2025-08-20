import { useState, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import WidgetPreview from "../components/WidgetPreview";
import {
	Button,
	Panel,
	PanelBody,
	ColorPicker,
	Notice,
	Snackbar,
	__experimentalHeading as Heading,
	__experimentalSpacer as Spacer,
	RadioControl,
} from "@wordpress/components";
import { PageTitle, PageHeading } from "../components/PageTitle";
import Text from "../components/Text";
import HowItWorks from "../components/HowItWorks";
import ExportData from "../components/ExportData";
import ResetAllData from "./ResetAllData";
import Troubleshooting from "../components/Troubleshooting";
import StartModal from "../components/StartModal";

const SettingsPage = () => {
	const [backgroundColor, setBackgroundColor] = useState("#000000");
	const [textColor, setTextColor] = useState("#FFFFFF");
	const [displaySetting, setDisplaySetting] = useState("shortcode");
	const [widgetStyle, setWidgetStyle] = useState("minimal");
	const [notice, setNotice] = useState(null);
	const [settingsNotice, setSettingsNotice] = useState(null);
	const [displayNotice, setDisplayNotice] = useState(null);
	const [styleNotice, setStyleNotice] = useState(null);
	const [clearDataNotice, setClearDataNotice] = useState(null);
	const [isClearingData, setIsClearingData] = useState(false);
	const [exportNotice, setExportNotice] = useState(null);
	const [isExportingData, setIsExportingData] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const [isOpen, setIsOpen] = useState(false);
	const [measurementStatus, setMeasurementStatus] = useState(null);
	const [homepageEmissions, setHomepageEmissions] = useState(null);
	const [snackbarMessage, setSnackbarMessage] = useState(null);
	const openModal = async () => {
		setIsOpen(true);
		setIsLoading(true);

		// Trigger homepage measurement
		try {
			// Get homepage post ID
			const homepageId = getHomepagePostId();

			if (homepageId) {
				// Make AJAX request to measure homepage
				const formData = new FormData();
				formData.append("action", "carbonfooter_measure");
				formData.append("nonce", window.carbonfooterVars?.nonce);
				formData.append("post_id", homepageId);

				const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
					method: "POST",
					body: formData,
				});

				const data = await response.json();
				if (data.success) {
					console.log("Homepage measurement completed:", data.data);
					console.log("Emissions value:", data.data.emissions);
					setMeasurementStatus({
						type: "success",
						message: `Homepage measurement completed: ${data.data.formatted}`,
						emissions: data.data.emissions,
					});
					// Update homepage emissions state so UI switches to "View homepage" button
					setHomepageEmissions(data.data.emissions);
				} else {
					console.error("Homepage measurement failed:", data.data);
					setMeasurementStatus({
						type: "error",
						message: "Homepage measurement failed. Please try again.",
						data: null,
					});
				}
			}
		} catch (error) {
			console.error("Error measuring homepage:", error);
			setMeasurementStatus({
				type: "error",
				message: "Error measuring homepage. Please try again.",
			});
		}
	};

	// Helper function to get homepage post ID
	const getHomepagePostId = () => {
		// Check if there's a static page set as homepage
		const showOnFront = window.carbonfooterVars?.siteSettings?.show_on_front;
		const pageOnFront = window.carbonfooterVars?.siteSettings?.page_on_front;

		if (showOnFront === "page" && pageOnFront) {
			return Number.parseInt(pageOnFront);
		}

		// If no static page is set, we can't measure a specific homepage
		// The measurement will happen when user visits the homepage
		return null;
	};

	// Check if homepage already has emissions data
	const checkHomepageEmissions = async () => {
		const homepageId = getHomepagePostId();
		if (!homepageId) return;

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
				// Check if homepage has emissions data
				const homepageEmissions = data.data.homepage_emissions;
				console.log("Homepage emissions check:", homepageEmissions);
				setHomepageEmissions(homepageEmissions);
			}
		} catch (error) {
			console.error("Error checking homepage emissions:", error);
		}
	};
	const closeModal = () => {
		setIsOpen(false);
		setIsLoading(false);
	};

	// Load initial data
	useEffect(() => {
		loadSettings();
		checkHomepageEmissions();
	}, []);

	const loadSettings = async () => {
		try {
			const settings = await apiFetch({
				path: "carbonfooter/v1/settings",
			});

			setBackgroundColor(settings.background_color || "#000000");
			setTextColor(settings.text_color || "#FFFFFF");
			setDisplaySetting(settings.display_setting || "auto");
			setWidgetStyle(settings.widget_style || "minimal");
		} catch (error) {
			console.error("Error loading settings:", error);
			// Fallback to default values if API fails
			setBackgroundColor("#000000");
			setTextColor("#FFFFFF");
			setDisplaySetting("auto");
			setWidgetStyle("minimal");
		}
	};

	const saveSettings = async (section = "all") => {
		try {
			const response = await apiFetch({
				path: "carbonfooter/v1/settings",
				method: "POST",
				data: {
					background_color: backgroundColor,
					text_color: textColor,
					display_setting: displaySetting,
					widget_style: widgetStyle,
				},
			});

			// Show snackbar with success message
			setSnackbarMessage(
				__("Carbonfooter widget settings have been saved.", "carbonfooter"),
			);
		} catch (error) {
			console.error("Error saving settings:", error);

			// Show snackbar error message
			setSnackbarMessage(
				__(
					"There was an error saving the carbonfooter widget settings.",
					"carbonfooter",
				),
			);

			// Show local error notification in display settings section
			setDisplayNotice({
				type: "error",
				message: __(
					"Failed to save display settings. Please try again.",
					"carbonfooter",
				),
			});

			// Show local error notification in style settings section
			setStyleNotice({
				type: "error",
				message: __(
					"Failed to save widget style. Please try again.",
					"carbonfooter",
				),
			});

			// Show local error notification in appearance settings section
			setSettingsNotice({
				type: "error",
				message: __(
					"Failed to save settings. Please try again.",
					"carbonfooter",
				),
			});

			// Clear notices after 4 seconds
			setTimeout(() => {
				setDisplayNotice(null);
				setStyleNotice(null);
				setSettingsNotice(null);
			}, 4000);
		}
	};

	const clearAllData = async () => {
		if (
			!window.confirm(
				__(
					"Are you sure you want to clear all CarbonFooter data? This action cannot be undone and will remove all emissions measurements, resource data, and history. Your settings will be preserved.",
					"carbonfooter",
				),
			)
		) {
			return;
		}

		setIsClearingData(true);
		try {
			const formData = new FormData();
			formData.append("action", "carbonfooter_clear_data");
			formData.append("nonce", window.carbonfooterVars?.nonce);

			const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
				method: "POST",
				body: formData,
			});

			const data = await response.json();
			if (data.success) {
				setClearDataNotice({
					type: "success",
					message: data.data.message,
				});
			} else {
				setClearDataNotice({
					type: "error",
					message:
						data.data ||
						__("Failed to clear data. Please try again.", "carbonfooter"),
				});
			}
		} catch (error) {
			console.error("Error clearing data:", error);
			setClearDataNotice({
				type: "error",
				message: __("Failed to clear data. Please try again.", "carbonfooter"),
			});
		} finally {
			setIsClearingData(false);
		}

		// Clear notice after 6 seconds
		setTimeout(() => setClearDataNotice(null), 6000);
	};

	const exportHistoricalData = async () => {
		setIsExportingData(true);
		try {
			const formData = new FormData();
			formData.append("action", "carbonfooter_export_data");
			formData.append("nonce", window.carbonfooterVars?.nonce);

			const response = await fetch(window.carbonfooterVars?.ajaxUrl, {
				method: "POST",
				body: formData,
			});

			const data = await response.json();
			if (data.success) {
				// Create and download the file
				const blob = new Blob([JSON.stringify(data.data, null, 2)], {
					type: "application/json",
				});
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement("a");
				a.href = url;
				a.download =
					data.filename ||
					`carbon-emissions-${new Date().toISOString().split("T")[0]}.json`;
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);

				setExportNotice({
					type: "success",
					message:
						data.message ||
						__("Data is exported successfully.", "carbonfooter"),
				});
			} else {
				setExportNotice({
					type: "error",
					message:
						data.data ||
						__("Failed to export data. Please try again.", "carbonfooter"),
				});
			}
		} catch (error) {
			console.error("Error exporting data:", error);
			setExportNotice({
				type: "error",
				message: __("Failed to export data. Please try again.", "carbonfooter"),
			});
		} finally {
			setIsExportingData(false);
		}

		// Clear notice after 6 seconds
		setTimeout(() => setExportNotice(null), 6000);
	};

	return (
		<div className="wrap carbonfooter-settings-page">
			<PageTitle title={__("Settings", "carbonfooter")} />
			<Text>
				{__(
					"Thank you for joining us in our quest to make the internet more sustainable. With this plugin, you can calculate and display the carbon footprint of your website in the footprint. Follow these steps to set up your own carbonfooter! ",
					"carbonfooter",
				)}
			</Text>

			{notice && (
				<Notice
					status={notice.type}
					isDismissible={true}
					onRemove={() => setNotice(null)}
				>
					{notice.message}
				</Notice>
			)}

			<Spacer margin={12} />

			{/* Design your carbonfooter */}
			<Panel>
				<PanelBody
					title={__("Step 1 - Design your carbonfooter", "carbonfooter")}
					className="carbonfooter-settings-panel"
					initialOpen={false}
				>
					{styleNotice && (
						<>
							<Notice
								status={styleNotice.type}
								isDismissible={true}
								onRemove={() => setStyleNotice(null)}
							>
								{styleNotice.message}
							</Notice>
							<Spacer margin={3} />
						</>
					)}

					<Spacer margin={4} />

					<div className="carbonfooter-widget-design">
						<div>
							<Panel header={__("Define your style", "carbonfooter")}>
								<PanelBody
									title={__("Layout", "carbonfooter")}
									initialOpen={false}
								>
									<RadioControl
										onChange={setWidgetStyle}
										className="carbonfooter-radio-group-vertical"
										selected={widgetStyle}
										options={[
											{
												label: __("Minimal", "carbonfooter"),
												description: __(
													"Minimal: only one line of text showing the CO2 value of the web page.",
													"carbonfooter",
												),
												value: "minimal",
											},
											{
												label: __("Full Banner", "carbonfooter"),
												description: __(
													"Full banner: shows 5 icons and more detailed information on the sustainability of the web page",
													"carbonfooter",
												),
												value: "full",
											},
											{
												label: __("Sticker", "carbonfooter"),
												description: __(
													"Sticker: compact sticker-style badge perfect to fit into a corner of your web page.",
													"carbonfooter",
												),
												value: "sticker",
											},
										]}
									/>
								</PanelBody>

								<PanelBody
									title={__("Background color", "carbonfooter")}
									initialOpen={false}
								>
									<ColorPicker
										color={backgroundColor}
										onChange={setBackgroundColor}
									/>
								</PanelBody>
								<PanelBody
									title={__("Text color", "carbonfooter")}
									initialOpen={false}
								>
									<ColorPicker color={textColor} onChange={setTextColor} />
								</PanelBody>
							</Panel>
						</div>

						<div className="carbonfooter-widget-preview">
							<Panel className="carbonfooter-widget-preview-card">
								<PanelBody>
									<Heading level={4}>
										{__("Live Preview", "carbonfooter")}
									</Heading>
									<Text>
										{__(
											"This is what your carbonfooter will look like:",
											"carbonfooter",
										)}
									</Text>
									<Spacer margin={4} />
									<div
										style={{
											padding: "20px",
											backgroundColor: "#f8f9fa",
											borderRadius: "4px",
											textAlign: "center",
										}}
									>
										<WidgetPreview
											backgroundColor={backgroundColor}
											textColor={textColor}
											widgetStyle={widgetStyle}
										/>
									</div>
								</PanelBody>
							</Panel>
						</div>
					</div>
					<Spacer margin={8} />
					<Button isPrimary onClick={() => saveSettings("design")}>
						{__("Save your design", "carbonfooter")}
					</Button>
				</PanelBody>
			</Panel>
			<Spacer margin={2} />

			<Panel>
				<PanelBody
					title={__("Step 2. Show your carbonfooter", "carbonfooter")}
					className="carbonfooter-settings-panel"
					initialOpen={false}
				>
					{displayNotice && (
						<>
							<Notice
								status={displayNotice.type}
								isDismissible={true}
								onRemove={() => setDisplayNotice(null)}
							>
								{displayNotice.message}
							</Notice>
							<Spacer margin={3} />
						</>
					)}
					<Text>
						{__(
							"You can choose if you want to show your carbonfooter automatically or manually. Don’t know which one to choose? Start with the automatic display, and if that one doesn’t show in the proper place, switch to the manual mode.",
							"carbonfooter",
						)}
					</Text>
					<Spacer margin={4} />
					<RadioControl
						onChange={setDisplaySetting}
						className="carbonfooter-radio-group"
						selected={displaySetting}
						options={[
							{
								label: __("Display automatically", "carbonfooter"),
								description: __(
									"The carbonfooter will be shown automatically on all pages at the end of your footer. Works fine with most themes.",
									"carbonfooter",
								),
								value: "auto",
							},
							{
								label: __("Add manually", "carbonfooter"),
								description: __(
									"The carbonfooter will be shown after you added the shortcode to your footer (or any other widget or textfield).",
									"carbonfooter",
								),
								value: "shortcode",
							},
						]}
					/>

					{displaySetting === "shortcode" && (
						<>
							<Spacer margin={6} />
							<Text>
								{__(
									"Copy and paste the shortcode below, including the brackets, to your footer or any widget / textfield. The shortcode will automatically use the layout and colors you’ve configured above.",
									"carbonfooter",
								)}
							</Text>
							<Spacer margin={6} />
							<code
								style={{
									display: "inline-block",
									background: "rgb(240, 240, 241)",
									padding: "8px 12px",
									marginBottom: "5px",
									borderRadius: "3px",
								}}
							>
								[carbonfooter]
							</code>
						</>
					)}

					<Spacer margin={6} />
					<Button isPrimary onClick={() => saveSettings("display")}>
						{__("Save Display Settings", "carbonfooter")}
					</Button>
				</PanelBody>
			</Panel>
			<Spacer margin={2} />

			<Panel>
				<PanelBody
					title={__("Step 3. You're all set!", "carbonfooter")}
					className="carbonfooter-settings-panel"
					initialOpen={false}
				>
					{homepageEmissions && homepageEmissions > 0 ? (
						<>
							<Text>
								{__(
									"Great! Your homepage has already been measured. You can view the results on your homepage.",
									"carbonfooter",
								)}
							</Text>
							<Spacer margin={4} />
							<Button
								__next40pxDefaultSize
								isPrimary
								onClick={() =>
									window.open(
										`${window.carbonfooterVars?.siteUrl}#carbonfooter`,
										"_blank",
									)
								}
								style={{ textTransform: "uppercase" }}
							>
								{__("View your homepage", "carbonfooter")}
							</Button>
						</>
					) : (
						<>
							<Text>
								{__(
									"You can start measuring, showing and reducing your footprint.",
									"carbonfooter",
								)}
							</Text>
							<Spacer margin={4} />
							<Button
								__next40pxDefaultSize
								isPrimary
								onClick={openModal}
								style={{ textTransform: "uppercase" }}
							>
								{__("Start measuring now", "carbonfooter")}
							</Button>
						</>
					)}
					<Spacer margin={4} />
					<Text>
						{__(
							"Besides showing the carbonfooterprint in the footer, you will now see an extra column behind each post and page that shows the carbon emissions generated when someone visits the page.",
							"carbonfooter",
						)}
					</Text>
				</PanelBody>
			</Panel>

			<Spacer margin={8} />

			<PageHeading title={__("Information & tools", "carbonfooter")} />
			<Spacer margin={4} />

			<HowItWorks />
			<Spacer margin={2} />

			<Troubleshooting />
			<Spacer margin={2} />

			<ExportData
				exportHistoricalData={exportHistoricalData}
				isExportingData={isExportingData}
				exportNotice={exportNotice}
				setExportNotice={setExportNotice}
			/>

			<Spacer margin={2} />

			<ResetAllData
				clearAllData={clearAllData}
				isClearingData={isClearingData}
				clearDataNotice={clearDataNotice}
				setClearDataNotice={setClearDataNotice}
			/>
			<StartModal
				isOpen={isOpen}
				closeModal={closeModal}
				isLoading={isLoading}
				setIsLoading={setIsLoading}
				emissions={homepageEmissions}
				onMeasurementComplete={() => {
					if (measurementStatus?.type === "success") {
						// Show success message briefly
						setTimeout(() => {
							setMeasurementStatus(null);
						}, 3000);
					}
				}}
			/>

			{snackbarMessage && (
				<Snackbar
					onRemove={() => setSnackbarMessage(null)}
					actions={[
						{
							label: __("Dismiss", "carbonfooter"),
							onClick: () => setSnackbarMessage(null),
						},
					]}
					className="carbonfooter-snackbar"
				>
					{snackbarMessage}
				</Snackbar>
			)}
		</div>
	);
};

export default SettingsPage;
