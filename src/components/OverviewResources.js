import { Panel, PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { formatBytes } from "../lib/formatBytes";
import Text from "./Text";
import { Table } from "./Table";

const OverviewResources = ({ stats }) => {
	const columns = [
		{
			key: "type",
			label: __("Resource type", "carbonfooter"),
			align: "left",
		},
		{
			key: "requests",
			label: __("Avg. Requests per page", "carbonfooter"),
			align: "right",
		},
		{
			key: "size",
			label: __("Avg. size of resources per page", "carbonfooter"),
			align: "right",
		},
	];

	const tableData = Object.entries(stats.resource_stats).map(
		([type, data]) => ({
			type,
			requests: data.avgRequestCount || 0,
			size: formatBytes(data.avgTransferSize || 0),
		}),
	);

	return (
		<Panel>
			<PanelBody
				title={__("Resource Statistics", "carbonfooter")}
				initialOpen={false}
				className="carbonfooter-settings-panel"
			>
				<Text>
					{__(
						"Average resource usage across your posts and pages.",
						"carbonfooter",
					)}
				</Text>

				<Table
					columns={columns}
					data={tableData}
					actions={[]}
					showActions={false}
				/>
			</PanelBody>
		</Panel>
	);
};

export default OverviewResources;
