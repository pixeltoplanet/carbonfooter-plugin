import {
	Panel,
	PanelBody,
	Notice,
	Button,
	__experimentalSpacer as Spacer,
} from "@wordpress/components";

import { __ } from "@wordpress/i18n";

import Text from "../components/Text";

const ResetAllData = ({
	clearAllData,
	isClearingData,
	clearDataNotice,
	setClearDataNotice,
}) => {
	return (
		<Panel>
			<PanelBody
				title={__("Danger zone - Reset all Carbonfooter data", "carbonfooter")}
				className="carbonfooter-settings-panel"
				initialOpen={false}
			>
				{clearDataNotice && (
					<>
						<Notice
							status={clearDataNotice.type}
							isDismissible={true}
							onRemove={() => setClearDataNotice(null)}
						>
							{clearDataNotice.message}
						</Notice>
						<Spacer margin={3} />
					</>
				)}
				<Spacer margin={8} />

				<Notice status={"warning"} isDismissible={false}>
					<Text>
						{__(
							"This will permanently delete all CarbonFooter data including:",
							"carbonfooter",
						)}
					</Text>
					<ul
						style={{
							marginTop: "10px",
							paddingLeft: "20px",
						}}
					>
						<li>{__("All emissions measurements", "carbonfooter")}</li>
						<li>{__("Resource usage data", "carbonfooter")}</li>
						<li>{__("Measurement history", "carbonfooter")}</li>
						<li>{__("Page size data", "carbonfooter")}</li>
						<li>{__("Green hosting status", "carbonfooter")}</li>
					</ul>
				</Notice>

				<Spacer margin={4} />

				<Button
					isDestructive
					onClick={clearAllData}
					disabled={isClearingData}
					style={{ border: "1px solid #d63638" }}
				>
					{isClearingData
						? __("Clearing Data...", "carbonfooter")
						: __("Clear All CarbonFooter Data", "carbonfooter")}
				</Button>
			</PanelBody>
		</Panel>
	);
};

export default ResetAllData;
