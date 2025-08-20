import {
	Notice,
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { Table, ActionButtons } from "./Table";

const OverViewDirtyPages = ({ heaviestPages, maxPages }) => {
	// Limit the number of pages if maxPages is provided
	const limitedPages = maxPages
		? heaviestPages.slice(0, maxPages)
		: heaviestPages;

	// Define table columns
	const columns = [
		{
			key: "title",
			label: __("Page title", "carbonfooter"),
			align: "left",
		},
		{
			key: "emissions",
			label: __("Emissions", "carbonfooter"),
			align: "left",
		},
		{
			key: "actions",
			label: __("Actions", "carbonfooter"),
			align: "right",
		},
	];

	// Transform data to include actions
	const tableData = limitedPages.map((page) => ({
		...page,
		emissions: `${page.emissions.toFixed(2)}g CO2`,
		actions: (
			<ActionButtons
				actions={[
					{
						href: page.edit_url,
						label: __("Edit", "carbonfooter"),
					},
					{
						href: page.url,
						label: __("View", "carbonfooter"),
						target: "_blank",
						rel: "noopener noreferrer",
					},
				]}
			/>
		),
	}));

	return (
		<Panel>
			<PanelBody
				title={__("Emissions per page - from dirty to clean", "carbonfooter")}
				initialOpen={true}
				className="carbonfooter-settings-panel"
			>
				{limitedPages.length > 0 ? (
					<div style={{ overflowX: "auto" }}>
						<Table
							data={tableData}
							columns={columns}
							style={{
								width: "100%",
								borderCollapse: "collapse",
								fontSize: "14px",
							}}
						/>
					</div>
				) : (
					<Notice status="info">
						{__("No pages with emissions data found.", "carbonfooter")}
					</Notice>
				)}
			</PanelBody>
		</Panel>
	);
};

export default OverViewDirtyPages;
