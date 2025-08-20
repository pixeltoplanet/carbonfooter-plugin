import {
	Button,
	Panel,
	PanelBody,
	Notice,
	__experimentalSpacer as Spacer,
} from "@wordpress/components";

import { __ } from "@wordpress/i18n";

import Text from "../components/Text";

const ExportData = ({
	exportHistoricalData,
	isExportingData,
	exportNotice,
	setExportNotice,
}) => {
	return (
		<Panel>
			<PanelBody
				title={__("Export Historical Emissions Data", "carbonfooter")}
				className="carbonfooter-settings-panel"
				initialOpen={false}
			>
				{exportNotice && (
					<>
						<Notice
							status={exportNotice.type}
							isDismissible={true}
							onRemove={() => setExportNotice(null)}
						>
							{exportNotice.message}
						</Notice>
						<Spacer margin={3} />
					</>
				)}
				<Spacer margin={8} />

				<Text>
					{__(
						"This is especially relevant if you want to use the carbon emissions from your website for another platform. You can export all historical emissions as a JSON file. This includes all pages with measurement history, including the serialized PHP arrays that contain the historical measurements. ",
						"carbonfooter",
					)}
				</Text>
				<Spacer margin={4} />

				<Notice status={"info"} isDismissible={false}>
					<Text>{__("The exported file will contain:", "carbonfooter")}</Text>
					<ul
						style={{
							marginTop: "10px",
							paddingLeft: "20px",
						}}
					>
						<li>{__("Post ID and title", "carbonfooter")}</li>
						<li>{__("Complete measurement history", "carbonfooter")}</li>
						<li>
							{__(
								"Serialized PHP arrays with dates and values",
								"carbonfooter",
							)}
						</li>
						<li>{__("All pages with historical data", "carbonfooter")}</li>
					</ul>
				</Notice>

				<Spacer margin={4} />

				<Button
					isPrimary
					onClick={exportHistoricalData}
					disabled={isExportingData}
				>
					{isExportingData
						? __("Exporting Data...", "carbonfooter")
						: __("Export Historical Data", "carbonfooter")}
				</Button>
			</PanelBody>
		</Panel>
	);
};

export default ExportData;
