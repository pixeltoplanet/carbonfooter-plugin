import OverviewGreenhost from "./OverviewGreenhost";
import OverviewEmissions from "./OverviewEmissions";
import OverviewDriving from "./OverviewDriving";
import OverviewTrees from "./OverviewTrees";
import { formatEmissions } from "../lib/formatEmissions";
import { __ } from "@wordpress/i18n";
import Text from "./Text";

const Overview = ({ stats }) => {
	const { average, hosting_status } = stats;

	const emissionsPerYear = (emissions) => {
		// avarage emissions is used for calculating the emissions per year
		// we use 2.5 pages per visit and 1000 visitors per month
		// this is a rough estimate, but it's a good starting point
		const pagesPerVisit = 2.5;
		const visitorsPerMonth = 1000;

		const emissionsPerVisitor = emissions * pagesPerVisit;

		const emissionsPerYear = emissionsPerVisitor * visitorsPerMonth * 12;

		return emissionsPerYear;
	};

	return (
		<>
			<div className="cf-overview">
				<OverviewGreenhost isGreenhost={hosting_status} />
				<OverviewEmissions
					averageEmissions={formatEmissions(emissionsPerYear(average))}
				/>
				<OverviewDriving emissionsPerYear={emissionsPerYear(average)} />
				<OverviewTrees emissionsPerYear={emissionsPerYear(average)} />
			</div>

			<div className="cf-overview__disclaimer">
				<Text>
					{__(
						"These statistics are based on 1,000 visitors per month, each viewing 2.5 average pages, over a 12-month period.",
						"carbonfooter",
					)}
				</Text>
			</div>
		</>
	);
};

export default Overview;
