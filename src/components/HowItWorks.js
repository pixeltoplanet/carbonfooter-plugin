import {
	Button,
	Panel,
	PanelBody,
	ColorPicker,
	Notice,
	__experimentalHeading as Heading,
	__experimentalSpacer as Spacer,
	RadioControl,
} from "@wordpress/components";

import Text from "../components/Text";

import { __ } from "@wordpress/i18n";

const HowItWorks = () => {
	return (
		<Panel>
			<PanelBody
				title={__("How Measurements Work", "carbonfooter")}
				className="carbonfooter-settings-panel"
				initialOpen={false}
			>
				<Text>
					{__(
						"Carbonfooter automatically measures the carbon emission of your pages: ",
						"carbonfooter",
					)}
				</Text>
				<ul style={{ marginTop: "10px", paddingLeft: "20px" }}>
					<li>
						{__(
							"Measurements are taken when someone visits a page that hasn’t been measured in the last week.",
							"carbonfooter",
						)}
					</li>
					<li>
						{__(
							"Each page stores up to 12 historical measurements, providing about 3 months of history",
							"carbonfooter",
						)}
					</li>
					<li>
						{__(
							"The process runs in the background and won’t affect your visitors’ experience (except ofcourse they see the carbon footprint in the footer!)",
							"carbonfooter",
						)}
					</li>
					<li>
						{__(
							"The methodology of the carbon calculation is based on the universal Sustainable Web Model version 4, improved for Dutch visitors.",
							"carbonfooter",
						)}
					</li>
				</ul>

				<Spacer margin={4} />
				<Button
					isPrimary
					onClick={() =>
						window.open(
							"https://carbonfooter.nl/posts/hoe-berekenen-we-de-co2-voetafdruk-van-je-site",
							"_blank",
						)
					}
				>
					{__("Read more about how it works", "carbonfooter")}
				</Button>
			</PanelBody>
		</Panel>
	);
};

export default HowItWorks;
