import {
	Panel,
	PanelBody,
	Notice,
	Button,
	__experimentalSpacer as Spacer,
} from "@wordpress/components";

import Text from "./Text";
import { __ } from "@wordpress/i18n";

const Troubleshooting = () => {
	// Get caching plugins info from WordPress
	const cachingInfo = window.carbonfooterVars?.cachingPlugins || {
		active: false,
		plugins: [],
	};

	return (
		<Panel>
			<PanelBody
				title={__("Troubleshooting: not working?", "carbonfooter")}
				className="carbonfooter-settings-panel"
				initialOpen={false}
			>
				<Spacer margin={8} />

				<Text>
					{__("Don't see any data? Try these things:", "carbonfooter")}
				</Text>
				<Spacer margin={4} />

				<ul
					style={{
						marginTop: "10px",
						paddingLeft: "20px",
					}}
				>
					<li>
						{__(
							"Visit your webpage: The measuring updates automatically when somebody visits the web page. So, visit your webpage, and wait a few minutes.",
							"carbonfooter",
						)}
					</li>
					<li>
						{__(
							"Clear your cache: Still not working after 30 minutes? This might be because you are using a caching plugin.",
							"carbonfooter",
						)}{" "}
						<br />
						{cachingInfo.plugins.length > 0 && (
							<>
								{__(
									"We ran a check and detected that your website is using the following caching plugin(s):",
									"carbonfooter",
								)}{" "}
								<strong>{cachingInfo.plugins.join(", ")}</strong>.
								<br />
								{__(
									"Clear your cache, and refresh your webpage. If you are using LiteSpeed Cache, you need to clear the cache manually after every measurement.",
									"carbonfooter",
								)}
							</>
						)}
						{cachingInfo.plugins.length === 0 && (
							<>
								{__(
									"We ran a check and detected that your website is not using any of the common caching plugins.",
									"carbonfooter",
								)}{" "}
								<br />
								{__(
									"Clear your cache, and refresh your webpage.",
									"carbonfooter",
								)}
							</>
						)}
					</li>
					<li>
						{__(
							"Improve your website: possibly your web page is too heavy to go through our calculation. Go to",
							"carbonfooter",
						)}{" "}
						<a href="https://carbonfooter.nl/" target="_blank" rel="noreferrer">
							carbonfooter.nl
						</a>
						,{" "}
						{__(
							"check our tips, update your webpage and try again.",
							"carbonfooter",
						)}
					</li>
				</ul>

				<Spacer margin={4} />

				<Text>
					{__(
						"If that also doesn't work, it is possible that your web page is too heavy to go through our calculation. Go to carbonfooter.nl, check our tips, update your webpage and try again.",
						"carbonfooter",
					)}
				</Text>
				<Spacer margin={4} />

				<Button
					isPrimary
					onClick={() => window.open("https://carbonfooter.nl/", "_blank")}
				>
					{__("Visit Carbonfooter", "carbonfooter")}
				</Button>
			</PanelBody>
		</Panel>
	);
};

export default Troubleshooting;
